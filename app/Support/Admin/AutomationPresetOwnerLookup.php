<?php

declare(strict_types=1);

namespace App\Support\Admin;

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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class AutomationPresetOwnerLookup
{
    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return [
            User::class,
            Peformer::class,
            Musician::class,
            ConcertVenue::class,
            Studio::class,
            Rehersal::class,
            School::class,
            RecordLabel::class,
            ProducerCenter::class,
            Shop::class,
        ];
    }

    public static function table(string $ownerType): ?string
    {
        return match ($ownerType) {
            User::class => 'users',
            Peformer::class => 'peformers',
            Musician::class => 'musicians',
            ConcertVenue::class => 'concert_venues',
            Studio::class => 'studios',
            Rehersal::class => 'rehearsals',
            School::class => 'schools',
            RecordLabel::class => 'record_labels',
            ProducerCenter::class => 'producer_centers',
            Shop::class => 'shops',
            default => null,
        };
    }

    /**
     * @return list<array{value:string,label:string,selected:bool,properties:array<string,mixed>}>
     */
    public static function search(string $ownerType, string $term, int $offset, int $limit, ?int $ensureId): array
    {
        $query = self::baseQuery($ownerType, $term);
        if ($query === null) {
            return [];
        }

        /** @var list<Model> $rows */
        $rows = $query->offset(max(0, $offset))->limit(max(1, $limit))->get()->all();

        if ($ensureId !== null && $ensureId > 0) {
            $exists = false;
            foreach ($rows as $row) {
                if ((int) $row->getKey() === $ensureId) {
                    $exists = true;
                    break;
                }
            }
            if (! $exists) {
                $extra = self::findById($ownerType, $ensureId);
                if ($extra !== null) {
                    array_unshift($rows, $extra);
                }
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = self::toOption($ownerType, $row);
        }

        return $out;
    }

    private static function findById(string $ownerType, int $id): ?Model
    {
        return match ($ownerType) {
            User::class => User::query()->find($id),
            Peformer::class => Peformer::query()->find($id),
            Musician::class => Musician::query()->find($id),
            ConcertVenue::class => ConcertVenue::query()->find($id),
            Studio::class => Studio::query()->find($id),
            Rehersal::class => Rehersal::query()->find($id),
            School::class => School::query()->find($id),
            RecordLabel::class => RecordLabel::query()->find($id),
            ProducerCenter::class => ProducerCenter::query()->find($id),
            Shop::class => Shop::query()->find($id),
            default => null,
        };
    }

    private static function toOption(string $ownerType, Model $model): array
    {
        $label = match ($ownerType) {
            User::class => self::userLabel($model instanceof User ? $model : null),
            default => (string) ($model->name ?? ''),
        };
        if ($label === '') {
            $label = class_basename($ownerType).' #'.$model->getKey();
        }

        return [
            'value' => (string) $model->getKey(),
            'label' => htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'selected' => false,
            'properties' => [],
        ];
    }

    private static function userLabel(?User $user): string
    {
        if ($user === null) {
            return '';
        }
        $label = trim((string) ($user->name ?? ''));
        if ($user->email !== null && $user->email !== '') {
            $label .= ($label !== '' ? ' · ' : '').$user->email;
        }

        return $label;
    }

    private static function baseQuery(string $ownerType, string $term): ?Builder
    {
        $like = '%'.$term.'%';

        return match ($ownerType) {
            User::class => User::query()
                ->when(
                    $term !== '',
                    static fn (Builder $q) => $q->where(static function (Builder $inner) use ($like, $term): void {
                        $inner->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                        if (ctype_digit($term)) {
                            $inner->orWhere('id', (int) $term);
                        }
                    }),
                )
                ->orderByDesc('id'),
            Peformer::class => self::nameSearchQuery(Peformer::query(), $like, $term),
            Musician::class => self::nameSearchQuery(Musician::query(), $like, $term),
            ConcertVenue::class => self::nameSearchQuery(ConcertVenue::query(), $like, $term),
            Studio::class => self::nameSearchQuery(Studio::query(), $like, $term),
            Rehersal::class => self::nameSearchQuery(Rehersal::query(), $like, $term),
            School::class => self::nameSearchQuery(School::query(), $like, $term),
            RecordLabel::class => self::nameSearchQuery(RecordLabel::query(), $like, $term),
            ProducerCenter::class => self::nameSearchQuery(ProducerCenter::query(), $like, $term),
            Shop::class => self::nameSearchQuery(Shop::query(), $like, $term),
            default => null,
        };
    }

    private static function nameSearchQuery(Builder $query, string $like, string $term): Builder
    {
        return $query
            ->when(
                $term !== '',
                static fn (Builder $q) => $q->where(static function (Builder $inner) use ($like, $term): void {
                    $inner->where('name', 'like', $like);
                    if (ctype_digit($term)) {
                        $inner->orWhere('id', (int) $term);
                    }
                }),
            )
            ->orderByDesc('id');
    }
}
