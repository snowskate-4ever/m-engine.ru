<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Models\MatchingControlSetting;
use App\Models\MatchingRunLog;

final class MatchingControlSettingsService
{
    public function get(): MatchingControlSetting
    {
        return MatchingControlSetting::instance();
    }

    public function isAutomaticRunAllowed(): bool
    {
        $settings = $this->get();
        if (! $settings->is_enabled) {
            return false;
        }

        $lastRun = MatchingRunLog::query()
            ->where('is_automatic', true)
            ->latest('id')
            ->first();

        if ($lastRun === null || $lastRun->started_at === null) {
            return true;
        }

        $minutes = max(1, (int) $settings->interval_minutes);

        return $lastRun->started_at->addMinutes($minutes)->lte(now());
    }
}
