<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Listeners\RecordQueueJobFailedMetric;
use App\Models\ProductMetricEvent;
use App\Services\Analytics\ProductMetricsService;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductMetricsObservabilitySnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_baseline_snapshot_includes_observability_counts(): void
    {
        $now = now();
        foreach ([
            'notification.delivery_failed',
            'notification.delivery_failed',
            'notification.gateway.empty_channels',
            'observability.queue.job_failed',
            'observability.http.slow_api',
        ] as $name) {
            ProductMetricEvent::query()->create([
                'event_name' => $name,
                'user_id' => null,
                'channel' => 'test',
                'meta' => [],
                'occurred_at' => $now,
            ]);
        }

        $snapshot = app(ProductMetricsService::class)->baselineSnapshot(7);

        $this->assertSame(2, (int) ($snapshot['observability']['notification_delivery_failed'] ?? 0));
        $this->assertSame(1, (int) ($snapshot['observability']['notification_empty_channels'] ?? 0));
        $this->assertSame(1, (int) ($snapshot['observability']['queue_job_failed'] ?? 0));
        $this->assertSame(1, (int) ($snapshot['observability']['slow_api_requests'] ?? 0));
    }

    public function test_job_failed_listener_writes_metric(): void
    {
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('resolveName')->once()->andReturn('Tests\\StubFailingJob');

        app(RecordQueueJobFailedMetric::class)->handle(new JobFailed('sync', $job, new \RuntimeException('x')));

        $this->assertDatabaseHas('product_metric_events', [
            'event_name' => 'observability.queue.job_failed',
            'channel' => 'queue',
        ]);

    }
}
