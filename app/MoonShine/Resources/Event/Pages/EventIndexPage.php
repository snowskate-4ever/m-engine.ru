<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Event\Pages;

use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Support\ListOf;
use Throwable;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use App\Models\Resource;
use App\Models\Room;
use App\Models\User;
use App\MoonShine\Resources\Event\EventResource;
use App\MoonShine\Resources\Resource\ResourceResource;
use App\MoonShine\Resources\Room\RoomResource;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Contracts\Database\Eloquent\Builder;


/**
 * @extends IndexPage<EventResource>
 */
class EventIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make(__('moonshine.events.name'), 'name'),
            // description и notes не отображаются на индексе, так как Textarea не поддерживает hideOnIndex()
            Checkbox::make(__('moonshine.events.active'), 'active'),
            Select::make(__('moonshine.events.status'), 'status')
                ->options([
                    'pending' => __('moonshine.events.statuses.pending'),
                    'confirmed' => __('moonshine.events.statuses.confirmed'),
                    'cancelled' => __('moonshine.events.statuses.cancelled'),
                    'completed' => __('moonshine.events.statuses.completed'),
                ])
                ->default('pending'),
            // bookingResource не отображается на индексе, так как BelongsTo не поддерживает hideOnIndex()
            BelongsTo::make(
                    __('moonshine.events.booked_resource'),
                    'bookedResource',
                    formatted: static fn (?Resource $model) => $model?->name ?? '',
                    resource: ResourceResource::class,
                )
                ->nullable(),
            BelongsTo::make(
                    __('moonshine.events.room'),
                    'room',
                    formatted: static fn (?Room $model) => $model ? ($model->name . ($model->relationLoaded('resource') && $model->resource ? ' (' . $model->resource->name . ')' : '')) : '',
                    resource: RoomResource::class,
                )
                ->nullable(),
            // user не отображается на индексе, так как BelongsTo не поддерживает hideOnIndex()
            Date::make(__('moonshine.events.start_at'), 'start_at')
                ->withTime()
                ->format('H:i d.m.Y'),
            Date::make(__('moonshine.events.end_at'), 'end_at')
                ->withTime()
                ->format('H:i d.m.Y'),
            // price не отображается на индексе, так как Number не поддерживает hideOnIndex()
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Text::make(__('moonshine.events.name'), 'name'),
            Checkbox::make(__('moonshine.events.active'), 'active'),
            Select::make(__('moonshine.events.status'), 'status')
                ->options([
                    'pending' => __('moonshine.events.statuses.pending'),
                    'confirmed' => __('moonshine.events.statuses.confirmed'),
                    'cancelled' => __('moonshine.events.statuses.cancelled'),
                    'completed' => __('moonshine.events.statuses.completed'),
                ])
                ->nullable(),
            BelongsTo::make(
                    __('moonshine.events.booked_resource'),
                    'bookedResource',
                    formatted: static fn (Resource $model) => $model->name,
                    resource: ResourceResource::class,
                )
                ->nullable()
                ->valuesQuery(fn(Builder $query, Field $field) => $query
                    ->select('resources.*', 'types.resource_type')
                    ->leftjoin('types', 'types.id', '=', 'resources.type_id')
                    ->where('types.resource_type', 'resources')),
            BelongsTo::make(
                    __('moonshine.events.room'),
                    'room',
                    formatted: static fn (?Room $model) => $model ? ($model->name . ($model->relationLoaded('resource') && $model->resource ? ' (' . $model->resource->name . ')' : '')) : '',
                    resource: RoomResource::class,
                )
                ->nullable(),
            BelongsTo::make(
                    __('moonshine.events.user'),
                    'user',
                    formatted: static fn (User $model) => $model->name . ' (' . $model->email . ')',
                    resource: UserResource::class,
                )
                ->nullable(),
            Date::make(__('moonshine.events.start_at'), 'start_at')
                ->withTime()
                ->nullable(),
            Date::make(__('moonshine.events.end_at'), 'end_at')
                ->withTime()
                ->nullable(),
        ];
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
