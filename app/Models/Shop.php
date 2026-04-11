<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LegalEntityType;
use App\Models\Concerns\HasPublicPageLayouts;
use App\Models\Concerns\ModeratablePublicProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Shop extends Model
{
    use HasPublicPageLayouts;
    use ModeratablePublicProfile;

    /**
     * Для старых опубликованных вёрсток без блока «витрина» показываем позиции по умолчанию.
     */
    public function shouldShowPublicBlock(string $blockId): bool
    {
        $layout = $this->layout_published;
        if (! is_array($layout) || empty($layout['blocks']) || ! is_array($layout['blocks'])) {
            return true;
        }

        foreach ($layout['blocks'] as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (($block['id'] ?? '') === $blockId) {
                return (bool) ($block['enabled'] ?? true);
            }
        }

        return $blockId === 'listings';
    }

    protected $fillable = [
        'name',
        'description',
        'owner_user_id',
        'slug',
        'public_page_enabled',
        'layout_draft',
        'layout_published',
        'legal_entity_type',
        'company_name',
        'inn',
        'ogrn',
        'platform_fee_rate',
        'moderation_hidden_at',
        'moderation_reason',
        'moderation_review_requested_at',
    ];

    protected $casts = [
        'public_page_enabled' => 'boolean',
        'layout_draft' => 'array',
        'layout_published' => 'array',
        'legal_entity_type' => LegalEntityType::class,
        'platform_fee_rate' => 'decimal:4',
        'moderation_hidden_at' => 'datetime',
        'moderation_review_requested_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ShopOrder::class);
    }

    public function socials(): MorphMany
    {
        return $this->morphMany(Social::class, 'socialable');
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
