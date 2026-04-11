<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\MusicMembershipRole;
use App\Models\ConcertVenue;
use App\Models\MusicProfileMembership;
use App\Models\User;
use App\Services\Music\MusicProfileMembershipService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class VenueRepresentativesPanel extends Component
{
    public int $venueId;

    public ?int $memberUserId = null;

    public function mount(int $venueId): void
    {
        $this->venueId = $venueId;
        $venue = ConcertVenue::query()->findOrFail($venueId);
        Gate::authorize('update', $venue);
    }

    public function invite(): void
    {
        $validated = $this->validate([
            'memberUserId' => ['required', 'integer', 'exists:users,id'],
        ]);

        $venue = ConcertVenue::query()->findOrFail($this->venueId);
        app(MusicProfileMembershipService::class)->invite(
            Auth::user(),
            $venue,
            User::query()->findOrFail((int) $validated['memberUserId']),
            MusicMembershipRole::VenueRepresentative,
        );

        $this->reset('memberUserId');
        session()->flash('success', __('ui.music.saved'));
    }

    public function revoke(int $membershipId): void
    {
        $membership = MusicProfileMembership::query()->findOrFail($membershipId);
        app(MusicProfileMembershipService::class)->revoke(Auth::user(), $membership);
        session()->flash('success', __('ui.music.saved'));
    }

    public function render(): View
    {
        $venue = ConcertVenue::query()->findOrFail($this->venueId);
        $memberships = $venue->memberships()
            ->where('role', MusicMembershipRole::VenueRepresentative->value)
            ->latest()
            ->get();

        return view('livewire.music.venue-representatives-panel', [
            'venue' => $venue,
            'memberships' => $memberships,
        ]);
    }
}
