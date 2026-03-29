<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PushPlatform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevicePushToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'app_version',
    ];

    protected function casts(): array
    {
        return [
            'platform' => PushPlatform::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
