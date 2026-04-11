<?php

declare(strict_types=1);

namespace App\Events\Notifications;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Синхронизация счётчика/состояния in-app уведомлений для открытых вкладок (прочитано и т.д.).
 */
final class UserInAppNotificationsSynced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $unreadCount,
        public ?string $notificationId = null,
        public ?string $readAtIso = null,
        public bool $refreshPreview = false,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'user.notifications.synced';
    }

    /**
     * @return array{unread_count: int, notification_id: string|null, read_at: string|null, refresh_preview: bool}
     */
    public function broadcastWith(): array
    {
        return [
            'unread_count' => $this->unreadCount,
            'notification_id' => $this->notificationId,
            'read_at' => $this->readAtIso,
            'refresh_preview' => $this->refreshPreview,
        ];
    }
}
