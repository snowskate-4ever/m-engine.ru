<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
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
                'label' => $this->buildActorLabel(User::class, $user->name),
            ];
        }

        foreach ($user->ownedPeformers()->get(['id', 'name']) as $peformer) {
            $actors[] = [
                'type' => Peformer::class,
                'id' => $peformer->id,
                'label' => $this->buildActorLabel(Peformer::class, $peformer->name),
            ];
        }

        if ($user->musician !== null) {
            $actors[] = [
                'type' => Musician::class,
                'id' => $user->musician->id,
                'label' => $this->buildActorLabel(Musician::class, $user->musician->name),
            ];
        }

        foreach ($user->ownedConcertVenues()->get(['id', 'name']) as $venue) {
            $actors[] = [
                'type' => ConcertVenue::class,
                'id' => $venue->id,
                'label' => $this->buildActorLabel(ConcertVenue::class, $venue->name),
            ];
        }

        foreach ($user->ownedStudios()->get(['id', 'name']) as $studio) {
            $actors[] = [
                'type' => Studio::class,
                'id' => $studio->id,
                'label' => $this->buildActorLabel(Studio::class, $studio->name),
            ];
        }

        foreach ($user->ownedRehearsals()->get(['id', 'name']) as $rehersal) {
            $actors[] = [
                'type' => Rehersal::class,
                'id' => $rehersal->id,
                'label' => $this->buildActorLabel(Rehersal::class, $rehersal->name),
            ];
        }

        foreach ($user->ownedSchools()->get(['id', 'name']) as $school) {
            $actors[] = [
                'type' => School::class,
                'id' => $school->id,
                'label' => $this->buildActorLabel(School::class, $school->name),
            ];
        }

        foreach ($user->ownedRecordLabels()->get(['id', 'name']) as $label) {
            $actors[] = [
                'type' => RecordLabel::class,
                'id' => $label->id,
                'label' => $this->buildActorLabel(RecordLabel::class, $label->name),
            ];
        }

        foreach ($user->ownedProducerCenters()->get(['id', 'name']) as $producerCenter) {
            $actors[] = [
                'type' => ProducerCenter::class,
                'id' => $producerCenter->id,
                'label' => $this->buildActorLabel(ProducerCenter::class, $producerCenter->name),
            ];
        }

        foreach ($user->ownedShops()->get(['id', 'name']) as $shop) {
            $actors[] = [
                'type' => Shop::class,
                'id' => $shop->id,
                'label' => $this->buildActorLabel(Shop::class, $shop->name),
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

            $label = $this->buildActorLabel($entity::class, (string) $entity->name);

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

    private function buildActorLabel(string $actorType, string $name): string
    {
        return $this->actorTypeLabel($actorType).': '.$name;
    }

    private function actorTypeLabel(string $actorType): string
    {
        return match ($actorType) {
            User::class => __('ui.music.search_initiator_user'),
            Peformer::class => __('ui.music.search_initiator_performer'),
            Musician::class => __('ui.music.search_initiator_musician'),
            ConcertVenue::class => __('ui.music.search_initiator_concert_venue'),
            Studio::class => __('ui.music.search_initiator_studio'),
            Rehersal::class => __('ui.music.search_initiator_rehersal'),
            School::class => __('ui.music.search_initiator_school'),
            RecordLabel::class => __('ui.music.search_initiator_record_label'),
            ProducerCenter::class => __('ui.music.search_initiator_producer_center'),
            Shop::class => __('ui.music.search_initiator_shop'),
            default => class_basename($actorType),
        };
    }
}
