<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\LegalEntityType;
use App\Enums\SearchRequestStatus;
use App\Models\ConcertVenue;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\SearchRequest;
use App\Models\Shop;
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

    /** @var array{open_requests: int, incomplete_events: int, ready_events: int} */
    public array $matchingProgress = [
        'open_requests' => 0,
        'incomplete_events' => 0,
        'ready_events' => 0,
    ];

    public function mount(string $kind, ?int $recordId = null): void
    {
        if (! in_array($kind, ['studio', 'rehearsal', 'concert_venue', 'school', 'record_label', 'producer_center', 'shop'], true)) {
            abort(404);
        }
        $this->kind = $kind;
        $this->recordId = $recordId;

        $catalog = $this->venueBlockCatalog();
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
        $this->matchingProgress = $this->resolveMatchingProgress();
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
            'kind' => $this->kind,
            'blockCatalog' => $this->venueBlockCatalog(),
            'matchingProgress' => $this->resolveMatchingProgress(),
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

    /** @return class-string<Model> */
    private function modelClass(): string
    {
        return match ($this->kind) {
            'studio' => Studio::class,
            'rehearsal' => Rehersal::class,
            'concert_venue' => ConcertVenue::class,
            'school' => School::class,
            'record_label' => RecordLabel::class,
            'producer_center' => ProducerCenter::class,
            'shop' => Shop::class,
        };
    }

    private function slugTable(): string
    {
        return match ($this->kind) {
            'studio' => 'studios',
            'rehearsal' => 'rehearsals',
            'concert_venue' => 'concert_venues',
            'school' => 'schools',
            'record_label' => 'record_labels',
            'producer_center' => 'producer_centers',
            'shop' => 'shops',
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
            'concert_venue' => route('music.concert-venues.edit', $model),
            'school' => route('music.schools.edit', $model),
            'record_label' => route('music.labels.edit', $model),
            'producer_center' => route('music.producer-centers.edit', $model),
            'shop' => route('music.shops.edit', $model),
        };
    }

    /**
     * @return list<array{id: string, enabled: bool, order: int}>
     */
    /**
     * @return list<array{id: string, label_key: string}>
     */
    private function venueBlockCatalog(): array
    {
        return $this->kind === 'shop'
            ? PublicProfileBlocks::shopCatalog()
            : PublicProfileBlocks::venueCatalog();
    }

    private function buildLayoutBlocks(): array
    {
        $catalog = $this->venueBlockCatalog();
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

    /**
     * @return array{open_requests: int, incomplete_events: int, ready_events: int}
     */
    private function resolveMatchingProgress(): array
    {
        if (! $this->record instanceof Model) {
            return [
                'open_requests' => 0,
                'incomplete_events' => 0,
                'ready_events' => 0,
            ];
        }

        $matchingClass = match ($this->kind) {
            'concert_venue' => ConcertVenue::class,
            'studio' => Studio::class,
            'rehearsal' => Rehersal::class,
            'school' => School::class,
            default => null,
        };

        if ($matchingClass === null) {
            return [
                'open_requests' => 0,
                'incomplete_events' => 0,
                'ready_events' => 0,
            ];
        }

        $openRequests = SearchRequest::query()
            ->where('initiator_type', $matchingClass)
            ->where('initiator_id', $this->record->id)
            ->whereIn('status', [SearchRequestStatus::Open->value, SearchRequestStatus::AwaitingApproval->value])
            ->count();

        $eventsQuery = \App\Models\Event::query();
        if ($matchingClass === ConcertVenue::class) {
            $eventsQuery->where(function ($query) use ($matchingClass): void {
                $query->where('concert_venue_id', $this->record->id)
                    ->orWhere(function ($nested) use ($matchingClass): void {
                        $nested->where('matching_space_type', $matchingClass)
                            ->where('matching_space_id', $this->record->id);
                    });
            });
        } else {
            $eventsQuery
                ->where('matching_space_type', $matchingClass)
                ->where('matching_space_id', $this->record->id);
        }

        return [
            'open_requests' => $openRequests,
            'incomplete_events' => (clone $eventsQuery)->where('assembly_status', 'incomplete')->count(),
            'ready_events' => (clone $eventsQuery)->where('assembly_status', 'ready')->count(),
        ];
    }
}
