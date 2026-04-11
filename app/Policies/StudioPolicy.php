<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MusicProfileMembership;
use App\Models\Studio;
use App\Models\User;

class StudioPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Studio $studio): bool
    {
        return $this->owns($user, $studio) || $this->hasMembership($user, $studio, ['pending', 'accepted']);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Studio $studio): bool
    {
        return $this->owns($user, $studio) || $this->hasMembership($user, $studio, ['accepted']);
    }

    public function delete(User $user, Studio $studio): bool
    {
        return $this->owns($user, $studio);
    }

    public function manageMatching(User $user, Studio $studio): bool
    {
        if ($this->owns($user, $studio)) {
            return $user->canActAsVenueRepresentative();
        }

        return $user->canActAsVenueRepresentative()
            && $this->hasMembership($user, $studio, ['accepted'], 'venue_representative');
    }

    private function owns(User $user, Studio $studio): bool
    {
        return $studio->owner_user_id !== null
            && (int) $studio->owner_user_id === (int) $user->id;
    }

    /**
     * @param  list<string>  $statuses
     */
    private function hasMembership(User $user, Studio $studio, array $statuses, ?string $role = null): bool
    {
        $query = MusicProfileMembership::query()
            ->where('member_user_id', $user->id)
            ->where('entity_type', Studio::class)
            ->where('entity_id', $studio->id)
            ->whereIn('status', $statuses);

        if ($role !== null) {
            $query->where('role', $role);
        }

        return $query->exists();
    }
}
