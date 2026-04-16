<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerUserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'push_enabled',
        'priority_mode',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected function casts(): array
    {
        return [
            'push_enabled' => 'boolean',
            'quiet_hours_start' => 'integer',
            'quiet_hours_end' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
