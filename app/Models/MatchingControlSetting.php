<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchingControlSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'interval_minutes',
        'default_scope',
        'provider',
        'model',
        'score_threshold',
        'weights',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'interval_minutes' => 'integer',
            'score_threshold' => 'float',
            'weights' => 'array',
        ];
    }

    public static function instance(): self
    {
        return self::query()->firstOrCreate([], [
            'is_enabled' => true,
            'interval_minutes' => 60,
            'default_scope' => 'all',
            'provider' => (string) config('ai.matching.provider', 'openai'),
            'model' => (string) config('ai.matching.model', 'gpt-4o-mini'),
            'score_threshold' => (float) config('ai.matching.score_threshold', 0.65),
            'weights' => (array) config('ai.matching.weights', []),
        ]);
    }
}
