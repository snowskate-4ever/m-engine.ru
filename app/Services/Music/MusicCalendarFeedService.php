<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Models\ConcertVenue;
use App\Models\Event;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MusicCalendarFeedService
{
    public const EVENT_KIND_ALL = 'all';
    public const EVENT_KIND_EVENT = 'event';
    public const EVENT_KIND_BOOKING = 'booking';
    public const EVENT_KIND_ROOM_BOOKING = 'room_booking';
    public const EVENT_KIND_RESOURCE_BOOKING = 'resource_booking';

    public function __construct(
        private readonly MusicActorContextService $actorContextService,
    ) {}

    /**
     * @return list<array{value:string,label:string}>
     */
    public function ownerFilterOptions(User $user): array
    {
        $options = [
            ['value' => 'all_linked', 'label' => __('ui.calendar.owner_all_linked')],
            ['value' => User::class.'#'.$user->id, 'label' => __('ui.calendar.owner_me')],
        ];

        foreach ($this->actorContextService->availableActors($user) as $actor) {
            $value = $actor['type'].'#'.$actor['id'];
            if ($value === User::class.'#'.$user->id) {
                continue;
            }

            $options[] = [
                'value' => $value,
                'label' => $actor['label'],
            ];
        }

        return collect($options)
            ->unique('value')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Event>
     */
    public function eventsForRange(User $user, CarbonImmutable $startUtc, CarbonImmutable $endUtc, array $filters = []): Collection
    {
        $query = Event::query()
            ->with(['bookedResource', 'bookingResource', 'room', 'user', 'musicOrganizer', 'concertVenue'])
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('start_at', '<=', $endUtc)
            ->where('end_at', '>=', $startUtc);

        $this->applyEventKindFilter($query, (string) ($filters['event_kind'] ?? self::EVENT_KIND_ALL));
        $this->applyOwnershipFilter($query, $user, $filters);
        $this->applyLegacyFilters($query, $filters);

        return $query
            ->orderBy('start_at')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyOwnershipFilter(Builder $query, User $user, array $filters): void
    {
        $ownerEntity = $filters['owner_entity'] ?? null;
        if (is_string($ownerEntity) && $ownerEntity !== '' && $ownerEntity !== 'all_linked') {
            [$ownerType, $ownerId] = $this->parseOwnerEntity($ownerEntity);
            if ($ownerType !== null && $ownerId !== null) {
                $this->applyOwnerClause($query, $ownerType, $ownerId);

                return;
            }
        }

        $includeRelated = ! array_key_exists('include_related_entities', $filters) || (bool) $filters['include_related_entities'];
        if (! $includeRelated) {
            $query->where(function (Builder $owned) use ($user): void {
                $owned->where('user_id', $user->id)
                    ->orWhere('music_organizer_user_id', $user->id);
            });

            return;
        }

        $actors = collect($this->actorContextService->availableActors($user));
        $idsByType = $actors
            ->groupBy('type')
            ->map(fn (Collection $items): Collection => $items->pluck('id')->map(fn (mixed $id): int => (int) $id)->filter()->unique()->values());

        $query->where(function (Builder $owned) use ($user, $idsByType): void {
            $owned->where('user_id', $user->id)
                ->orWhere('music_organizer_user_id', $user->id);

            $venueIds = $idsByType->get(ConcertVenue::class, collect())->all();
            if ($venueIds !== []) {
                $owned->orWhereIn('concert_venue_id', $venueIds);
            }

            $performerIds = $idsByType->get(Peformer::class, collect())->all();
            if ($performerIds !== []) {
                $owned->orWhere(function (Builder $space) use ($performerIds): void {
                    $space->where('matching_space_type', Peformer::class)
                        ->whereIn('matching_space_id', $performerIds);
                })->orWhereHas('peformers', function (Builder $performers) use ($performerIds): void {
                    $performers->whereIn('peformers.id', $performerIds);
                    });
            }

            foreach ([ConcertVenue::class, Musician::class, Studio::class, Rehersal::class, School::class] as $type) {
                $ids = $idsByType->get($type, collect())->all();
                if ($ids === []) {
                    continue;
                }

                $owned->orWhere(function (Builder $space) use ($type, $ids): void {
                    $space->where('matching_space_type', $type)
                        ->whereIn('matching_space_id', $ids);
                });
            }
        });
    }

    private function applyEventKindFilter(Builder $query, string $kind): void
    {
        match ($kind) {
            self::EVENT_KIND_EVENT => $query->whereNull('booked_resource_id'),
            self::EVENT_KIND_BOOKING => $query->whereNotNull('booked_resource_id'),
            self::EVENT_KIND_ROOM_BOOKING => $query->whereNotNull('booked_resource_id')->whereNotNull('room_id'),
            self::EVENT_KIND_RESOURCE_BOOKING => $query->whereNotNull('booked_resource_id')->whereNull('room_id'),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyLegacyFilters(Builder $query, array $filters): void
    {
        if (array_key_exists('active', $filters)) {
            $query->where('active', (bool) $filters['active']);
        }
        if (isset($filters['booked_resource_id'])) {
            $query->where('booked_resource_id', (int) $filters['booked_resource_id']);
        }
        if (isset($filters['booking_resource_id'])) {
            $query->where('booking_resource_id', (int) $filters['booking_resource_id']);
        }
        if (isset($filters['room_id'])) {
            $query->where('room_id', (int) $filters['room_id']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['bookings_only'])) {
            $query->whereNotNull('booked_resource_id');
        }
        if (! empty($filters['room_bookings_only'])) {
            $query->whereNotNull('room_id');
        }
    }

    private function applyOwnerClause(Builder $query, string $ownerType, int $ownerId): void
    {
        if ($ownerType === User::class) {
            $query->where(function (Builder $owned) use ($ownerId): void {
                $owned->where('user_id', $ownerId)
                    ->orWhere('music_organizer_user_id', $ownerId);
            });

            return;
        }

        if ($ownerType === ConcertVenue::class) {
            $query->where(function (Builder $owned) use ($ownerId): void {
                $owned->where('concert_venue_id', $ownerId)
                    ->orWhere(function (Builder $space) use ($ownerId): void {
                        $space->where('matching_space_type', ConcertVenue::class)
                            ->where('matching_space_id', $ownerId);
                    });
            });

            return;
        }

        if ($ownerType === Peformer::class) {
            $query->where(function (Builder $owned) use ($ownerId): void {
                $owned->where(function (Builder $space) use ($ownerId): void {
                    $space->where('matching_space_type', Peformer::class)
                        ->where('matching_space_id', $ownerId);
                })->orWhereHas('peformers', function (Builder $performers) use ($ownerId): void {
                    $performers->where('peformers.id', $ownerId);
                });
            });

            return;
        }

        $query->where('matching_space_type', $ownerType)
            ->where('matching_space_id', $ownerId);
    }

    /**
     * @return array{0:?string,1:?int}
     */
    private function parseOwnerEntity(string $ownerEntity): array
    {
        [$rawType, $rawId] = array_pad(explode('#', $ownerEntity, 2), 2, null);
        if (! is_string($rawType) || ! is_string($rawId) || $rawType === '' || $rawId === '') {
            return [null, null];
        }

        $type = $this->normalizeOwnerType($rawType);
        $id = (int) $rawId;

        if ($type === null || $id < 1) {
            return [null, null];
        }

        return [$type, $id];
    }

    private function normalizeOwnerType(string $type): ?string
    {
        return match ($type) {
            User::class, 'user', 'organizer' => User::class,
            Peformer::class, 'performer', 'peformer' => Peformer::class,
            ConcertVenue::class, 'concert_venue', 'venue' => ConcertVenue::class,
            Musician::class, 'musician' => Musician::class,
            Studio::class, 'studio' => Studio::class,
            Rehersal::class, 'rehersal', 'rehearsal' => Rehersal::class,
            School::class, 'school' => School::class,
            default => null,
        };
    }
}
