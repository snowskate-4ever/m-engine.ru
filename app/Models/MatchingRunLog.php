<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchingRunLog extends Model
{
    protected $fillable = [
        'run_by_user_id',
        'is_automatic',
        'scope',
        'processed_count',
        'matched_count',
        'failed_count',
        'meta',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'is_automatic' => 'boolean',
            'meta' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function runBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by_user_id');
    }
}
