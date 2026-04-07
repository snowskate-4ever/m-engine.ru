<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserAiSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'ai_subscription_tier_id',
        'status',
        'current_period_start',
        'current_period_end',
        'payment_provider',
        'external_payment_ref',
        'cancel_at_period_end',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserAiSubscriptionStatus::class,
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(AiSubscriptionTier::class, 'ai_subscription_tier_id');
    }
}
