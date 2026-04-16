<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MessengerPreferencesGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_show_includes_gateway_flags(): void
    {
        $user = User::factory()->create();
        $user->setInAppNotifications(false);
        $user->setMusicLineupInvitationEmail(true);
        Sanctum::actingAs($user);

        $this->getJson('/api/messenger/preferences')
            ->assertOk()
            ->assertJsonPath('data.in_app_enabled', false)
            ->assertJsonPath('data.music_lineup_email', true);
    }

    public function test_preferences_patch_updates_gateway_flags(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/messenger/preferences', [
            'push_enabled' => true,
            'in_app_enabled' => false,
            'music_lineup_email' => false,
        ])->assertOk()
            ->assertJsonPath('data.in_app_enabled', false)
            ->assertJsonPath('data.music_lineup_email', false);

        $user->refresh();
        $this->assertFalse($user->wantsInAppNotifications());
        $this->assertFalse($user->wantsMusicLineupInvitationEmail());
    }
}
