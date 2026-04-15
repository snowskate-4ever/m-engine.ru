<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\MatchingInviteStatus;
use App\Enums\SearchGoal;
use App\Enums\SearchRequestStatus;
use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\OrganizerPerformerInvite;
use App\Models\OrganizerRehersalInvite;
use App\Models\OrganizerSchoolInvite;
use App\Models\OrganizerStudioInvite;
use App\Models\OrganizerVenueInvite;
use App\Models\Peformer;
use App\Models\PeformerMusician;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\SearchRequest;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SearchRequestService
{
    public function __construct(
        private readonly SearchGoalEligibilityService $searchGoalEligibilityService,
    ) {}

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function createUsingActorContext(
        User $actor,
        SearchGoal $goal,
        array $criteria = [],
        ?string $initiatorType = null,
        ?int $initiatorId = null,
        ?\DateTimeInterface $expiresAt = null
    ): SearchRequest {
        [$resolvedType, $resolvedId] = $this->resolveActorContext($actor, $initiatorType, $initiatorId);

        $initiator = match ($resolvedType) {
            User::class => $actor,
            Peformer::class => Peformer::query()->findOrFail($resolvedId),
            Musician::class => Musician::query()->findOrFail($resolvedId),
            ConcertVenue::class => ConcertVenue::query()->findOrFail($resolvedId),
            Studio::class => Studio::query()->findOrFail($resolvedId),
            Rehersal::class => Rehersal::query()->findOrFail($resolvedId),
            School::class => School::query()->findOrFail($resolvedId),
            RecordLabel::class => RecordLabel::query()->findOrFail($resolvedId),
            ProducerCenter::class => ProducerCenter::query()->findOrFail($resolvedId),
            Shop::class => Shop::query()->findOrFail($resolvedId),
            default => throw new \InvalidArgumentException('Unsupported initiator type.'),
        };

        $this->authorizeActor($actor, $initiator);
        $this->ensureGoalAllowedForInitiator($goal, $resolvedType);

        return $this->create($actor, $initiator, $goal, $criteria, $expiresAt);
    }

    public function create(
        User $actor,
        Model $initiator,
        SearchGoal $goal,
        array $criteria = [],
        ?\DateTimeInterface $expiresAt = null
    ): SearchRequest {
        return SearchRequest::query()->create([
            'search_goal' => $goal,
            'status' => SearchRequestStatus::Open,
            'initiator_type' => $initiator::class,
            'initiator_id' => $initiator->getKey(),
            'created_by_user_id' => $actor->id,
            'criteria' => $criteria,
            'submitted_at' => now(),
            'expires_at' => $expiresAt,
        ]);
    }

    public function markAwaitingApproval(SearchRequest $request): void
    {
        $this->transition($request, SearchRequestStatus::AwaitingApproval);
    }

    public function reopen(SearchRequest $request): void
    {
        $this->transition($request, SearchRequestStatus::Open);
    }

    public function markFulfilled(SearchRequest $request, array $context = []): void
    {
        $this->ensureTransitionAllowed($request, SearchRequestStatus::Fulfilled);

        $request->forceFill([
            'status' => SearchRequestStatus::Fulfilled,
            'fulfilled_at' => now(),
            'fulfillment_context' => $context,
            'closure_reason' => 'matched_and_linked',
        ])->save();
    }

    public function cancel(SearchRequest $request): void
    {
        $this->ensureTransitionAllowed($request, SearchRequestStatus::Cancelled);

        DB::transaction(function () use ($request): void {
            $request->forceFill([
                'status' => SearchRequestStatus::Cancelled,
                'closure_reason' => 'user_cancelled',
            ])->save();
            $this->revokePendingInvites($request);
        });
    }

    public function expire(SearchRequest $request): void
    {
        $this->ensureTransitionAllowed($request, SearchRequestStatus::Expired);

        DB::transaction(function () use ($request): void {
            $request->forceFill([
                'status' => SearchRequestStatus::Expired,
                'closure_reason' => 'expired',
            ])->save();
            $this->revokePendingInvites($request);
        });
    }

    public function revokePendingInvites(SearchRequest $request): void
    {
        OrganizerPerformerInvite::query()
            ->where('search_request_id', $request->id)
            ->where('status', MatchingInviteStatus::Pending->value)
            ->update([
                'status' => MatchingInviteStatus::Revoked->value,
                'responded_at' => now(),
            ]);

        OrganizerVenueInvite::query()
            ->where('search_request_id', $request->id)
            ->where('status', MatchingInviteStatus::Pending->value)
            ->update([
                'status' => MatchingInviteStatus::Revoked->value,
                'responded_at' => now(),
            ]);

        OrganizerStudioInvite::query()
            ->where('search_request_id', $request->id)
            ->where('status', MatchingInviteStatus::Pending->value)
            ->update([
                'status' => MatchingInviteStatus::Revoked->value,
                'responded_at' => now(),
            ]);

        OrganizerRehersalInvite::query()
            ->where('search_request_id', $request->id)
            ->where('status', MatchingInviteStatus::Pending->value)
            ->update([
                'status' => MatchingInviteStatus::Revoked->value,
                'responded_at' => now(),
            ]);

        OrganizerSchoolInvite::query()
            ->where('search_request_id', $request->id)
            ->where('status', MatchingInviteStatus::Pending->value)
            ->update([
                'status' => MatchingInviteStatus::Revoked->value,
                'responded_at' => now(),
            ]);

        PeformerMusician::query()
            ->where('search_request_id', $request->id)
            ->where('status', MatchingInviteStatus::Pending->value)
            ->update([
                'status' => MatchingInviteStatus::Revoked->value,
                'responded_at' => now(),
            ]);
    }

    private function transition(SearchRequest $request, SearchRequestStatus $status): void
    {
        $this->ensureTransitionAllowed($request, $status);
        $request->forceFill(['status' => $status])->save();
    }

    public function canTransition(SearchRequestStatus $from, SearchRequestStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return match ($from) {
            SearchRequestStatus::Draft => in_array($to, [SearchRequestStatus::Open, SearchRequestStatus::Cancelled], true),
            SearchRequestStatus::Open => in_array($to, [
                SearchRequestStatus::AwaitingApproval,
                SearchRequestStatus::Fulfilled,
                SearchRequestStatus::Cancelled,
                SearchRequestStatus::Expired,
                SearchRequestStatus::Failed,
            ], true),
            SearchRequestStatus::AwaitingApproval => in_array($to, [
                SearchRequestStatus::Open,
                SearchRequestStatus::Fulfilled,
                SearchRequestStatus::Cancelled,
                SearchRequestStatus::Expired,
                SearchRequestStatus::Failed,
            ], true),
            SearchRequestStatus::Cancelled,
            SearchRequestStatus::Expired => $to === SearchRequestStatus::Open,
            SearchRequestStatus::Failed => in_array($to, [SearchRequestStatus::Open, SearchRequestStatus::Cancelled], true),
            SearchRequestStatus::Fulfilled => false,
        };
    }

    private function ensureTransitionAllowed(SearchRequest $request, SearchRequestStatus $to): void
    {
        $from = $request->status instanceof SearchRequestStatus
            ? $request->status
            : SearchRequestStatus::from((string) $request->status);

        if (! $this->canTransition($from, $to)) {
            throw new \InvalidArgumentException(sprintf(
                'Transition from %s to %s is not allowed.',
                $from->value,
                $to->value
            ));
        }
    }

    /**
     * @return array{0: class-string, 1: int}
     */
    private function resolveActorContext(User $actor, ?string $type, ?int $id): array
    {
        $resolvedType = $type ?? $actor->active_music_actor_type ?? User::class;
        $resolvedId = $id ?? (int) ($actor->active_music_actor_id ?? $actor->id);

        return [$resolvedType, $resolvedId];
    }

    private function authorizeActor(User $actor, Model $initiator): void
    {
        match ($initiator::class) {
            User::class => $this->authorizeOrganizerUser($actor, $initiator),
            Peformer::class => Gate::forUser($actor)->authorize('canManageSearchRequests', $initiator),
            Musician::class => Gate::forUser($actor)->authorize('canManageSearchRequests', $initiator),
            ConcertVenue::class => Gate::forUser($actor)->authorize('manageMatching', $initiator),
            Studio::class => Gate::forUser($actor)->authorize('manageMatching', $initiator),
            Rehersal::class => Gate::forUser($actor)->authorize('manageMatching', $initiator),
            School::class => Gate::forUser($actor)->authorize('manageMatching', $initiator),
            RecordLabel::class => Gate::forUser($actor)->authorize('update', $initiator),
            ProducerCenter::class => Gate::forUser($actor)->authorize('update', $initiator),
            Shop::class => Gate::forUser($actor)->authorize('update', $initiator),
            default => throw new \InvalidArgumentException('Unsupported initiator model.'),
        };
    }

    private function authorizeOrganizerUser(User $actor, User $initiator): void
    {
        $canUseOwnProfile = $actor->canActAsEventOrganizer()
            || $actor->canActAsVenueRepresentative()
            || $actor->canActAsManager();

        if ((int) $actor->id !== (int) $initiator->id || ! $canUseOwnProfile) {
            throw new \Illuminate\Auth\Access\AuthorizationException;
        }
    }

    private function ensureGoalAllowedForInitiator(SearchGoal $goal, string $initiatorType): void
    {
        if (! $this->searchGoalEligibilityService->isAllowed($initiatorType, $goal)) {
            throw new \InvalidArgumentException('Search goal is not allowed for selected initiator.');
        }
    }
}
