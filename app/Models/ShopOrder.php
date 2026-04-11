<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ShopDeliveryMode;
use App\Enums\ShopOrderStatus;
use App\Enums\ShopPaymentMethod;
use App\Enums\ShopPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOrder extends Model
{
    protected $fillable = [
        'shop_id',
        'buyer_user_id',
        'status',
        'buyer_note',
        'delivery_mode',
        'shipping_address_id',
        'subtotal_amount',
        'platform_fee_rate',
        'platform_fee_amount',
        'shop_payout_amount',
        'payment_status',
        'payment_method',
        'payment_external_reference',
        'paid_at',
    ];

    protected $casts = [
        'status' => ShopOrderStatus::class,
        'delivery_mode' => ShopDeliveryMode::class,
        'payment_status' => ShopPaymentStatus::class,
        'payment_method' => ShopPaymentMethod::class,
        'subtotal_amount' => 'decimal:2',
        'platform_fee_rate' => 'decimal:4',
        'platform_fee_amount' => 'decimal:2',
        'shop_payout_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
