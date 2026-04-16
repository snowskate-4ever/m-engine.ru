<?php

declare(strict_types=1);

namespace App\Http\Controllers\api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationWebhookReceipt;
use App\Services\Analytics\ProductMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class IntegrationWebhookController extends Controller
{
    public function store(Request $request, ProductMetricsService $metrics): JsonResponse
    {
        $eventName = (string) $request->header('X-Integration-Event', 'unknown');
        $idempotencyKey = (string) $request->header('Idempotency-Key', '');
        $signature = (string) $request->header('X-Integration-Signature', '');

        if ($idempotencyKey === '') {
            return response()->json(['message' => 'Missing Idempotency-Key header.'], 422);
        }

        $secret = (string) config('integration.webhook_signature_secret', '');
        if ($secret !== '') {
            $expected = hash_hmac('sha256', (string) $request->getContent(), $secret);
            if (! hash_equals($expected, $signature)) {
                $metrics->track('integration.v2.webhook.signature_rejected', null, 'integration', [
                    'event_name' => $eventName,
                ]);

                return response()->json(['message' => 'Invalid webhook signature.'], 401);
            }
        }

        return DB::transaction(function () use ($request, $metrics, $idempotencyKey, $eventName, $signature): JsonResponse {
            $existing = IntegrationWebhookReceipt::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();
            if ($existing !== null) {
                return response()->json([
                    'ok' => true,
                    'duplicate' => true,
                    'receipt_id' => $existing->id,
                ]);
            }

            $receipt = IntegrationWebhookReceipt::query()->create([
                'idempotency_key' => $idempotencyKey,
                'event_name' => $eventName,
                'status' => 'completed',
                'client_ip' => $request->ip(),
                'signature' => $signature !== '' ? $signature : null,
                'payload' => $request->json()->all(),
                'processed_at' => now(),
            ]);

            $metrics->track('integration.v2.webhook.received', null, 'integration', [
                'receipt_id' => $receipt->id,
                'event_name' => $eventName,
            ]);

            return response()->json(['ok' => true, 'receipt_id' => $receipt->id], 201);
        });
    }
}
