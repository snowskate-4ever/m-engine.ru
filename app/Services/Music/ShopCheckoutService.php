<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\ShopDeliveryMode;
use App\Enums\ShopOrderStatus;
use App\Enums\ShopPaymentMethod;
use App\Enums\ShopPaymentStatus;
use App\Models\Address;
use App\Models\ShopCartItem;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ShopCheckoutService
{
    /**
     * @return Collection<int, ShopOrder>
     */
    public function placeOrdersFromCart(
        User $user,
        ?string $buyerNote = null,
        ShopDeliveryMode $deliveryMode = ShopDeliveryMode::Pickup,
        ?int $shippingAddressId = null,
    ): Collection {
        if ($deliveryMode === ShopDeliveryMode::Shipping) {
            if ($shippingAddressId === null) {
                throw ValidationException::withMessages([
                    'shipping_address_id' => __('ui.music.shop_checkout_shipping_address_required'),
                ]);
            }
            $this->assertBuyerOwnsShippingAddress($user, $shippingAddressId);
        } else {
            $shippingAddressId = null;
        }

        $cart = ShopCartItem::query()
            ->where('user_id', $user->id)
            ->with(['shopItem.shop'])
            ->get();

        if ($cart->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => __('ui.music.shop_cart_empty_checkout'),
            ]);
        }

        foreach ($cart as $line) {
            $item = $line->shopItem;
            if ($item->stock_quantity < $line->quantity) {
                throw ValidationException::withMessages([
                    'cart' => __('ui.music.shop_cart_insufficient', ['title' => $item->displayTitle()]),
                ]);
            }
        }

        return DB::transaction(function () use ($user, $cart, $buyerNote, $deliveryMode, $shippingAddressId) {
            /** @var Collection<int, Collection<int, ShopCartItem>> $byShop */
            $byShop = $cart->groupBy(fn (ShopCartItem $l) => $l->shopItem->shop_id);

            $orders = collect();

            foreach ($byShop as $lines) {
                $first = $lines->first();
                $shop = $first->shopItem->shop;
                $shopId = (int) $shop->id;

                $subtotal = $lines->sum(function (ShopCartItem $line): float {
                    return (float) $line->shopItem->price * (int) $line->quantity;
                });
                $rate = $shop->platform_fee_rate !== null
                    ? (float) $shop->platform_fee_rate
                    : (float) config('shop.platform_fee_rate', 0);
                $fee = round($subtotal * $rate, 2);
                $payout = round($subtotal - $fee, 2);

                $order = ShopOrder::query()->create([
                    'shop_id' => $shopId,
                    'buyer_user_id' => $user->id,
                    'status' => ShopOrderStatus::Pending,
                    'buyer_note' => $buyerNote ?: null,
                    'delivery_mode' => $deliveryMode,
                    'shipping_address_id' => $shippingAddressId,
                    'subtotal_amount' => number_format($subtotal, 2, '.', ''),
                    'platform_fee_rate' => number_format($rate, 4, '.', ''),
                    'platform_fee_amount' => number_format($fee, 2, '.', ''),
                    'shop_payout_amount' => number_format($payout, 2, '.', ''),
                    'payment_status' => ShopPaymentStatus::Pending,
                    'payment_method' => ShopPaymentMethod::None,
                ]);

                foreach ($lines as $line) {
                    $item = $line->shopItem;
                    ShopOrderItem::query()->create([
                        'shop_order_id' => $order->id,
                        'shop_item_id' => $item->id,
                        'quantity' => $line->quantity,
                        'unit_price' => $item->price,
                        'title_snapshot' => $item->displayTitle(),
                    ]);
                    $item->decrement('stock_quantity', $line->quantity);
                }

                ShopCartItem::query()->whereIn('id', $lines->pluck('id')->all())->delete();
                $orders->push($order->fresh(['items', 'shop']));
            }

            $buyerId = (int) $user->id;
            $orderIds = $orders->map(fn (ShopOrder $o) => $o->id)->values()->all();
            DB::afterCommit(function () use ($buyerId, $orderIds): void {
                $buyer = User::query()->find($buyerId);
                if ($buyer === null || $orderIds === []) {
                    return;
                }
                $placed = ShopOrder::query()->whereIn('id', $orderIds)->with('shop')->get();
                app(ShopOrderMessengerNotifier::class)->notifyOrdersPlaced($buyer, $placed);
            });

            return $orders;
        });
    }

    private function assertBuyerOwnsShippingAddress(User $user, int $addressId): void
    {
        $ok = Address::query()
            ->whereKey($addressId)
            ->where('addressable_type', User::class)
            ->where('addressable_id', $user->id)
            ->where('is_active', true)
            ->exists();
        if (! $ok) {
            throw ValidationException::withMessages([
                'shipping_address_id' => __('ui.music.shop_checkout_bad_shipping_address'),
            ]);
        }
    }
}
