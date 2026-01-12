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
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use App\Models\Resource;
use App\MoonShine\Resources\Event\EventResource;
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
            Textarea::make(__('moonshine.events.description'), 'description'),
            Checkbox::make(__('moonshine.events.active'), 'active'),
            BelongsTo::make(
                    __('moonshine.events.booking_resource'),
                    'bookingResource',
                    'booking_resource_id',
                    formatted: static fn (Resource $model) => $model->name,
                ),
            BelongsTo::make(
                    __('moonshine.events.booked_resource'),
                    'bookedResource',
                    'booked_resource_id',
                    formatted: static fn (Resource $model) => $model->name,
                ),
            Date::make(__('moonshine.events.start_at'), 'start_at')
                ->withTime()
                ->format('H:i d.m.Y'),
            Date::make(__('moonshine.events.end_at'), 'end_at')
                ->withTime()
                ->format('H:i d.m.Y'),
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
        return [];
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
