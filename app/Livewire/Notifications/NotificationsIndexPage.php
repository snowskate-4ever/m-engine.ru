<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Services\Notifications\InAppNotificationSyncBroadcaster;
use App\Services\Notifications\NotificationPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationsIndexPage extends Component
{
    use WithPagination;

    #[Url(as: 'type')]
    public string $typeFilter = '';

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function markRead(
        string $id,
        NotificationPresenter $presenter,
        InAppNotificationSyncBroadcaster $broadcaster,
    ): void {
        $user = Auth::user();
        $n = $user->notifications()->whereKey($id)->first();
        if ($n !== null && $n->read_at === null) {
            $n->markAsRead();
            $n->refresh();
            $broadcaster->sync($user, false, (string) $n->id, $n->read_at?->toIso8601String());
        }
    }

    public function markReadAndOpen(
        string $id,
        string $url,
        NotificationPresenter $presenter,
        InAppNotificationSyncBroadcaster $broadcaster,
    ): mixed {
        $this->markRead($id, $presenter, $broadcaster);

        return $this->redirect($url, navigate: true);
    }

    public function markAllRead(
        NotificationPresenter $presenter,
        InAppNotificationSyncBroadcaster $broadcaster,
    ): void {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();
        $broadcaster->sync($user, true);
    }

    public function render(NotificationPresenter $presenter): View
    {
        $user = Auth::user();
        $typeOptions = $user->notifications()
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values()
            ->all();

        $query = $user->notifications()->latest();
        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        $page = $query->paginate(20);

        return view('livewire.notifications.notifications-index-page', [
            'notifications' => $page,
            'presenter' => $presenter,
            'typeOptions' => $typeOptions,
        ])->layout('components.layouts.second_level_layout', [
            'title' => __('ui.notifications.index_title'),
        ]);
    }
}
