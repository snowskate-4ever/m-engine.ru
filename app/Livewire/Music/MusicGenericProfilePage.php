<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\UserMusicProfile;
use App\Support\Music\MusicProfileCriteriaMatrix;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MusicGenericProfilePage extends Component
{
    public string $profile = '';

    public bool $enabled = false;

    public function mount(string $profile): void
    {
        $this->profile = $profile;
        $this->enabled = $this->currentEnabled();
    }

    public function toggle(): void
    {
        $enum = UserMusicProfile::tryFrom($this->profile);
        if ($enum === null) {
            return;
        }

        $user = Auth::user();
        $profiles = collect($user->music_profiles ?? []);
        $target = $enum->value;

        if ($profiles->contains($target)) {
            $profiles = $profiles->reject(fn (string $value) => $value === $target)->values();
        } else {
            $profiles->push($target);
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();

        $this->enabled = $this->currentEnabled();
        session()->flash('success', __('ui.music.saved'));
    }

    public function render(): View
    {
        $enum = MusicProfileCriteriaMatrix::profileFromTab($this->profile)
            ?? UserMusicProfile::tryFrom($this->profile);
        $criteriaFormSink = $enum !== null ? MusicProfileCriteriaMatrix::formSink($enum) : null;

        return view('livewire.music.music-generic-profile-page', [
            'tabLabel' => __('ui.music.profile_tab_'.$this->profile),
            'title' => __('ui.music.profile_'.$this->profile.'_title'),
            'hint' => __('ui.music.profile_'.$this->profile.'_hint'),
            'criteriaFormSink' => $criteriaFormSink,
            'criteriaProfileKey' => $enum?->value,
        ]);
    }

    private function currentEnabled(): bool
    {
        return Auth::user()->hasMusicProfile($this->profile);
    }
}
