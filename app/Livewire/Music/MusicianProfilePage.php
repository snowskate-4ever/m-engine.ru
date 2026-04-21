<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\PerformerMembershipStatus;
use App\Enums\UserMusicProfile;
use App\Models\City;
use App\Models\Country;
use App\Models\Genre;
use App\Models\Instrument;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\User;
use App\Services\Music\PerformerMembershipService;
use App\Support\Music\PublicProfileBlocks;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MusicianProfilePage extends Component
{
    /**
     * Встроен в /profiles: без блока состава и без формулировок «публичная страница».
     */
    public bool $embeddedInProfilesHub = false;

    public bool $enabled = false;

    public ?Musician $record = null;

    public string $name = '';

    public string $description = '';

    public string $slug = '';

    public bool $public_page_enabled = false;

    /** @var list<int> */
    public array $instrumentIds = [];

    public ?int $selectedInstrumentId = null;

    /** @var list<int> */
    public array $genreIds = [];

    public ?int $selectedGenreId = null;

    /** @var list<int> */
    public array $cityIds = [];

    public ?int $selectedCityId = null;

    public int $cityPickerCountryId = 0;

    public ?int $experienceStartMonth = null;

    public ?int $experienceStartYear = null;

    /** @var array<string, bool> */
    public array $layoutBlockEnabled = [];

    public ?string $lineupNotice = null;

    public ?string $lineupError = null;

    public ?int $lineupRequestPerformerId = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->enabled = $user->canActAsMusician();
        $this->record = $user->musician;
        if ($this->record === null) {
            Gate::authorize('create', Musician::class);
            $this->record = Musician::create([
                'user_id' => $user->id,
                'name' => (string) $user->name,
                'is_session' => $user->canActAsSessionMusician(),
            ]);
        }

        $this->record->loadMissing(['instruments', 'genres', 'cities']);

        $catalog = PublicProfileBlocks::musicianCatalog();
        foreach ($catalog as $row) {
            $this->layoutBlockEnabled[$row['id']] = true;
        }

        if ($this->record) {
            Gate::authorize('update', $this->record);
            $this->name = (string) $this->record->name;
            $this->description = (string) ($this->record->description ?? '');
            $this->slug = (string) ($this->record->slug ?? '');
            $this->public_page_enabled = (bool) $this->record->public_page_enabled;
            $this->instrumentIds = $this->record->instruments->pluck('id')->all();
            $this->genreIds = $this->record->genres->pluck('id')->all();
            $this->cityIds = $this->record->cities->pluck('id')->all();
            if ($this->record->experience_started_on !== null) {
                $this->experienceStartMonth = (int) $this->record->experience_started_on->format('n');
                $this->experienceStartYear = (int) $this->record->experience_started_on->format('Y');
            }
            $this->cityPickerCountryId = (int) (
                Country::query()->where('code', 'RU')->value('id')
                ?? Country::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->value('id')
                ?? Country::query()->orderBy('sort_order')->orderBy('name')->value('id')
                ?? 0
            );

            $draft = $this->record->layout_draft;
            if (is_array($draft) && ! empty($draft['blocks']) && is_array($draft['blocks'])) {
                foreach ($draft['blocks'] as $b) {
                    if (is_array($b) && isset($b['id'])) {
                        $this->layoutBlockEnabled[(string) $b['id']] = (bool) ($b['enabled'] ?? true);
                    }
                }
            }

            return;
        }

        if ($this->enabled) {
            Gate::authorize('create', Musician::class);
        }
        $this->name = (string) $user->name;
    }

    public function toggleProfile(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $profiles = collect($user->music_profiles ?? []);
        $target = UserMusicProfile::Musician->value;

        if ($profiles->contains($target)) {
            $profiles = $profiles->reject(fn (string $value) => $value === $target)->values();
        } else {
            $profiles->push($target);
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();
        $this->enabled = $user->canActAsMusician();
        $this->dispatch('music-profiles-updated');
        session()->flash('success', __('ui.music.saved'));
    }

    public function save(): void
    {
        if (! $this->enabled) {
            $this->addError('name', __('ui.music.profile_enable_required'));

            return;
        }

        if ($this->record) {
            Gate::authorize('update', $this->record);
        } else {
            Gate::authorize('create', Musician::class);
        }

        $this->experienceStartMonth = $this->experienceStartMonth === '' || $this->experienceStartMonth === false
            ? null
            : ($this->experienceStartMonth !== null ? (int) $this->experienceStartMonth : null);
        $this->experienceStartYear = $this->experienceStartYear === '' || $this->experienceStartYear === false
            ? null
            : ($this->experienceStartYear !== null ? (int) $this->experienceStartYear : null);

        $slugRules = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('musicians', 'slug')->ignore($this->record?->id),
        ];
        if ($this->public_page_enabled) {
            $slugRules = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('musicians', 'slug')->ignore($this->record?->id),
            ];
        }

        $this->instrumentIds = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $this->instrumentIds)));
        $this->genreIds = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $this->genreIds)));
        $this->cityIds = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $this->cityIds)));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'slug' => $slugRules,
            'public_page_enabled' => ['boolean'],
            'instrumentIds' => ['required', 'array', 'min:1'],
            'instrumentIds.*' => ['integer', 'exists:instruments,id'],
            'genreIds' => ['required', 'array', 'min:1'],
            'genreIds.*' => ['integer', 'exists:genres,id'],
            'cityIds' => ['nullable', 'array'],
            'cityIds.*' => ['integer', 'exists:cities,id'],
            'experienceStartMonth' => ['nullable', 'integer', 'between:1,12', 'required_with:experienceStartYear'],
            'experienceStartYear' => [
                'nullable',
                'integer',
                'min:'.(now()->year - 80),
                'max:'.now()->year,
                'required_with:experienceStartMonth',
            ],
        ], [
            'instrumentIds.required' => __('ui.music.validation.instruments_required'),
            'instrumentIds.min' => __('ui.music.validation.instruments_required'),
            'genreIds.required' => __('ui.music.validation.genres_required'),
            'genreIds.min' => __('ui.music.validation.genres_required'),
            'slug.required' => __('ui.music.validation.slug_required_public'),
            'slug.regex' => __('ui.music.validation.slug_format'),
        ]);

        $experienceStartedOn = null;
        if (($validated['experienceStartMonth'] ?? null) !== null && ($validated['experienceStartYear'] ?? null) !== null) {
            $start = CarbonImmutable::parse(sprintf(
                '%04d-%02d-01',
                (int) $validated['experienceStartYear'],
                (int) $validated['experienceStartMonth'],
            ))->startOfMonth();
            $latestAllowed = now()->startOfMonth();
            if ($start->greaterThan($latestAllowed)) {
                throw ValidationException::withMessages([
                    'experienceStartMonth' => [__('ui.music.validation.experience_started_future')],
                ]);
            }
            $oldestAllowed = now()->subYears(80)->startOfMonth();
            if ($start->lessThan($oldestAllowed)) {
                throw ValidationException::withMessages([
                    'experienceStartYear' => [__('ui.music.validation.experience_started_too_old', [
                        'year' => $oldestAllowed->year,
                    ])],
                ]);
            }
            $experienceStartedOn = $start->toDateString();
        }

        $layoutDraft = PublicProfileBlocks::wrapVersion1($this->buildLayoutBlocks());
        /** @var User $user */
        $user = Auth::user();

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => $validated['slug'] ?: null,
            'public_page_enabled' => $validated['public_page_enabled'],
            'is_session' => $user->canActAsSessionMusician(),
            'experience_started_on' => $experienceStartedOn,
            'layout_draft' => $layoutDraft,
        ];

        if ($this->record === null) {
            $payload['user_id'] = Auth::id();
            $this->record = Musician::create($payload);
        } else {
            $this->record->update($payload);
        }

        $this->record->instruments()->sync($validated['instrumentIds']);
        $this->record->genres()->sync($validated['genreIds']);
        $this->record->cities()->sync($validated['cityIds'] ?? []);

        session()->flash('success', __('ui.music.saved'));
    }

    public function addInstrument(): void
    {
        if ($this->selectedInstrumentId === null) {
            return;
        }

        $instrumentId = (int) $this->selectedInstrumentId;
        $exists = Instrument::query()
            ->whereKey($instrumentId)
            ->where('active', true)
            ->exists();

        if (! $exists) {
            return;
        }

        if (! in_array($instrumentId, $this->instrumentIds, true)) {
            $this->instrumentIds[] = $instrumentId;
            sort($this->instrumentIds);
        }

        $this->selectedInstrumentId = null;
    }

    public function addGenre(): void
    {
        if ($this->selectedGenreId === null) {
            return;
        }

        $genreId = (int) $this->selectedGenreId;
        $exists = Genre::query()
            ->whereKey($genreId)
            ->where('active', true)
            ->exists();

        if (! $exists) {
            return;
        }

        if (! in_array($genreId, $this->genreIds, true)) {
            $this->genreIds[] = $genreId;
            sort($this->genreIds);
        }

        $this->selectedGenreId = null;
    }

    public function removeGenre(int $genreId): void
    {
        $this->genreIds = array_values(array_filter(
            $this->genreIds,
            static fn (int $id): bool => $id !== $genreId
        ));
    }

    public function addCity(): void
    {
        if ($this->selectedCityId === null) {
            return;
        }

        $cityId = (int) $this->selectedCityId;
        $exists = City::query()
            ->whereKey($cityId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            return;
        }

        if (! in_array($cityId, $this->cityIds, true)) {
            $this->cityIds[] = $cityId;
            sort($this->cityIds);
        }

        $this->selectedCityId = null;
    }

    public function removeCity(int $cityId): void
    {
        $this->cityIds = array_values(array_filter(
            $this->cityIds,
            static fn (int $id): bool => $id !== $cityId
        ));
    }

    public function removeInstrument(int $instrumentId): void
    {
        $this->instrumentIds = array_values(array_filter(
            $this->instrumentIds,
            static fn (int $id): bool => $id !== $instrumentId
        ));
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

    public function acceptLineupInvite(int $peformerId): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;
        try {
            app(PerformerMembershipService::class)->accept(
                Peformer::findOrFail($peformerId),
                $this->record,
                Auth::user(),
            );
        } catch (ValidationException $e) {
            $this->lineupError = $e->errors()['lineup'][0] ?? null;

            return;
        }
        $this->lineupNotice = __('ui.music.lineup_accepted');
    }

    public function declineLineupInvite(int $peformerId): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;
        app(PerformerMembershipService::class)->decline(
            Peformer::findOrFail($peformerId),
            $this->record,
            Auth::user(),
        );
        $this->lineupNotice = __('ui.music.lineup_declined');
    }

    public function leaveLineup(int $peformerId): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;
        app(PerformerMembershipService::class)->leave(
            Peformer::findOrFail($peformerId),
            $this->record,
            Auth::user(),
        );
        $this->lineupNotice = __('ui.music.lineup_left');
    }

    public function requestLineupJoin(): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;

        $validated = $this->validate([
            'lineupRequestPerformerId' => ['required', 'integer', 'exists:peformers,id'],
        ], [], [
            'lineupRequestPerformerId' => __('ui.music.lineup_request_label'),
        ]);

        try {
            app(PerformerMembershipService::class)->requestJoin(
                Peformer::findOrFail((int) $validated['lineupRequestPerformerId']),
                $this->record,
                Auth::user(),
            );
        } catch (ValidationException $e) {
            $this->lineupError = $e->errors()['lineup'][0] ?? null;

            return;
        }

        $this->lineupRequestPerformerId = null;
        $this->lineupNotice = __('ui.music.lineup_request_sent');
    }

    public function cancelLineupRequest(int $peformerId): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;

        app(PerformerMembershipService::class)->cancelOwnRequest(
            Peformer::findOrFail($peformerId),
            $this->record,
            Auth::user(),
        );

        $this->lineupNotice = __('ui.music.lineup_request_cancelled');
    }

    public function setLineupShowOnProfile(bool $show, int $peformerId): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;
        try {
            app(PerformerMembershipService::class)->setShowOnMusicianProfile(
                Peformer::findOrFail($peformerId),
                $this->record,
                Auth::user(),
                $show,
            );
        } catch (ValidationException $e) {
            $this->lineupError = $e->errors()['lineup'][0] ?? null;

            return;
        }
        $this->lineupNotice = __('ui.music.lineup_visibility_updated');
    }

    private function requireMusician(): void
    {
        $this->record ??= Auth::user()->musician;
        if ($this->record === null) {
            abort(403);
        }
    }

    /**
     * @return list<array{id: string, enabled: bool, order: int}>
     */
    private function buildLayoutBlocks(): array
    {
        $catalog = PublicProfileBlocks::musicianCatalog();
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
        $instruments = Instrument::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $genres = Genre::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $countries = Country::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $cityPickerCities = collect();
        if ($this->cityPickerCountryId > 0) {
            $cityPickerCities = City::query()
                ->where('country_id', $this->cityPickerCountryId)
                ->where('is_active', true)
                ->orderByDesc('is_capital')
                ->orderByDesc('population')
                ->orderBy('name')
                ->limit(500)
                ->get();
        }

        $pickedCities = $this->cityIds !== []
            ? City::query()->whereIn('id', $this->cityIds)->orderBy('name')->get()
            : collect();

        $experienceMonthOptions = [];
        foreach (range(1, 12) as $m) {
            $experienceMonthOptions[] = [
                'value' => $m,
                'label' => CarbonImmutable::parse(sprintf('2000-%02d-01', $m))
                    ->locale(app()->getLocale())
                    ->translatedFormat('F'),
            ];
        }
        $y0 = (int) now()->year;
        $experienceYearOptions = range($y0, $y0 - 80);

        return view('livewire.music.musician-profile-page', [
            'instruments' => $instruments,
            'genres' => $genres,
            'countries' => $countries,
            'cityPickerCities' => $cityPickerCities,
            'pickedCities' => $pickedCities,
            'blockCatalog' => PublicProfileBlocks::musicianCatalog(),
            'pendingLineupInvites' => $this->record
                ? $this->record->peformers()->wherePivot('status', PerformerMembershipStatus::Pending->value)->orderBy('name')->get()
                : collect(),
            'lineupJoinRequests' => $this->record
                ? $this->record->peformers()
                    ->wherePivot('status', PerformerMembershipStatus::Pending->value)
                    ->wherePivot('invited_by_user_id', Auth::id())
                    ->orderBy('name')
                    ->get()
                : collect(),
            'lineupInviteInbox' => $this->record
                ? $this->record->peformers()
                    ->wherePivot('status', PerformerMembershipStatus::Pending->value)
                    ->wherePivot('invited_by_user_id', '!=', Auth::id())
                    ->orderBy('name')
                    ->get()
                : collect(),
            'acceptedLineup' => $this->record
                ? $this->record->peformers()->wherePivot('status', PerformerMembershipStatus::Accepted->value)->orderBy('name')->get()
                : collect(),
            'lineupRequestOptions' => $this->record
                ? Peformer::query()
                    ->where('owner_user_id', '!=', Auth::id())
                    ->whereNotIn(
                        'id',
                        $this->record->peformers()
                            ->wherePivotIn('status', [
                                PerformerMembershipStatus::Pending->value,
                                PerformerMembershipStatus::Accepted->value,
                            ])
                            ->pluck('peformers.id')
                    )
                    ->orderBy('name')
                    ->limit(400)
                    ->get(['id', 'name'])
                : collect(),
            'experienceMonthOptions' => $experienceMonthOptions,
            'experienceYearOptions' => $experienceYearOptions,
        ]);
    }
}
