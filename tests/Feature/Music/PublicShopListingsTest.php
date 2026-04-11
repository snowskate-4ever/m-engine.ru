<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\ShopItemCondition;
use App\Models\Category;
use App\Models\Good;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicShopListingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shop_page_shows_listings_when_enabled(): void
    {
        $user = User::factory()->create();
        $slug = 'shop-list-'.uniqid('', true);
        $shop = Shop::query()->create([
            'name' => 'Listed Shop',
            'description' => 'We sell things',
            'owner_user_id' => $user->id,
            'slug' => $slug,
            'public_page_enabled' => true,
        ]);

        $good = Good::query()->create([
            'name' => 'Listed good',
            'code' => 'GOOD-LIST-A',
            'description' => null,
        ]);

        ShopItem::query()->create([
            'shop_id' => $shop->id,
            'good_id' => $good->id,
            'code' => 'LIST-A',
            'condition' => ShopItemCondition::New,
            'price' => '99.00',
            'stock_quantity' => 2,
            'title_override' => 'Custom title',
        ]);

        $response = $this->get(route('public.shops.show', ['slug' => $slug]));

        $response->assertOk()
            ->assertSee('Custom title')
            ->assertSee('LIST-A');
    }

    public function test_public_shop_filters_by_category_query(): void
    {
        $user = User::factory()->create();
        $slug = 'shop-cat-'.uniqid('', true);
        $shop = Shop::query()->create([
            'name' => 'Cat Shop',
            'owner_user_id' => $user->id,
            'slug' => $slug,
            'public_page_enabled' => true,
        ]);

        $catStrings = Category::query()->create(['name' => 'Strings']);
        $catWind = Category::query()->create(['name' => 'Wind']);

        $goodGuitar = Good::query()->create(['name' => 'Guitar item', 'code' => 'GTR-1']);
        $goodFlute = Good::query()->create(['name' => 'Flute item', 'code' => 'FLT-1']);
        $goodGuitar->categories()->attach($catStrings->id);
        $goodFlute->categories()->attach($catWind->id);

        ShopItem::query()->create([
            'shop_id' => $shop->id,
            'good_id' => $goodGuitar->id,
            'code' => 'SKU-GTR',
            'condition' => ShopItemCondition::New,
            'price' => '1.00',
            'stock_quantity' => 1,
        ]);
        ShopItem::query()->create([
            'shop_id' => $shop->id,
            'good_id' => $goodFlute->id,
            'code' => 'SKU-FLT',
            'condition' => ShopItemCondition::New,
            'price' => '2.00',
            'stock_quantity' => 1,
        ]);

        $this->get(route('public.shops.show', ['slug' => $slug, 'category' => $catStrings->id]))
            ->assertOk()
            ->assertSee('Guitar item')
            ->assertDontSee('Flute item');
    }
}
