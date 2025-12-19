<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Region;

use Illuminate\Database\Eloquent\Model;
use App\Models\Region;
use App\MoonShine\Resources\Region\Pages\RegionIndexPage;
use App\MoonShine\Resources\Region\Pages\RegionFormPage;
use App\MoonShine\Resources\Region\Pages\RegionDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Fields\ID;
use MoonShine\Fields\Text;
use MoonShine\Fields\Number;
use MoonShine\Fields\Select;
use MoonShine\Fields\Switcher;
use MoonShine\Resources\Resource;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Actions\FiltersAction;

/**
 * @extends ModelResource<Region, RegionIndexPage, RegionFormPage, RegionDetailPage>
 */
class RegionResource extends ModelResource
{
    protected string $model = Region::class;

    public string $title = 'Регионы';
    public static string $subTitle = 'Управление регионами';

    public function fields(): array
    {
        return [
            ID::make()->sortable()->showOnExport(),
            
            BelongsTo::make('Страна', 'country', fn($item) => $item->name, resource: new CountryResource())
                ->required()
                ->searchable()
                ->valuesQuery(fn($query) => $query->orderBy('name'))
                ->showOnExport(),
            
            Text::make('Название', 'name')
                ->required()
                ->sortable()
                ->showOnExport(),
            
            Text::make('Код региона', 'code')
                ->nullable()
                ->sortable()
                ->showOnExport(),
            
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
                ->nullable()
                ->showOnExport(),
            
            Text::make('Федеральный округ', 'federal_district')
                ->nullable()
                ->showOnExport(),
            
            Number::make('Широта', 'latitude')
                ->nullable()
                ->step(0.000001)
                ->showOnExport(),
            
            Number::make('Долгота', 'longitude')
                ->nullable()
                ->step(0.000001)
                ->showOnExport(),
            
            Number::make('Порядок сортировки', 'sort_order')
                ->default(0)
                ->sortable()
                ->showOnExport(),
            
            Switcher::make('Активен', 'is_active')
                ->default(true)
                ->showOnExport(),
            
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
            BelongsTo::make('Страна', 'country', fn($item) => $item->name, resource: new CountryResource())
                ->nullable()
                ->searchable(),
            
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

    public function actions(): array
    {
        return [
            FiltersAction::make(trans('moonshine::ui.filters')),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Регион';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Регионы';
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
