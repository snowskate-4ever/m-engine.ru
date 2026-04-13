<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\UserMusicProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MusicSessionMusicianProfilePage extends Component
{
    public bool $enabled = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->enabled = $user->canActAsSessionMusician();
    }

    public function toggle(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $profiles = collect($user->music_profiles ?? []);
        $target = UserMusicProfile::SessionMusician->value;

        if ($profiles->contains($target)) {
            $profiles = $profiles->reject(fn (string $value) => $value === $target)->values();
        } else {
            $profiles->push($target);
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();

        $musician = $user->musician;
        if ($musician !== null) {
            $musician->is_session = $user->hasMusicProfile(UserMusicProfile::SessionMusician);
            $musician->save();
        }

        $this->enabled = $user->canActAsSessionMusician();
        session()->flash('success', __('ui.music.saved'));
    }

    public function render(): View
    {
        return view('livewire.music.music-session-musician-profile-page');
    }
}
