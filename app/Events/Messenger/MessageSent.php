<?php

declare(strict_types=1);

namespace App\Events\Messenger;

use App\Models\Message;
use App\Services\Messenger\MessengerService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public bool $broadcastImmediately = false,
    ) {}

    /**
     * When true, the broadcast runs synchronously (no queued BroadcastEvent).
     * Used for assistant/system messages from the AI job so Echo clients get a timely push.
     */
    public function shouldBroadcastNow(): bool
    {
        return $this->broadcastImmediately;
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('messenger.conversation.'.$this->message->conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'messenger.message.sent';
    }

    /**
     * @return array{message: array<string, mixed>}
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing(['user:id,name', 'attachments']);

        return [
            'message' => app(MessengerService::class)->messageToPublicArray($this->message),
        ];
    }
}
