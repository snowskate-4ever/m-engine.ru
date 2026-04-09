<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\ShopOrderStatus;
use App\Models\Shop;
use App\Models\ShopOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ShopOwnerOrdersPage extends Component
{
    public int $shopId;

    public function mount(int $shopId): void
    {
        $shop = Shop::query()->whereKey($shopId)->firstOrFail();
        Gate::authorize('update', $shop);
        $this->shopId = $shopId;
    }

    public function confirmOrder(int $orderId): void
    {
        $shop = $this->resolveShop();
        $order = ShopOrder::query()->where('shop_id', $shop->id)->whereKey($orderId)->firstOrFail();
        Gate::authorize('confirmStore', $order);

        if ($order->status !== ShopOrderStatus::Pending) {
            return;
        }

        $order->update(['status' => ShopOrderStatus::StoreConfirmed]);
    }

    public function render(): View
    {
        $shop = $this->resolveShop();
        Gate::authorize('update', $shop);

        $orders = ShopOrder::query()
            ->where('shop_id', $shop->id)
            ->with(['buyer', 'items.shopItem'])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('livewire.music.shop-owner-orders-page', [
            'shop' => $shop,
            'orders' => $orders,
            'statusLabels' => [
                ShopOrderStatus::Pending->value => __('ui.music.shop_order_status.pending'),
                ShopOrderStatus::StoreConfirmed->value => __('ui.music.shop_order_status.store_confirmed'),
                ShopOrderStatus::Cancelled->value => __('ui.music.shop_order_status.cancelled'),
            ],
        ]);
    }

    private function resolveShop(): Shop
    {
        return Shop::query()
            ->whereKey($this->shopId)
            ->where('owner_user_id', Auth::id())
            ->firstOrFail();
    }
}
