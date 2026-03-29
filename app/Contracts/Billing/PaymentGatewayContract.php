<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

/**
 * Точка расширения для эквайринга (ЮKassa, CloudPayments, …).
 * Сейчас в контейнере зарегистрирована заглушка {@see \App\Services\Billing\StubBillingPaymentGateway}.
 */
interface PaymentGatewayContract
{
    public function key(): string;
}
