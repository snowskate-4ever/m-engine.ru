<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingSubscriptionSyncService;
use App\Services\Billing\YooKassaNotificationHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class BillingWebhookController extends Controller
{
    public function __construct(
        private readonly BillingSubscriptionSyncService $sync,
        private readonly YooKassaNotificationHandler $yooKassa,
    ) {}

    public function stub(Request $request): JsonResponse
    {
        $secret = (string) config('billing.webhook_secret', '');
        if ($secret === '') {
            abort(403, 'Billing webhook is not configured.');
        }

        $body = $request->getContent();
        $sig = $request->header('X-Billing-Signature', '');
        if (! is_string($sig) || $sig === '') {
            abort(401, 'Missing signature.');
        }

        $expected = hash_hmac('sha256', $body, $secret);
        if (! hash_equals($expected, $sig)) {
            abort(401, 'Invalid signature.');
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($body, true);
        if (! is_array($data)) {
            return response()->json(['ok' => false, 'error' => 'invalid_json'], 422);
        }

        $eventId = $data['webhook_event_id'] ?? null;
        if (is_string($eventId) && $eventId !== '') {
            $cacheKey = 'billing:stub_event:'.hash('sha256', $eventId);
            $cachedId = Cache::get($cacheKey);
            if ($cachedId !== null && is_numeric($cachedId)) {
                return response()->json([
                    'ok' => true,
                    'subscription_id' => (int) $cachedId,
                    'deduplicated' => true,
                ]);
            }
        }

        try {
            $sub = $this->sync->applyFromStubPayload($data);
        } catch (Throwable $e) {
            Log::warning('billing.stub_webhook_rejected', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        if (is_string($eventId) && $eventId !== '') {
            $ttl = max(60, (int) config('billing.webhook_event_idempotency_ttl_seconds', 2_592_000));
            Cache::put($cacheKey, $sub->id, $ttl);
        }

        return response()->json([
            'ok' => true,
            'subscription_id' => $sub->id,
        ]);
    }

    public function yookassa(Request $request): JsonResponse
    {
        try {
            $sub = $this->yooKassa->handleNotification($request->getContent());
        } catch (Throwable $e) {
            Log::warning('billing.yookassa_webhook_rejected', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'subscription_id' => $sub->id,
        ]);
    }
}
