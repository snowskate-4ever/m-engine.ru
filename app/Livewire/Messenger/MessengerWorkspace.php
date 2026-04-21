<?php

declare(strict_types=1);

namespace App\Livewire\Messenger;

use App\Models\Conversation;
use App\Services\Messenger\MessengerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

    #[On('messenger-conversations-refresh')]
    public function onConversationsRefresh(MessengerService $messenger): void
    {
        $this->refreshList($messenger);
    }

    public function refreshList(?MessengerService $messenger = null): void
    {
        $messenger ??= app(MessengerService::class);
        $this->conversations = $messenger->listConversationsSummary(Auth::user());
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
