<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Messenger\MessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MessengerController extends Controller
{
    public function __construct(
        private readonly MessengerService $messenger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $this->messenger->listConversationsSummary($user);

        return response()->json(['data' => $items]);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $user = $request->user();
        $data = $this->messenger->conversationToDetailArray($conversation, $user);

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['direct', 'group', 'ai'])],
            'user_id' => ['required_if:type,direct', 'nullable', 'integer', 'exists:users,id'],
            'title' => ['required_if:type,group', 'nullable', 'string', 'max:255'],
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        if ($validated['type'] === 'ai') {
            $validated = array_merge($validated, $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'ai_server_model_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('ai_server_models', 'id')->where('is_active', true),
                ],
                'user_ai_connection_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('user_ai_connections', 'id')->where('user_id', $user->id),
                ],
            ]));
        }

        $conversation = $this->messenger->createConversation($user, $validated);

        return response()->json([
            'data' => $this->messenger->conversationToDetailArray($conversation->fresh(['latestMessage.user', 'latestMessage.attachments']), $user),
        ], 201);
    }

    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $user = $request->user();
        $validated = $request->validate([
            'retention_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'add_user_ids' => ['sometimes', 'array'],
            'add_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $conversation = $this->messenger->updateConversation($user, $conversation, $validated);

        return response()->json([
            'data' => $this->messenger->conversationToDetailArray($conversation->fresh(['latestMessage.user', 'latestMessage.attachments']), $user),
        ]);
    }

    public function messagesIndex(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $user = $request->user();
        $validated = $request->validate([
            'before_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'after_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($validated['before_id']) && ! empty($validated['after_id'])) {
            return response()->json([
                'message' => 'Use only one of before_id or after_id.',
            ], 422);
        }

        $payload = $this->messenger->listMessages(
            $user,
            $conversation,
            $validated['before_id'] ?? null,
            $validated['after_id'] ?? null,
            $validated['per_page'] ?? 50,
        );

        return response()->json($payload);
    }

    public function messagesStore(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $user = $request->user();

        $validated = $request->validate([
            'body' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'client_message_id' => ['sometimes', 'nullable', 'uuid'],
            'forward_from_message_id' => ['sometimes', 'nullable', 'integer', 'exists:messages,id'],
        ]);

        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }
        $files = array_values(array_filter($files, fn ($f) => $f !== null));

        $message = $this->messenger->sendMessage($user, $conversation, $validated, $files);

        return response()->json([
            'data' => $this->messenger->messageToPublicArray($message),
        ], 201);
    }

    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $user = $request->user();
        $validated = $request->validate([
            'message_id' => ['required', 'integer', 'min:1'],
        ]);

        $this->messenger->markRead($user, $conversation, (int) $validated['message_id']);

        return response()->json(['ok' => true]);
    }

    public function presence(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $user = $request->user();
        $validated = $request->validate([
            'foreground' => ['sometimes', 'boolean'],
        ]);
        $foreground = $validated['foreground'] ?? true;
        $key = 'messenger_presence:'.$user->id;

        if ($foreground) {
            $ttl = (int) config('messenger.presence_ttl_seconds', 60);
            Cache::put($key, [
                'conversation_id' => $conversation->id,
                'ts' => now()->timestamp,
            ], $ttl);
        } else {
            Cache::forget($key);
        }

        return response()->json(['ok' => true]);
    }

    public function updateNotifications(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $user = $request->user();
        $validated = $request->validate([
            'notifications_muted' => ['required', 'boolean'],
            'mute_until' => ['sometimes', 'nullable', 'date'],
        ]);

        $membership = $this->messenger->updateConversationNotifications($user, $conversation, $validated);

        return response()->json([
            'data' => [
                'notifications_muted' => (bool) $membership->notifications_muted,
                'mute_until' => $membership->mute_until?->toIso8601String(),
            ],
        ]);
    }

    public function preferencesShow(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => $this->messenger->preferencesToArray($user),
        ]);
    }

    public function preferencesUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'push_enabled' => ['required', 'boolean'],
        ]);

        $this->messenger->updatePreferences($user, (bool) $validated['push_enabled']);

        return response()->json([
            'data' => $this->messenger->preferencesToArray($user),
        ]);
    }
}
