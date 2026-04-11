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
}
