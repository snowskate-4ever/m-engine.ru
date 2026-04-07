<?php

declare(strict_types=1);

namespace App\Events\Messenger;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Realtime + domain hook for push: direct chats set {@see $notifyUserId} to the peer; groups use null.
 */
class ConversationRetentionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public User $changedBy,
        public ?int $notifyUserId,
        public ?int $retentionDays,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('messenger.conversation.'.$this->conversation->id)];
    }

    public function broadcastAs(): string
    {
        return 'messenger.conversation.updated';
    }

    /**
     * @return array{conversation_id: int, retention_days: int|null, changed_by_user_id: int, notify_user_id: int|null}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'retention_days' => $this->retentionDays,
            'changed_by_user_id' => $this->changedBy->id,
            'notify_user_id' => $this->notifyUserId,
        ];
    }
}
