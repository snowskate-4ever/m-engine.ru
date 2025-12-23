<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\City\Pages;
use App\Models\Country;
use App\Models\Region;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\City\CityResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use App\MoonShine\Resources\Country\CountryResource;
use App\MoonShine\Resources\Region\RegionResource;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Throwable;


/**
 * @extends DetailPage<CityResource>
 */
class CityDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            
            Text::make('Название', 'name')
                ->required()
                ->sortable(),
            
            Text::make('Код', 'code')
                ->required()
                ->sortable()
                ->hint('alpha-2 (например: RU, US)'),
            
            Text::make('Телефонный код', 'phone_code')
                ->nullable(),
            
            Text::make('Код валюты', 'currency_code')
                ->nullable()
                ->hint('USD, EUR, RUB'),
            
            Text::make('Символ валюты', 'currency_symbol')
                ->nullable(),
            
            Number::make('Порядок сортировки', 'sort_order')
                ->default(0)
                ->sortable(),

            BelongsTo::make(
                        'Страна', 
                        'country', 
                        formatted: static fn (Country $model) => __('moonshine.countries.values.'.$model->code),
                    )
                    ->required()
                    ->creatable()
                    ->valuesQuery(fn(Builder $query, Field $field) => $query),

            BelongsTo::make(
                    'Регион', 
                    'region', 
                    formatted: static fn (Region $model) => __('moonshine.regions.values.'.$model->code),
                )
                ->required()
                ->creatable()
                ->valuesQuery(fn(Builder $query, Field $field) => $query),
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
