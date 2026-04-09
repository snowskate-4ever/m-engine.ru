<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\ShopOrderStatus;
use App\Models\ShopOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ShopMyOrdersPage extends Component
{
    public function render(): View
    {
        $orders = ShopOrder::query()
            ->where('buyer_user_id', Auth::id())
            ->with(['shop', 'items.shopItem'])
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('livewire.music.shop-my-orders-page', [
            'orders' => $orders,
            'statusLabels' => [
                ShopOrderStatus::Pending->value => __('ui.music.shop_order_status.pending'),
                ShopOrderStatus::StoreConfirmed->value => __('ui.music.shop_order_status.store_confirmed'),
                ShopOrderStatus::Cancelled->value => __('ui.music.shop_order_status.cancelled'),
            ],
        ]);
    }
}
