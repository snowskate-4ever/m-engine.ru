<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Models\Peformer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PerformersIndexPage extends Component
{
    public function render(): View
    {
        $user = Auth::user();
        $ids = $user->ownedPeformers()->pluck('id')
            ->merge($user->administeredPeformers()->pluck('peformers.id'))
            ->unique()
            ->values();

        $performers = Peformer::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        return view('livewire.music.performers-index-page', [
            'performers' => $performers,
        ]);
    }
}
