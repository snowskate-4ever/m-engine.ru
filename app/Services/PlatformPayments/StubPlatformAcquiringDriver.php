<?php

declare(strict_types=1);

namespace App\Services\PlatformPayments;

use App\Contracts\PlatformPayments\PlatformAcquiringDriverContract;
use App\Models\PlatformPayment;

final class StubPlatformAcquiringDriver implements PlatformAcquiringDriverContract
{
    public function key(): string
    {
        return 'stub';
    }

    public function createPaymentIntent(PlatformPayment $payment, array $metadata = []): array
    {
        $externalId = 'stub_pi_'.hash('sha256', (string) $payment->getKey().'_'.now()->timestamp);

        return [
            'external_id' => $externalId,
            'client_payload' => [
                'mode' => 'stub',
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
                'metadata' => $metadata,
            ],
        ];
    }

    public function capture(PlatformPayment $payment, array $context = []): bool
    {
        return true;
    }

    public function refund(PlatformPayment $payment, int $amountMinor, string $reason = ''): bool
    {
        return $amountMinor > 0;
    }
}
