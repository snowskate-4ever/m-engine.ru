<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Messenger\MessengerRetentionPruner;
use Illuminate\Console\Command;

class MessengerPruneRetentionCommand extends Command
{
    protected $signature = 'messenger:prune-retention {--dry-run : Count messages that would be deleted without deleting}';

    protected $description = 'Delete messenger messages older than each conversation retention_days (sliding window)';

    public function handle(MessengerRetentionPruner $pruner): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry run — no data will be deleted.');
        }

        $stats = $pruner->prune($dryRun);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Conversations with pruned rows', $stats['conversations_touched']],
                ['Messages '.($dryRun ? '(would delete)' : 'deleted'), $stats['messages_deleted']],
                ['Attachment files deleted', $stats['files_deleted']],
                ['File delete failures', $stats['file_delete_failures']],
            ],
        );

        return self::SUCCESS;
    }
}
