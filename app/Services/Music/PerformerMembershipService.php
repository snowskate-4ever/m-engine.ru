<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\PerformerMembershipStatus;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\User;
use App\Notifications\Music\PerformerLineupInvitationNotification;
use App\Services\Notifications\InAppNotificationSyncBroadcaster;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PerformerMembershipService
{
    public function __construct(
        private readonly InAppNotificationSyncBroadcaster $inAppNotificationSyncBroadcaster,
        private readonly LineupInvitationMessengerNotifier $lineupInvitationMessengerNotifier,
    ) {}

    public function invite(Peformer $peformer, Musician $musician, User $inviter): void
    {
        Gate::authorize('manageMembers', $peformer);
        if ($musician->user_id === null) {
            throw ValidationException::withMessages([
                'musician_id' => __('ui.music.lineup_invite_no_account'),
            ]);
        }

        $existing = $peformer->musicians()->whereKey($musician->id)->first();
        if ($existing !== null) {
            $status = $this->pivotMembershipStatus($existing->pivot->status);
            if ($status === PerformerMembershipStatus::Accepted) {
                throw ValidationException::withMessages([
                    'musician_id' => __('ui.music.lineup_already_member'),
                ]);
            }
            if ($status === PerformerMembershipStatus::Pending) {
                throw ValidationException::withMessages([
                    'musician_id' => __('ui.music.lineup_invite_pending'),
                ]);
            }
            $peformer->musicians()->updateExistingPivot($musician->id, [
                'status' => PerformerMembershipStatus::Pending->value,
                'invited_by_user_id' => $inviter->id,
                'responded_at' => null,
            ]);
        } else {
            $peformer->musicians()->attach($musician->id, [
                'status' => PerformerMembershipStatus::Pending->value,
                'show_on_musician_profile' => false,
                'invited_by_user_id' => $inviter->id,
            ]);
        }

        $invitee = $musician->user;
        if ($invitee === null) {
            return;
        }

        $invitee->notify(new PerformerLineupInvitationNotification(
            peformerId: $peformer->id,
            musicianId: $musician->id,
            peformerName: $peformer->name,
            inviterName: $inviter->name,
        ));
        $this->lineupInvitationMessengerNotifier->notifyInvited($invitee, $peformer->name, $inviter->name);

        Log::info('music.lineup_invite.sent', [
            'peformer_id' => $peformer->id,
            'invited_user_id' => $invitee->id,
        ]);
    }

    public function cancelPending(Peformer $peformer, Musician $musician, User $actor): void
    {
        Gate::authorize('manageMembers', $peformer);
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        if ($row === null) {
            return;
        }
        if ($this->pivotMembershipStatus($row->pivot->status) !== PerformerMembershipStatus::Pending) {
            return;
        }
        $peformer->musicians()->detach($musician->id);
        if ($musician->user) {
            $this->markInvitationNotificationsReadForPeformer($musician->user, $peformer->id);
        }
    }

    public function setAcceptedLeft(Peformer $peformer, Musician $musician, User $actor): void
    {
        Gate::authorize('manageMembers', $peformer);
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        if ($row === null) {
            return;
        }
        if ($this->pivotMembershipStatus($row->pivot->status) !== PerformerMembershipStatus::Accepted) {
            return;
        }
        $peformer->musicians()->updateExistingPivot($musician->id, [
            'status' => PerformerMembershipStatus::Left->value,
            'responded_at' => now(),
        ]);
    }

    public function accept(Peformer $peformer, Musician $musician, User $user): void
    {
        $this->assertMusicianOwner($musician, $user);
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        if ($row === null || $this->pivotMembershipStatus($row->pivot->status) !== PerformerMembershipStatus::Pending) {
            throw ValidationException::withMessages([
                'lineup' => __('ui.music.lineup_no_pending'),
            ]);
        }
        $peformer->musicians()->updateExistingPivot($musician->id, [
            'status' => PerformerMembershipStatus::Accepted->value,
            'responded_at' => now(),
        ]);
        $this->markInvitationNotificationsReadForPeformer($user, $peformer->id);
    }

    public function decline(Peformer $peformer, Musician $musician, User $user): void
    {
        $this->assertMusicianOwner($musician, $user);
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        if ($row === null || $this->pivotMembershipStatus($row->pivot->status) !== PerformerMembershipStatus::Pending) {
            return;
        }
        $peformer->musicians()->updateExistingPivot($musician->id, [
            'status' => PerformerMembershipStatus::Declined->value,
            'responded_at' => now(),
        ]);
        $this->markInvitationNotificationsReadForPeformer($user, $peformer->id);
    }

    public function leave(Peformer $peformer, Musician $musician, User $user): void
    {
        $this->assertMusicianOwner($musician, $user);
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        if ($row === null || $this->pivotMembershipStatus($row->pivot->status) !== PerformerMembershipStatus::Accepted) {
            return;
        }
        $peformer->musicians()->updateExistingPivot($musician->id, [
            'status' => PerformerMembershipStatus::Left->value,
            'responded_at' => now(),
        ]);
    }

    public function setShowOnMusicianProfile(Peformer $peformer, Musician $musician, User $user, bool $show): void
    {
        $this->assertMusicianOwner($musician, $user);
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        if ($row === null || $this->pivotMembershipStatus($row->pivot->status) !== PerformerMembershipStatus::Accepted) {
            throw ValidationException::withMessages([
                'lineup' => __('ui.music.lineup_not_accepted'),
            ]);
        }
        $peformer->musicians()->updateExistingPivot($musician->id, ['show_on_musician_profile' => $show]);
    }

    public function requestJoin(Peformer $peformer, Musician $musician, User $requester): void
    {
        $this->assertMusicianOwner($musician, $requester);

        if ((int) $peformer->owner_user_id === (int) $requester->id) {
            throw ValidationException::withMessages([
                'lineup' => __('ui.music.lineup_request_own_performer'),
            ]);
        }

        $existing = $peformer->musicians()->whereKey($musician->id)->first();
        if ($existing !== null) {
            $status = $this->pivotMembershipStatus($existing->pivot->status);

            if ($status === PerformerMembershipStatus::Accepted) {
                throw ValidationException::withMessages([
                    'lineup' => __('ui.music.lineup_already_member'),
                ]);
            }

            if ($status === PerformerMembershipStatus::Pending) {
                $invitedBy = (int) ($existing->pivot->invited_by_user_id ?? 0);
                $message = $invitedBy === (int) $requester->id
                    ? __('ui.music.lineup_request_pending')
                    : __('ui.music.lineup_invite_pending');

                throw ValidationException::withMessages([
                    'lineup' => $message,
                ]);
            }

            $peformer->musicians()->updateExistingPivot($musician->id, [
                'status' => PerformerMembershipStatus::Pending->value,
                'invited_by_user_id' => $requester->id,
                'show_on_musician_profile' => false,
                'responded_at' => null,
            ]);

            return;
        }

        $peformer->musicians()->attach($musician->id, [
            'status' => PerformerMembershipStatus::Pending->value,
            'show_on_musician_profile' => false,
            'invited_by_user_id' => $requester->id,
        ]);
    }

    public function cancelOwnRequest(Peformer $peformer, Musician $musician, User $requester): void
    {
        $this->assertMusicianOwner($musician, $requester);

        $row = $peformer->musicians()->whereKey($musician->id)->first();
        if ($row === null || $this->pivotMembershipStatus($row->pivot->status) !== PerformerMembershipStatus::Pending) {
            return;
        }

        if ((int) ($row->pivot->invited_by_user_id ?? 0) !== (int) $requester->id) {
            return;
        }

        $peformer->musicians()->detach($musician->id);
    }

    private function pivotMembershipStatus(mixed $raw): ?PerformerMembershipStatus
    {
        if ($raw instanceof PerformerMembershipStatus) {
            return $raw;
        }

        return PerformerMembershipStatus::tryFrom((string) $raw);
    }

    private function assertMusicianOwner(Musician $musician, User $user): void
    {
        if ((int) $musician->user_id !== (int) $user->id) {
            throw new AuthorizationException;
        }
    }

    private function markInvitationNotificationsReadForPeformer(User $user, int $peformerId): void
    {
        $user->unreadNotifications
            ->filter(fn ($n) => $n->type === PerformerLineupInvitationNotification::class)
            ->filter(fn ($n) => (int) data_get($n->data, 'peformer_id') === $peformerId)
            ->each->markAsRead();

        $this->inAppNotificationSyncBroadcaster->sync($user, true);
    }
}
