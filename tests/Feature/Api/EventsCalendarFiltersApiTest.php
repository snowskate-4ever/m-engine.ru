<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ConcertVenue;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EventsCalendarFiltersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_events_returns_only_linked_entities_by_default(): void
    {
        $owner = User::factory()->create([
            'music_profiles' => ['event_organizer', 'venue_representative'],
        ]);
        $other = User::factory()->create();
        $venue = ConcertVenue::query()->create([
            'name' => 'API Linked Venue',
            'owner_user_id' => $owner->id,
        ]);
        $otherVenue = ConcertVenue::query()->create([
            'name' => 'API Other Venue',
            'owner_user_id' => $other->id,
        ]);

        $includedDirect = Event::query()->create([
            'name' => 'Included direct',
            'description' => 'd',
            'active' => true,
            'music_organizer_user_id' => $owner->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
        ]);
        $includedVenue = Event::query()->create([
            'name' => 'Included venue',
            'description' => 'd',
            'active' => true,
            'concert_venue_id' => $venue->id,
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
        ]);
        Event::query()->create([
            'name' => 'Excluded venue',
            'description' => 'd',
            'active' => true,
            'concert_venue_id' => $otherVenue->id,
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
        ]);

        Sanctum::actingAs($owner);
        $response = $this->getJson('/api/events?date_from='.now()->toDateString().'&date_to='.now()->addDays(10)->toDateString());

        $response->assertOk();
        $ids = collect($response->json('data.events'))->pluck('id')->all();
        $this->assertContains($includedDirect->id, $ids);
        $this->assertContains($includedVenue->id, $ids);
        $this->assertCount(2, $ids);
    }
}
