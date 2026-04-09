<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\ShopDeliveryMode;
use App\Models\Address;
use App\Models\ShopCartItem;
use App\Models\User;
use App\Services\Music\ShopCheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ShopCartPage extends Component
{
    public string $buyer_note = '';

    public string $delivery_mode = 'pickup';

    public ?int $shipping_address_id = null;

    public function updateQty(int $cartId, mixed $qty): void
    {
        $user = Auth::user();
        $line = ShopCartItem::query()
            ->where('user_id', $user->id)
            ->whereKey($cartId)
            ->with('shopItem')
            ->firstOrFail();

        $qty = max(1, min(9999, (int) $qty));
        if ($qty > $line->shopItem->stock_quantity) {
            $this->addError('cart', __('ui.music.shop_cart_max_stock', ['max' => $line->shopItem->stock_quantity]));

            return;
        }
        $line->quantity = $qty;
        $line->save();
    }

    public function remove(int $cartId): void
    {
        $user = Auth::user();
        ShopCartItem::query()
            ->where('user_id', $user->id)
            ->whereKey($cartId)
            ->delete();
    }

    public function checkout(ShopCheckoutService $checkout): void
    {
        $user = Auth::user();
        $this->resetErrorBag();

        $mode = ShopDeliveryMode::tryFrom($this->delivery_mode) ?? ShopDeliveryMode::Pickup;
        if ($mode === ShopDeliveryMode::Shipping && $this->shipping_address_id === null) {
            $this->addError('cart', __('ui.music.shop_checkout_shipping_address_required'));

            return;
        }

        try {
            $orders = $checkout->placeOrdersFromCart(
                $user,
                $this->buyer_note !== '' ? $this->buyer_note : null,
                $mode,
                $this->shipping_address_id,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('cart', collect($e->errors())->flatten()->first() ?? $e->getMessage());

            return;
        }

        session()->flash('orders_placed', $orders->pluck('id')->all());
        $this->redirect(route('music.shop.orders'), navigate: true);
    }

    public function render(): View
    {
        $user = Auth::user();
        $lines = ShopCartItem::query()
            ->where('user_id', $user->id)
            ->with(['shopItem.shop'])
            ->orderBy('id')
            ->get();

        $grouped = $lines->groupBy(fn (ShopCartItem $l) => $l->shopItem->shop_id);

        $shippingAddresses = Address::query()
            ->where('addressable_type', User::class)
            ->where('addressable_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return view('livewire.music.shop-cart-page', [
            'lines' => $lines,
            'grouped' => $grouped,
            'shippingAddresses' => $shippingAddresses,
        ]);
    }
}
