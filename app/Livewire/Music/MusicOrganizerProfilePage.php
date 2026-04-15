<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\UserMusicProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MusicOrganizerProfilePage extends Component
{
    public bool $enabled = false;

    public function mount(): void
    {
        $this->enabled = Auth::user()->canActAsEventOrganizer();
    }

    public function toggle(): void
    {
        $user = Auth::user();
        $profiles = collect($user->music_profiles ?? []);
        $target = UserMusicProfile::EventOrganizer->value;

        if ($profiles->contains($target)) {
            $profiles = $profiles->reject(fn (string $value) => $value === $target)->values();
        } else {
            $profiles->push($target);
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();
        $this->enabled = $user->canActAsEventOrganizer();
        session()->flash('success', __('ui.music.saved'));
    }

    public function render(): View
    {
        return view('livewire.music.music-organizer-profile-page', [
            'criteriaProfileKey' => UserMusicProfile::EventOrganizer->value,
        ]);
    }
}
