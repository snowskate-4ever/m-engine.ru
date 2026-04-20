<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ConcertVenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MusicProfilesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_venue_representative_via_api(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $venue = ConcertVenue::query()->create([
            'name' => 'Api Venue',
            'owner_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($owner);
        $this->postJson('/api/music/memberships', [
            'entity_type' => 'concert_venue',
            'entity_id' => $venue->id,
            'member_user_id' => $member->id,
            'role' => 'venue_representative',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_user_can_update_active_actor_context(): void
    {
        $owner = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);

        Sanctum::actingAs($owner);
        $this->patchJson('/api/music/actor-context', [
            'type' => User::class,
            'id' => $owner->id,
        ])->assertOk()
            ->assertJsonPath('current.type', User::class)
            ->assertJsonPath('current.id', $owner->id);
    }

    public function test_user_can_fetch_music_profiles_for_mobile(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['musician', 'agent'],
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/music/profiles')
            ->assertOk()
            ->assertJsonPath('data.enabled.0', 'musician')
            ->assertJsonPath('data.enabled.1', 'agent');
    }

    public function test_user_can_enable_and_disable_music_profile_for_mobile(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['musician'],
        ]);

        Sanctum::actingAs($user);
        $this->patchJson('/api/music/profiles', [
            'profile' => 'agent',
            'enabled' => true,
        ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertContains('agent', $user->fresh()->music_profiles ?? []);

        $this->patchJson('/api/music/profiles', [
            'profile' => 'musician',
            'enabled' => false,
        ])->assertOk();

        $this->assertNotContains('musician', $user->fresh()->music_profiles ?? []);
    }

    public function test_music_profiles_index_normalizes_legacy_map_flags(): void
    {
        $user = User::factory()->create([
            'music_profiles' => [
                'musician' => '1',
                'teacher' => 1,
                'agent' => 'true',
                'manager' => 0,
                'session_musician' => '0',
            ],
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/music/profiles')
            ->assertOk()
            ->assertJsonPath('data.enabled.0', 'musician')
            ->assertJsonPath('data.enabled.1', 'teacher')
            ->assertJsonPath('data.enabled.2', 'agent');
    }
}
