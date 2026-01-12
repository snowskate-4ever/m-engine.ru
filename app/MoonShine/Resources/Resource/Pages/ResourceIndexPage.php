<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Resource\Pages;

use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\Resource\ResourceResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Number;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use App\Models\Resource as MResource;
use App\Models\Type;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Throwable;


/**
 * @extends IndexPage<ResourceResource>
 */
class ResourceIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make(__('moonshine.resources.name'), 'name'),
            Textarea::make(__('moonshine.resources.description'), 'description'),
            Checkbox::make(__('moonshine.resources.active'), 'active'),
            BelongsTo::make(
                    __('moonshine.resources.resource_type'),
                    'type',
                    formatted: static fn (Type $model) => __('moonshine.types.values.'.$model->name),
                    
                )
                    ->creatable()
                    ->valuesQuery(fn(Builder $query) => $query->where('resource_type', 'resources')),
                    // ->values(fn() => 
                    //     Type::query()
                    //         ->where('resource_type', 'resources')
                    //         ->orderBy('id', 'asc')
                    //         ->get()
                    //         ->mapWithKeys(fn($item) => [$item->id => $item->name])
                    // ),
            Date::make(__('moonshine.resources.start_at'), 'start_at'),
            Date::make(__('moonshine.resources.end_at'), 'end_at')
                ->nullable(),
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
