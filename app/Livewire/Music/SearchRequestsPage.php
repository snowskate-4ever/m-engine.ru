<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\SearchGoal;
use App\Enums\SearchRequestStatus;
use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\SearchRequest;
use App\Models\Studio;
use App\Models\User;
use App\Services\Music\MusicActorContextService;
use App\Services\Music\SearchGoalEligibilityService;
use App\Services\Music\SearchRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchRequestsPage extends Component
{
    public string $searchGoal = '';

    public string $initiatorRef = '';

    /** @var array<string, mixed> */
    public array $criteriaValues = [];

    public string $criteriaJson = '{}';

    public string $expiresAt = '';

    #[Url(history: true)]
    public string $statusFilter = 'all';

    #[Url(history: true)]
    public string $goalFilter = 'all';

    #[Url(history: true)]
    public string $initiatorFilter = 'all';

    public function mount(): void
    {
        $this->criteriaValues = [];

        $initiators = $this->initiatorOptions();
        if ($initiators->isNotEmpty()) {
            $this->initiatorRef = (string) $initiators->first()['value'];
        }

        $this->syncSearchGoalWithInitiator();
    }

    /**
     * @throws ValidationException
     */
    public function createRequest(): void
    {
        $this->validate($this->creationRules());

        [$type, $id] = $this->parseInitiatorRef();
        $criteria = $this->parseCriteria();
        $expiresAt = $this->parseExpiresAt();

        try {
            app(SearchRequestService::class)->createUsingActorContext(
                Auth::user(),
                SearchGoal::from($this->searchGoal),
                $criteria,
                $type,
                $id,
                $expiresAt,
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
        $this->expiresAt = '';
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
        return view('livewire.music.search-requests-page', [
            'searchGoalOptions' => SearchGoal::cases(),
            'createGoalOptions' => $this->availableSearchGoalsForInitiator(),
            'statusOptions' => SearchRequestStatus::cases(),
            'initiatorOptions' => $this->initiatorOptions()->all(),
            'entityOptions' => $this->entityOptions()->all(),
            'criteriaFieldOptions' => $this->criteriaFieldOptions(),
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
        if ($this->selectedProfileKey() !== null) {
            return $this->criteriaForProfile($this->selectedProfileKey());
        }

        return $this->criteriaForEntityType($this->selectedEntityType());
    }

    /**
     * @return list<array{key: string, label: string, type: string, options?: array<int, array{value: string, label: string}>, placeholder?: string}>
     */
    private function criteriaForProfile(?string $profile): array
    {
        return match ($profile) {
            'manager' => [
                ['key' => 'city', 'label' => __('ui.music.search_filters_city'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_city_placeholder')],
                ['key' => 'genre', 'label' => __('ui.music.search_filters_genre'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_genre_placeholder')],
                ['key' => 'collaboration_mode', 'label' => __('ui.music.search_filters_collaboration_mode'), 'type' => 'select', 'options' => [
                    ['value' => 'session', 'label' => __('ui.music.search_filters_collaboration_session')],
                    ['value' => 'long_term', 'label' => __('ui.music.search_filters_collaboration_long_term')],
                ]],
                ['key' => 'budget_from', 'label' => __('ui.music.search_filters_budget_from'), 'type' => 'number'],
                ['key' => 'budget_to', 'label' => __('ui.music.search_filters_budget_to'), 'type' => 'number'],
            ],
            'venue_representative' => [
                ['key' => 'city', 'label' => __('ui.music.search_filters_city'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_city_placeholder')],
                ['key' => 'event_date', 'label' => __('ui.music.search_filters_event_date'), 'type' => 'date'],
                ['key' => 'capacity_from', 'label' => __('ui.music.search_filters_capacity_from'), 'type' => 'number'],
            ],
            default => [
                ['key' => 'city', 'label' => __('ui.music.search_filters_city'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_city_placeholder')],
                ['key' => 'genre', 'label' => __('ui.music.search_filters_genre'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_genre_placeholder')],
                ['key' => 'event_date', 'label' => __('ui.music.search_filters_event_date'), 'type' => 'date'],
                ['key' => 'budget_from', 'label' => __('ui.music.search_filters_budget_from'), 'type' => 'number'],
                ['key' => 'budget_to', 'label' => __('ui.music.search_filters_budget_to'), 'type' => 'number'],
            ],
        };
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
                ['key' => 'city', 'label' => __('ui.music.search_filters_city'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_city_placeholder')],
                ['key' => 'genre', 'label' => __('ui.music.search_filters_genre'), 'type' => 'text', 'placeholder' => __('ui.music.search_filters_genre_placeholder')],
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
            'criteriaValues' => ['array'],
            'criteriaJson' => ['nullable', 'string'],
            'expiresAt' => ['nullable', 'date'],
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
            ->filter(static fn ($value): bool => ! in_array($value, [null, ''], true))
            ->map(static function ($value) {
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

    private function parseExpiresAt(): ?CarbonImmutable
    {
        if ($this->expiresAt === '') {
            return null;
        }

        return CarbonImmutable::parse($this->expiresAt);
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
