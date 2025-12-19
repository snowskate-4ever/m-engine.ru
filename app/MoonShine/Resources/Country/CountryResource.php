<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Country;

use Illuminate\Database\Eloquent\Model;
use App\Models\Country;
use App\MoonShine\Resources\Country\Pages\CountryIndexPage;
use App\MoonShine\Resources\Country\Pages\CountryFormPage;
use App\MoonShine\Resources\Country\Pages\CountryDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\Resources\Resource;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Actions\FiltersAction;

/**
 * @extends ModelResource<Country, CountryIndexPage, CountryFormPage, CountryDetailPage>
 */
class CountryResource extends ModelResource
{
    protected string $model = Country::class;

    public string $title = 'Страны';
    public static string $subTitle = 'Управление странами';

    public function fields(): array
    {
        return [
            ID::make()->sortable()->showOnExport(),
            
            Text::make('Название', 'name')
                ->required()
                ->sortable()
                ->showOnExport(),
            
            Text::make('Код', 'code')
                ->required()
                ->sortable()
                ->showOnExport()
                ->hint('ISO 3166-1 alpha-2 (например: RU, US)'),
            
            Text::make('Телефонный код', 'phone_code')
                ->nullable()
                ->showOnExport(),
            
            Text::make('Код валюты', 'currency_code')
                ->nullable()
                ->showOnExport()
                ->hint('USD, EUR, RUB'),
            
            Text::make('Символ валюты', 'currency_symbol')
                ->nullable()
                ->showOnExport(),
            
            Number::make('Порядок сортировки', 'sort_order')
                ->default(0)
                ->sortable()
                ->showOnExport(),
            
            Switcher::make('Активна', 'is_active')
                ->default(true)
                ->showOnExport(),
            
            HasMany::make('Регионы', 'regions', resource: new RegionResource())
                ->creatable()
                ->hideOnIndex(),
            
            HasMany::make('Города', 'cities', resource: new CityResource())
                ->creatable()
                ->hideOnIndex(),
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

    public function search(): array
    {
        return ['id', 'name', 'code'];
    }

    public function filters(): array
    {
        return [
            Text::make('Название', 'name'),
            Text::make('Код', 'code'),
            Switcher::make('Активна', 'is_active'),
        ];
    }

    public function actions(): array
    {
        return [
            FiltersAction::make(trans('moonshine::ui.filters')),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Страна';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Страны';
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            CountryIndexPage::class,
            CountryFormPage::class,
            CountryDetailPage::class,
        ];
    }
    
    public function permissions(): array
    {
        return [
            'view' => auth()->user()->hasRole('admin') || auth()->user()->hasRole('editor'),
            'create' => auth()->user()->hasRole('admin'),
            'update' => auth()->user()->hasRole('admin') || auth()->user()->hasRole('editor'),
            'delete' => auth()->user()->hasRole('admin'),
            'massDelete' => auth()->user()->hasRole('admin'),
        ];
    }
}
