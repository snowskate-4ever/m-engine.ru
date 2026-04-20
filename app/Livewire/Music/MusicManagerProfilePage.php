<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\UserMusicProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MusicManagerProfilePage extends Component
{
    public bool $enabled = false;

    public function mount(): void
    {
        $this->enabled = Auth::user()->canActAsManager();
    }

    public function toggle(): void
    {
        $user = Auth::user();
        $profiles = collect($user->music_profiles ?? []);
        $target = UserMusicProfile::Manager->value;

        if ($profiles->contains($target)) {
            $profiles = $profiles->reject(fn (string $value) => $value === $target)->values();
        } else {
            $profiles->push($target);
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();
        $this->enabled = $user->canActAsManager();
        $this->dispatch('music-profiles-updated');
        session()->flash('success', __('ui.music.saved'));
    }

    public function render(): View
    {
        $memberships = Auth::user()->musicProfileMemberships()
            ->where('role', MusicMembershipRole::Manager->value)
            ->latest()
            ->get();

        return view('livewire.music.music-manager-profile-page', [
            'memberships' => $memberships,
            'criteriaProfileKey' => UserMusicProfile::Manager->value,
        ]);
    }
}
