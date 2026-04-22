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

    public int $roomRenderVersion = 0;

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
        // В popup-режиме опираемся на уже загруженный список чатов компонента:
        // это исключает "тихие" 404/403 в Livewire-запросе и гарантирует переключение правой панели.
        $inList = collect($this->conversations)->contains(
            static fn (array $row): bool => (int) ($row['id'] ?? 0) === $id,
        );
        if ($inList) {
            $this->activeConversationId = $id;
            $this->roomRenderVersion++;
            $this->dispatch('messenger-chat-opened', conversationId: $id);

            return;
        }

        // Fallback для полноэкранного режима/прямых переходов.
        $conversation = Conversation::query()->find($id);
        if ($conversation === null) {
            return;
        }
        if (Gate::allows('view', $conversation)) {
            $this->activeConversationId = $id;
            $this->roomRenderVersion++;
            $this->dispatch('messenger-chat-opened', conversationId: $id);
        }
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
        $chatIds = array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $this->conversations
        )));
        if ($chatIds !== []) {
            $this->dispatch('messenger-chats-loaded', chatIds: $chatIds);
        }
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
