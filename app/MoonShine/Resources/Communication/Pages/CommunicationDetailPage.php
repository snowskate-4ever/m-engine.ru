<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Communication\Pages;

use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\Communication\CommunicationResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use Throwable;


/**
 * @extends DetailPage<CommunicationResource>
 */
class CommunicationDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),

            BelongsTo::make(
                    __('moonshine.communications.user'),
                    'user',
                    formatted: static fn (User $model) => $model->name,
                ),  
            BelongsTo::make(
                    __('moonshine.communications.resource'),
                    'resource',
                    formatted: static fn (Resource $model) => $model->name, 
                ),
            BelongsTo::make(
                     __('moonshine.resources.resource_type'),
                    'type',
                    formatted: static fn (Type $model) => __('moonshine.types.values.'.$model->name),
                 )
                    ->valuesQuery(fn(Builder $query, Field $field) => $query->where('resource_type', 'communication')),
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
