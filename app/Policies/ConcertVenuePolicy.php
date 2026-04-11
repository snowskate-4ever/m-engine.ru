<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ConcertVenue;
use App\Models\MusicProfileMembership;
use App\Models\User;

class ConcertVenuePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ConcertVenue $concertVenue): bool
    {
        return $this->owns($user, $concertVenue) || $this->hasMembership($user, $concertVenue, ['pending', 'accepted']);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ConcertVenue $concertVenue): bool
    {
        return $this->owns($user, $concertVenue) || $this->hasMembership($user, $concertVenue, ['accepted']);
    }

    public function delete(User $user, ConcertVenue $concertVenue): bool
    {
        return $this->owns($user, $concertVenue);
    }

    public function manageMatching(User $user, ConcertVenue $concertVenue): bool
    {
        if ($this->owns($user, $concertVenue)) {
            return $user->canActAsVenueRepresentative();
        }

        return $user->canActAsVenueRepresentative()
            && $this->hasMembership($user, $concertVenue, ['accepted'], 'venue_representative');
    }

    private function owns(User $user, ConcertVenue $concertVenue): bool
    {
        return $concertVenue->owner_user_id !== null
            && (int) $concertVenue->owner_user_id === (int) $user->id;
    }

    /**
     * @param  list<string>  $statuses
     */
    private function hasMembership(User $user, ConcertVenue $concertVenue, array $statuses, ?string $role = null): bool
    {
        $query = MusicProfileMembership::query()
            ->where('member_user_id', $user->id)
            ->where('entity_type', ConcertVenue::class)
            ->where('entity_id', $concertVenue->id)
            ->whereIn('status', $statuses);

        if ($role !== null) {
            $query->where('role', $role);
        }

        return $query->exists();
    }
}
