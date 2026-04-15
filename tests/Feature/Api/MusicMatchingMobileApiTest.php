<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\MatchingControlSetting;
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

    public function test_user_can_respond_to_active_ad_and_owner_sees_responses(): void
    {
        $owner = User::factory()->create();
        $responder = User::factory()->create();

        $performer = Peformer::query()->create([
            'name' => 'Responses Band',
            'owner_user_id' => $owner->id,
            'performer_kind' => 'band',
        ]);

        $request = SearchRequest::query()->create([
            'search_goal' => 'find_organizer_for_performer',
            'status' => 'open',
            'ad_status' => 'active',
            'moderation_status' => 'approved',
            'initiator_type' => Peformer::class,
            'initiator_id' => $performer->id,
            'created_by_user_id' => $owner->id,
            'criteria' => [],
            'submitted_at' => now(),
            'published_at' => now(),
        ]);

        Sanctum::actingAs($responder);
        $this->postJson("/api/music/search-requests/{$request->id}/responses", [
            'message' => 'Can join this project.',
        ])
            ->assertCreated()
            ->assertJsonPath('ok', true);

        Sanctum::actingAs($owner);
        $this->getJson("/api/music/search-requests/{$request->id}/responses")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.responder_user_id', $responder->id);
    }

    public function test_matching_command_creates_run_log_record(): void
    {
        config()->set('ai.matching.score_threshold', 0.4);

        $owner = User::factory()->create();

        Peformer::query()->create([
            'name' => 'Candidate Band',
            'owner_user_id' => $owner->id,
            'performer_kind' => 'band',
        ]);

        SearchRequest::query()->create([
            'search_goal' => 'find_performer_for_organizer',
            'status' => 'open',
            'ad_status' => 'active',
            'moderation_status' => 'approved',
            'target_kind' => 'performer',
            'initiator_type' => User::class,
            'initiator_id' => $owner->id,
            'criteria' => [],
            'submitted_at' => now(),
            'published_at' => now(),
        ]);

        $this->artisan('music:run-matching')
            ->assertExitCode(0);

        $this->assertDatabaseHas('matching_run_logs', [
            'scope' => 'all',
            'processed_count' => 1,
        ]);

        $this->assertDatabaseHas('search_request_matches', [
            'search_request_id' => 1,
        ]);
    }

    public function test_matching_control_settings_disable_automatic_but_allow_manual_runs(): void
    {
        $owner = User::factory()->create();
        Peformer::query()->create([
            'name' => 'Control Candidate Band',
            'owner_user_id' => $owner->id,
            'performer_kind' => 'band',
        ]);

        SearchRequest::query()->create([
            'search_goal' => 'find_performer_for_organizer',
            'status' => 'open',
            'ad_status' => 'active',
            'moderation_status' => 'approved',
            'target_kind' => 'performer',
            'initiator_type' => User::class,
            'initiator_id' => $owner->id,
            'criteria' => [],
            'submitted_at' => now(),
            'published_at' => now(),
        ]);

        MatchingControlSetting::query()->create([
            'is_enabled' => false,
            'interval_minutes' => 60,
            'default_scope' => 'all',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'score_threshold' => 0.4,
            'weights' => [],
        ]);

        $this->artisan('music:run-matching')
            ->assertExitCode(0);

        $this->assertDatabaseCount('search_request_matches', 0);

        $this->artisan('music:run-matching --manual')
            ->assertExitCode(0);

        $this->assertDatabaseHas('search_request_matches', [
            'search_request_id' => 1,
        ]);
    }

    public function test_manual_command_stores_dry_run_and_max_requests_in_log_meta(): void
    {
        $owner = User::factory()->create();

        Peformer::query()->create([
            'name' => 'Meta Candidate Band',
            'owner_user_id' => $owner->id,
            'performer_kind' => 'band',
        ]);

        SearchRequest::query()->create([
            'search_goal' => 'find_performer_for_organizer',
            'status' => 'open',
            'ad_status' => 'active',
            'moderation_status' => 'approved',
            'target_kind' => 'performer',
            'initiator_type' => User::class,
            'initiator_id' => $owner->id,
            'criteria' => [],
            'submitted_at' => now(),
            'published_at' => now(),
        ]);

        $this->artisan('music:run-matching --manual --dry-run --max-requests=1 --run-by-user-id='.$owner->id.' --explanation-level=summary')
            ->assertExitCode(0);

        $log = \App\Models\MatchingRunLog::query()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertFalse((bool) $log->is_automatic);
        $this->assertSame(true, (bool) ($log->meta['dry_run'] ?? false));
        $this->assertSame(1, (int) ($log->meta['max_requests'] ?? 0));
        $this->assertSame('summary', (string) ($log->meta['explanation_level'] ?? ''));
        $this->assertIsArray($log->meta['trace'] ?? null);
        $this->assertNotEmpty($log->meta['trace'] ?? []);
        $this->assertSame($owner->id, (int) ($log->meta['run_params']['run_by_user_id'] ?? 0));
    }
}
