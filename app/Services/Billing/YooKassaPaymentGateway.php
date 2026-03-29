<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\PaymentGatewayContract;

final class YooKassaPaymentGateway implements PaymentGatewayContract
{
    public function key(): string
    {
        return 'yookassa';
    }
}
