<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\SearchGoal;
use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Models\User;
use App\Services\Music\EventAssemblyService;
use App\Services\Music\SearchRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_using_actor_context_supports_all_supported_initiators(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer', 'venue_representative'],
        ]);

        $performer = Peformer::query()->create([
            'name' => 'Performer A',
            'owner_user_id' => $user->id,
        ]);
        $musician = Musician::query()->create([
            'name' => 'Musician A',
            'user_id' => $user->id,
        ]);
        $venue = ConcertVenue::query()->create([
            'name' => 'Venue A',
            'owner_user_id' => $user->id,
        ]);
        $studio = Studio::query()->create([
            'name' => 'Studio A',
            'owner_user_id' => $user->id,
        ]);
        $rehersal = Rehersal::query()->create([
            'name' => 'Rehersal A',
            'owner_user_id' => $user->id,
        ]);
        $school = School::query()->create([
            'name' => 'School A',
            'owner_user_id' => $user->id,
        ]);

        $service = app(SearchRequestService::class);

        $initiators = [
            [User::class, $user->id, SearchGoal::FindPerformerForOrganizer],
            [Peformer::class, $performer->id, SearchGoal::FindMusicianForPerformer],
            [Musician::class, $musician->id, SearchGoal::FindPerformerForMusician],
            [ConcertVenue::class, $venue->id, SearchGoal::FindOrganizerForVenue],
            [Studio::class, $studio->id, SearchGoal::FindOrganizerForStudio],
            [Rehersal::class, $rehersal->id, SearchGoal::FindOrganizerForRehearsal],
            [School::class, $school->id, SearchGoal::FindOrganizerForSchool],
        ];

        foreach ($initiators as [$type, $id, $goal]) {
            $request = $service->createUsingActorContext(
                $user,
                $goal,
                ['region' => 'Moscow'],
                $type,
                $id,
            );

            $this->assertSame($type, $request->initiator_type);
            $this->assertSame($id, $request->initiator_id);
            $this->assertSame('open', $request->status->value);
        }
    }

    public function test_create_using_actor_context_rejects_goal_not_allowed_for_initiator(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);

        $performer = Peformer::query()->create([
            'name' => 'Restricted Performer',
            'owner_user_id' => $user->id,
            'performer_kind' => 'band',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Search goal is not allowed for selected initiator.');

        app(SearchRequestService::class)->createUsingActorContext(
            $user,
            SearchGoal::FindVenueForOrganizerEvent,
            [],
            Peformer::class,
            $performer->id,
        );
    }

    public function test_cancel_request_revokes_pending_invites(): void
    {
        $organizer = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);
        $venueOwner = User::factory()->create([
            'music_profiles' => ['venue_representative'],
        ]);
        $venue = ConcertVenue::query()->create([
            'name' => 'Hall C',
            'owner_user_id' => $venueOwner->id,
        ]);

        $request = app(SearchRequestService::class)->create(
            $organizer,
            $venue,
            SearchGoal::FindVenueForOrganizerEvent
        );

        $invite = app(EventAssemblyService::class)->sendOrganizerVenueInvite(
            $organizer,
            $venue->id,
            $request->id
        );

        app(SearchRequestService::class)->cancel($request->fresh());

        $this->assertDatabaseHas('search_requests', [
            'id' => $request->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('organizer_venue_invites', [
            'id' => $invite->id,
            'status' => 'revoked',
        ]);
    }

    public function test_cancel_request_revokes_pending_studio_invites(): void
    {
        $organizer = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);
        $studioOwner = User::factory()->create([
            'music_profiles' => ['venue_representative'],
        ]);
        $studio = Studio::query()->create([
            'name' => 'Studio Cancel',
            'owner_user_id' => $studioOwner->id,
        ]);

        $request = app(SearchRequestService::class)->create(
            $organizer,
            $studio,
            SearchGoal::FindStudioForOrganizerEvent
        );

        $invite = app(EventAssemblyService::class)->sendOrganizerStudioInvite(
            $organizer,
            $studio->id,
            $request->id
        );

        app(SearchRequestService::class)->cancel($request->fresh());

        $this->assertDatabaseHas('search_requests', [
            'id' => $request->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('organizer_studio_invites', [
            'id' => $invite->id,
            'status' => 'revoked',
        ]);
    }

    public function test_reopen_allows_only_cancelled_or_expired_requests(): void
    {
        $organizer = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);

        $service = app(SearchRequestService::class);

        $cancelled = $service->create($organizer, $organizer, SearchGoal::FindPerformerForOrganizer);
        $service->cancel($cancelled->fresh());
        $service->reopen($cancelled->fresh());
        $this->assertDatabaseHas('search_requests', [
            'id' => $cancelled->id,
            'status' => 'open',
        ]);

        $fulfilled = $service->create($organizer, $organizer, SearchGoal::FindPerformerForOrganizer);
        $service->markFulfilled($fulfilled->fresh());

        $this->expectException(\InvalidArgumentException::class);
        $service->reopen($fulfilled->fresh());
    }
}
