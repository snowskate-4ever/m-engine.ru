<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Services\Music\MusicPublicSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MusicDirectoryPage extends Component
{
    public string $q = '';

    public string $category = MusicPublicSearchService::CATEGORY_ALL;

    public bool $spaNavigate = true;

    public function render(): View
    {
        $category = in_array($this->category, MusicPublicSearchService::categories(), true)
            ? $this->category
            : MusicPublicSearchService::CATEGORY_ALL;

        $results = app(MusicPublicSearchService::class)->search($this->q, $category);

        return view('livewire.music.music-directory-page', [
            'results' => $results,
            'activeCategory' => $category,
            'spaNavigate' => $this->spaNavigate,
        ]);
    }
}
