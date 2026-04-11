<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Models\ShopOrder;
use App\Models\User;
use App\Services\Messenger\MessengerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * Дублирует ключевые события заказа в ленту мессенджера пользователя (ConversationType::Notice).
 */
final class ShopOrderMessengerNotifier
{
    public function __construct(
        private readonly MessengerService $messenger,
    ) {}

    /**
     * @param  Collection<int, ShopOrder>  $orders
     */
    public function notifyOrdersPlaced(User $buyer, Collection $orders): void
    {
        if ($orders->isEmpty()) {
            return;
        }

        $conversation = $this->messenger->getOrCreateNoticeFeed($buyer);
        foreach ($orders as $order) {
            $order->loadMissing('shop');
            $shopName = $order->shop?->name ?? '—';
            $body = __('ui.music.shop_notice_order_placed', [
                'id' => $order->id,
                'shop' => $shopName,
                'total' => (string) $order->subtotal_amount,
                'payment' => __('ui.music.shop_payment_status.'.$order->payment_status->value),
                'url' => URL::route('music.shop.orders'),
            ]);
            $this->messenger->postSystemMessage($conversation, $body);
        }
    }

    public function notifyPaymentRecorded(ShopOrder $order): void
    {
        $buyer = $order->buyer;
        if ($buyer === null) {
            return;
        }

        $order->loadMissing('shop');
        $conversation = $this->messenger->getOrCreateNoticeFeed($buyer);
        $body = __('ui.music.shop_notice_payment_recorded', [
            'id' => $order->id,
            'shop' => $order->shop?->name ?? '—',
            'total' => (string) $order->subtotal_amount,
            'url' => URL::route('music.shop.orders'),
        ]);
        $this->messenger->postSystemMessage($conversation, $body);
    }
}
