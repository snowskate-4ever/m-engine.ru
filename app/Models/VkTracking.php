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
        'next_from',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'group_id' => 'integer',
    ];

    public function vkPosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VkPost::class, 'vk_tracking_id');
    }
}
