<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPaymentRefund extends Model
{
    protected $fillable = [
        'platform_payment_id',
        'amount_minor',
        'reason',
        'policy_label',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PlatformPayment::class, 'platform_payment_id');
    }
}
