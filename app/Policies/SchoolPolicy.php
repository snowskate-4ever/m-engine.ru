<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MusicProfileMembership;
use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, School $school): bool
    {
        return $this->owns($user, $school) || $this->hasMembership($user, $school, ['pending', 'accepted']);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, School $school): bool
    {
        return $this->owns($user, $school) || $this->hasMembership($user, $school, ['accepted']);
    }

    public function delete(User $user, School $school): bool
    {
        return $this->owns($user, $school);
    }

    public function manageMatching(User $user, School $school): bool
    {
        if ($this->owns($user, $school)) {
            return $user->canActAsVenueRepresentative();
        }

        return $user->canActAsVenueRepresentative()
            && $this->hasMembership($user, $school, ['accepted'], 'venue_representative');
    }

    private function owns(User $user, School $school): bool
    {
        return $school->owner_user_id !== null
            && (int) $school->owner_user_id === (int) $user->id;
    }

    /**
     * @param  list<string>  $statuses
     */
    private function hasMembership(User $user, School $school, array $statuses, ?string $role = null): bool
    {
        $query = MusicProfileMembership::query()
            ->where('member_user_id', $user->id)
            ->where('entity_type', School::class)
            ->where('entity_id', $school->id)
            ->whereIn('status', $statuses);

        if ($role !== null) {
            $query->where('role', $role);
        }

        return $query->exists();
    }
}
