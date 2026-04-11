<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Livewire\Calendar\CalendarPage;
use App\Models\Event;
use App\Models\Resource;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CalendarPageFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_page_shows_only_booking_when_kind_filter_selected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Event::query()->create([
            'name' => 'Regular Event',
            'description' => 'd',
            'active' => true,
            'user_id' => $user->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
        ]);
        $type = Type::query()->create([
            'name' => 'Test type',
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
            'name' => 'Booking Event',
            'description' => 'd',
            'active' => true,
            'user_id' => $user->id,
            'booked_resource_id' => $resource->id,
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
        ]);

        Livewire::test(CalendarPage::class)
            ->set('eventKind', 'event')
            ->assertSee('Regular Event')
            ->assertDontSee('Booking Event');
    }
}
