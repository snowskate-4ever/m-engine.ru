<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class MusicActorContextService
{
    /**
     * @return list<array{type: string, id: int, label: string}>
     */
    public function availableActors(User $user): array
    {
        $actors = [];

        if ($user->canActAsEventOrganizer()) {
            $actors[] = [
                'type' => User::class,
                'id' => $user->id,
                'label' => 'Organizer: '.$user->name,
            ];
        }

        foreach ($user->ownedPeformers()->get(['id', 'name']) as $peformer) {
            $actors[] = [
                'type' => Peformer::class,
                'id' => $peformer->id,
                'label' => 'Performer: '.$peformer->name,
            ];
        }

        if ($user->musician !== null) {
            $actors[] = [
                'type' => Musician::class,
                'id' => $user->musician->id,
                'label' => 'Musician: '.$user->musician->name,
            ];
        }

        foreach ($user->ownedConcertVenues()->get(['id', 'name']) as $venue) {
            $actors[] = [
                'type' => ConcertVenue::class,
                'id' => $venue->id,
                'label' => 'Venue: '.$venue->name,
            ];
        }

        foreach ($user->ownedStudios()->get(['id', 'name']) as $studio) {
            $actors[] = [
                'type' => Studio::class,
                'id' => $studio->id,
                'label' => 'Studio: '.$studio->name,
            ];
        }

        foreach ($user->ownedRehearsals()->get(['id', 'name']) as $rehersal) {
            $actors[] = [
                'type' => Rehersal::class,
                'id' => $rehersal->id,
                'label' => 'Rehearsal: '.$rehersal->name,
            ];
        }

        foreach ($user->ownedSchools()->get(['id', 'name']) as $school) {
            $actors[] = [
                'type' => School::class,
                'id' => $school->id,
                'label' => 'School: '.$school->name,
            ];
        }

        $delegated = $user->musicProfileMemberships()
            ->where('status', MusicMembershipStatus::Accepted->value)
            ->whereIn('role', [MusicMembershipRole::Manager->value, MusicMembershipRole::VenueRepresentative->value])
            ->get();

        foreach ($delegated as $membership) {
            $entity = $membership->entity;
            if (! $entity instanceof Model) {
                continue;
            }

            $label = match ($entity::class) {
                Peformer::class => 'Managed performer: '.$entity->name,
                Musician::class => 'Managed musician: '.$entity->name,
                ConcertVenue::class => 'Represented venue: '.$entity->name,
                Studio::class => 'Represented studio: '.$entity->name,
                Rehersal::class => 'Represented rehearsal: '.$entity->name,
                School::class => 'Represented school: '.$entity->name,
                default => null,
            };

            if ($label === null) {
                continue;
            }

            $actors[] = [
                'type' => $entity::class,
                'id' => (int) $entity->getKey(),
                'label' => $label,
            ];
        }

        return collect($actors)
            ->unique(fn (array $actor) => $actor['type'].'#'.$actor['id'])
            ->values()
            ->all();
    }

    public function setActiveActor(User $user, string $type, int $id): void
    {
        $candidate = collect($this->availableActors($user))
            ->first(fn (array $actor) => $actor['type'] === $type && $actor['id'] === $id);

        if ($candidate === null) {
            throw ValidationException::withMessages([
                'actor' => 'Selected actor is not available for this user.',
            ]);
        }

        $user->setActiveMusicActor($type, $id);
    }

    /**
     * @return array{type: string, id: int}|null
     */
    public function currentActor(User $user): ?array
    {
        if ($user->active_music_actor_type === null || $user->active_music_actor_id === null) {
            return null;
        }

        foreach ($this->availableActors($user) as $actor) {
            if ($actor['type'] === $user->active_music_actor_type && $actor['id'] === (int) $user->active_music_actor_id) {
                return ['type' => $actor['type'], 'id' => $actor['id']];
            }
        }

        return null;
    }
}
