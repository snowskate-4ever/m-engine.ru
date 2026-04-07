<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Ai\AiRruleNextOccurrenceService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AiRruleNextOccurrenceServiceTest extends TestCase
{
    public function test_next_daily_after_start(): void
    {
        $svc = new AiRruleNextOccurrenceService;
        $start = Carbon::parse('2026-06-01 10:00:00', 'UTC');
        $after = Carbon::parse('2026-06-01 10:00:00', 'UTC');

        $next = $svc->nextAfter('FREQ=DAILY;INTERVAL=1', $start, $after);

        $this->assertNotNull($next);
        $this->assertSame('2026-06-02', $next->utc()->format('Y-m-d'));
    }

    public function test_returns_null_when_series_ended(): void
    {
        $svc = new AiRruleNextOccurrenceService;
        $start = Carbon::parse('2026-06-01 10:00:00', 'UTC');
        $after = Carbon::parse('2026-06-03 10:00:00', 'UTC');

        $next = $svc->nextAfter('FREQ=DAILY;INTERVAL=1;COUNT=2', $start, $after);

        $this->assertNull($next);
    }
}
