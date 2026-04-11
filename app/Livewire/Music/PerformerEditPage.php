<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\PerformerKind;
use App\Models\Peformer;
use App\Support\Music\PublicProfileBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PerformerEditPage extends Component
{
    public ?int $recordId = null;

    public ?Peformer $record = null;

    public string $name = '';

    public string $description = '';

    public string $slug = '';

    public bool $public_page_enabled = false;

    public string $performer_kind = 'band';

    /** @var array<string, bool> */
    public array $layoutBlockEnabled = [];

    public function mount(?int $recordId = null): void
    {
        $this->recordId = $recordId;

        $catalog = PublicProfileBlocks::performerCatalog();
        foreach ($catalog as $row) {
            $this->layoutBlockEnabled[$row['id']] = true;
        }

        if ($this->recordId === null) {
            Gate::authorize('create', Peformer::class);
            $this->name = '';

            return;
        }

        $this->record = Peformer::query()->whereKey($this->recordId)->firstOrFail();
        Gate::authorize('update', $this->record);

        $this->name = (string) $this->record->name;
        $this->description = (string) ($this->record->description ?? '');
        $this->slug = (string) ($this->record->slug ?? '');
        $this->public_page_enabled = (bool) $this->record->public_page_enabled;
        $this->performer_kind = $this->record->performer_kind instanceof PerformerKind
            ? $this->record->performer_kind->value
            : (string) $this->record->performer_kind;

        $draft = $this->record->layout_draft;
        if (is_array($draft) && ! empty($draft['blocks']) && is_array($draft['blocks'])) {
            foreach ($draft['blocks'] as $b) {
                if (is_array($b) && isset($b['id'])) {
                    $this->layoutBlockEnabled[(string) $b['id']] = (bool) ($b['enabled'] ?? true);
                }
            }
        }
    }

    public function save(): void
    {
        if ($this->recordId === null && $this->record === null) {
            Gate::authorize('create', Peformer::class);
        } else {
            $this->record ??= Peformer::query()->whereKey($this->recordId)->firstOrFail();
            Gate::authorize('update', $this->record);
        }

        $isCreating = $this->record === null;

        $slugRules = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('peformers', 'slug')->ignore($this->record?->id),
        ];
        if ($this->public_page_enabled) {
            $slugRules = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('peformers', 'slug')->ignore($this->record?->id),
            ];
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'slug' => $slugRules,
            'public_page_enabled' => ['boolean'],
            'performer_kind' => ['required', 'string', Rule::in(array_map(fn (PerformerKind $c) => $c->value, PerformerKind::cases()))],
        ], [
            'slug.required' => __('ui.music.validation.slug_required_public'),
            'slug.regex' => __('ui.music.validation.slug_format'),
        ]);

        $layoutDraft = PublicProfileBlocks::wrapVersion1($this->buildLayoutBlocks());

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => $validated['slug'] ?: null,
            'public_page_enabled' => $validated['public_page_enabled'],
            'performer_kind' => $validated['performer_kind'],
            'layout_draft' => $layoutDraft,
        ];

        if ($isCreating) {
            $payload['owner_user_id'] = Auth::id();
            $this->record = Peformer::create($payload);
            $this->recordId = $this->record->id;

            session()->flash('success', __('ui.music.saved'));
            $this->redirect(route('music.performers.edit', $this->record), navigate: true);

            return;
        }

        $this->record->update($payload);

        session()->flash('success', __('ui.music.saved'));
    }

    public function publishLayout(): void
    {
        $this->save();
        if ($this->record === null) {
            return;
        }
        Gate::authorize('update', $this->record);
        $draft = PublicProfileBlocks::wrapVersion1($this->buildLayoutBlocks());
        $this->record->layout_draft = $draft;
        $this->record->layout_published = $draft;
        $this->record->save();

        session()->flash('success', __('ui.music.layout_published'));
    }

    /**
     * @return list<array{id: string, enabled: bool, order: int}>
     */
    private function buildLayoutBlocks(): array
    {
        $catalog = PublicProfileBlocks::performerCatalog();
        $out = [];
        foreach (array_values($catalog) as $order => $row) {
            $id = $row['id'];
            $out[] = [
                'id' => $id,
                'enabled' => (bool) ($this->layoutBlockEnabled[$id] ?? false),
                'order' => $order,
            ];
        }

        return $out;
    }

    public function render(): View
    {
        return view('livewire.music.performer-edit-page', [
            'blockCatalog' => PublicProfileBlocks::performerCatalog(),
            'performerKinds' => PerformerKind::cases(),
        ]);
    }
}
