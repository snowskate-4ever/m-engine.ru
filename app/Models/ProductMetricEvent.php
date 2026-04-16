<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMetricEvent extends Model
{
    protected $fillable = [
        'event_name',
        'user_id',
        'channel',
        'meta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
