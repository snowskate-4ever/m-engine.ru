<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Event\Pages;

use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\Event\EventResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use Throwable;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Number;
use App\Models\Resource;
use App\Models\Room;
use App\Models\User;
use App\MoonShine\Resources\Resource\ResourceResource;
use App\MoonShine\Resources\Room\RoomResource;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Field;


/**
 * @extends DetailPage<EventResource>
 */
class EventDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make(__('moonshine.events.name'), 'name'),
            Textarea::make(__('moonshine.events.description'), 'description'),
            Checkbox::make(__('moonshine.events.active'), 'active'),
            Select::make(__('moonshine.events.status'), 'status')
                ->options([
                    'pending' => __('moonshine.events.statuses.pending'),
                    'confirmed' => __('moonshine.events.statuses.confirmed'),
                    'cancelled' => __('moonshine.events.statuses.cancelled'),
                    'completed' => __('moonshine.events.statuses.completed'),
                ])
                ->default('pending'),
            BelongsTo::make(
                    __('moonshine.events.booking_resource'),
                    'bookingResource',
                    formatted: static fn (?Resource $model) => $model?->name ?? '',
                    resource: ResourceResource::class,
                )
                ->nullable(),
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
            BelongsTo::make(
                    __('moonshine.events.user'),
                    'user',
                    formatted: static fn (?User $model) => $model ? ($model->name . ' (' . $model->email . ')') : '',
                    resource: UserResource::class,
                )
                ->nullable(),
            Date::make(__('moonshine.events.start_at'), 'start_at')
                ->withTime(),
            Date::make(__('moonshine.events.end_at'), 'end_at')
                ->withTime(),
            Number::make(__('moonshine.events.price'), 'price')
                ->nullable()
                ->step(0.01),
            Textarea::make(__('moonshine.events.notes'), 'notes')
                ->nullable(),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyDetailComponent(ComponentContract $component): ComponentContract
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
