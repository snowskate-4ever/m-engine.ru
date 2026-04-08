<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\LegalEntityType;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Support\Music\PublicProfileBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class VenueEditPage extends Component
{
    public string $kind = 'studio';

    public ?int $recordId = null;

    public ?Model $record = null;

    public string $name = '';

    public string $description = '';

    public string $slug = '';

    public bool $public_page_enabled = false;

    public ?string $legal_entity_type = null;

    public string $company_name = '';

    public string $inn = '';

    public string $ogrn = '';

    /** @var array<string, bool> */
    public array $layoutBlockEnabled = [];

    public function mount(string $kind, ?int $recordId = null): void
    {
        if (! in_array($kind, ['studio', 'rehearsal', 'school'], true)) {
            abort(404);
        }
        $this->kind = $kind;
        $this->recordId = $recordId;

        $catalog = PublicProfileBlocks::venueCatalog();
        foreach ($catalog as $row) {
            $this->layoutBlockEnabled[$row['id']] = true;
        }

        if ($this->recordId === null) {
            Gate::authorize('create', $this->modelClass());

            return;
        }

        $this->record = $this->findOwnedOrFail();
        Gate::authorize('update', $this->record);

        $this->name = (string) $this->record->name;
        $this->description = (string) ($this->record->description ?? '');
        $this->slug = (string) ($this->record->slug ?? '');
        $this->public_page_enabled = (bool) $this->record->public_page_enabled;
        $le = $this->record->legal_entity_type;
        $this->legal_entity_type = $le instanceof LegalEntityType ? $le->value : null;
        $this->company_name = (string) ($this->record->company_name ?? '');
        $this->inn = (string) ($this->record->inn ?? '');
        $this->ogrn = (string) ($this->record->ogrn ?? '');

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
            Gate::authorize('create', $this->modelClass());
        } else {
            $this->record ??= $this->findOwnedOrFail();
            Gate::authorize('update', $this->record);
        }

        $isCreating = $this->record === null;

        $slugRules = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique($this->slugTable(), 'slug')->ignore($this->record?->getKey()),
        ];
        if ($this->public_page_enabled) {
            $slugRules = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique($this->slugTable(), 'slug')->ignore($this->record?->getKey()),
            ];
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'slug' => $slugRules,
            'public_page_enabled' => ['boolean'],
            'legal_entity_type' => ['nullable', 'string', Rule::in(array_map(fn (LegalEntityType $c) => $c->value, LegalEntityType::cases()))],
            'company_name' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:32'],
            'ogrn' => ['nullable', 'string', 'max:32'],
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
            'legal_entity_type' => $validated['legal_entity_type'] ?: null,
            'company_name' => $validated['company_name'] ?: null,
            'inn' => $validated['inn'] ?: null,
            'ogrn' => $validated['ogrn'] ?: null,
            'layout_draft' => $layoutDraft,
        ];

        if ($isCreating) {
            $payload['owner_user_id'] = Auth::id();
            $modelClass = $this->modelClass();
            $this->record = $modelClass::create($payload);
            $this->recordId = (int) $this->record->getKey();

            session()->flash('success', __('ui.music.saved'));
            $this->redirect($this->editRoute($this->record), navigate: true);

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

    public function render(): View
    {
        return view('livewire.music.venue-edit-page', [
            'blockCatalog' => PublicProfileBlocks::venueCatalog(),
            'routePrefix' => match ($this->kind) {
                'studio' => 'studios',
                'rehearsal' => 'rehearsals',
                'school' => 'schools',
            },
            'publicUrlPrefix' => match ($this->kind) {
                'studio' => 'studios',
                'rehearsal' => 'rehearsals',
                'school' => 'schools',
            },
        ]);
    }

    /** @return class-string<Model> */
    private function modelClass(): string
    {
        return match ($this->kind) {
            'studio' => Studio::class,
            'rehearsal' => Rehersal::class,
            'school' => School::class,
        };
    }

    private function slugTable(): string
    {
        return match ($this->kind) {
            'studio' => 'studios',
            'rehearsal' => 'rehearsals',
            'school' => 'schools',
        };
    }

    private function findOwnedOrFail(): Model
    {
        $class = $this->modelClass();

        return $class::query()
            ->whereKey($this->recordId)
            ->where('owner_user_id', Auth::id())
            ->firstOrFail();
    }

    private function editRoute(Model $model): string
    {
        return match ($this->kind) {
            'studio' => route('music.studios.edit', $model),
            'rehearsal' => route('music.rehearsals.edit', $model),
            'school' => route('music.schools.edit', $model),
        };
    }

    /**
     * @return list<array{id: string, enabled: bool, order: int}>
     */
    private function buildLayoutBlocks(): array
    {
        $catalog = PublicProfileBlocks::venueCatalog();
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
}
