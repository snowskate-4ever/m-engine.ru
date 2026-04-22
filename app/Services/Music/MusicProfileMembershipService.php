<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Models\ConcertVenue;
use App\Models\MusicProfileMembership;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\User;
use App\Notifications\Music\MatchingLifecycleNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class MusicProfileMembershipService
{
    public function __construct(
        private readonly EntityLinkedAccessService $entityLinkedAccess,
    ) {}

    public function invite(User $owner, Model $entity, User $member, MusicMembershipRole $role): MusicProfileMembership
    {
        $this->assertOwnerCanInvite($owner, $entity);

        if ($owner->id === $member->id) {
            throw ValidationException::withMessages([
                'member_user_id' => 'Owner cannot invite themselves.',
            ]);
        }

        $membership = MusicProfileMembership::query()
            ->where('member_user_id', $member->id)
            ->where('entity_type', $entity::class)
            ->where('entity_id', $entity->getKey())
            ->where('role', $role->value)
            ->first();

        if ($membership !== null && $membership->status === MusicMembershipStatus::Pending) {
            throw ValidationException::withMessages([
                'member_user_id' => 'Invitation is already pending.',
            ]);
        }

        if ($membership !== null) {
            $membership->forceFill([
                'status' => MusicMembershipStatus::Pending,
                'invited_by_user_id' => $owner->id,
                'responded_at' => null,
            ])->save();

            $this->notifyInvitation($member, $entity);

            return $membership;
        }
        $membership = MusicProfileMembership::query()->create([
            'member_user_id' => $member->id,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'role' => $role,
            'status' => MusicMembershipStatus::Pending,
            'invited_by_user_id' => $owner->id,
        ]);

        $this->notifyInvitation($member, $entity);

        return $membership;
    }

    public function respond(User $member, MusicProfileMembership $membership, MusicMembershipStatus $decision): MusicProfileMembership
    {
        if (! in_array($decision, [MusicMembershipStatus::Accepted, MusicMembershipStatus::Declined], true)) {
            throw ValidationException::withMessages([
                'status' => 'Unsupported membership status transition.',
            ]);
        }

        if ((int) $membership->member_user_id !== (int) $member->id) {
            throw new AuthorizationException;
        }

        $membership->status = $decision;
        $membership->responded_at = now();
        $membership->save();

        $entity = $membership->entity;
        if ($entity instanceof Model) {
            if ($decision === MusicMembershipStatus::Accepted) {
                $this->entityLinkedAccess->grantForMember($entity, $member);
            }
            if ($decision === MusicMembershipStatus::Declined) {
                $this->entityLinkedAccess->revokeForMember($entity, $member);
            }
        }

        $this->notifyDecision($membership, $decision);

        return $membership;
    }

    public function revoke(User $owner, MusicProfileMembership $membership): void
    {
        $entity = $membership->entity;
        if (! $entity instanceof Model) {
            throw ValidationException::withMessages([
                'membership' => 'Membership entity is invalid.',
            ]);
        }

        $this->assertOwnerCanInvite($owner, $entity);

        $membership->status = MusicMembershipStatus::Revoked;
        $membership->responded_at = now();
        $membership->save();
        if ($membership->member !== null) {
            $this->entityLinkedAccess->revokeForMember($entity, $membership->member);
        }

        $membership->member?->notify(new MatchingLifecycleNotification(
            'ui.notifications.music_matching_invite_declined_title',
            'ui.notifications.music_matching_membership_revoked_body',
            ['entity' => $this->entityLabel($entity)],
        ));
    }

    private function assertOwnerCanInvite(User $owner, Model $entity): void
    {
        $allowed = match ($entity::class) {
            ConcertVenue::class => (int) ($entity->owner_user_id ?? 0) === (int) $owner->id,
            Studio::class => (int) ($entity->owner_user_id ?? 0) === (int) $owner->id,
            Rehersal::class => (int) ($entity->owner_user_id ?? 0) === (int) $owner->id,
            School::class => (int) ($entity->owner_user_id ?? 0) === (int) $owner->id,
            RecordLabel::class => (int) ($entity->owner_user_id ?? 0) === (int) $owner->id,
            ProducerCenter::class => (int) ($entity->owner_user_id ?? 0) === (int) $owner->id,
            Shop::class => (int) ($entity->owner_user_id ?? 0) === (int) $owner->id,
            Peformer::class => (int) ($entity->owner_user_id ?? 0) === (int) $owner->id,
            Musician::class => (int) ($entity->user_id ?? 0) === (int) $owner->id,
            default => false,
        };

        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    private function notifyInvitation(User $member, Model $entity): void
    {
        $member->notify(new MatchingLifecycleNotification(
            'ui.notifications.music_matching_event_updated_title',
            'ui.notifications.music_matching_membership_invited_body',
            ['entity' => $this->entityLabel($entity)],
        ));
    }

    private function notifyDecision(MusicProfileMembership $membership, MusicMembershipStatus $decision): void
    {
        $owner = $membership->invitedBy;
        if ($owner === null) {
            return;
        }

        $entity = $membership->entity;
        if (! $entity instanceof Model) {
            return;
        }

        $bodyKey = $decision === MusicMembershipStatus::Accepted
            ? 'ui.notifications.music_matching_membership_accepted_body'
            : 'ui.notifications.music_matching_membership_declined_body';

        $owner->notify(new MatchingLifecycleNotification(
            'ui.notifications.music_matching_event_updated_title',
            $bodyKey,
            ['entity' => $this->entityLabel($entity)],
        ));
    }

    private function entityLabel(Model $entity): string
    {
        return (string) ($entity->name ?? ('#'.$entity->getKey()));
    }
}
