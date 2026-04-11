<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\MatchingInviteStatus;
use App\Enums\MusicEventAssemblyStatus;
use App\Models\Event;
use App\Models\OrganizerPerformerInvite;
use App\Models\OrganizerRehersalInvite;
use App\Models\OrganizerSchoolInvite;
use App\Models\OrganizerStudioInvite;
use App\Models\OrganizerVenueInvite;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Models\User;
use App\Notifications\Music\MatchingLifecycleNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EventAssemblyService
{
    public function __construct(
        private readonly SearchRequestService $searchRequestService,
    ) {}

    public function sendOrganizerVenueInvite(
        User $organizer,
        int $venueId,
        ?int $searchRequestId = null,
        ?int $eventId = null,
        ?\DateTimeInterface $startAt = null,
        ?\DateTimeInterface $endAt = null,
    ): OrganizerVenueInvite {
        if (! $organizer->canActAsEventOrganizer()) {
            throw new AuthorizationException('User cannot act as event organizer.');
        }

        $invite = OrganizerVenueInvite::query()->create([
            'organizer_user_id' => $organizer->id,
            'concert_venue_id' => $venueId,
            'event_id' => $eventId,
            'search_request_id' => $searchRequestId,
            'invited_by_user_id' => $organizer->id,
            'proposed_start_at' => $startAt,
            'proposed_end_at' => $endAt,
            'status' => MatchingInviteStatus::Pending,
        ]);

        if ($invite->search_request_id !== null && $invite->searchRequest !== null) {
            $this->searchRequestService->markAwaitingApproval($invite->searchRequest);
        }

        return $invite;
    }

    public function sendOrganizerStudioInvite(
        User $organizer,
        int $studioId,
        ?int $searchRequestId = null,
        ?int $eventId = null,
        ?\DateTimeInterface $startAt = null,
        ?\DateTimeInterface $endAt = null,
    ): OrganizerStudioInvite {
        if (! $organizer->canActAsEventOrganizer()) {
            throw new AuthorizationException('User cannot act as event organizer.');
        }

        $invite = OrganizerStudioInvite::query()->create([
            'organizer_user_id' => $organizer->id,
            'studio_id' => $studioId,
            'event_id' => $eventId,
            'search_request_id' => $searchRequestId,
            'invited_by_user_id' => $organizer->id,
            'proposed_start_at' => $startAt,
            'proposed_end_at' => $endAt,
            'status' => MatchingInviteStatus::Pending,
        ]);

        if ($invite->search_request_id !== null && $invite->searchRequest !== null) {
            $this->searchRequestService->markAwaitingApproval($invite->searchRequest);
        }

        return $invite;
    }

    public function sendOrganizerRehersalInvite(
        User $organizer,
        int $rehersalId,
        ?int $searchRequestId = null,
        ?int $eventId = null,
        ?\DateTimeInterface $startAt = null,
        ?\DateTimeInterface $endAt = null,
    ): OrganizerRehersalInvite {
        if (! $organizer->canActAsEventOrganizer()) {
            throw new AuthorizationException('User cannot act as event organizer.');
        }

        $invite = OrganizerRehersalInvite::query()->create([
            'organizer_user_id' => $organizer->id,
            'rehersal_id' => $rehersalId,
            'event_id' => $eventId,
            'search_request_id' => $searchRequestId,
            'invited_by_user_id' => $organizer->id,
            'proposed_start_at' => $startAt,
            'proposed_end_at' => $endAt,
            'status' => MatchingInviteStatus::Pending,
        ]);

        if ($invite->search_request_id !== null && $invite->searchRequest !== null) {
            $this->searchRequestService->markAwaitingApproval($invite->searchRequest);
        }

        return $invite;
    }

    public function sendOrganizerSchoolInvite(
        User $organizer,
        int $schoolId,
        ?int $searchRequestId = null,
        ?int $eventId = null,
        ?\DateTimeInterface $startAt = null,
        ?\DateTimeInterface $endAt = null,
    ): OrganizerSchoolInvite {
        if (! $organizer->canActAsEventOrganizer()) {
            throw new AuthorizationException('User cannot act as event organizer.');
        }

        $invite = OrganizerSchoolInvite::query()->create([
            'organizer_user_id' => $organizer->id,
            'school_id' => $schoolId,
            'event_id' => $eventId,
            'search_request_id' => $searchRequestId,
            'invited_by_user_id' => $organizer->id,
            'proposed_start_at' => $startAt,
            'proposed_end_at' => $endAt,
            'status' => MatchingInviteStatus::Pending,
        ]);

        if ($invite->search_request_id !== null && $invite->searchRequest !== null) {
            $this->searchRequestService->markAwaitingApproval($invite->searchRequest);
        }

        return $invite;
    }

    public function respondOrganizerVenueInvite(OrganizerVenueInvite $invite, User $actor, MatchingInviteStatus $status): Event
    {
        if (! in_array($status, [MatchingInviteStatus::Accepted, MatchingInviteStatus::Declined], true)) {
            throw ValidationException::withMessages([
                'status' => 'Unsupported transition for organizer-venue invite.',
            ]);
        }

        Gate::authorize('manageMatching', $invite->concertVenue);

        return DB::transaction(function () use ($invite, $status): Event {
            $invite->status = $status;
            $invite->responded_at = now();
            $invite->save();

            if ($status === MatchingInviteStatus::Declined) {
                if ($invite->searchRequest) {
                    $this->searchRequestService->reopen($invite->searchRequest);
                }
                $invite->organizer->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_venue_declined_body',
                    ['venue' => $invite->concertVenue->name],
                ));
                $invite->concertVenue->owner?->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_venue_declined_by_you_body',
                    ['venue' => $invite->concertVenue->name],
                ));

                return $invite->event ?? $this->resolveOrganizerEvent($invite);
            }

            $event = $invite->event ?? $this->resolveOrganizerEvent($invite);
            $event->forceFill([
                'music_organizer_user_id' => $invite->organizer_user_id,
                'concert_venue_id' => $invite->concert_venue_id,
                'matching_space_type' => \App\Models\ConcertVenue::class,
                'matching_space_id' => $invite->concert_venue_id,
                'matching_proposed_start_at' => $invite->proposed_start_at ?? $event->matching_proposed_start_at,
                'matching_proposed_end_at' => $invite->proposed_end_at ?? $event->matching_proposed_end_at,
                'start_at' => $invite->proposed_start_at ?? $event->start_at,
                'end_at' => $invite->proposed_end_at ?? $event->end_at,
            ]);
            $this->refreshAssemblyStatus($event);
            $event->save();

            $invite->event_id = $event->id;
            $invite->save();

            if ($invite->searchRequest) {
                $this->searchRequestService->markFulfilled($invite->searchRequest, [
                    'event_id' => $event->id,
                    'concert_venue_id' => $invite->concert_venue_id,
                    'invite_id' => $invite->id,
                ]);
            }

            $invite->organizer->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_venue_accepted_body',
                ['venue' => $invite->concertVenue->name, 'event' => $event->name],
            ));
            $invite->concertVenue->owner?->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_venue_confirmed_body',
                ['event' => $event->name],
            ));

            return $event;
        });
    }

    public function respondOrganizerStudioInvite(OrganizerStudioInvite $invite, User $actor, MatchingInviteStatus $status): Event
    {
        if (! in_array($status, [MatchingInviteStatus::Accepted, MatchingInviteStatus::Declined], true)) {
            throw ValidationException::withMessages([
                'status' => 'Unsupported transition for organizer-studio invite.',
            ]);
        }

        Gate::authorize('manageMatching', $invite->studio);

        return DB::transaction(function () use ($invite, $status): Event {
            $invite->status = $status;
            $invite->responded_at = now();
            $invite->save();

            if ($status === MatchingInviteStatus::Declined) {
                if ($invite->searchRequest) {
                    $this->searchRequestService->reopen($invite->searchRequest);
                }

                $invite->organizer->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_venue_declined_body',
                    ['venue' => $invite->studio->name],
                ));
                $invite->studio->owner?->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_venue_declined_by_you_body',
                    ['venue' => $invite->studio->name],
                ));

                return $invite->event ?? $this->resolveOrganizerEvent($invite);
            }

            $event = $invite->event ?? $this->resolveOrganizerEvent($invite);
            $event->forceFill([
                'music_organizer_user_id' => $invite->organizer_user_id,
                'matching_space_type' => Studio::class,
                'matching_space_id' => $invite->studio_id,
                'matching_proposed_start_at' => $invite->proposed_start_at ?? $event->matching_proposed_start_at,
                'matching_proposed_end_at' => $invite->proposed_end_at ?? $event->matching_proposed_end_at,
                'start_at' => $invite->proposed_start_at ?? $event->start_at,
                'end_at' => $invite->proposed_end_at ?? $event->end_at,
            ]);
            $this->refreshAssemblyStatus($event);
            $event->save();

            $invite->event_id = $event->id;
            $invite->save();

            if ($invite->searchRequest) {
                $this->searchRequestService->markFulfilled($invite->searchRequest, [
                    'event_id' => $event->id,
                    'studio_id' => $invite->studio_id,
                    'invite_id' => $invite->id,
                ]);
            }

            $invite->organizer->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_venue_accepted_body',
                ['venue' => $invite->studio->name, 'event' => $event->name],
            ));
            $invite->studio->owner?->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_venue_confirmed_body',
                ['event' => $event->name],
            ));

            return $event;
        });
    }

    public function respondOrganizerRehersalInvite(OrganizerRehersalInvite $invite, User $actor, MatchingInviteStatus $status): Event
    {
        if (! in_array($status, [MatchingInviteStatus::Accepted, MatchingInviteStatus::Declined], true)) {
            throw ValidationException::withMessages([
                'status' => 'Unsupported transition for organizer-rehersal invite.',
            ]);
        }

        Gate::authorize('manageMatching', $invite->rehersal);

        return DB::transaction(function () use ($invite, $status): Event {
            $invite->status = $status;
            $invite->responded_at = now();
            $invite->save();

            if ($status === MatchingInviteStatus::Declined) {
                if ($invite->searchRequest) {
                    $this->searchRequestService->reopen($invite->searchRequest);
                }

                $invite->organizer->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_venue_declined_body',
                    ['venue' => $invite->rehersal->name],
                ));
                $invite->rehersal->owner?->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_venue_declined_by_you_body',
                    ['venue' => $invite->rehersal->name],
                ));

                return $invite->event ?? $this->resolveOrganizerEvent($invite);
            }

            $event = $invite->event ?? $this->resolveOrganizerEvent($invite);
            $event->forceFill([
                'music_organizer_user_id' => $invite->organizer_user_id,
                'matching_space_type' => Rehersal::class,
                'matching_space_id' => $invite->rehersal_id,
                'matching_proposed_start_at' => $invite->proposed_start_at ?? $event->matching_proposed_start_at,
                'matching_proposed_end_at' => $invite->proposed_end_at ?? $event->matching_proposed_end_at,
                'start_at' => $invite->proposed_start_at ?? $event->start_at,
                'end_at' => $invite->proposed_end_at ?? $event->end_at,
            ]);
            $this->refreshAssemblyStatus($event);
            $event->save();

            $invite->event_id = $event->id;
            $invite->save();

            if ($invite->searchRequest) {
                $this->searchRequestService->markFulfilled($invite->searchRequest, [
                    'event_id' => $event->id,
                    'rehersal_id' => $invite->rehersal_id,
                    'invite_id' => $invite->id,
                ]);
            }

            $invite->organizer->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_venue_accepted_body',
                ['venue' => $invite->rehersal->name, 'event' => $event->name],
            ));
            $invite->rehersal->owner?->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_venue_confirmed_body',
                ['event' => $event->name],
            ));

            return $event;
        });
    }

    public function respondOrganizerSchoolInvite(OrganizerSchoolInvite $invite, User $actor, MatchingInviteStatus $status): Event
    {
        if (! in_array($status, [MatchingInviteStatus::Accepted, MatchingInviteStatus::Declined], true)) {
            throw ValidationException::withMessages([
                'status' => 'Unsupported transition for organizer-school invite.',
            ]);
        }

        Gate::authorize('manageMatching', $invite->school);

        return DB::transaction(function () use ($invite, $status): Event {
            $invite->status = $status;
            $invite->responded_at = now();
            $invite->save();

            if ($status === MatchingInviteStatus::Declined) {
                if ($invite->searchRequest) {
                    $this->searchRequestService->reopen($invite->searchRequest);
                }

                $invite->organizer->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_venue_declined_body',
                    ['venue' => $invite->school->name],
                ));
                $invite->school->owner?->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_venue_declined_by_you_body',
                    ['venue' => $invite->school->name],
                ));

                return $invite->event ?? $this->resolveOrganizerEvent($invite);
            }

            $event = $invite->event ?? $this->resolveOrganizerEvent($invite);
            $event->forceFill([
                'music_organizer_user_id' => $invite->organizer_user_id,
                'matching_space_type' => School::class,
                'matching_space_id' => $invite->school_id,
                'matching_proposed_start_at' => $invite->proposed_start_at ?? $event->matching_proposed_start_at,
                'matching_proposed_end_at' => $invite->proposed_end_at ?? $event->matching_proposed_end_at,
                'start_at' => $invite->proposed_start_at ?? $event->start_at,
                'end_at' => $invite->proposed_end_at ?? $event->end_at,
            ]);
            $this->refreshAssemblyStatus($event);
            $event->save();

            $invite->event_id = $event->id;
            $invite->save();

            if ($invite->searchRequest) {
                $this->searchRequestService->markFulfilled($invite->searchRequest, [
                    'event_id' => $event->id,
                    'school_id' => $invite->school_id,
                    'invite_id' => $invite->id,
                ]);
            }

            $invite->organizer->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_venue_accepted_body',
                ['venue' => $invite->school->name, 'event' => $event->name],
            ));
            $invite->school->owner?->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_venue_confirmed_body',
                ['event' => $event->name],
            ));

            return $event;
        });
    }

    public function sendOrganizerPerformerInvite(
        User $organizer,
        int $peformerId,
        ?int $searchRequestId = null,
        ?int $eventId = null,
    ): OrganizerPerformerInvite {
        if (! $organizer->canActAsEventOrganizer()) {
            throw new AuthorizationException('User cannot act as event organizer.');
        }

        $invite = OrganizerPerformerInvite::query()->create([
            'organizer_user_id' => $organizer->id,
            'peformer_id' => $peformerId,
            'event_id' => $eventId,
            'search_request_id' => $searchRequestId,
            'invited_by_user_id' => $organizer->id,
            'status' => MatchingInviteStatus::Pending,
        ]);

        if ($invite->search_request_id !== null && $invite->searchRequest !== null) {
            $this->searchRequestService->markAwaitingApproval($invite->searchRequest);
        }

        return $invite;
    }

    public function respondOrganizerPerformerInvite(OrganizerPerformerInvite $invite, User $actor, MatchingInviteStatus $status): Event
    {
        if (! in_array($status, [MatchingInviteStatus::Accepted, MatchingInviteStatus::Declined], true)) {
            throw ValidationException::withMessages([
                'status' => 'Unsupported transition for organizer-performer invite.',
            ]);
        }

        Gate::authorize('manageOrganizerMatching', $invite->peformer);

        return DB::transaction(function () use ($invite, $status): Event {
            $invite->status = $status;
            $invite->responded_at = now();
            $invite->save();

            if ($status === MatchingInviteStatus::Declined) {
                if ($invite->searchRequest) {
                    $this->searchRequestService->reopen($invite->searchRequest);
                }
                $invite->organizer->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_performer_declined_body',
                    ['performer' => $invite->peformer->name],
                ));
                $invite->peformer->owner?->notify(new MatchingLifecycleNotification(
                    'ui.notifications.music_matching_invite_declined_title',
                    'ui.notifications.music_matching_performer_declined_by_you_body',
                    ['performer' => $invite->peformer->name],
                ));

                return $invite->event ?? $this->resolveOrganizerEvent($invite);
            }

            $event = $invite->event ?? $this->resolveOrganizerEvent($invite);
            $event->music_organizer_user_id = $invite->organizer_user_id;
            $event->save();

            $event->peformers()->syncWithoutDetaching([
                $invite->peformer_id => ['added_via_search_request_id' => $invite->search_request_id],
            ]);
            $this->refreshAssemblyStatus($event);
            $event->save();

            $invite->event_id = $event->id;
            $invite->save();

            if ($invite->searchRequest) {
                $this->searchRequestService->markFulfilled($invite->searchRequest, [
                    'event_id' => $event->id,
                    'peformer_id' => $invite->peformer_id,
                    'invite_id' => $invite->id,
                ]);
            }

            $invite->organizer->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_performer_accepted_body',
                ['performer' => $invite->peformer->name, 'event' => $event->name],
            ));
            $invite->peformer->owner?->notify(new MatchingLifecycleNotification(
                'ui.notifications.music_matching_event_updated_title',
                'ui.notifications.music_matching_performer_confirmed_body',
                ['event' => $event->name],
            ));

            return $event;
        });
    }

    private function resolveOrganizerEvent(
        OrganizerPerformerInvite|OrganizerVenueInvite|OrganizerStudioInvite|OrganizerRehersalInvite|OrganizerSchoolInvite $invite
    ): Event
    {
        if ($invite->event_id !== null) {
            $event = Event::query()->find($invite->event_id);
            if ($event !== null) {
                return $event;
            }
        }

        $event = Event::query()
            ->where('music_organizer_user_id', $invite->organizer_user_id)
            ->where('assembly_status', MusicEventAssemblyStatus::Incomplete->value)
            ->orderByDesc('id')
            ->first();

        if ($event !== null) {
            return $event;
        }

        return Event::query()->create([
            'name' => 'music-event-'.$invite->organizer_user_id.'-'.now()->format('YmdHisv'),
            'description' => 'Music matching event draft',
            'active' => true,
            'status' => 'pending',
            'music_organizer_user_id' => $invite->organizer_user_id,
            'assembly_status' => MusicEventAssemblyStatus::Incomplete,
        ]);
    }

    private function refreshAssemblyStatus(Event $event): void
    {
        $hasVenue = $event->concert_venue_id !== null;
        $hasSpace = $event->matching_space_type !== null && $event->matching_space_id !== null;
        $hasTime = $event->start_at !== null && $event->end_at !== null;
        $hasPeformers = $event->peformers()->exists();

        $event->assembly_status = ($hasVenue || $hasSpace) && $hasTime && $hasPeformers
            ? MusicEventAssemblyStatus::Ready
            : MusicEventAssemblyStatus::Incomplete;
    }
}
