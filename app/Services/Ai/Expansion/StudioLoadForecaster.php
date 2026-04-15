<?php

declare(strict_types=1);

namespace App\Services\Ai\Expansion;

/**
 * Прогноз загрузки студий (заглушка для исторических рядов).
 *
 * @return list<array{hour:int,load:float}>
 */
final class StudioLoadForecaster
{
    public function forecastNextDay(int $studioId): array
    {
        if (! config('ai_expansion.studio_forecast_enabled')) {
            return [];
        }

        $out = [];
        for ($h = 9; $h <= 21; $h++) {
            $out[] = ['hour' => $h, 'load' => 0.35 + ($h % 5) * 0.05];
        }

        return $out;
    }
}
