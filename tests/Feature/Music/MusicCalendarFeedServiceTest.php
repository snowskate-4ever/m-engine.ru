<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Models\ConcertVenue;
use App\Models\Event;
use App\Models\Resource;
use App\Models\Type;
use App\Models\User;
use App\Services\Music\MusicCalendarFeedService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MusicCalendarFeedServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_returns_events_from_linked_entities_only(): void
    {
        $owner = User::factory()->create([
            'music_profiles' => ['event_organizer', 'venue_representative'],
        ]);
        $other = User::factory()->create();
        $venue = ConcertVenue::query()->create([
            'name' => 'Linked Venue',
            'owner_user_id' => $owner->id,
        ]);
        $otherVenue = ConcertVenue::query()->create([
            'name' => 'Other Venue',
            'owner_user_id' => $other->id,
        ]);

        $direct = Event::query()->create([
            'name' => 'Direct owner event',
            'description' => 'd',
            'active' => true,
            'music_organizer_user_id' => $owner->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
        ]);
        $venueEvent = Event::query()->create([
            'name' => 'Venue linked event',
            'description' => 'd',
            'active' => true,
            'concert_venue_id' => $venue->id,
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
        ]);
        Event::query()->create([
            'name' => 'Other venue event',
            'description' => 'd',
            'active' => true,
            'concert_venue_id' => $otherVenue->id,
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
        ]);

        $events = app(MusicCalendarFeedService::class)->eventsForRange(
            $owner,
            CarbonImmutable::now()->subDay()->utc(),
            CarbonImmutable::now()->addDays(7)->utc(),
            ['owner_entity' => 'all_linked'],
        );

        $this->assertTrue($events->contains('id', $direct->id));
        $this->assertTrue($events->contains('id', $venueEvent->id));
        $this->assertSame(2, $events->count());
    }

    public function test_feed_event_kind_booking_returns_only_bookings(): void
    {
        $owner = User::factory()->create();
        $type = Type::query()->create([
            'name' => 'Space',
            'resource_type' => 'space',
            'description' => 'space',
        ]);
        $resource = Resource::query()->create([
            'active' => true,
            'type_id' => $type->id,
            'start_at' => now()->toDateString(),
            'end_at' => now()->addMonth()->toDateString(),
        ]);

        Event::query()->create([
            'name' => 'Regular event',
            'description' => 'd',
            'active' => true,
            'user_id' => $owner->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
        ]);
        $booking = Event::query()->create([
            'name' => 'Booking event',
            'description' => 'd',
            'active' => true,
            'user_id' => $owner->id,
            'booked_resource_id' => $resource->id,
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
        ]);

        $events = app(MusicCalendarFeedService::class)->eventsForRange(
            $owner,
            CarbonImmutable::now()->subDay()->utc(),
            CarbonImmutable::now()->addDays(7)->utc(),
            ['owner_entity' => User::class.'#'.$owner->id, 'event_kind' => MusicCalendarFeedService::EVENT_KIND_BOOKING],
        );

        $this->assertSame([$booking->id], $events->pluck('id')->all());
    }
}
