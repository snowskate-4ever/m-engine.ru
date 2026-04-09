<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class MusicProfilesPage extends Component
{
    /** @var 'musician'|'teacher' */
    #[Url(history: true)]
    public string $tab = 'musician';

    public function mount(): void
    {
        if (! in_array($this->tab, ['musician', 'teacher'], true)) {
            $this->tab = 'musician';
        }
    }

    public function render(): View
    {
        return view('livewire.music.music-profiles-page');
    }
}
