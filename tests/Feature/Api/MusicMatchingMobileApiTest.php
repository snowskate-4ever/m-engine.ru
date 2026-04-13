<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Peformer;
use App\Models\SearchRequest;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MusicMatchingMobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_music_resource_catalog_returns_all_sections_for_current_user(): void
    {
        $user = User::factory()->create();

        Peformer::query()->create([
            'name' => 'Catalog Band',
            'owner_user_id' => $user->id,
            'performer_kind' => 'band',
        ]);
        Studio::query()->create([
            'name' => 'Catalog Studio',
            'owner_user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/music/resources/catalog')
            ->assertOk();

        $keys = collect($response->json('data'))->pluck('key')->all();
        $this->assertSame([
            'performers',
            'studios',
            'rehearsals',
            'concert_venues',
            'schools',
            'record_labels',
            'producer_centers',
            'shops',
        ], $keys);
    }

    public function test_owner_can_cancel_and_reopen_search_request(): void
    {
        $user = User::factory()->create();
        $performer = Peformer::query()->create([
            'name' => 'Request Band',
            'owner_user_id' => $user->id,
            'performer_kind' => 'band',
        ]);
        $request = SearchRequest::query()->create([
            'search_goal' => 'find_organizer_for_performer',
            'status' => 'open',
            'initiator_type' => Peformer::class,
            'initiator_id' => $performer->id,
            'created_by_user_id' => $user->id,
            'criteria' => [],
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/music/search-requests/{$request->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->postJson("/api/music/search-requests/{$request->id}/reopen")
            ->assertOk()
            ->assertJsonPath('data.status', 'open');
    }

    public function test_user_cannot_transition_foreign_search_request(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $performer = Peformer::query()->create([
            'name' => 'Foreign Band',
            'owner_user_id' => $owner->id,
            'performer_kind' => 'band',
        ]);
        $request = SearchRequest::query()->create([
            'search_goal' => 'find_organizer_for_performer',
            'status' => 'open',
            'initiator_type' => Peformer::class,
            'initiator_id' => $performer->id,
            'created_by_user_id' => $owner->id,
            'criteria' => [],
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($other);

        $this->postJson("/api/music/search-requests/{$request->id}/cancel")
            ->assertNotFound();
    }

    public function test_api_rejects_goal_not_allowed_for_selected_initiator(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);
        $performer = Peformer::query()->create([
            'name' => 'Goal Guard Band',
            'owner_user_id' => $user->id,
            'performer_kind' => 'band',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/music/search-requests', [
            'search_goal' => 'find_venue_for_organizer_event',
            'initiator_type' => Peformer::class,
            'initiator_id' => $performer->id,
            'criteria' => [],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['search_goal']);
    }
}
