<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VkPostMedia extends Model
{
    protected $table = 'vk_post_media';

    protected $fillable = [
        'vk_post_id',
        'type',
        'vk_url',
        'path',
        'sort_order',
    ];

    public function vkPost(): BelongsTo
    {
        return $this->belongsTo(VkPost::class, 'vk_post_id');
    }
}
