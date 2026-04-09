<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\ShopDeliveryMode;
use App\Enums\ShopItemCondition;
use App\Enums\ShopPaymentMethod;
use App\Enums\ShopPaymentStatus;
use App\Models\Address;
use App\Models\Country;
use App\Models\Good;
use App\Models\Shop;
use App\Models\ShopCartItem;
use App\Models\ShopItem;
use App\Models\User;
use App\Services\Music\ShopCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShopCheckoutPaymentTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_stores_subtotal_and_platform_fee(): void
    {
        config(['shop.platform_fee_rate' => 0.1]);

        $buyer = User::factory()->create();
        $owner = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'Fee Shop',
            'owner_user_id' => $owner->id,
            'slug' => 'fee-shop-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $good = Good::query()->create([
            'name' => 'G',
            'code' => 'G-F',
            'description' => null,
        ]);
        $item = ShopItem::query()->create([
            'shop_id' => $shop->id,
            'good_id' => $good->id,
            'code' => 'SKU-F',
            'condition' => ShopItemCondition::New,
            'price' => '100.00',
            'stock_quantity' => 5,
        ]);

        ShopCartItem::query()->create([
            'user_id' => $buyer->id,
            'shop_item_id' => $item->id,
            'quantity' => 2,
        ]);

        $orders = app(ShopCheckoutService::class)->placeOrdersFromCart($buyer, 'note');

        $this->assertCount(1, $orders);
        $order = $orders->first();
        $this->assertNotNull($order);
        $this->assertSame('200.00', $order->subtotal_amount);
        $this->assertSame('20.00', $order->platform_fee_amount);
        $this->assertSame('180.00', $order->shop_payout_amount);
        $this->assertSame(ShopPaymentStatus::Pending, $order->payment_status);
        $this->assertSame(ShopPaymentMethod::None, $order->payment_method);
    }

    public function test_checkout_with_shipping_stores_address(): void
    {
        $buyer = User::factory()->create();
        $owner = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'Ship Shop',
            'owner_user_id' => $owner->id,
            'slug' => 'ship-shop-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $country = Country::query()->create([
            'name' => 'ShipLand',
            'code' => 'SP',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $address = Address::query()->create([
            'addressable_type' => User::class,
            'addressable_id' => $buyer->id,
            'country_id' => $country->id,
            'street' => 'River',
            'house' => '5',
            'address_type' => 'shipping',
            'is_primary' => true,
            'is_active' => true,
            'is_verified' => false,
            'is_public' => false,
        ]);

        $good = Good::query()->create([
            'name' => 'G2',
            'code' => 'G-S',
            'description' => null,
        ]);
        $item = ShopItem::query()->create([
            'shop_id' => $shop->id,
            'good_id' => $good->id,
            'code' => 'SKU-S',
            'condition' => ShopItemCondition::New,
            'price' => '50.00',
            'stock_quantity' => 3,
        ]);

        ShopCartItem::query()->create([
            'user_id' => $buyer->id,
            'shop_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $orders = app(ShopCheckoutService::class)->placeOrdersFromCart(
            $buyer,
            null,
            ShopDeliveryMode::Shipping,
            $address->id,
        );

        $order = $orders->first();
        $this->assertNotNull($order);
        $this->assertSame(ShopDeliveryMode::Shipping, $order->delivery_mode);
        $this->assertSame($address->id, $order->shipping_address_id);
    }
}
