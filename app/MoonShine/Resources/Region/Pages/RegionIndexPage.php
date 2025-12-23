<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Region\Pages;

use App\Models\Type;
use App\Models\Country;
use App\MoonShine\Resources\Region\RegionResource;
use App\MoonShine\Resources\Country\CountryResource;
use App\MoonShine\Resources\City\CityResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Support\ListOf;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Throwable;


/**
 * @extends IndexPage<RegionResource>
 */
class RegionIndexPage extends IndexPage
{
    protected bool $isLazy = true;

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
     * @return list<FieldContract>
     */
    public function filters(): array
    {
        return [
            // BelongsTo::make(
            //         'Страна', 
            //         'country', 
            //         // fn($item) => $item->name, 
            //         // resource: new CountryResource()
            //         formatted: static fn (Country $model) => __('moonshine.types.values.'.$model->name),
            //     )
            //     ->required()
            //     ->searchable()
            //     ->valuesQuery(fn(Builder $query, Field $field) => $query->where('country_id', 'country')),
            
            Text::make('Название', 'name'),
            
            Select::make('Тип', 'type')
                ->options([
                    'oblast' => 'Область',
                    'krai' => 'Край',
                    'republic' => 'Республика',
                    'state' => 'Штат',
                    'autonomous_okrug' => 'Автономный округ',
                    'city' => 'Город федерального значения',
                    'other' => 'Другое',
                ])
                ->nullable(),
            
            Switcher::make('Активен', 'is_active'),
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
