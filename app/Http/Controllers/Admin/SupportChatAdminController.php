<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Ai\SupportAiReplyService;
use App\Services\Messenger\MessengerService;
use App\Services\Messenger\SupportChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use MoonShine\Laravel\Models\MoonshineUser;

final class SupportChatAdminController extends Controller
{
    public function __construct(
        private readonly SupportChatService $supportChats,
        private readonly MessengerService $messenger,
        private readonly SupportAiReplyService $aiReplies,
    ) {}

    public function index(Request $request)
    {
        $this->authorizeOperator($request);

        $supportUser = $this->supportChats->resolveSupportUser();
        abort_if($supportUser === null, 500, 'Support user is not configured.');

        $conversations = $this->supportChats->listSupportConversations();
        $activeId = (int) ($request->query('conversation') ?? 0);
        $active = $conversations->firstWhere('id', $activeId) ?? $conversations->first();

        $items = [];
        if ($active instanceof Conversation) {
            $payload = $this->messenger->listMessages($supportUser, $active, null, null, 80);
            $items = $payload['data'];
        }

        $customer = null;
        if ($active instanceof Conversation) {
            $customerId = $this->supportChats->customerId($active);
            $customer = $customerId !== null ? User::query()->find($customerId) : null;
        }

        return view('admin.support-chat.index', [
            'conversations' => $conversations,
            'activeConversation' => $active,
            'activeMessages' => $items,
            'activeCustomer' => $customer,
            'draft' => session('support_ai_draft'),
            'aiEnabled' => (bool) config('support_chat.ai.enabled'),
            'allowAutoSend' => (bool) config('support_chat.ai.allow_auto_send'),
        ]);
    }

    public function reply(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeOperator($request);
        abort_unless($this->supportChats->isSupportConversation($conversation), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:65535'],
        ]);

        $supportUser = $this->supportChats->resolveSupportUser();
        abort_if($supportUser === null, 500, 'Support user is not configured.');
        $this->messenger->sendMessage($supportUser, $conversation, [
            'body' => trim($validated['body']),
        ]);

        return redirect()
            ->route('admin.support-chats.index', ['conversation' => $conversation->id])
            ->with('status', 'Ответ отправлен');
    }

    public function generateDraft(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeOperator($request);
        abort_unless($this->supportChats->isSupportConversation($conversation), 404);

        $supportUser = $this->supportChats->resolveSupportUser();
        abort_if($supportUser === null, 500, 'Support user is not configured.');

        $draft = $this->aiReplies->generateDraft($conversation, $supportUser);

        return redirect()
            ->route('admin.support-chats.index', ['conversation' => $conversation->id])
            ->with('support_ai_draft', $draft ?? 'Не удалось сгенерировать ответ');
    }

    public function generateAndSend(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeOperator($request);
        abort_unless($this->supportChats->isSupportConversation($conversation), 404);
        abort_unless((bool) config('support_chat.ai.allow_auto_send'), 403);

        $supportUser = $this->supportChats->resolveSupportUser();
        abort_if($supportUser === null, 500, 'Support user is not configured.');

        $draft = $this->aiReplies->generateDraft($conversation, $supportUser);
        if (is_string($draft) && trim($draft) !== '') {
            $this->messenger->sendMessage($supportUser, $conversation, [
                'body' => '[AI] '.$draft,
            ]);
        }

        return redirect()
            ->route('admin.support-chats.index', ['conversation' => $conversation->id])
            ->with('status', is_string($draft) && trim($draft) !== '' ? 'AI-ответ отправлен' : 'AI не вернул текст');
    }

    private function authorizeOperator(Request $request): void
    {
        /** @var MoonshineUser|null $user */
        $user = $request->user('moonshine');
        abort_if($user === null, 401);

        $allowed = array_map(
            static fn (string $v): string => mb_strtolower(trim($v)),
            (array) config('support_chat.operator_roles', ['Admin', 'Manager']),
        );
        $roleName = mb_strtolower(trim((string) optional($user->moonshineUserRole)->name));
        abort_unless(in_array($roleName, $allowed, true), 403);
    }
}
