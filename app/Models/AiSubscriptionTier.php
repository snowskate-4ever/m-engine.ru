<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed>|null $limits
 */
class AiSubscriptionTier extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_monthly_rub',
        'discount_percent',
        'discount_amount_fixed',
        'discount_valid_until',
        'internal_reference_cost_monthly',
        'limits',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'is_active' => 'boolean',
            'price_monthly_rub' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount_fixed' => 'decimal:2',
            'discount_valid_until' => 'datetime',
            'internal_reference_cost_monthly' => 'decimal:2',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserAiSubscription::class, 'ai_subscription_tier_id');
    }

    /**
     * null — без дневного лимита по тарифу.
     */
    public function serverRequestsPerDayCap(): ?int
    {
        $limits = $this->limits;
        if (! is_array($limits) || ! array_key_exists('server_requests_per_day', $limits)) {
            return null;
        }
        $v = $limits['server_requests_per_day'];
        if ($v === null) {
            return null;
        }

        return max(0, (int) $v);
    }

    /**
     * null — все серверные модели разрешены (если нет ключа или значение не массив).
     * Пустой массив — ни одна серверная модель не входит в тариф.
     *
     * @return null|list<int>
     */
    public function allowedServerModelIds(): ?array
    {
        $limits = $this->limits;
        if (! is_array($limits) || ! array_key_exists('allowed_ai_server_model_ids', $limits)) {
            return null;
        }
        $ids = $limits['allowed_ai_server_model_ids'];
        if (! is_array($ids)) {
            return null;
        }

        return array_values(array_map(static fn (mixed $id): int => (int) $id, $ids));
    }

    /**
     * false только если в limits явно tools_enabled = false.
     */
    public function toolsEnabled(): bool
    {
        $limits = $this->limits;
        if (! is_array($limits) || ! array_key_exists('tools_enabled', $limits)) {
            return true;
        }

        return filter_var($limits['tools_enabled'], FILTER_VALIDATE_BOOL);
    }

    /**
     * null — без лимита числа AI-чатов.
     */
    public function maxAiChats(): ?int
    {
        $limits = $this->limits;
        if (! is_array($limits) || ! array_key_exists('max_ai_chats', $limits)) {
            return null;
        }
        $v = $limits['max_ai_chats'];
        if ($v === null) {
            return null;
        }

        return max(0, (int) $v);
    }

    /**
     * null — без месячного лимита токенов (prompt+completion) на сервере.
     */
    public function serverTokensPerMonthCap(): ?int
    {
        $limits = $this->limits;
        if (! is_array($limits) || ! array_key_exists('server_tokens_per_month', $limits)) {
            return null;
        }
        $v = $limits['server_tokens_per_month'];
        if ($v === null) {
            return null;
        }

        return max(0, (int) $v);
    }

    /**
     * Цена для клиента после скидки (база → % → фикс, не ниже нуля). Скидка не применяется после discount_valid_until.
     */
    public function effectivePriceMonthlyRub(): ?string
    {
        if ($this->price_monthly_rub === null) {
            return null;
        }

        $price = (float) $this->price_monthly_rub;
        $until = $this->discount_valid_until;
        $discountActive = ! $until instanceof CarbonInterface || $until->isFuture();

        if ($discountActive) {
            if ($this->discount_percent !== null && (float) $this->discount_percent > 0) {
                $pct = min(100.0, (float) $this->discount_percent);
                $price *= (100.0 - $pct) / 100.0;
            }
            if ($this->discount_amount_fixed !== null) {
                $price -= (float) $this->discount_amount_fixed;
            }
        }

        if ($price < 0.0) {
            $price = 0.0;
        }

        return number_format($price, 2, '.', '');
    }
}
