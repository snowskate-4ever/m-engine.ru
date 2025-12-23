<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Region\Pages;

use App\Models\Type;
use App\Models\Country;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\Region\RegionResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Throwable;


/**
 * @extends DetailPage<RegionResource>
 */
class RegionDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            
            BelongsTo::make(
                    'Страна', 
                    'country', 
                    formatted: static fn (Country $model) => __('moonshine.countries.values.'.$model->code),
                )
                ->required()
                ->searchable()
                ->valuesQuery(fn(Builder $query, Field $field) => $query),
            
            BelongsTo::make(
                    'Тип', 
                    'type', 
                    formatted: static fn (Type $model) => __('moonshine.types.values.'.$model->name),
                )
                    ->required()
                    ->searchable()
                    ->valuesQuery(fn(Builder $query, Field $field) => $query->where('resource_type', '=', 'regions')),

            Text::make('Название', 'name')
                ->required()
                ->sortable(),
            
            Text::make('Код региона', 'code')
                ->nullable()
                ->sortable(),
                
            Text::make('Федеральный округ', 'federal_district')
                ->nullable(),
            
            Number::make('Широта', 'latitude')
                ->nullable()
                ->step(0.000001),
            
            Number::make('Долгота', 'longitude')
                ->nullable()
                ->step(0.000001),
            
            Number::make('Порядок сортировки', 'sort_order')
                ->default(0)
                ->sortable(),
            
            Switcher::make('Активен', 'is_active')
                ->default(true),
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
