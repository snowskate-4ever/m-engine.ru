<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\ConversationType;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\UserAiChatSkill;
use App\Services\Messenger\MessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MessengerConversationSkillController extends Controller
{
    public function __construct(
        private readonly MessengerService $messenger,
    ) {}

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $this->assertAiConversation($conversation);
        $this->messenger->membershipOrAbort($request->user(), $conversation);

        $skills = UserAiChatSkill::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $skills->map(fn (UserAiChatSkill $s) => $this->skillToArray($s)),
        ]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $this->assertAiConversation($conversation);
        $this->messenger->membershipOrAbort($request->user(), $conversation);

        $max = (int) config('ai.max_skills_per_conversation', 50);
        $current = UserAiChatSkill::query()->where('conversation_id', $conversation->id)->count();
        if ($current >= $max) {
            throw ValidationException::withMessages([
                'conversation' => ["Maximum {$max} skills per conversation."],
            ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'instruction_text' => ['required', 'string', 'max:16000'],
            'enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ]);

        $skill = UserAiChatSkill::query()->create([
            'user_id' => $request->user()->id,
            'conversation_id' => $conversation->id,
            'title' => $validated['title'],
            'instruction_text' => $validated['instruction_text'],
            'enabled' => $validated['enabled'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'data' => $this->skillToArray($skill),
        ], 201);
    }

    public function update(Request $request, Conversation $conversation, UserAiChatSkill $skill): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $this->assertAiConversation($conversation);
        $this->messenger->membershipOrAbort($request->user(), $conversation);
        $this->assertSkillInConversation($skill, $conversation);
        $this->assertSkillOwner($request, $skill);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:160'],
            'instruction_text' => ['sometimes', 'string', 'max:16000'],
            'enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ]);

        $skill->fill($validated);
        $skill->save();

        return response()->json([
            'data' => $this->skillToArray($skill->fresh()),
        ]);
    }

    public function destroy(Request $request, Conversation $conversation, UserAiChatSkill $skill): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $this->assertAiConversation($conversation);
        $this->messenger->membershipOrAbort($request->user(), $conversation);
        $this->assertSkillInConversation($skill, $conversation);
        $this->assertSkillOwner($request, $skill);

        $skill->delete();

        return response()->json(['ok' => true]);
    }

    private function assertAiConversation(Conversation $conversation): void
    {
        if ($conversation->type !== ConversationType::Ai) {
            throw ValidationException::withMessages([
                'conversation' => ['Skills are only available for AI conversations.'],
            ]);
        }
    }

    private function assertSkillInConversation(UserAiChatSkill $skill, Conversation $conversation): void
    {
        if ($skill->conversation_id !== $conversation->id) {
            abort(404);
        }
    }

    private function assertSkillOwner(Request $request, UserAiChatSkill $skill): void
    {
        if ($skill->user_id !== $request->user()->id) {
            abort(403, 'You can only change your own skills.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function skillToArray(UserAiChatSkill $skill): array
    {
        return [
            'id' => $skill->id,
            'user_id' => $skill->user_id,
            'conversation_id' => $skill->conversation_id,
            'title' => $skill->title,
            'instruction_text' => $skill->instruction_text,
            'enabled' => $skill->enabled,
            'sort_order' => $skill->sort_order,
            'created_at' => $skill->created_at?->toIso8601String(),
            'updated_at' => $skill->updated_at?->toIso8601String(),
        ];
    }
}
