<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VkTracking extends Model
{
    protected $table = 'vk_trackings';

    protected $fillable = [
        'name',
        'screen_name',
        'group_id',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'group_id' => 'integer',
    ];
}
