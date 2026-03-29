<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\UserAiSubscriptionStatus;
use App\Models\AiSubscriptionTier;
use App\Models\User;
use App\Models\UserAiSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Синхронизация подписки из webhook-заглушки или ручного админ-ввода.
 */
final class BillingSubscriptionSyncService
{
    /**
     * @param  array{
     *     user_id: int,
     *     tier_slug: string,
     *     current_period_end: string,
     *     current_period_start?: string|null,
     *     payment_provider?: string|null,
     *     external_payment_ref?: string|null,
     *     sync_user_valid_until?: bool
     * }  $payload
     */
    public function applyFromStubPayload(array $payload): UserAiSubscription
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        $tierSlug = trim((string) ($payload['tier_slug'] ?? ''));
        $periodEndRaw = (string) ($payload['current_period_end'] ?? '');

        if ($userId < 1 || $tierSlug === '' || $periodEndRaw === '') {
            throw new InvalidArgumentException('user_id, tier_slug and current_period_end are required.');
        }

        $periodEnd = CarbonImmutable::parse($periodEndRaw);
        $periodStart = isset($payload['current_period_start']) && $payload['current_period_start'] !== null && $payload['current_period_start'] !== ''
            ? CarbonImmutable::parse((string) $payload['current_period_start'])
            : now()->toImmutable();

        $user = User::query()->find($userId);
        if ($user === null) {
            throw new InvalidArgumentException('User not found.');
        }

        $tier = AiSubscriptionTier::query()
            ->where('slug', $tierSlug)
            ->where('is_active', true)
            ->first();
        if ($tier === null) {
            throw new InvalidArgumentException('Tier not found or inactive.');
        }

        $provider = isset($payload['payment_provider']) ? trim((string) $payload['payment_provider']) : 'stub';
        if ($provider === '') {
            $provider = 'stub';
        }
        $extRef = isset($payload['external_payment_ref']) ? trim((string) $payload['external_payment_ref']) : '';

        return DB::transaction(function () use ($user, $tier, $periodStart, $periodEnd, $payload, $provider, $extRef): UserAiSubscription {
            if ($extRef !== '') {
                $existing = UserAiSubscription::query()
                    ->where('payment_provider', $provider)
                    ->where('external_payment_ref', $extRef)
                    ->lockForUpdate()
                    ->first();
                if ($existing !== null) {
                    if ($existing->user_id !== $user->id) {
                        throw new InvalidArgumentException('external_payment_ref is already used for another user.');
                    }
                    $existing->forceFill([
                        'ai_subscription_tier_id' => $tier->id,
                        'status' => UserAiSubscriptionStatus::Active,
                        'current_period_start' => $periodStart,
                        'current_period_end' => $periodEnd,
                        'cancel_at_period_end' => false,
                    ])->save();

                    $sync = ! array_key_exists('sync_user_valid_until', $payload)
                        || filter_var($payload['sync_user_valid_until'], FILTER_VALIDATE_BOOLEAN);
                    if ($sync) {
                        $user->forceFill(['ai_subscription_valid_until' => $periodEnd])->save();
                    }

                    return $existing->fresh();
                }
            }

            UserAiSubscription::query()
                ->where('user_id', $user->id)
                ->where('status', UserAiSubscriptionStatus::Active)
                ->where('current_period_end', '>', now())
                ->update(['status' => UserAiSubscriptionStatus::Cancelled]);

            $sub = UserAiSubscription::query()->create([
                'user_id' => $user->id,
                'ai_subscription_tier_id' => $tier->id,
                'status' => UserAiSubscriptionStatus::Active,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'payment_provider' => $provider,
                'external_payment_ref' => $extRef !== '' ? $extRef : null,
                'cancel_at_period_end' => false,
            ]);

            $sync = ! array_key_exists('sync_user_valid_until', $payload)
                || filter_var($payload['sync_user_valid_until'], FILTER_VALIDATE_BOOLEAN);
            if ($sync) {
                $user->forceFill(['ai_subscription_valid_until' => $periodEnd])->save();
            }

            return $sub;
        });
    }
}
