<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Analytics\ProductMetricsService;
use Illuminate\Console\Command;

final class ProductMetricsBaselineSnapshotCommand extends Command
{
    protected $signature = 'metrics:baseline-snapshot {--days=30 : Window in days, max 365}';

    protected $description = 'Print baseline KPI snapshot (matching, integration, AI, mobile, observability)';

    public function handle(ProductMetricsService $metrics): int
    {
        $days = (int) $this->option('days');
        $snapshot = $metrics->baselineSnapshot($days);

        $this->line(json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
