<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ShopItemCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ShopItem extends Model
{
    protected $fillable = [
        'shop_id',
        'good_id',
        'code',
        'condition',
        'title_override',
        'description_override',
        'price',
        'stock_quantity',
    ];

    protected $casts = [
        'condition' => ShopItemCondition::class,
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function good(): BelongsTo
    {
        return $this->belongsTo(Good::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ShopItemImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function displayTitle(): string
    {
        if (filled($this->title_override)) {
            return (string) $this->title_override;
        }
        if ($this->good) {
            return (string) ($this->good->name ?? $this->code);
        }

        return (string) $this->code;
    }

    public function displayDescription(): ?string
    {
        if (filled($this->description_override)) {
            return (string) $this->description_override;
        }
        if ($this->good && filled($this->good->description)) {
            return (string) $this->good->description;
        }

        return null;
    }

    public function publicPrimaryImageUrl(): ?string
    {
        $this->loadMissing(['good', 'images']);

        if ($this->condition === ShopItemCondition::Used) {
            $path = $this->images->first()?->path;
            if (filled($path)) {
                return Storage::disk('public')->url($path);
            }

            return $this->catalogImageUrl();
        }

        return $this->catalogImageUrl();
    }

    /**
     * Для б/у без своих фото — опционально картинка из карточки каталога.
     */
    public function catalogImageUrl(): ?string
    {
        $this->loadMissing('good');
        $good = $this->good;
        if ($good === null || ! is_array($good->default_images)) {
            return null;
        }

        foreach ($good->default_images as $entry) {
            if (! is_string($entry) || $entry === '') {
                continue;
            }
            if (preg_match('#^https?://#i', $entry)) {
                return $entry;
            }

            return Storage::disk('public')->url($entry);
        }

        return null;
    }
}
