<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\PerformerKind;
use App\Models\Peformer;
use App\Services\Music\EntityOnCreateAutomationService;
use App\Support\Music\PublicProfileBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class PerformersIndexPage extends Component
{
    public bool $showCreateModal = false;

    public string $name = '';

    public string $description = '';

    public string $performer_kind = 'band';

    #[On('music-performers-open-create')]
    public function openCreateModal(): void
    {
        Gate::authorize('create', Peformer::class);

        $this->resetErrorBag();
        $this->name = '';
        $this->description = '';
        $this->performer_kind = 'band';
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function createPerformer(): void
    {
        Gate::authorize('create', Peformer::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'performer_kind' => ['required', 'string', Rule::in(array_map(fn (PerformerKind $kind): string => $kind->value, PerformerKind::cases()))],
        ]);

        $layoutBlocks = [];
        foreach (array_values(PublicProfileBlocks::performerCatalog()) as $order => $row) {
            $layoutBlocks[] = [
                'id' => (string) $row['id'],
                'enabled' => true,
                'order' => $order,
            ];
        }

        $layoutDraft = PublicProfileBlocks::wrapVersion1($layoutBlocks);

        $record = Peformer::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'performer_kind' => $validated['performer_kind'],
            'owner_user_id' => Auth::id(),
            'layout_draft' => $layoutDraft,
        ]);
        $owner = Auth::user();
        if ($owner !== null) {
            app(EntityOnCreateAutomationService::class)->run($record, $owner);
        }

        $this->showCreateModal = false;
        $this->name = '';
        $this->description = '';
        $this->performer_kind = 'band';

        session()->flash('success', __('ui.music.saved'));
    }

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
            'performerKinds' => PerformerKind::cases(),
        ]);
    }
}
