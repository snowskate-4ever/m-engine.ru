<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MusicProfileMembership;
use App\Models\Rehersal;
use App\Models\User;

class RehersalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rehersal $rehersal): bool
    {
        return $this->owns($user, $rehersal) || $this->hasMembership($user, $rehersal, ['pending', 'accepted']);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Rehersal $rehersal): bool
    {
        return $this->owns($user, $rehersal) || $this->hasMembership($user, $rehersal, ['accepted']);
    }

    public function delete(User $user, Rehersal $rehersal): bool
    {
        return $this->owns($user, $rehersal);
    }

    public function manageMatching(User $user, Rehersal $rehersal): bool
    {
        if ($this->owns($user, $rehersal)) {
            return $user->canActAsVenueRepresentative();
        }

        return $user->canActAsVenueRepresentative()
            && $this->hasMembership($user, $rehersal, ['accepted'], 'venue_representative');
    }

    private function owns(User $user, Rehersal $rehersal): bool
    {
        return $rehersal->owner_user_id !== null
            && (int) $rehersal->owner_user_id === (int) $user->id;
    }

    /**
     * @param  list<string>  $statuses
     */
    private function hasMembership(User $user, Rehersal $rehersal, array $statuses, ?string $role = null): bool
    {
        $query = MusicProfileMembership::query()
            ->where('member_user_id', $user->id)
            ->where('entity_type', Rehersal::class)
            ->where('entity_id', $rehersal->id)
            ->whereIn('status', $statuses);

        if ($role !== null) {
            $query->where('role', $role);
        }

        return $query->exists();
    }
}
