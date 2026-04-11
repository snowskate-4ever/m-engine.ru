<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Enums\UserMusicProfile;
use App\Models\MusicProfileMembership;
use App\Services\Music\MusicProfileMembershipService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MusicVenueRepresentativeProfilePage extends Component
{
    public bool $enabled = false;

    public function mount(): void
    {
        $this->enabled = Auth::user()->canActAsVenueRepresentative();
    }

    public function toggle(): void
    {
        $user = Auth::user();
        $profiles = collect($user->music_profiles ?? []);
        $target = UserMusicProfile::VenueRepresentative->value;

        if ($profiles->contains($target)) {
            $profiles = $profiles->reject(fn (string $value) => $value === $target)->values();
        } else {
            $profiles->push($target);
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();
        $this->enabled = $user->canActAsVenueRepresentative();
        session()->flash('success', __('ui.music.saved'));
    }

    public function acceptMembership(int $membershipId): void
    {
        $membership = MusicProfileMembership::query()->findOrFail($membershipId);
        app(MusicProfileMembershipService::class)->respond(Auth::user(), $membership, MusicMembershipStatus::Accepted);
        session()->flash('success', __('ui.music.saved'));
    }

    public function declineMembership(int $membershipId): void
    {
        $membership = MusicProfileMembership::query()->findOrFail($membershipId);
        app(MusicProfileMembershipService::class)->respond(Auth::user(), $membership, MusicMembershipStatus::Declined);
        session()->flash('success', __('ui.music.saved'));
    }

    public function render(): View
    {
        $memberships = Auth::user()->musicProfileMemberships()
            ->where('role', MusicMembershipRole::VenueRepresentative->value)
            ->whereIn('status', [MusicMembershipStatus::Pending->value, MusicMembershipStatus::Accepted->value])
            ->latest()
            ->get();

        return view('livewire.music.music-venue-representative-profile-page', [
            'memberships' => $memberships,
        ]);
    }
}
