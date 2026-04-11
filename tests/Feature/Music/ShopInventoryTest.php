<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\ShopItemCondition;
use App\Livewire\Music\ShopInventoryPage;
use App\Models\Good;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class ShopInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_list_shop_item(): void
    {
        $user = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'Test Shop '.uniqid('', true),
            'description' => 'Desc',
            'owner_user_id' => $user->id,
            'slug' => 'shop-'.uniqid('', true),
            'public_page_enabled' => false,
        ]);

        $good = Good::query()->create([
            'name' => 'Catalog Mic',
            'code' => 'MIC-1',
            'description' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ShopInventoryPage::class, ['shopId' => $shop->id])
            ->set('code', 'SKU-001')
            ->set('condition', ShopItemCondition::New->value)
            ->set('good_id', $good->id)
            ->set('price', '1500.50')
            ->set('stock_quantity', '3')
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shop_items', [
            'shop_id' => $shop->id,
            'code' => 'SKU-001',
            'good_id' => $good->id,
            'condition' => ShopItemCondition::New->value,
        ]);

        $row = ShopItem::query()->where('code', 'SKU-001')->first();
        $this->assertNotNull($row);
        $this->assertSame('1500.50', $row->price);
        $this->assertSame(3, $row->stock_quantity);
    }

    public function test_guest_cannot_open_inventory(): void
    {
        $user = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'S',
            'owner_user_id' => $user->id,
            'public_page_enabled' => false,
        ]);

        $this->get(route('music.shops.inventory', $shop))->assertRedirect();
    }

    public function test_owner_can_attach_photo_for_used_item(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'Photo Shop',
            'owner_user_id' => $user->id,
            'public_page_enabled' => false,
        ]);

        $good = Good::query()->create([
            'name' => 'For photo item',
            'code' => 'GOOD-USED-IMG',
            'description' => null,
        ]);

        $file = UploadedFile::fake()->image('item.jpg', 80, 80);

        Livewire::actingAs($user)
            ->test(ShopInventoryPage::class, ['shopId' => $shop->id])
            ->set('code', 'USED-IMG')
            ->set('good_id', $good->id)
            ->set('condition', ShopItemCondition::Used->value)
            ->set('price', '10')
            ->set('stock_quantity', '1')
            ->set('photoUploads', [$file])
            ->call('saveItem')
            ->assertHasNoErrors();

        $item = ShopItem::query()->where('code', 'USED-IMG')->first();
        $this->assertNotNull($item);
        $this->assertCount(1, $item->images);
        Storage::disk('public')->assertExists($item->images->first()->path);
    }

    public function test_stranger_cannot_open_inventory_page(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'S',
            'owner_user_id' => $owner->id,
            'public_page_enabled' => false,
        ]);

        $this->actingAs($other)
            ->get(route('music.shops.inventory', $shop))
            ->assertForbidden();
    }
}
