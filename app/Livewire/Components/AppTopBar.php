<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Services\Notifications\InAppNotificationSyncBroadcaster;
use App\Services\Notifications\NotificationPresenter;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AppTopBar extends Component
{
    public ?string $title = null;

    /** @var array<string, mixed>|null */
    public ?array $titleButton = null;

    public int $unreadCount = 0;

    /** @var list<array<string, mixed>> */
    public array $previewItems = [];

    public function mount(NotificationPresenter $presenter): void
    {
        $this->refreshPreview($presenter);
    }

    public function refreshPreview(NotificationPresenter $presenter): void
    {
        $user = Auth::user();
        $this->unreadCount = $user->unreadNotifications()->count();
        $this->previewItems = $user->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(static fn ($n) => $presenter->toPublicArray($n))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $notification
     */
    public function prependFromBroadcast(array $notification, NotificationPresenter $presenter): void
    {
        $id = (string) ($notification['id'] ?? '');
        if ($id === '') {
            return;
        }

        $this->unreadCount = Auth::user()->unreadNotifications()->count();

        foreach ($this->previewItems as $item) {
            if (($item['id'] ?? '') === $id) {
                return;
            }
        }

        array_unshift($this->previewItems, $notification);
        $this->previewItems = array_slice($this->previewItems, 0, 10);
    }

    public function applySyncFromBroadcast(
        int $unreadCount,
        ?string $notificationId,
        ?string $readAt,
        bool $refreshPreview,
        NotificationPresenter $presenter,
    ): void {
        $this->unreadCount = $unreadCount;
        if ($refreshPreview) {
            $this->refreshPreview($presenter);

            return;
        }
        if ($notificationId !== null && $readAt !== null) {
            foreach ($this->previewItems as $i => $item) {
                if (($item['id'] ?? '') === $notificationId) {
                    $this->previewItems[$i]['read_at'] = $readAt;

                    return;
                }
            }
        }
        $this->refreshPreview($presenter);
    }

    public function markOneRead(
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
        $this->refreshPreview($presenter);
    }

    public function markOneReadAndGo(
        string $id,
        ?string $url,
        NotificationPresenter $presenter,
        InAppNotificationSyncBroadcaster $broadcaster,
    ): mixed {
        $this->markOneRead($id, $presenter, $broadcaster);
        if ($url !== null && $url !== '') {
            return $this->redirect($url, navigate: true);
        }

        return null;
    }

    public function markAllRead(
        NotificationPresenter $presenter,
        InAppNotificationSyncBroadcaster $broadcaster,
    ): void {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();
        $broadcaster->sync($user, true);
        $this->refreshPreview($presenter);
    }

    public function render()
    {
        return view('livewire.components.app-top-bar');
    }
}
