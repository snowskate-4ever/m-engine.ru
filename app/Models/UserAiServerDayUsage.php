<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiServerDayUsage extends Model
{
    protected $fillable = [
        'user_id',
        'usage_date',
        'request_count',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
            'request_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
