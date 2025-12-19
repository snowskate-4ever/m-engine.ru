<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\City;

use Illuminate\Database\Eloquent\Model;
use App\Models\City;
use App\MoonShine\Resources\City\Pages\CityIndexPage;
use App\MoonShine\Resources\City\Pages\CityFormPage;
use App\MoonShine\Resources\City\Pages\CityDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use Illuminate\Validation\Rule;
use MoonShine\Fields\ID;
use MoonShine\Fields\Text;
use MoonShine\Fields\Number;
use MoonShine\Fields\Switcher;
use MoonShine\Resources\Resource;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Actions\FiltersAction;

/**
 * @extends ModelResource<City, CityIndexPage, CityFormPage, CityDetailPage>
 */
class CityResource extends ModelResource
{
    protected string $model = City::class;

    public string $title = 'Города';
    public static string $subTitle = 'Управление городами';

    public function fields(): array
    {
        return [
            ID::make()->sortable()->showOnExport(),
            
            BelongsTo::make('Страна', 'country', fn($item) => $item->name, resource: new CountryResource())
                ->required()
                ->searchable()
                ->valuesQuery(fn($query) => $query->orderBy('name'))
                ->showOnExport(),
            
            BelongsTo::make('Регион', 'region', fn($item) => $item->name . ' (' . $item->country->code . ')', resource: new RegionResource())
                ->nullable()
                ->searchable()
                ->valuesQuery(fn($query) => $query->with('country')->orderBy('name'))
                ->showOnExport()
                ->hideOnIndex(),
            
            Text::make('Название', 'name')
                ->required()
                ->sortable()
                ->showOnExport(),
            
            Text::make('Английское название', 'name_eng')
                ->nullable()
                ->showOnExport(),
            
            Text::make('Slug', 'slug')
                ->required()
                ->showOnExport()
                ->hideOnIndex(),
            
            Text::make('Часовой пояс', 'timezone')
                ->nullable()
                ->showOnExport()
                ->hint('Europe/Moscow, America/New_York'),
            
            Number::make('Широта', 'latitude')
                ->nullable()
                ->step(0.000001)
                ->showOnExport(),
            
            Number::make('Долгота', 'longitude')
                ->nullable()
                ->step(0.000001)
                ->showOnExport(),
            
            Number::make('Население', 'population')
                ->nullable()
                ->min(0)
                ->showOnExport(),
            
            Number::make('Порядок сортировки', 'sort_order')
                ->default(0)
                ->sortable()
                ->showOnExport(),
            
            Switcher::make('Столица', 'is_capital')
                ->default(false)
                ->showOnExport(),
            
            Switcher::make('Активен', 'is_active')
                ->default(true)
                ->showOnExport(),
        ];
    }

    public function rules($item): array
    {
        return [
            'country_id' => ['required', 'exists:countries,id'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'name' => ['required', 'string', 'max:100'],
            'name_eng' => ['nullable', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('cities', 'slug')->ignore($item?->id)],
            'timezone' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'population' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['integer', 'min:0'],
            'is_capital' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function search(): array
    {
        return ['id', 'name', 'name_eng', 'slug'];
    }

    public function filters(): array
    {
        return [
            BelongsTo::make('Страна', 'country', fn($item) => $item->name, resource: new CountryResource())
                ->nullable()
                ->searchable(),
            
            BelongsTo::make('Регион', 'region', fn($item) => $item->name, resource: new RegionResource())
                ->nullable()
                ->searchable(),
            
            Text::make('Название', 'name'),
            
            Switcher::make('Столица', 'is_capital'),
            
            Switcher::make('Активен', 'is_active'),
        ];
    }

    public function actions(): array
    {
        return [
            FiltersAction::make(trans('moonshine::ui.filters')),
        ];
    }

    // public function beforeCreating($item)
    // {
    //     // Автоматическая генерация slug при создании
    //     if (empty($item->slug)) {
    //         $item->slug = \Str::slug($item->name);
    //     }
        
    //     // Автоматическая установка региона на основе страны, если регион не выбран
    //     if (!$item->region_id && $item->country_id) {
    //         $defaultRegion = \App\Models\Region::where('country_id', $item->country_id)
    //             ->where('code', '00')
    //             ->orWhere('type', 'city')
    //             ->first();
            
    //         if ($defaultRegion) {
    //             $item->region_id = $defaultRegion->id;
    //         }
    //     }
    // }

    public static function getModelLabel(): string
    {
        return 'Город';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Города';
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            CityIndexPage::class,
            CityFormPage::class,
            CityDetailPage::class,
        ];
    }
}
