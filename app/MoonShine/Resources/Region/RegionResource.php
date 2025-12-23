<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Region;

use App\Models\Region;
use App\Models\Country;
use App\MoonShine\Resources\City\CityResource;
use App\MoonShine\Resources\Region\Pages\RegionIndexPage;
use App\MoonShine\Resources\Region\Pages\RegionFormPage;
use App\MoonShine\Resources\Region\Pages\RegionDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Actions\FiltersAction;

/**
 * @extends ModelResource<Region, RegionIndexPage, RegionFormPage, RegionDetailPage>
 */
class RegionResource extends ModelResource
{
    protected string $model = Region::class;

    public static string $subTitle = 'Управление регионами';

    public function getTitle(): string
    {
        return __('moonshine.regions.Tablename');
    }

    public function fields(): array
    {
        return [
            ID::make()->sortable()->showOnExport(),
            
            BelongsTo::make(
                    'Страна', 
                    'country', 
                    // fn($item) => $item->name, 
                    // resource: new CountryResource()
                    formatted: static fn (Country $model) => __('moonshine.types.values.'.$model->name),
                )
                ->required()
                ->searchable()
                ->valuesQuery(fn($query) => $query->orderBy('name')),

            Text::make('Название', 'name')
                ->required()
                ->sortable(),
            
            Text::make('Код региона', 'code')
                ->nullable()
                ->sortable(),
            
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
            
            HasMany::make('Города', 'cities', resource: new CityResource())
                ->creatable()
                ->hideOnIndex(),
        ];
    }

    public function rules($item): array
    {
        return [
            'country_id' => ['required', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:10'],
            'type' => ['nullable', 'string', 'max:50'],
            'federal_district' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
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
            BelongsTo::make(
                    'Страна', 
                    'country', 
                    formatted: static fn (Country $model) => __('moonshine.types.values.'.$model->name),
                )
                ->required()
                ->searchable()
                ->valuesQuery(fn($query) => $query->orderBy('name')),
            
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
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            RegionIndexPage::class,
            RegionFormPage::class,
            RegionDetailPage::class,
        ];
    }
}
