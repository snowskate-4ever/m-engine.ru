<?php

declare(strict_types=1);

namespace App\Events\Messenger;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class MessengerReadUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $conversationId,
        public int $userId,
        public int $lastReadMessageId,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('messenger.conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'messenger.read.updated';
    }

    /**
     * @return array{conversation_id: int, user_id: int, last_read_message_id: int}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'last_read_message_id' => $this->lastReadMessageId,
        ];
    }
}
