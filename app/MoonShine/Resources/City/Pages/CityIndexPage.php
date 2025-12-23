<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\City\Pages;

use App\Models\Country;
use App\Models\Region;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Field;
use MoonShine\Resources\Resource;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use App\MoonShine\Resources\City\CityResource;
use App\MoonShine\Resources\Region\RegionResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Support\ListOf;
use Throwable;


/**
 * @extends IndexPage<CityResource>
 */
class CityIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    public function fields(): array
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

    public function rules($item): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:2', 'unique:countries,code,' . $item?->id],
            'phone_code' => ['nullable', 'string', 'max:10'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
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
            Text::make('Название', 'name'),
            Text::make('Код', 'code'),
            Switcher::make('Активна', 'is_active'),
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
