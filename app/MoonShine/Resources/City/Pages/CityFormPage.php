<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\City\Pages;

use App\Models\Country;
use App\Models\Region;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
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
use MoonShine\UI\Components\Layout\Box;
use Throwable;


/**
 * @extends FormPage<CityResource>
 */
class CityFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make()->sortable(),

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
                
                Text::make('Название', 'name')
                    ->required()
                    ->sortable(),
                
                Text::make('Английское название', 'name_eng')
                    ->nullable(),
                
                Text::make('Slug', 'slug')
                    ->required(),
                
                Text::make('Часовой пояс', 'timezone')
                    ->nullable()
                    ->hint('Europe/Moscow, America/New_York'),Text::make('Код', 'code')
                ->required()
                ->sortable()
                ->hint('ISO 3166-1 alpha-2 (например: RU, US)'),
            
                Text::make('Телефонный код', 'phone_code')
                    ->nullable(),
                
                Text::make('Код валюты', 'currency_code')
                    ->nullable()
                    ->hint('USD, EUR, RUB'),
                
                Text::make('Символ валюты', 'currency_symbol')
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
            ]),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons();
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }

    /**
     * @param  FormBuilder  $component
     *
     * @return FormBuilder
     */
    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
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
