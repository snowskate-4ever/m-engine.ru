<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ShopPaymentStatus;
use App\Models\ShopOrder;
use App\Services\Music\ShopOrderMessengerNotifier;

final class ShopOrderObserver
{
    public function saving(ShopOrder $order): void
    {
        if (! $order->isDirty('payment_status')) {
            return;
        }
        if ($order->payment_status !== ShopPaymentStatus::Paid) {
            return;
        }
        if ($order->paid_at === null) {
            $order->paid_at = now();
        }
    }

    public function updated(ShopOrder $order): void
    {
        if (! $order->wasChanged('payment_status')) {
            return;
        }
        if ($order->payment_status !== ShopPaymentStatus::Paid) {
            return;
        }
        $prev = $order->getOriginal('payment_status');
        $prevVal = $prev instanceof ShopPaymentStatus ? $prev->value : $prev;
        if ($prevVal === ShopPaymentStatus::Paid->value) {
            return;
        }
        app(ShopOrderMessengerNotifier::class)->notifyPaymentRecorded($order->fresh(['shop', 'buyer']));
    }
}
