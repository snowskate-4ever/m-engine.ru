<?php

declare(strict_types=1);

namespace App\Livewire\Messenger;

use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\UserAiChatSkill;
use App\Services\Messenger\MessengerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

class MessengerRoom extends Component
{
    use WithFileUploads;

    public bool $embedded = false;

    public Conversation $conversation;

    public string $headerTitle = '';

    /** @var array<string, mixed> Данные для шапки (как контекст сессии во внешнем чате CRM). */
    public array $headerMeta = [];

    public array $items = [];

    public bool $hasMoreOlder = false;

    public ?int $nextBeforeId = null;

    public string $body = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $attachment = null;

    public bool $muted = false;

    public bool $isAiChat = false;

    /** @var int Poll interval for wire:poll (seconds); shorter while waiting for an AI reply. */
    public int $pollIntervalSeconds = 15;

    public bool $aiWaitingForReply = false;

    public ?int $aiAwaitAfterMessageId = null;

    public ?int $aiWaitStartedAt = null;

    /** @var list<array<string, mixed>> */
    public array $skills = [];

    public string $skillTitle = '';

    public string $skillInstruction = '';

    public ?int $editingSkillId = null;

    public function mount(
        MessengerService $messenger,
        ?Conversation $conversation = null,
        ?int $conversationId = null,
        bool $embedded = false,
    ): void {
        // Явный id с родителя (workspace) важнее implicit route binding: иначе на страницах вроде
        // /resources/... Livewire может подставить Conversation из чужого контекста маршрута и
        // Gate::authorize('view', …) вернёт 403 после создания чата.
        if ($conversationId !== null) {
            $conversation = Conversation::query()->findOrFail($conversationId);
        } elseif ($conversation === null) {
            abort(404);
        }

        $this->embedded = $embedded;

        Gate::authorize('view', $conversation);
        $this->conversation = $conversation;
        $this->isAiChat = $conversation->type === ConversationType::Ai;

        $detail = $messenger->conversationToDetailArray($conversation, Auth::user());
        $this->headerMeta = $detail;
        $this->headerTitle = $detail['title'] ?? '';
        if ($this->headerTitle === '' || $this->headerTitle === null) {
            $peer = $detail['direct_peer'] ?? null;
            $this->headerTitle = is_array($peer)
                ? (string) ($peer['name'] ?? __('ui.messenger.direct_chat'))
                : __('ui.messenger.chat');
        }

        $pivot = ConversationUser::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', Auth::id())
            ->first();
        $this->muted = $pivot !== null && (bool) $pivot->notifications_muted;

        $this->loadMessages($messenger);

        if ($this->isAiChat && config('ai.enabled')) {
            $this->refreshSkills();
        }
    }

    public function refreshSkills(): void
    {
        if (! $this->isAiChat || ! config('ai.enabled')) {
            $this->skills = [];

            return;
        }

        Gate::authorize('view', $this->conversation);

        $this->skills = UserAiChatSkill::query()
            ->where('conversation_id', $this->conversation->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static fn (UserAiChatSkill $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'instruction_text' => $s->instruction_text,
                'enabled' => $s->enabled,
                'owned' => $s->user_id === Auth::id(),
            ])
            ->values()
            ->all();
    }

    public function addSkill(MessengerService $messenger): void
    {
        if (! $this->isAiChat || ! config('ai.enabled')) {
            return;
        }

        Gate::authorize('view', $this->conversation);
        $messenger->membershipOrAbort(Auth::user(), $this->conversation);

        $this->validate([
            'skillTitle' => ['required', 'string', 'max:160'],
            'skillInstruction' => ['required', 'string', 'max:16000'],
        ], [], [
            'skillTitle' => __('ui.messenger.skill_title_field'),
            'skillInstruction' => __('ui.messenger.skill_instruction_field'),
        ]);

        $max = (int) config('ai.max_skills_per_conversation', 50);
        $current = UserAiChatSkill::query()->where('conversation_id', $this->conversation->id)->count();
        if ($current >= $max) {
            $this->addError('skillTitle', __('ui.messenger.skills_max', ['max' => $max]));

            return;
        }

        UserAiChatSkill::query()->create([
            'user_id' => Auth::id(),
            'conversation_id' => $this->conversation->id,
            'title' => trim($this->skillTitle),
            'instruction_text' => trim($this->skillInstruction),
            'enabled' => true,
            'sort_order' => 0,
        ]);

        $this->reset(['skillTitle', 'skillInstruction', 'editingSkillId']);
        $this->refreshSkills();
    }

    public function startEditSkill(int $skillId): void
    {
        if (! $this->isAiChat || ! config('ai.enabled')) {
            return;
        }

        Gate::authorize('view', $this->conversation);

        $skill = UserAiChatSkill::query()->whereKey($skillId)->where('conversation_id', $this->conversation->id)->first();
        if ($skill === null || $skill->user_id !== Auth::id()) {
            return;
        }

        $this->editingSkillId = $skill->id;
        $this->skillTitle = $skill->title;
        $this->skillInstruction = $skill->instruction_text;
    }

    public function saveSkillEdit(MessengerService $messenger): void
    {
        if (! $this->isAiChat || ! config('ai.enabled') || $this->editingSkillId === null) {
            return;
        }

        Gate::authorize('view', $this->conversation);
        $messenger->membershipOrAbort(Auth::user(), $this->conversation);

        $this->validate([
            'skillTitle' => ['required', 'string', 'max:160'],
            'skillInstruction' => ['required', 'string', 'max:16000'],
        ], [], [
            'skillTitle' => __('ui.messenger.skill_title_field'),
            'skillInstruction' => __('ui.messenger.skill_instruction_field'),
        ]);

        $skill = UserAiChatSkill::query()
            ->whereKey($this->editingSkillId)
            ->where('conversation_id', $this->conversation->id)
            ->where('user_id', Auth::id())
            ->first();
        if ($skill === null) {
            $this->editingSkillId = null;

            return;
        }

        $skill->update([
            'title' => trim($this->skillTitle),
            'instruction_text' => trim($this->skillInstruction),
        ]);

        $this->reset(['skillTitle', 'skillInstruction', 'editingSkillId']);
        $this->refreshSkills();
    }

    public function cancelSkillEdit(): void
    {
        $this->reset(['skillTitle', 'skillInstruction', 'editingSkillId']);
    }

    public function deleteSkill(int $skillId, MessengerService $messenger): void
    {
        if (! $this->isAiChat || ! config('ai.enabled')) {
            return;
        }

        Gate::authorize('view', $this->conversation);
        $messenger->membershipOrAbort(Auth::user(), $this->conversation);

        $skill = UserAiChatSkill::query()
            ->whereKey($skillId)
            ->where('conversation_id', $this->conversation->id)
            ->where('user_id', Auth::id())
            ->first();
        if ($skill === null) {
            return;
        }

        $skill->delete();
        if ($this->editingSkillId === $skillId) {
            $this->cancelSkillEdit();
        }
        $this->refreshSkills();
    }

    public function toggleSkillEnabled(int $skillId, MessengerService $messenger): void
    {
        if (! $this->isAiChat || ! config('ai.enabled')) {
            return;
        }

        Gate::authorize('view', $this->conversation);
        $messenger->membershipOrAbort(Auth::user(), $this->conversation);

        $skill = UserAiChatSkill::query()
            ->whereKey($skillId)
            ->where('conversation_id', $this->conversation->id)
            ->where('user_id', Auth::id())
            ->first();
        if ($skill === null) {
            return;
        }

        $skill->enabled = ! $skill->enabled;
        $skill->save();
        $this->refreshSkills();
    }

    public function loadMessages(?MessengerService $messenger = null): void
    {
        $messenger ??= app(MessengerService::class);
        $result = $messenger->listMessages(Auth::user(), $this->conversation, null, null, 80);
        $this->items = $result['data'];
        $this->hasMoreOlder = $result['meta']['has_more'];
        $this->nextBeforeId = $result['meta']['next_before_id'];

        if ($this->items !== []) {
            $last = $this->items[array_key_last($this->items)];
            if (isset($last['id'])) {
                $messenger->markRead(Auth::user(), $this->conversation, (int) $last['id']);
            }
        }

        $this->syncAiReplyWaitState();
        $this->updatePollIntervalForAiWait();
        $this->scrollEmbeddedToBottom();
    }

    public function loadOlderMessages(MessengerService $messenger): void
    {
        if ($this->nextBeforeId === null) {
            return;
        }
        $result = $messenger->listMessages(Auth::user(), $this->conversation, $this->nextBeforeId, null, 40);
        $this->items = [...$result['data'], ...$this->items];
        $this->hasMoreOlder = $result['meta']['has_more'];
        $this->nextBeforeId = $result['meta']['next_before_id'];

        $this->syncAiReplyWaitState();
        $this->updatePollIntervalForAiWait();
        $this->scrollEmbeddedToBottom();
    }

    public function send(MessengerService $messenger): void
    {
        $this->validate([
            'body' => ['nullable', 'string', 'max:65535'],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ]);

        $text = trim($this->body);
        if ($this->isAiChat && $this->attachment !== null) {
            $this->addError('attachment', __('ui.messenger.ai_no_attachments'));

            return;
        }

        if ($text === '' && $this->attachment === null) {
            $this->addError('body', __('ui.messenger.empty_message'));

            return;
        }

        $files = $this->attachment !== null ? [$this->attachment] : [];
        $message = $messenger->sendMessage(Auth::user(), $this->conversation, ['body' => $text], $files);

        $this->reset('body', 'attachment');

        if ($this->isAiChat && $text !== '' && $files === [] && config('ai.enabled')) {
            $this->aiWaitingForReply = true;
            $this->aiAwaitAfterMessageId = $message->id;
            $this->aiWaitStartedAt = time();
        }

        $this->loadMessages($messenger);
    }

    /**
     * Stop fast polling once an assistant or system row appears after the user trigger message.
     */
    private function syncAiReplyWaitState(): void
    {
        if (! $this->isAiChat || ! $this->aiWaitingForReply || $this->aiAwaitAfterMessageId === null) {
            return;
        }

        foreach ($this->items as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= $this->aiAwaitAfterMessageId) {
                continue;
            }
            $userId = $row['user_id'] ?? null;
            $kind = (string) ($row['kind'] ?? '');
            if ($userId === null || $kind === 'system') {
                $this->aiWaitingForReply = false;
                $this->aiAwaitAfterMessageId = null;
                $this->aiWaitStartedAt = null;

                return;
            }
        }
    }

    private function updatePollIntervalForAiWait(): void
    {
        if (! $this->isAiChat || ! $this->aiWaitingForReply || $this->aiWaitStartedAt === null) {
            $this->pollIntervalSeconds = 15;

            return;
        }

        $elapsed = time() - $this->aiWaitStartedAt;
        $this->pollIntervalSeconds = match (true) {
            $elapsed < 90 => 2,
            $elapsed < 300 => 5,
            default => 15,
        };
    }

    private function scrollEmbeddedToBottom(): void
    {
        if (! $this->embedded) {
            return;
        }
        $this->js('window.dispatchEvent(new CustomEvent("messages-updated"))');
    }

    public function toggleMute(MessengerService $messenger): void
    {
        $next = ! $this->muted;
        $messenger->updateConversationNotifications(Auth::user(), $this->conversation, [
            'notifications_muted' => $next,
        ]);
        $this->muted = $next;
    }

    public function render()
    {
        $view = view('livewire.messenger.messenger-room');

        if ($this->embedded) {
            return $view;
        }

        return $view->layout('components.layouts.second_level_layout', [
            'title' => $this->headerTitle,
        ]);
    }
}
