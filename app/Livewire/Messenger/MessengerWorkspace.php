<?php

declare(strict_types=1);

namespace App\Livewire\Messenger;

use App\Models\AiServerModel;
use App\Models\Conversation;
use App\Models\User;
use App\Models\UserAiConnection;
use App\Services\Messenger\MessengerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Двухколоночный мессенджер (список + окно переписки), как экран внешних чатов в CRM.
 */
class MessengerWorkspace extends Component
{
    /** Встраивание в плавающую панель layout без обёртки second_level_layout. */
    public bool $embedMode = false;

    public ?int $activeConversationId = null;

    public array $conversations = [];

    public string $createType = 'direct';

    public string $directUserId = '';

    public string $groupTitle = '';

    public string $groupUserIds = '';

    /** @var list<array{id: int, label: string}> */
    public array $aiServerModels = [];

    /** @var list<array{id: int, label: string}> */
    public array $aiConnections = [];

    public string $aiTitle = '';

    public string $aiSource = 'server';

    public string $aiServerModelId = '';

    public string $aiConnectionId = '';

    public function mount(MessengerService $messenger, ?Conversation $conversation = null): void
    {
        // Route-model binding для чата имеет смысл только на странице messenger.show.
        // Вложенный workspace (embedMode, панель в layout) рендерится на других маршрутах — привязанный
        // Conversation из чужого контекста или устаревший снимок даёт ложный 403 при mount/update.
        if (
            $conversation !== null
            && $conversation->exists
            && request()->routeIs('messenger.show')
        ) {
            Gate::authorize('view', $conversation);
            $this->activeConversationId = $conversation->id;
        }

        $this->refreshList($messenger);
        $this->loadAiOptions();
    }

    public function openConversation(int $id): void
    {
        $conversation = Conversation::query()->findOrFail($id);
        Gate::authorize('view', $conversation);
        $this->activeConversationId = $id;
    }

    /** Выбор чата из правой рейки (верхняя панель: Alpine открывает попап и шлёт это событие). */
    #[On('messenger-rail-select-chat')]
    public function openConversationFromRail(int $conversationId): void
    {
        $this->openConversation($conversationId);
    }

    private function loadAiOptions(): void
    {
        if (! config('ai.enabled')) {
            $this->aiServerModels = [];
            $this->aiConnections = [];

            return;
        }

        $user = Auth::user();
        $this->aiServerModels = AiServerModel::query()
            ->where('is_active', true)
            ->whereHas('provider', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static fn (AiServerModel $m) => [
                'id' => $m->id,
                'label' => $m->display_name.' ('.$m->vendor_model_id.')',
            ])
            ->all();

        $this->aiConnections = UserAiConnection::query()
            ->where('user_id', $user->id)
            ->where('enabled', true)
            ->orderByDesc('id')
            ->get()
            ->map(static fn (UserAiConnection $c) => [
                'id' => $c->id,
                'label' => $c->name,
            ])
            ->all();
    }

    public function refreshList(?MessengerService $messenger = null): void
    {
        $messenger ??= app(MessengerService::class);
        $this->conversations = $messenger->listConversationsSummary(Auth::user());
    }

    public function createChat(MessengerService $messenger): void
    {
        $this->validate([
            'createType' => ['required', 'string', 'in:direct,group,ai'],
            'directUserId' => ['required_if:createType,direct', 'nullable', 'integer', 'exists:users,id'],
            'groupTitle' => ['required_if:createType,group', 'nullable', 'string', 'max:255'],
            'groupUserIds' => ['nullable', 'string', 'max:2000'],
            'aiTitle' => ['required_if:createType,ai', 'nullable', 'string', 'max:255'],
            'aiSource' => ['required_if:createType,ai', 'nullable', 'string', 'in:server,byok'],
            'aiServerModelId' => [
                Rule::requiredIf(fn () => $this->createType === 'ai' && $this->aiSource === 'server'),
                'nullable',
                'integer',
                'min:1',
            ],
            'aiConnectionId' => [
                Rule::requiredIf(fn () => $this->createType === 'ai' && $this->aiSource === 'byok'),
                'nullable',
                'integer',
                'min:1',
            ],
        ], [], [
            'directUserId' => __('ui.messenger.peer_user_id'),
            'groupTitle' => __('ui.messenger.group_title'),
            'aiTitle' => __('ui.messenger.ai_chat_title'),
        ]);

        $user = Auth::user();

        if ($this->createType === 'direct') {
            if ((int) $this->directUserId === (int) $user->id) {
                $this->addError('directUserId', __('ui.messenger.cannot_chat_self'));

                return;
            }
            $data = [
                'type' => 'direct',
                'user_id' => (int) $this->directUserId,
            ];
        } elseif ($this->createType === 'group') {
            $ids = array_values(array_unique(array_filter(
                array_map('intval', preg_split('/[\s,]+/', trim($this->groupUserIds))),
                fn (int $id) => $id > 0 && $id !== (int) $user->id,
            )));

            foreach ($ids as $id) {
                if (! User::query()->whereKey($id)->exists()) {
                    $this->addError('groupUserIds', __('ui.messenger.invalid_user_id'));

                    return;
                }
            }

            $data = [
                'type' => 'group',
                'title' => trim($this->groupTitle),
                'user_ids' => $ids,
            ];
        } elseif ($this->createType === 'ai') {
            if (! config('ai.enabled')) {
                $this->addError('createType', __('ui.messenger.ai_disabled'));

                return;
            }

            $data = [
                'type' => 'ai',
                'title' => trim($this->aiTitle),
                'ai_server_model_id' => $this->aiSource === 'server' ? (int) $this->aiServerModelId : null,
                'user_ai_connection_id' => $this->aiSource === 'byok' ? (int) $this->aiConnectionId : null,
            ];
        } else {
            return;
        }

        try {
            $conversation = $messenger->createConversation($user, $data);
        } catch (ValidationException $e) {
            throw $e;
        }

        $this->reset(['directUserId', 'groupTitle', 'groupUserIds', 'aiTitle', 'aiServerModelId', 'aiConnectionId']);
        $this->aiSource = 'server';
        $this->activeConversationId = $conversation->id;
        $this->refreshList($messenger);
        $this->loadAiOptions();

        if ($this->embedMode) {
            return;
        }

        $this->redirect(route('messenger.show', $conversation), navigate: true);
    }

    public function render()
    {
        $view = view('livewire.messenger.messenger-workspace');
        if ($this->embedMode) {
            return $view;
        }

        return $view->layout('components.layouts.second_level_layout', [
            'title' => __('ui.messenger.title'),
        ]);
    }
}
