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
        if (! in_array($kind, ['studio', 'rehearsal', 'concert_venue', 'school', 'record_label', 'producer_center', 'shop'], true)) {
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
            'concert_venue' => $user->ownedConcertVenues()->orderBy('name')->get(),
            'school' => $user->ownedSchools()->orderBy('name')->get(),
            'record_label' => $user->ownedRecordLabels()->orderBy('name')->get(),
            'producer_center' => $user->ownedProducerCenters()->orderBy('name')->get(),
            'shop' => $user->ownedShops()->orderBy('name')->get(),
        };

        return view('livewire.music.venue-index-page', [
            'items' => $items,
            'routePrefix' => match ($this->kind) {
                'studio' => 'studios',
                'rehearsal' => 'rehearsals',
                'concert_venue' => 'concert-venues',
                'school' => 'schools',
                'record_label' => 'labels',
                'producer_center' => 'producer-centers',
                'shop' => 'shops',
            },
        ]);
    }
}
