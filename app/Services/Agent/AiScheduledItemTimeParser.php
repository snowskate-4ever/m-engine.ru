<?php

declare(strict_types=1);

namespace App\Services\Agent;

use Carbon\Carbon;

/**
 * Parses user/model datetime strings: explicit Z/offset → UTC; otherwise Europe/Moscow.
 */
final class AiScheduledItemTimeParser
{
    public function parseToUtcCarbon(string $value): Carbon
    {
        $v = trim($value);
        if ($v === '') {
            throw new \InvalidArgumentException('Empty datetime');
        }

        if (preg_match('/Z$/i', $v) || preg_match('/[+-]\d{2}:\d{2}$/', $v)) {
            return Carbon::parse($v)->utc();
        }

        return Carbon::parse($v, 'Europe/Moscow')->utc();
    }
}
