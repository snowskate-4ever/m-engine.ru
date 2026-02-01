<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VkSetting extends Model
{
    protected $table = 'vk_settings';

    protected $fillable = [
        'vk_access_token',
        'vk_refresh_token',
        'vk_token_expires_at',
        'vk_user_id',
        'token_received_at',
    ];

    protected $casts = [
        'vk_token_expires_at' => 'datetime',
        'token_received_at' => 'datetime',
    ];

    /** Единственная запись настроек VK (singleton) */
    public static function instance(): self
    {
        $row = self::query()->first();
        if ($row !== null) {
            return $row;
        }
        return self::query()->create([]);
    }
}
