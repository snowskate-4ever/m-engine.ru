<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Communication\Pages;

use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\Communication\CommunicationResource;
use MoonShine\Support\ListOf;
use App\Models\User;
use App\Models\Resource;
use App\Models\Type;
use MoonShine\UI\Fields\Field;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use Throwable;


/**
 * @extends IndexPage<CommunicationResource>
 */
class CommunicationIndexPage extends IndexPage
{
    protected bool $isLazy = true;

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
