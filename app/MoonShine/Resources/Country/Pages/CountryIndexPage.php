<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Country\Pages;

use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Switcher;
use App\MoonShine\Resources\Country\CountryResource;
use MoonShine\Support\ListOf;
use Throwable;


/**
 * @extends IndexPage<CountryResource>
 */
class CountryIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            // Добавляем флаг страны в таблицу
            // MoonShine\Fields\Preview::make('Флаг', 'code', fn($item) => 
            //     view('moonshine.components.country-flag', [
            //         'code' => strtolower($item->code),
            //         'size' => '20px'
            //     ])
            // )->badge('primary'),
            
            ID::make()->sortable(),
            
            Text::make('Код', 'code')
                ->sortable()
                ->badge('success'),
            
            Text::make('Название', 'name')
                ->sortable(),
                //->searchable(),
            
            Text::make('Телефонный код', 'phone_code')
                ->badge('purple'),
            
            Switcher::make('Активна', 'is_active')
                ->sortable()
                ->badge(fn($status, $field) => $status 
                    ? 'green' 
                    : 'red'),
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
