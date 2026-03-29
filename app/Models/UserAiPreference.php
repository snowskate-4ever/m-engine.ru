<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiPreference extends Model
{
    protected $fillable = [
        'user_id',
        'max_requests_per_day_self',
    ];

    protected function casts(): array
    {
        return [
            'max_requests_per_day_self' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
