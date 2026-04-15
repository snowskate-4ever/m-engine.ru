<?php

declare(strict_types=1);

namespace App\Services\PlatformPayments;

use App\Contracts\PlatformPayments\PlatformAcquiringDriverContract;
use App\Enums\PlatformPaymentStatus;
use App\Models\Booking;
use App\Models\PlatformPayment;
use App\Models\PlatformPaymentRefund;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class PlatformPaymentService
{
    public function __construct(
        private readonly PlatformAcquiringDriverContract $driver,
        private readonly RefundPolicyEvaluator $refundPolicy,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function initiateForPayable(
        User $payer,
        Model $payable,
        int $amountMinor,
        string $currency = 'RUB',
        array $meta = [],
    ): PlatformPayment {
        $escrowKey = $this->resolveEscrowKey($payable);
        $useEscrow = $escrowKey !== null
            && (bool) data_get(config('platform_payments.escrow_enabled_for'), $escrowKey, false);

        $feeBps = max(0, (int) config('platform_payments.platform_fee_bps', 0));
        $feeMinor = (int) floor($amountMinor * $feeBps / 10_000);

        $payment = PlatformPayment::query()->create([
            'payer_user_id' => $payer->id,
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'status' => PlatformPaymentStatus::Pending,
            'use_escrow' => $useEscrow,
            'platform_fee_bps' => $feeBps,
            'platform_fee_minor' => $feeMinor,
            'driver' => $this->driver->key(),
            'meta' => $meta,
        ]);

        $intent = $this->driver->createPaymentIntent($payment, [
            'payable_type' => $payment->payable_type,
            'payable_id' => $payment->payable_id,
        ]);

        $payment->forceFill([
            'external_id' => $intent['external_id'],
            'driver_payload' => $intent['client_payload'],
            'status' => $useEscrow ? PlatformPaymentStatus::Authorized : PlatformPaymentStatus::Captured,
        ])->save();

        return $payment->fresh();
    }

    /**
     * Симуляция успешного списания для stub-драйвера / тестов.
     */
    public function markCaptured(PlatformPayment $payment): PlatformPayment
    {
        $payment->forceFill([
            'status' => $payment->use_escrow
                ? PlatformPaymentStatus::EscrowHeld
                : PlatformPaymentStatus::Captured,
        ])->save();

        return $payment->fresh();
    }

    public function releaseEscrow(PlatformPayment $payment): bool
    {
        if ($payment->status !== PlatformPaymentStatus::EscrowHeld) {
            return false;
        }

        if (! $this->driver->capture($payment, ['release_escrow' => true])) {
            return false;
        }

        $payment->forceFill(['status' => PlatformPaymentStatus::Released])->save();

        return true;
    }

    /**
     * Частичный возврат согласно политике (дата начала услуги из payable при наличии).
     *
     * @return array{refund: PlatformPaymentRefund, payment: PlatformPayment}
     */
    public function refundByPolicy(PlatformPayment $payment, string $reason = ''): array
    {
        return DB::transaction(function () use ($payment, $reason): array {
            $payment->refresh();

            $alreadyRefunded = (int) $payment->refunds()->sum('amount_minor');
            $remaining = $payment->amount_minor - $alreadyRefunded;
            if ($remaining <= 0) {
                throw new \InvalidArgumentException('Nothing to refund.');
            }

            $serviceStart = $this->resolveServiceStart($payment->payable);
            $eval = $this->refundPolicy->refundAmountMinor($remaining, $serviceStart);
            $refundMinor = min($remaining, $eval['refund_minor']);

            if ($refundMinor <= 0) {
                throw new \InvalidArgumentException('Refund amount is zero under current policy.');
            }

            if (! $this->driver->refund($payment, $refundMinor, $reason)) {
                throw new \RuntimeException('Acquiring driver refused refund.');
            }

            $refund = PlatformPaymentRefund::query()->create([
                'platform_payment_id' => $payment->id,
                'amount_minor' => $refundMinor,
                'reason' => $reason,
                'policy_label' => $eval['label'],
                'meta' => ['ratio' => $eval['ratio']],
            ]);

            $newTotalRefunded = $alreadyRefunded + $refundMinor;
            $status = $newTotalRefunded >= $payment->amount_minor
                ? PlatformPaymentStatus::RefundedFull
                : PlatformPaymentStatus::RefundedPartial;

            $payment->forceFill(['status' => $status])->save();

            return ['refund' => $refund, 'payment' => $payment->fresh()];
        });
    }

    private function resolveEscrowKey(Model $payable): ?string
    {
        return match (true) {
            $payable instanceof Booking => 'booking',
            default => null,
        };
    }

    private function resolveServiceStart(?Model $payable): ?CarbonInterface
    {
        if ($payable instanceof Booking) {
            return $payable->starts_at;
        }

        return null;
    }
}
