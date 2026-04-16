<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Analytics\ProductMetricsService;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

final class RecordQueueJobFailedMetric
{
    public function handle(JobFailed $event): void
    {
        $jobName = $event->job->resolveName();

        Log::warning('queue.job.failed', [
            'connection' => $event->connectionName,
            'job' => $jobName,
            'message' => $event->exception->getMessage(),
        ]);

        app(ProductMetricsService::class)->track('observability.queue.job_failed', null, 'queue', [
            'connection' => $event->connectionName,
            'job' => $jobName,
        ]);
    }
}
