<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\SearchGoal;
use App\Livewire\Music\SearchRequestsPage;
use App\Models\User;
use App\Services\Music\SearchRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchRequestsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requests_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);

        $this->actingAs($user)
            ->get(route('music.search-requests.index'))
            ->assertOk()
            ->assertSee(__('ui.music.search_requests_page_title'));
    }

    public function test_livewire_component_creates_cancel_and_reopens_request(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);

        Livewire::actingAs($user)->test(SearchRequestsPage::class)
            ->set('searchGoal', SearchGoal::FindPerformerForOrganizer->value)
            ->set('initiatorRef', User::class.':'.$user->id)
            ->set('criteriaJson', '{"genre":"rock"}')
            ->call('createRequest')
            ->assertHasNoErrors();

        $requestId = (int) \App\Models\SearchRequest::query()->value('id');
        $this->assertGreaterThan(0, $requestId);

        Livewire::actingAs($user)->test(SearchRequestsPage::class)
            ->call('cancelRequest', $requestId)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('search_requests', [
            'id' => $requestId,
            'status' => 'cancelled',
        ]);

        Livewire::actingAs($user)->test(SearchRequestsPage::class)
            ->call('reopenRequest', $requestId)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('search_requests', [
            'id' => $requestId,
            'status' => 'open',
        ]);
    }

    public function test_livewire_component_filters_requests_by_status(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);

        $service = app(SearchRequestService::class);
        $open = $service->create($user, $user, SearchGoal::FindPerformerForOrganizer, ['marker' => 'open-only']);
        $cancelled = $service->create($user, $user, SearchGoal::FindVenueForOrganizerEvent, ['marker' => 'cancelled-only']);
        $service->cancel($cancelled->fresh());

        Livewire::actingAs($user)->test(SearchRequestsPage::class)
            ->set('statusFilter', 'cancelled')
            ->assertSee('cancelled-only')
            ->assertDontSee('open-only');
    }
}
