<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Services\Music\MusicActorContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class MusicProfilesPage extends Component
{
    /** @var 'musician'|'teacher'|'organizer'|'venue_representative'|'manager' */
    #[Url(history: true)]
    public string $tab = 'musician';

    public ?string $activeActorRef = null;

    public function mount(): void
    {
        if (! in_array($this->tab, ['musician', 'teacher', 'organizer', 'venue_representative', 'manager'], true)) {
            $this->tab = 'musician';
        }

        $current = app(MusicActorContextService::class)->currentActor(Auth::user());
        if ($current !== null) {
            $this->activeActorRef = $current['type'].':'.$current['id'];
        }
    }

    public function saveActiveActor(): void
    {
        if ($this->activeActorRef === null || $this->activeActorRef === '') {
            Auth::user()->setActiveMusicActor(null, null);
            session()->flash('success', __('ui.music.saved'));

            return;
        }

        [$type, $id] = explode(':', $this->activeActorRef, 2);
        app(MusicActorContextService::class)->setActiveActor(Auth::user(), (string) $type, (int) $id);
        session()->flash('success', __('ui.music.saved'));
    }

    public function render(): View
    {
        $actorOptions = app(MusicActorContextService::class)->availableActors(Auth::user());

        return view('livewire.music.music-profiles-page', [
            'actorOptions' => $actorOptions,
        ]);
    }
}
