<?php

declare(strict_types=1);

namespace App\Contracts\PlatformPayments;

use App\Models\PlatformPayment;

/**
 * Абстракция эквайринга для бронирований/мероприятий (Stripe, YooKassa, …).
 */
interface PlatformAcquiringDriverContract
{
    public function key(): string;

    /**
     * Создать намерение оплаты у провайдера (заглушка возвращает client_secret для теста).
     *
     * @param  array<string, mixed>  $metadata
     * @return array{external_id: string, client_payload: array<string, mixed>}
     */
    public function createPaymentIntent(PlatformPayment $payment, array $metadata = []): array;

    /**
     * Подтвердить списание (после escrow — release).
     *
     * @param  array<string, mixed>  $context
     */
    public function capture(PlatformPayment $payment, array $context = []): bool;

    /**
     * Частичный или полный возврат в minor units.
     */
    public function refund(PlatformPayment $payment, int $amountMinor, string $reason = ''): bool;
}
