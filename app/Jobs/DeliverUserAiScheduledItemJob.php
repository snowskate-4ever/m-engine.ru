<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AiScheduledItemStatus;
use App\Mail\AiScheduledReminderMail;
use App\Models\UserAiScheduledItem;
use App\Services\Ai\AiRruleNextOccurrenceService;
use App\Services\Push\UserDirectPushService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DeliverUserAiScheduledItemJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 120, 600];

    public function __construct(public int $scheduledItemId) {}

    public function handle(
        UserDirectPushService $push,
        AiRruleNextOccurrenceService $rrule,
    ): void {
        $item = UserAiScheduledItem::query()->find($this->scheduledItemId);
        if ($item === null || $item->status !== AiScheduledItemStatus::Processing) {
            return;
        }

        $user = $item->user;
        if ($item->notify_push) {
            $push->sendToUser(
                $user,
                $item->title,
                $item->title,
                [
                    'type' => 'ai.scheduled',
                    'scheduled_item_id' => (string) $item->id,
                ],
            );
        }

        if ($item->notify_email && $user->email_verified_at !== null) {
            Mail::to($user)->queue(new AiScheduledReminderMail($item));
        }

        $repeat = $item->repeat_rule;
        if (is_string($repeat) && trim($repeat) !== '') {
            $anchorRaw = $item->payload['rrule_anchor_utc'] ?? null;
            $anchor = is_string($anchorRaw)
                ? Carbon::parse($anchorRaw)->utc()
                : $item->next_fire_at->clone()->utc();

            $next = $rrule->nextAfter(trim($repeat), $anchor, $item->next_fire_at);
            if ($next !== null) {
                $item->update([
                    'status' => AiScheduledItemStatus::Pending,
                    'next_fire_at' => $next,
                ]);

                return;
            }
        }

        $item->update(['status' => AiScheduledItemStatus::Completed]);
    }

    public function failed(?Throwable $exception): void
    {
        UserAiScheduledItem::query()
            ->whereKey($this->scheduledItemId)
            ->where('status', AiScheduledItemStatus::Processing)
            ->update(['status' => AiScheduledItemStatus::Pending]);
    }
}
