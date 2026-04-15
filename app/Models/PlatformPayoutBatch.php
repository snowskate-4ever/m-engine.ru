<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformPayoutBatch extends Model
{
    protected $fillable = [
        'status',
        'scheduled_for',
        'processed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'processed_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
