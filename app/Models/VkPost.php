<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VkPost extends Model
{
    protected $table = 'vk_posts';

    protected $fillable = [
        'vk_tracking_id',
        'vk_post_id',
        'from_id',
        'signer_id',
        'text',
        'raw_json',
        'posted_at',
        'processed_at',
    ];

    protected $casts = [
        'raw_json' => 'array',
        'posted_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function vkTracking(): BelongsTo
    {
        return $this->belongsTo(VkTracking::class, 'vk_tracking_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(VkPostMedia::class, 'vk_post_id');
    }

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    /** Очистить сырые данные после обработки (для экономии места) */
    public function clearRawAfterProcessed(): void
    {
        if ($this->processed_at !== null) {
            $this->update(['raw_json' => null]);
        }
    }
}
