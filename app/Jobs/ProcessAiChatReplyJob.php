<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Ai\AiMessengerReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessAiChatReplyJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public int $uniqueFor = 3600;

    public function __construct(public int $triggerMessageId) {}

    public function uniqueId(): string
    {
        return 'ai-reply-'.$this->triggerMessageId;
    }

    public function handle(AiMessengerReplyService $service): void
    {
        $service->replyToTriggerMessage($this->triggerMessageId);
    }
}
