<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PlatformPayment;
use App\Services\PlatformPayments\PlatformPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlatformPaymentController extends Controller
{
    public function __construct(
        private readonly PlatformPaymentService $payments,
    ) {}

    public function storeForBooking(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'max:8'],
        ]);

        $payment = $this->payments->initiateForPayable(
            $user,
            $booking,
            (int) $validated['amount_minor'],
            (string) ($validated['currency'] ?? 'RUB'),
        );

        return response()->json([
            'id' => $payment->id,
            'status' => $payment->status->value,
            'use_escrow' => $payment->use_escrow,
            'external_id' => $payment->external_id,
            'driver_payload' => $payment->driver_payload,
        ], 201);
    }

    public function captureStub(Request $request, PlatformPayment $platformPayment): JsonResponse
    {
        $this->authorizeOwner($request, $platformPayment);
        $payment = $this->payments->markCaptured($platformPayment);

        return response()->json(['status' => $payment->status->value]);
    }

    public function refund(Request $request, PlatformPayment $platformPayment): JsonResponse
    {
        $this->authorizeOwner($request, $platformPayment);
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->payments->refundByPolicy(
            $platformPayment,
            (string) ($validated['reason'] ?? ''),
        );

        return response()->json([
            'payment_status' => $result['payment']->status->value,
            'refund_minor' => $result['refund']->amount_minor,
            'policy_label' => $result['refund']->policy_label,
        ]);
    }

    private function authorizeOwner(Request $request, PlatformPayment $payment): void
    {
        $user = $request->user();
        abort_unless($user && (int) $payment->payer_user_id === (int) $user->id, 403);
    }
}
