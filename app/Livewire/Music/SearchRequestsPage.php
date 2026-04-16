<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\SearchGoal;
use App\Enums\SearchRequestStatus;
use App\Enums\UserMusicProfile;
use App\Models\City;
use App\Models\ConcertVenue;
use App\Models\Country;
use App\Models\Genre;
use App\Models\Instrument;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\SearchRequest;
use App\Models\Studio;
use App\Models\User;
use App\Support\Music\ActorTargetMatrix;
use App\Services\Music\MusicActorContextService;
use App\Services\Music\SearchGoalEligibilityService;
use App\Services\Music\SearchRequestService;
use App\Services\Kanban\KanbanAutomationPresetService;
use App\Enums\AutomationPresetType;
use App\Support\Music\MusicProfileCriteria;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchRequestsPage extends Component
{
    public string $searchGoal = '';

    public string $initiatorRef = '';

    /** @var array<string, mixed> */
    public array $criteriaValues = [];

    public string $criteriaJson = '{}';

    public ?int $criteriaPickerInstrumentId = null;

    public ?int $criteriaPickerGenreId = null;

    public ?int $criteriaPickerCityId = null;

    public int $criteriaCityPickerCountryId = 0;

    public string $targetKind = '';

    public ?int $cityId = null;

    public bool $myCityOnly = false;

    public string $description = '';

    public string $adStatus = 'active';

    #[Url(history: true)]
    public string $statusFilter = 'all';

    #[Url(history: true)]
    public string $goalFilter = 'all';

    #[Url(history: true)]
    public string $initiatorFilter = 'all';

    public bool $showCreateModal = false;

    public function mount(): void
    {
        $this->criteriaValues = [];
        $this->criteriaCityPickerCountryId = $this->defaultCountryIdForCityPicker();
        $this->adStatus = 'active';
        $this->targetKind = '';

        $initiators = $this->initiatorOptions();
        if ($initiators->isNotEmpty()) {
            $this->initiatorRef = (string) $initiators->first()['value'];
        }

        $this->syncSearchGoalWithInitiator();
    }

    #[On('search-requests-open-create')]
    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    /**
     * @throws ValidationException
     */
    public function createRequest(): void
    {
        $this->validate($this->creationRules());

        [$type, $id] = $this->parseInitiatorRef();
        $criteria = $this->parseCriteria();

        try {
            app(SearchRequestService::class)->createUsingActorContext(
                Auth::user(),
                SearchGoal::from($this->searchGoal),
                $criteria,
                $type,
                $id,
                null,
            );
            $created = SearchRequest::query()->latest('id')->firstOrFail();
            $created->forceFill([
                'target_kind' => $this->targetKind !== '' ? $this->targetKind : null,
                'city_id' => $this->cityId,
                'my_city_only' => $this->myCityOnly,
                'description' => $this->description !== '' ? $this->description : null,
                'ad_status' => $this->adStatus,
            ])->save();
            app(KanbanAutomationPresetService::class)->execute(
                AutomationPresetType::MyAdsBoard,
                $created,
                ['user_id' => (int) Auth::id()]
            );
        } catch (AuthorizationException) {
            $this->addError('initiatorRef', __('ui.music.search_requests_initiator_forbidden'));

            return;
        } catch (\InvalidArgumentException) {
            $this->addError('searchGoal', __('ui.music.search_requests_goal_not_allowed'));

            return;
        } catch (\Throwable) {
            $this->addError('searchGoal', __('ui.saved_error'));

            return;
        }

        $this->criteriaJson = '{}';
        $this->description = '';
        $this->cityId = null;
        $this->myCityOnly = false;
        $this->adStatus = 'active';
        $this->targetKind = '';
        $this->showCreateModal = false;
        session()->flash('success', __('ui.music.search_requests_created'));
    }

    public function cancelRequest(int $requestId): void
    {
        $request = $this->ownedRequestOrFail($requestId);

        try {
            app(SearchRequestService::class)->cancel($request);
        } catch (\InvalidArgumentException) {
            $this->addError('statusFilter', __('ui.music.search_requests_transition_not_allowed'));

            return;
        }

        session()->flash('success', __('ui.music.search_requests_cancelled'));
    }

    public function reopenRequest(int $requestId): void
    {
        $request = $this->ownedRequestOrFail($requestId);

        try {
            app(SearchRequestService::class)->reopen($request);
        } catch (\InvalidArgumentException) {
            $this->addError('statusFilter', __('ui.music.search_requests_transition_not_allowed'));

            return;
        }

        session()->flash('success', __('ui.music.search_requests_reopened'));
    }

    public function render(): View
    {
        $cityIds = $this->criteriaCityIdsForView();

        return view('livewire.music.search-requests-page', [
            'searchGoalOptions' => SearchGoal::cases(),
            'createGoalOptions' => $this->availableSearchGoalsForInitiator(),
            'statusOptions' => SearchRequestStatus::cases(),
            'initiatorOptions' => $this->initiatorOptions()->all(),
            'entityOptions' => $this->entityOptions()->all(),
            'criteriaFieldOptions' => $this->criteriaFieldOptions(),
            'targetKindOptions' => $this->targetKindOptions(),
            'cityOptions' => City::query()->where('is_active', true)->orderByDesc('population')->orderBy('name')->limit(300)->get(['id', 'name']),
            'criteriaInstruments' => $this->criteriaNeedsCatalogInstruments()
                ? Instrument::query()->where('active', true)->orderBy('sort_order')->orderBy('name')->get()
                : collect(),
            'criteriaGenres' => $this->criteriaNeedsCatalogGenres()
                ? Genre::query()->where('active', true)->orderBy('sort_order')->orderBy('name')->get()
                : collect(),
            'criteriaCountries' => $this->criteriaNeedsCityPicker()
                ? Country::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
                : collect(),
            'criteriaCityPickerCities' => $this->criteriaCityPickerCities(),
            'criteriaPickedCities' => $cityIds !== []
                ? City::query()->whereIn('id', $cityIds)->orderBy('name')->get()
                : collect(),
            'requests' => $this->requests(),
        ]);
    }

    /**
     * @return Collection<int, array{type: string, id: int, label: string}>
     */
    private function actorOptions(): Collection
    {
        return collect(app(MusicActorContextService::class)->availableActors(Auth::user()));
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function profileOptions(): Collection
    {
        /** @var User $user */
        $user = Auth::user();
        $profiles = [];

        if ($user->canActAsEventOrganizer()) {
            $profiles[] = [
                'value' => 'profile:event_organizer',
                'label' => __('ui.music.profile_tab_organizer'),
            ];
        }

        if ($user->canActAsVenueRepresentative()) {
            $profiles[] = [
                'value' => 'profile:venue_representative',
                'label' => __('ui.music.profile_tab_venue_representative'),
            ];
        }

        if ($user->canActAsManager()) {
            $profiles[] = [
                'value' => 'profile:manager',
                'label' => __('ui.music.profile_tab_manager'),
            ];
        }

        return collect($profiles)->values();
    }

    /**
     * @return Collection<int, array{value: string, type: string, id: int, label: string}>
     */
    private function entityOptions(): Collection
    {
        return $this->actorOptions()
            ->filter(fn (array $actor): bool => $actor['type'] !== User::class)
            ->map(fn (array $actor): array => [
                'value' => $actor['type'].':'.$actor['id'],
                'type' => $actor['type'],
                'id' => $actor['id'],
                'label' => $this->extractEntityLabel((string) $actor['label']),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{value: string, label: string, group: string}>
     */
    private function initiatorOptions(): Collection
    {
        $profiles = $this->profileOptions()->map(
            fn (array $item): array => [
                'value' => $item['value'],
                'label' => sprintf('%s (%s)', $item['label'], __('ui.profile')),
                'group' => 'profile',
            ]
        );

        $entities = $this->entityOptions()->map(
            fn (array $item): array => [
                'value' => $item['value'],
                'label' => sprintf('%s (%s)', $item['label'], $this->entityTypeLabel($item['type'])),
                'group' => 'entity',
            ]
        );

        return $profiles->concat($entities)->values();
    }

    private function entityTypeLabel(string $entityType): string
    {
        return match ($entityType) {
            Peformer::class => __('ui.music.search_initiator_performer'),
            Musician::class => __('ui.music.search_initiator_musician'),
            ConcertVenue::class => __('ui.music.search_initiator_concert_venue'),
            Studio::class => __('ui.music.search_initiator_studio'),
            Rehersal::class => __('ui.music.search_initiator_rehersal'),
            School::class => __('ui.music.search_initiator_school'),
            User::class => __('ui.music.search_initiator_user'),
            default => class_basename($entityType),
        };
    }

    private function extractEntityLabel(string $label): string
    {
        if (str_contains($label, ': ')) {
            [, $short] = explode(': ', $label, 2);

            return $short;
        }

        return $label;
    }

    public function updatedInitiatorRef(string $value): void
    {
        $valid = $this->initiatorOptions()
            ->contains(fn (array $option): bool => (string) $option['value'] === $value);

        if (! $valid) {
            $this->initiatorRef = (string) ($this->initiatorOptions()->first()['value'] ?? '');
        }

        $this->syncSearchGoalWithInitiator();
        $this->criteriaValues = [];
        $this->criteriaPickerInstrumentId = null;
        $this->criteriaPickerGenreId = null;
        $this->criteriaPickerCityId = null;
        $this->criteriaCityPickerCountryId = $this->defaultCountryIdForCityPicker();
        $this->targetKind = '';
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function targetKindOptions(): array
    {
        $initiatorKind = $this->currentInitiatorKind();
        $matrix = ActorTargetMatrix::matrix();
        $allowed = $matrix[$initiatorKind] ?? [];
        if ($allowed === ['*']) {
            $allowed = array_keys($matrix);
        }

        $labels = [
            'musician' => __('ui.music.search_initiator_musician'),
            'session' => __('ui.music.profile_tab_session_musician'),
            'teacher' => __('ui.music.search_initiator_teacher'),
            'organizer' => __('ui.music.profile_tab_organizer'),
            'agent' => __('ui.music.profile_tab_agent'),
            'performer' => __('ui.music.search_initiator_performer'),
            'studio' => __('ui.music.search_initiator_studio'),
            'rehearsal' => __('ui.music.search_initiator_rehersal'),
            'school' => __('ui.music.search_initiator_school'),
            'label' => __('ui.music.search_initiator_record_label'),
            'production' => __('ui.music.search_initiator_producer_center'),
            'venue' => __('ui.music.search_initiator_concert_venue'),
            'sound_engineer' => __('ui.music.profile_tab_sound_engineer'),
            'arranger' => __('ui.music.profile_tab_arranger'),
            'live_sound' => __('ui.music.profile_tab_live_sound'),
            'lighting_designer' => __('ui.music.profile_tab_lighting_designer'),
            'videographer' => __('ui.music.profile_tab_videographer'),
            'photographer' => __('ui.music.profile_tab_photographer'),
            'journalist' => __('ui.music.profile_tab_journalist'),
            'venue_manager' => __('ui.music.profile_tab_venue_manager'),
            'merchandiser' => __('ui.music.profile_tab_merchandiser'),
            'tour_manager' => __('ui.music.profile_tab_tour_manager'),
            'promoter' => __('ui.music.profile_tab_promoter'),
            'recording_engineer' => __('ui.music.profile_tab_recording_engineer'),
            'mastering_engineer' => __('ui.music.profile_tab_mastering_engineer'),
            'session_producer' => __('ui.music.profile_tab_session_producer'),
            'tech_rider' => __('ui.music.profile_tab_tech_rider'),
            'backline_tech' => __('ui.music.profile_tab_backline_tech'),
            'graphic_designer' => __('ui.music.profile_tab_graphic_designer'),
            'smm_manager' => __('ui.music.profile_tab_smm_manager'),
            'music_lawyer' => __('ui.music.profile_tab_music_lawyer'),
            'accountant' => __('ui.music.profile_tab_accountant'),
        ];

        return array_values(array_map(static fn (string $kind): array => [
            'value' => $kind,
            'label' => $labels[$kind] ?? ucfirst(str_replace('_', ' ', $kind)),
        ], $allowed));
    }

    public function addCriteriaInstrument(): void
    {
        if ($this->criteriaPickerInstrumentId === null) {
            return;
        }

        $instrumentId = (int) $this->criteriaPickerInstrumentId;
        if (! Instrument::query()->whereKey($instrumentId)->where('active', true)->exists()) {
            return;
        }

        $ids = $this->criteriaValues['instruments'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }
        if (! in_array($instrumentId, $ids, true)) {
            $ids[] = $instrumentId;
            sort($ids);
        }

        $this->criteriaValues['instruments'] = $ids;
        $this->criteriaPickerInstrumentId = null;
    }

    public function removeCriteriaInstrument(int $instrumentId): void
    {
        $this->criteriaValues['instruments'] ??= [];
        if (! is_array($this->criteriaValues['instruments'])) {
            return;
        }

        $this->criteriaValues['instruments'] = array_values(array_filter(
            $this->criteriaValues['instruments'],
            static fn (mixed $id): bool => (int) $id !== $instrumentId
        ));
    }

    public function addCriteriaGenre(): void
    {
        if ($this->criteriaPickerGenreId === null) {
            return;
        }

        $genreId = (int) $this->criteriaPickerGenreId;
        if (! Genre::query()->whereKey($genreId)->where('active', true)->exists()) {
            return;
        }

        $ids = $this->criteriaValues['genres'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }
        if (! in_array($genreId, $ids, true)) {
            $ids[] = $genreId;
            sort($ids);
        }

        $this->criteriaValues['genres'] = $ids;
        $this->criteriaPickerGenreId = null;
    }

    public function removeCriteriaGenre(int $genreId): void
    {
        $this->criteriaValues['genres'] ??= [];
        if (! is_array($this->criteriaValues['genres'])) {
            return;
        }

        $this->criteriaValues['genres'] = array_values(array_filter(
            $this->criteriaValues['genres'],
            static fn (mixed $id): bool => (int) $id !== $genreId
        ));
    }

    public function addCriteriaCity(): void
    {
        if ($this->criteriaPickerCityId === null) {
            return;
        }

        $cityId = (int) $this->criteriaPickerCityId;
        if (! City::query()->whereKey($cityId)->where('is_active', true)->exists()) {
            return;
        }

        $ids = $this->criteriaValues['cities'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }
        if (! in_array($cityId, $ids, true)) {
            $ids[] = $cityId;
            sort($ids);
        }

        $this->criteriaValues['cities'] = $ids;
        $this->criteriaPickerCityId = null;
    }

    public function removeCriteriaCity(int $cityId): void
    {
        $this->criteriaValues['cities'] ??= [];
        if (! is_array($this->criteriaValues['cities'])) {
            return;
        }

        $this->criteriaValues['cities'] = array_values(array_filter(
            $this->criteriaValues['cities'],
            static fn (mixed $id): bool => (int) $id !== $cityId
        ));
    }

    /**
     * @return list<SearchGoal>
     */
    private function availableSearchGoalsForInitiator(): array
    {
        $initiatorType = $this->selectedInitiatorType();
        if ($initiatorType === null) {
            return [];
        }

        return app(SearchGoalEligibilityService::class)
            ->allowedGoalsForInitiator($initiatorType, $this->selectedProfileKey());
    }

    private function syncSearchGoalWithInitiator(): void
    {
        $available = $this->availableSearchGoalsForInitiator();
        if ($available === []) {
            $this->searchGoal = '';

            return;
        }

        $isCurrentValid = in_array($this->searchGoal, array_map(static fn (SearchGoal $goal): string => $goal->value, $available), true);
        if (! $isCurrentValid) {
            $this->searchGoal = $available[0]->value;
        }
    }

    /**
     * @return list<array{key: string, label: string, type: string, options?: array<int, array{value: string, label: string}>, placeholder?: string}>
     */
    private function criteriaFieldOptions(): array
    {
        return $this->criteriaFieldDefinitions();
    }

    /**
     * Критерии для заявки «от лица профиля» совпадают с настраиваемыми полями профиля (MusicProfileCriteria).
     *
     * @return list<array{key: string, label: string, type: string, options?: array<int, array{value: string, label: string}>, placeholder?: string}>
     */
    private function criteriaForProfile(?string $profile): array
    {
        if ($profile === null) {
            return [];
        }

        $enum = UserMusicProfile::tryFrom($profile);
        if ($enum === null) {
            return [];
        }

        return $this->expandMusicProfileCriteriaRows(MusicProfileCriteria::for($enum));
    }

    /**
     * @param  list<array{key: string, label_key: string, type: string}>  $rows
     * @return list<array{key: string, label: string, type: string, options?: array<int, array{value: string, label: string}>, placeholder?: string}>
     */
    private function expandMusicProfileCriteriaRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'key' => $row['key'],
                'label' => __($row['label_key']),
                'type' => $row['type'],
            ];
        }

        return $out;
    }

    /**
     * @return list<array{key: string, label: string, type: string, options?: array<int, array{value: string, label: string}>, placeholder?: string}>
     */
    private function criteriaForEntityType(?string $type): array
    {
        return match ($type) {
            Peformer::class => [
                ['key' => 'genre', 'label' => __('ui.music.search_filters_genre'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_genre_placeholder')],
                ['key' => 'city', 'label' => __('ui.music.search_filters_city'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_city_placeholder')],
                ['key' => 'musician_level', 'label' => __('ui.music.search_filters_musician_level'), 'type' => 'select', 'options' => [
                    ['value' => 'beginner', 'label' => __('ui.music.search_filters_level_beginner')],
                    ['value' => 'intermediate', 'label' => __('ui.music.search_filters_level_intermediate')],
                    ['value' => 'pro', 'label' => __('ui.music.search_filters_level_pro')],
                ]],
            ],
            Musician::class => [
                ...$this->expandMusicProfileCriteriaRows(MusicProfileCriteria::for(UserMusicProfile::Musician)),
                ['key' => 'organizer_type', 'label' => __('ui.music.search_filters_organizer_type'), 'type' => 'select', 'options' => [
                    ['value' => 'club', 'label' => __('ui.music.search_filters_organizer_type_club')],
                    ['value' => 'festival', 'label' => __('ui.music.search_filters_organizer_type_festival')],
                    ['value' => 'private', 'label' => __('ui.music.search_filters_organizer_type_private')],
                ]],
            ],
            ConcertVenue::class, Studio::class, Rehersal::class, School::class => [
                ['key' => 'event_date', 'label' => __('ui.music.search_filters_event_date'), 'type' => 'date'],
                ['key' => 'city', 'label' => __('ui.music.search_filters_city'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_city_placeholder')],
                ['key' => 'budget_from', 'label' => __('ui.music.search_filters_budget_from'), 'type' => 'number'],
                ['key' => 'budget_to', 'label' => __('ui.music.search_filters_budget_to'), 'type' => 'number'],
            ],
            default => [
                ['key' => 'city', 'label' => __('ui.music.search_filters_city'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_city_placeholder')],
                ['key' => 'genre', 'label' => __('ui.music.search_filters_genre'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_genre_placeholder')],
            ],
        };
    }

    private function selectedProfileKey(): ?string
    {
        if (! str_starts_with($this->initiatorRef, 'profile:')) {
            return null;
        }

        [, $profile] = explode(':', $this->initiatorRef, 2);

        return $profile !== '' ? $profile : null;
    }

    private function selectedEntityType(): ?string
    {
        if (str_starts_with($this->initiatorRef, 'profile:')) {
            return User::class;
        }

        [$type] = explode(':', $this->initiatorRef, 2);

        return $type ?: null;
    }

    private function selectedInitiatorType(): ?string
    {
        if (str_starts_with($this->initiatorRef, 'profile:')) {
            return User::class;
        }

        [$type] = explode(':', $this->initiatorRef, 2);

        return $type !== '' ? $type : null;
    }

    private function currentInitiatorKind(): string
    {
        $profile = $this->selectedProfileKey();
        if ($profile !== null) {
            return match ($profile) {
                'event_organizer' => 'organizer',
                'manager' => 'agent',
                'venue_representative' => 'venue_manager',
                'session_musician' => 'session',
                default => 'musician',
            };
        }

        return match ($this->selectedEntityType()) {
            Peformer::class => 'performer',
            Musician::class => 'musician',
            ConcertVenue::class => 'venue',
            Studio::class => 'studio',
            Rehersal::class => 'rehearsal',
            School::class => 'school',
            default => 'musician',
        };
    }

    private function defaultCountryIdForCityPicker(): int
    {
        return (int) (
            Country::query()->where('code', 'RU')->value('id')
            ?? Country::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->value('id')
            ?? Country::query()->orderBy('sort_order')->orderBy('name')->value('id')
            ?? 0
        );
    }

    /**
     * @return list<int>
     */
    private function criteriaCityIdsForView(): array
    {
        $raw = $this->criteriaValues['cities'] ?? [];
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $raw)));
    }

    private function criteriaFieldDefinitions(): array
    {
        if ($this->selectedProfileKey() !== null) {
            return $this->criteriaForProfile($this->selectedProfileKey());
        }

        return $this->criteriaForEntityType($this->selectedEntityType());
    }

    private function criteriaNeedsCatalogInstruments(): bool
    {
        foreach ($this->criteriaFieldDefinitions() as $field) {
            if (($field['type'] ?? '') === 'catalog_multi' && ($field['key'] ?? '') === 'instruments') {
                return true;
            }
        }

        return false;
    }

    private function criteriaNeedsCatalogGenres(): bool
    {
        foreach ($this->criteriaFieldDefinitions() as $field) {
            if (($field['type'] ?? '') === 'catalog_multi' && ($field['key'] ?? '') === 'genres') {
                return true;
            }
        }

        return false;
    }

    private function criteriaNeedsCityPicker(): bool
    {
        foreach ($this->criteriaFieldDefinitions() as $field) {
            if (($field['type'] ?? '') === 'city_multi') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Illuminate\Support\Collection<int, City>
     */
    private function criteriaCityPickerCities(): \Illuminate\Support\Collection
    {
        if ($this->criteriaCityPickerCountryId <= 0) {
            return collect();
        }

        return City::query()
            ->where('country_id', $this->criteriaCityPickerCountryId)
            ->where('is_active', true)
            ->orderByDesc('is_capital')
            ->orderByDesc('population')
            ->orderBy('name')
            ->limit(500)
            ->get();
    }

    /**
     * @return Collection<int, SearchRequest>
     */
    private function requests(): Collection
    {
        $query = SearchRequest::query()
            ->with('initiator')
            ->where('created_by_user_id', Auth::id());

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->goalFilter !== 'all') {
            $query->where('search_goal', $this->goalFilter);
        }

        if ($this->initiatorFilter !== 'all') {
            [$type, $id] = explode(':', $this->initiatorFilter, 2);
            $query
                ->where('initiator_type', $type)
                ->where('initiator_id', (int) $id);
        }

        return $query
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    private function ownedRequestOrFail(int $requestId): SearchRequest
    {
        return SearchRequest::query()
            ->whereKey($requestId)
            ->where('created_by_user_id', Auth::id())
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function creationRules(): array
    {
        $allowedGoals = array_map(
            static fn (SearchGoal $goal): string => $goal->value,
            $this->availableSearchGoalsForInitiator()
        );

        return [
            'searchGoal' => ['required', Rule::in($allowedGoals)],
            'initiatorRef' => ['required', 'string', 'max:255'],
            'targetKind' => ['required', 'string', 'max:64'],
            'cityId' => ['nullable', 'integer', 'min:1'],
            'myCityOnly' => ['boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'adStatus' => ['required', Rule::in(['draft', 'active', 'closed'])],
            'criteriaValues' => ['array'],
            'criteriaJson' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function parseInitiatorRef(): array
    {
        if (str_starts_with($this->initiatorRef, 'profile:')) {
            $profile = $this->selectedProfileKey();
            $allowedProfiles = $this->profileOptions()->pluck('value')->all();
            if ($profile === null || ! in_array('profile:'.$profile, $allowedProfiles, true)) {
                throw ValidationException::withMessages([
                    'initiatorRef' => __('ui.music.search_requests_initiator_invalid'),
                ]);
            }

            return [User::class, (int) Auth::id()];
        }

        $parts = explode(':', $this->initiatorRef, 2);
        if (count($parts) !== 2 || ! is_numeric($parts[1])) {
            throw ValidationException::withMessages([
                'initiatorRef' => __('ui.music.search_requests_initiator_invalid'),
            ]);
        }

        return [(string) $parts[0], (int) $parts[1]];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseCriteria(): array
    {
        $criteria = collect($this->criteriaValues)
            ->filter(static function ($value): bool {
                if (is_array($value)) {
                    return $value !== [];
                }

                return ! in_array($value, [null, ''], true);
            })
            ->map(static function ($value) {
                if (is_array($value)) {
                    return array_values(array_map(static function (mixed $v): int|string|float {
                        if (is_numeric($v)) {
                            return str_contains((string) $v, '.') ? (float) $v : (int) $v;
                        }

                        return (string) $v;
                    }, $value));
                }

                if (is_numeric($value)) {
                    return str_contains((string) $value, '.') ? (float) $value : (int) $value;
                }

                return $value;
            })
            ->all();

        $raw = trim($this->criteriaJson);
        if ($raw === '') {
            return $criteria;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'criteriaJson' => __('ui.music.search_requests_criteria_invalid_json'),
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'criteriaJson' => __('ui.music.search_requests_criteria_must_be_object'),
            ]);
        }

        return array_merge($criteria, $decoded);
    }

    public function goalLabel(SearchGoal $goal): string
    {
        return __('ui.music.search_goal_'.$goal->value);
    }

    public function goalTargetLabel(SearchGoal $goal): string
    {
        return match ($goal) {
            SearchGoal::FindMusicianForPerformer => __('ui.music.search_initiator_musician'),
            SearchGoal::FindPerformerForMusician,
            SearchGoal::FindPerformerForOrganizer => __('ui.music.search_initiator_performer'),
            SearchGoal::FindOrganizerForPerformer,
            SearchGoal::FindOrganizerForVenue,
            SearchGoal::FindOrganizerForStudio,
            SearchGoal::FindOrganizerForRehearsal,
            SearchGoal::FindOrganizerForSchool => __('ui.music.search_initiator_user'),
            SearchGoal::FindVenueForOrganizerEvent => __('ui.music.search_initiator_concert_venue'),
            SearchGoal::FindStudioForOrganizerEvent => __('ui.music.search_initiator_studio'),
            SearchGoal::FindRehearsalForOrganizerEvent => __('ui.music.search_initiator_rehersal'),
            SearchGoal::FindSchoolForOrganizerEvent => __('ui.music.search_initiator_school'),
        };
    }

    public function statusLabel(SearchRequestStatus $status): string
    {
        return __('ui.music.search_request_status_'.$status->value);
    }

    public function initiatorLabel(SearchRequest $request): string
    {
        return match ($request->initiator_type) {
            User::class => __('ui.music.search_initiator_user').': '.($request->initiator?->name ?? '#'.$request->initiator_id),
            Peformer::class => __('ui.music.search_initiator_performer').': '.($request->initiator?->name ?? '#'.$request->initiator_id),
            Musician::class => __('ui.music.search_initiator_musician').': '.($request->initiator?->name ?? '#'.$request->initiator_id),
            ConcertVenue::class => __('ui.music.search_initiator_concert_venue').': '.($request->initiator?->name ?? '#'.$request->initiator_id),
            Studio::class => __('ui.music.search_initiator_studio').': '.($request->initiator?->name ?? '#'.$request->initiator_id),
            Rehersal::class => __('ui.music.search_initiator_rehersal').': '.($request->initiator?->name ?? '#'.$request->initiator_id),
            School::class => __('ui.music.search_initiator_school').': '.($request->initiator?->name ?? '#'.$request->initiator_id),
            default => class_basename((string) $request->initiator_type).': #'.$request->initiator_id,
        };
    }
}
