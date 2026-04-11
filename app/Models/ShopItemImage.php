<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ShopItemImage extends Model
{
    protected $fillable = [
        'shop_item_id',
        'path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ShopItemImage $image): void {
            if ($image->path !== '' && Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }
        });
    }

    public function shopItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class);
    }
}
