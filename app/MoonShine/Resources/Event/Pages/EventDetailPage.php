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
use App\Models\Resource;
use App\Models\Place;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Checkbox;


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
            BelongsTo::make(
                    __('moonshine.events.resource'),
                    'resource',
                    formatted: static fn (Resource $model) => $model->name,
                ),
            BelongsTo::make(
                    __('moonshine.events.place'),
                    'resource',
                    formatted: static fn (Resource $model) => $model->name,
                ),
            Date::make(__('moonshine.events.start_at'), 'start_at')
                ->withTime(),
            Date::make(__('moonshine.events.end_at'), 'end_at')
                ->withTime(),
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
