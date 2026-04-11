<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicProfileModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shop_shows_moderation_stub_when_flagged(): void
    {
        $user = User::factory()->create();
        $slug = 'mod-shop-'.uniqid('', true);
        Shop::query()->create([
            'name' => 'Mod Shop',
            'owner_user_id' => $user->id,
            'slug' => $slug,
            'public_page_enabled' => true,
            'moderation_hidden_at' => now(),
        ]);

        $this->get(route('public.shops.show', ['slug' => $slug]))->assertOk()
            ->assertSee(__('ui.public_profile.moderation_hidden_heading'), false);
    }
}
