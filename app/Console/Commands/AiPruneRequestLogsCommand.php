<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiRequestLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AiPruneRequestLogsCommand extends Command
{
    protected $signature = 'ai:prune-request-logs {--dry-run : Count rows that would be deleted}';

    protected $description = 'Delete ai_request_logs older than config ai.request_log.retention_days; wipe full prompt/response earlier';

    public function handle(): int
    {
        $fullDays = (int) config('ai.request_log.full_content_retention_days', 7);
        if (Schema::hasColumn('ai_request_logs', 'prompt_full')) {
            $fullCutoff = now()->subDays($fullDays);
            $fullQuery = AiRequestLog::query()
                ->where('created_at', '<', $fullCutoff)
                ->where(static function ($q): void {
                    $q->whereNotNull('prompt_full')->orWhereNotNull('response_full');
                });

            if ($this->option('dry-run')) {
                $n = $fullQuery->count();
                $this->info("Dry run — would clear prompt_full/response_full on {$n} row(s) older than {$fullDays} day(s).");
            } else {
                $cleared = (int) $fullQuery->update([
                    'prompt_full' => null,
                    'response_full' => null,
                ]);
                $this->info("Cleared full prompt/response on {$cleared} ai_request_log row(s) older than {$fullDays} day(s).");
            }
        }

        $days = (int) config('ai.request_log.retention_days', 30);
        $cutoff = now()->subDays($days);

        $query = AiRequestLog::query()->where('created_at', '<', $cutoff);

        if ($this->option('dry-run')) {
            $count = $query->count();
            $this->info("Dry run — would delete {$count} row(s) older than {$days} day(s) (before {$cutoff->toIso8601String()}).");

            return self::SUCCESS;
        }

        $deleted = (int) $query->delete();

        $this->info("Deleted {$deleted} ai_request_log row(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
