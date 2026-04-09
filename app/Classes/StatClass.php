<?php

namespace App\Classes;

use App\Models\Event;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\Resource;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\Teacher;
use App\Models\Type;
use App\Models\User;
use App\Services\Music\MusicPublicSearchService;
use Illuminate\Support\Facades\Schema;

class StatClass
{
    /**
     * Карточки для дашборда: события и ресурсы по каждому типу (catalog resources).
     *
     * @return list<array{id: string, title: string, count_all: int, my_items: int, count_all_label?: string}>
     */
    public static function dashboardStatCards(User $user): array
    {
        $cards = [];

        $cards[] = [
            'id' => 'events',
            'title' => __('ui.stat_card_events'),
            'count_all' => Event::count(),
            'my_items' => Schema::hasColumn('events', 'user_id')
                ? Event::query()->where('user_id', $user->id)->count()
                : 0,
        ];

        $myResourceIds = self::resourceIdsLinkedToUserEvents($user);

        $typeIdsInUse = Resource::query()->distinct()->pluck('type_id')->filter()->values();

        $types = Type::query()
            ->where(function ($q) use ($typeIdsInUse) {
                $q->where('resource_type', 'resources');
                if ($typeIdsInUse->isNotEmpty()) {
                    $q->orWhereIn('id', $typeIdsInUse);
                }
            })
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();

        foreach ($types as $type) {
            $countAll = Resource::query()->where('type_id', $type->id)->count();
            $myCount = $myResourceIds === []
                ? 0
                : Resource::query()
                    ->where('type_id', $type->id)
                    ->whereIn('id', $myResourceIds)
                    ->count();

            $cards[] = [
                'id' => 'resource-type-'.$type->id,
                'title' => self::typeDisplayName($type),
                'count_all' => $countAll,
                'my_items' => $myCount,
            ];
        }

        if ($types->isEmpty()) {
            $cards[] = [
                'id' => 'resources-total',
                'title' => __('ui.stat_card_resources'),
                'count_all' => Resource::count(),
                'my_items' => $myResourceIds === []
                    ? 0
                    : Resource::query()->whereIn('id', $myResourceIds)->count(),
            ];
        }

        self::appendMusicCatalogStatCards($user, $cards);

        return $cards;
    }

    /**
     * @param  list<array{id: string, title: string, count_all: int, my_items: int, count_all_label?: string}>  $cards
     */
    private static function appendMusicCatalogStatCards(User $user, array &$cards): void
    {
        $catalog = app(MusicPublicSearchService::class)->publicCatalogCounts();

        $myMusicians = Musician::query()->where('user_id', $user->id)->count();
        $myTeachers = Teacher::query()->where('user_id', $user->id)->count();
        $myPerformers = Peformer::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_user_id', $user->id)
                    ->orWhereHas('admins', fn ($q2) => $q2->where('users.id', $user->id));
            })
            ->count();
        $myStudios = Studio::query()->where('owner_user_id', $user->id)->count();
        $myRehearsals = Rehersal::query()->where('owner_user_id', $user->id)->count();
        $mySchools = School::query()->where('owner_user_id', $user->id)->count();
        $myRecordLabels = RecordLabel::query()->where('owner_user_id', $user->id)->count();
        $myProducerCenters = ProducerCenter::query()->where('owner_user_id', $user->id)->count();
        $myShops = Shop::query()->where('owner_user_id', $user->id)->count();

        $mineByKey = [
            'musician' => $myMusicians,
            'teacher' => $myTeachers,
            'performer' => $myPerformers,
            'studio' => $myStudios,
            'rehearsal' => $myRehearsals,
            'school' => $mySchools,
            'record_label' => $myRecordLabels,
            'producer_center' => $myProducerCenters,
            'shop' => $myShops,
        ];

        foreach (['musician', 'teacher', 'performer', 'studio', 'rehearsal', 'school', 'record_label', 'producer_center', 'shop'] as $key) {
            $cards[] = [
                'id' => 'catalog-'.$key,
                'title' => __('ui.music.discover_category.'.$key),
                'count_all' => $catalog[$key],
                'my_items' => $mineByKey[$key],
                'count_all_label' => 'ui.stat_card_in_catalog',
            ];
        }

        $cards[] = [
            'id' => 'catalog-all',
            'title' => __('ui.stat_card_discover_total'),
            'count_all' => array_sum($catalog),
            'my_items' => array_sum($mineByKey),
            'count_all_label' => 'ui.stat_card_in_catalog',
        ];
    }

    /**
     * @deprecated Используйте {@see self::dashboardStatCards}
     *
     * @return array{count_all: int, my_items: int}
     */
    public static function get_stats(User $user, string $type): array
    {
        $className = 'App\\Models\\'.$type;

        if ($type === 'Event') {
            return [
                'count_all' => Event::count(),
                'my_items' => Schema::hasColumn('events', 'user_id')
                    ? Event::query()->where('user_id', $user->id)->count()
                    : 0,
            ];
        }

        if ($type === 'Resource') {
            $ids = self::resourceIdsLinkedToUserEvents($user);

            return [
                'count_all' => Resource::count(),
                'my_items' => $ids === []
                    ? 0
                    : Resource::query()->whereIn('id', $ids)->count(),
            ];
        }

        $my_items = 0;
        $count_all = $className::count();

        return [
            'count_all' => $count_all,
            'my_items' => $my_items,
        ];
    }

    /**
     * @return list<int|string>
     */
    private static function resourceIdsLinkedToUserEvents(User $user): array
    {
        if (! Schema::hasColumn('events', 'user_id')) {
            return [];
        }

        $columns = array_values(array_filter(
            ['resource_id', 'booking_resource_id', 'booked_resource_id'],
            static fn (string $c): bool => Schema::hasColumn('events', $c),
        ));

        if ($columns === []) {
            return [];
        }

        $ids = collect();
        foreach ($columns as $col) {
            $ids = $ids->merge(
                Event::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull($col)
                    ->pluck($col),
            );
        }

        return $ids->filter()->unique()->values()->all();
    }

    private static function typeDisplayName(Type $type): string
    {
        $key = 'moonshine.types.values.'.$type->name;
        $trans = __($key);

        return $trans !== $key ? $trans : $type->name;
    }
}
