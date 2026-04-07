<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Carbon\Carbon;
use DateTimeInterface;
use RRule\RRule;

/**
 * Computes the next occurrence strictly after a moment, for an RFC 5545 RRULE fragment
 * (e.g. FREQ=DAILY;INTERVAL=1) with a fixed DTSTART anchor.
 */
final class AiRruleNextOccurrenceService
{
    /**
     * @param  string  $rruleFragment  RRULE keys without DTSTART line (e.g. FREQ=WEEKLY;BYDAY=MO)
     */
    public function nextAfter(string $rruleFragment, DateTimeInterface $dtStart, DateTimeInterface $afterExclusive): ?Carbon
    {
        $fragment = trim($rruleFragment);
        if ($fragment === '') {
            return null;
        }

        $start = $dtStart instanceof \DateTimeImmutable
            ? \DateTime::createFromImmutable($dtStart)
            : clone $dtStart;

        $rrule = new RRule($fragment, $start);
        $afterTs = $afterExclusive->getTimestamp();

        foreach ($rrule as $occurrence) {
            if ($occurrence->getTimestamp() > $afterTs) {
                return Carbon::instance($occurrence)->utc();
            }
        }

        return null;
    }
}
