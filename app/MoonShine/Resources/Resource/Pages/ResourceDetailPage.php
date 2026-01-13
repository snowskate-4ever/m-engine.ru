<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Resource\Pages;

use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\Resource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Date;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use App\Models\Resource as MResource;
use App\Models\Type;
use App\MoonShine\Resources\Resource\ResourceResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Throwable;


/**
 * @extends DetailPage<Resource>
 */
class ResourceDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Checkbox::make(__('moonshine.resources.active'), 'active'),
            BelongsTo::make(
                    __('moonshine.resources.resource_type'),
                    'type',
                    formatted: static fn (Type $model) => __('moonshine.types.values.'.$model->name),
                    
                )
                    ->creatable()
                    ->valuesQuery(fn(Builder $query) => $query->where('resource_type', 'resources')),
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
