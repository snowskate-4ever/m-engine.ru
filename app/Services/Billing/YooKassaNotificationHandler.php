<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\UserAiSubscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Входящие уведомления ЮKassa: подтверждение платежа через API + продление подписки.
 *
 * В metadata платежа при создании на стороне магазина должны быть:
 * user_id, tier_slug, current_period_end (ISO 8601); опционально current_period_start.
 */
final class YooKassaNotificationHandler
{
    public function __construct(
        private readonly BillingSubscriptionSyncService $sync,
    ) {}

    public function handleNotification(string $rawBody): UserAiSubscription
    {
        $shopId = trim((string) config('billing.yookassa.shop_id', ''));
        $secret = trim((string) config('billing.yookassa.secret_key', ''));
        if ($shopId === '' || $secret === '') {
            throw new RuntimeException('YooKassa is not configured.');
        }

        /** @var array<string, mixed>|null $notification */
        $notification = json_decode($rawBody, true);
        if (! is_array($notification)) {
            throw new InvalidArgumentException('Invalid JSON body.');
        }

        $event = isset($notification['event']) ? (string) $notification['event'] : '';
        if ($event !== '' && $event !== 'payment.succeeded') {
            throw new InvalidArgumentException('Unsupported notification event.');
        }

        $object = $notification['object'] ?? null;
        if (! is_array($object)) {
            throw new InvalidArgumentException('Missing payment object.');
        }

        $paymentId = isset($object['id']) ? (string) $object['id'] : '';
        if ($paymentId === '') {
            throw new InvalidArgumentException('Missing payment id.');
        }

        $cacheKey = 'billing:yookassa_payment:'.hash('sha256', $paymentId);
        $cachedId = Cache::get($cacheKey);
        if ($cachedId !== null && is_numeric($cachedId)) {
            $sub = UserAiSubscription::query()->find((int) $cachedId);
            if ($sub !== null) {
                return $sub;
            }
        }

        $verified = $this->fetchPayment($shopId, $secret, $paymentId);
        if (($verified['status'] ?? '') !== 'succeeded') {
            throw new InvalidArgumentException('Payment is not succeeded.');
        }

        $meta = $verified['metadata'] ?? [];
        if (! is_array($meta)) {
            $meta = [];
        }

        $userId = isset($meta['user_id']) ? (int) $meta['user_id'] : 0;
        $tierSlug = isset($meta['tier_slug']) ? trim((string) $meta['tier_slug']) : '';
        $periodEnd = isset($meta['current_period_end']) ? trim((string) $meta['current_period_end']) : '';
        if ($userId < 1 || $tierSlug === '' || $periodEnd === '') {
            throw new InvalidArgumentException('Payment metadata must include user_id, tier_slug, current_period_end.');
        }

        $payload = [
            'user_id' => $userId,
            'tier_slug' => $tierSlug,
            'current_period_end' => $periodEnd,
            'payment_provider' => 'yookassa',
            'external_payment_ref' => $paymentId,
            'sync_user_valid_until' => true,
        ];
        if (isset($meta['current_period_start']) && is_string($meta['current_period_start']) && $meta['current_period_start'] !== '') {
            $payload['current_period_start'] = $meta['current_period_start'];
        }

        $sub = $this->sync->applyFromStubPayload($payload);

        $ttl = max(60, (int) config('billing.webhook_event_idempotency_ttl_seconds', 2_592_000));
        Cache::put($cacheKey, $sub->id, $ttl);

        return $sub;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchPayment(string $shopId, string $secret, string $paymentId): array
    {
        $url = 'https://api.yookassa.ru/v3/payments/'.$paymentId;
        $response = Http::withBasicAuth($shopId, $secret)
            ->withHeaders([
                'Idempotence-Key' => (string) Str::uuid(),
            ])
            ->acceptJson()
            ->timeout(20)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('YooKassa payment lookup failed: HTTP '.$response->status());
        }

        /** @var array<string, mixed> $json */
        $json = $response->json();

        return $json;
    }
}
