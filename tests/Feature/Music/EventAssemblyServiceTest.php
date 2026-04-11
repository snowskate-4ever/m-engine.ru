<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\MatchingInviteStatus;
use App\Enums\MusicEventAssemblyStatus;
use App\Enums\SearchGoal;
use App\Models\ConcertVenue;
use App\Models\Peformer;
use App\Models\Studio;
use App\Models\User;
use App\Services\Music\EventAssemblyService;
use App\Services\Music\SearchRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAssemblyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_venue_acceptance_updates_event_and_fulfills_request(): void
    {
        $organizer = User::factory()->create([
            'music_profiles' => ['event_organizer', 'venue_representative'],
        ]);
        $venueOwner = User::factory()->create([
            'music_profiles' => ['venue_representative'],
        ]);
        $venue = ConcertVenue::query()->create([
            'name' => 'Hall A',
            'owner_user_id' => $venueOwner->id,
        ]);

        $request = app(SearchRequestService::class)->create(
            $organizer,
            $venue,
            SearchGoal::FindOrganizerForVenue
        );

        $invite = app(EventAssemblyService::class)->sendOrganizerVenueInvite(
            $organizer,
            $venue->id,
            $request->id,
            null,
            now()->addDay(),
            now()->addDay()->addHours(2),
        );

        $this->actingAs($venueOwner);
        $event = app(EventAssemblyService::class)->respondOrganizerVenueInvite($invite, $venueOwner, MatchingInviteStatus::Accepted);

        $this->assertNotNull($event->id);
        $this->assertSame($venue->id, $event->concert_venue_id);
        $this->assertSame(MusicEventAssemblyStatus::Incomplete->value, $event->assembly_status->value);
        $this->assertDatabaseHas('search_requests', [
            'id' => $request->id,
            'status' => 'fulfilled',
        ]);
    }

    public function test_organizer_performer_acceptance_adds_lineup_and_marks_event_ready(): void
    {
        $organizer = User::factory()->create([
            'music_profiles' => ['event_organizer', 'venue_representative'],
        ]);
        $venueOwner = User::factory()->create([
            'music_profiles' => ['venue_representative'],
        ]);
        $performerOwner = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);

        $venue = ConcertVenue::query()->create([
            'name' => 'Hall B',
            'owner_user_id' => $venueOwner->id,
        ]);
        $peformer = Peformer::query()->create([
            'name' => 'Band X',
            'owner_user_id' => $performerOwner->id,
            'performer_kind' => 'band',
        ]);

        $venueInvite = app(EventAssemblyService::class)->sendOrganizerVenueInvite(
            $organizer,
            $venue->id,
            null,
            null,
            now()->addDay(),
            now()->addDay()->addHours(3),
        );
        $this->actingAs($venueOwner);
        $event = app(EventAssemblyService::class)->respondOrganizerVenueInvite($venueInvite, $venueOwner, MatchingInviteStatus::Accepted);

        $request = app(SearchRequestService::class)->create($organizer, $peformer, SearchGoal::FindPerformerForOrganizer);
        $invite = app(EventAssemblyService::class)->sendOrganizerPerformerInvite($organizer, $peformer->id, $request->id, $event->id);
        $this->actingAs($performerOwner);
        $updated = app(EventAssemblyService::class)->respondOrganizerPerformerInvite($invite, $performerOwner, MatchingInviteStatus::Accepted);

        $this->assertSame(MusicEventAssemblyStatus::Ready->value, $updated->fresh()->assembly_status->value);
        $this->assertDatabaseHas('event_peformer', [
            'event_id' => $updated->id,
            'peformer_id' => $peformer->id,
            'added_via_search_request_id' => $request->id,
        ]);
    }

    public function test_organizer_studio_acceptance_sets_matching_context_and_fulfills_request(): void
    {
        $organizer = User::factory()->create([
            'music_profiles' => ['event_organizer', 'venue_representative'],
        ]);
        $studioOwner = User::factory()->create([
            'music_profiles' => ['venue_representative'],
        ]);
        $studio = Studio::query()->create([
            'name' => 'Studio A',
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
            $request->id,
            null,
            now()->addDay(),
            now()->addDay()->addHours(2),
        );

        $this->actingAs($studioOwner);
        $event = app(EventAssemblyService::class)->respondOrganizerStudioInvite($invite, $studioOwner, MatchingInviteStatus::Accepted);

        $this->assertNotNull($event->id);
        $this->assertSame(Studio::class, $event->matching_space_type);
        $this->assertSame($studio->id, $event->matching_space_id);
        $this->assertSame(MusicEventAssemblyStatus::Incomplete->value, $event->assembly_status->value);
        $this->assertDatabaseHas('search_requests', [
            'id' => $request->id,
            'status' => 'fulfilled',
        ]);
    }
}
