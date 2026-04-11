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
        $this->searchGoal = SearchGoal::cases()[0]->value;

        $actors = $this->actorOptions();
        if ($actors->isNotEmpty()) {
            $this->initiatorRef = $actors->first()['type'].':'.$actors->first()['id'];
        }
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
            'statusOptions' => SearchRequestStatus::cases(),
            'actorOptions' => $this->actorOptions()->all(),
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
        return [
            'searchGoal' => ['required', Rule::in(array_map(static fn (SearchGoal $goal) => $goal->value, SearchGoal::cases()))],
            'initiatorRef' => ['required', 'string', 'max:255'],
            'criteriaJson' => ['nullable', 'string'],
            'expiresAt' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function parseInitiatorRef(): array
    {
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
        $raw = trim($this->criteriaJson);
        if ($raw === '') {
            return [];
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

        return $decoded;
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
