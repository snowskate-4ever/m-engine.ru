<?php

declare(strict_types=1);

namespace App\Events\Notifications;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $notification
     */
    public function __construct(
        public int $userId,
        public array $notification,
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
        return 'user.notification.created';
    }

    /**
     * @return array{notification: array<string, mixed>}
     */
    public function broadcastWith(): array
    {
        return ['notification' => $this->notification];
    }
}
