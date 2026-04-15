<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlatformPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PlatformPayment extends Model
{
    protected $fillable = [
        'payer_user_id',
        'payable_type',
        'payable_id',
        'amount_minor',
        'currency',
        'status',
        'use_escrow',
        'platform_fee_bps',
        'platform_fee_minor',
        'driver',
        'external_id',
        'driver_payload',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlatformPaymentStatus::class,
            'use_escrow' => 'boolean',
            'driver_payload' => 'array',
            'meta' => 'array',
        ];
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PlatformPaymentRefund::class);
    }
}
