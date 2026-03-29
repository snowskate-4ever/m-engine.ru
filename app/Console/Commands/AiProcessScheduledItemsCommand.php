<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AiScheduledItemStatus;
use App\Jobs\DeliverUserAiScheduledItemJob;
use App\Models\UserAiScheduledItem;
use Illuminate\Console\Command;

class AiProcessScheduledItemsCommand extends Command
{
    protected $signature = 'ai:process-scheduled-items {--limit=200 : Max items to dequeue per run}';

    protected $description = 'Dispatch jobs for due user AI scheduled items (reminders)';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $ids = UserAiScheduledItem::query()
            ->where('status', AiScheduledItemStatus::Pending)
            ->where('next_fire_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        $dispatched = 0;
        foreach ($ids as $id) {
            $affected = UserAiScheduledItem::query()
                ->whereKey($id)
                ->where('status', AiScheduledItemStatus::Pending)
                ->where('next_fire_at', '<=', now())
                ->update(['status' => AiScheduledItemStatus::Processing]);

            if ($affected === 1) {
                DeliverUserAiScheduledItemJob::dispatch((int) $id);
                $dispatched++;
            }
        }

        $this->info("Dispatched {$dispatched} reminder job(s).");

        return self::SUCCESS;
    }
}
