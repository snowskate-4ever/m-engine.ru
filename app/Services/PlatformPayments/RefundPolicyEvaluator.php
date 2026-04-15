<?php

declare(strict_types=1);

namespace App\Services\PlatformPayments;

use Carbon\CarbonInterface;

/**
 * Правила частичного возврата из config/platform_payments.php.
 */
final class RefundPolicyEvaluator
{
    /**
     * @return array{refund_minor: int, ratio: float, label: string}
     */
    public function refundAmountMinor(
        int $amountMinor,
        ?CarbonInterface $serviceStartsAt,
        ?CarbonInterface $cancelledAt = null,
    ): array {
        $now = $cancelledAt ?? now();
        $fullHours = (int) config('platform_payments.refund.hours_before_full_refund', 48);
        $partialRatio = (float) config('platform_payments.refund.partial_refund_ratio', 0.5);

        if ($serviceStartsAt === null) {
            return [
                'refund_minor' => $amountMinor,
                'ratio' => 1.0,
                'label' => 'no_service_time_full_refund',
            ];
        }

        $hoursBefore = $now->diffInHours($serviceStartsAt, false);

        if ($hoursBefore >= $fullHours) {
            return [
                'refund_minor' => $amountMinor,
                'ratio' => 1.0,
                'label' => 'full_refund_window',
            ];
        }

        $refund = (int) floor($amountMinor * $partialRatio);

        return [
            'refund_minor' => max(0, min($amountMinor, $refund)),
            'ratio' => $partialRatio,
            'label' => 'partial_refund_after_window',
        ];
    }
}
