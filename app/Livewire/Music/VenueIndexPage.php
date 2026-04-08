<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VenueIndexPage extends Component
{
    public string $kind = 'studio';

    public function mount(string $kind): void
    {
        if (! in_array($kind, ['studio', 'rehearsal', 'school'], true)) {
            abort(404);
        }
        $this->kind = $kind;
    }

    public function render(): View
    {
        $user = Auth::user();
        $items = match ($this->kind) {
            'studio' => $user->ownedStudios()->orderBy('name')->get(),
            'rehearsal' => $user->ownedRehearsals()->orderBy('name')->get(),
            'school' => $user->ownedSchools()->orderBy('name')->get(),
        };

        return view('livewire.music.venue-index-page', [
            'items' => $items,
            'routePrefix' => match ($this->kind) {
                'studio' => 'studios',
                'rehearsal' => 'rehearsals',
                'school' => 'schools',
            },
        ]);
    }
}
