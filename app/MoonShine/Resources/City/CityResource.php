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
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use App\MoonShine\Resources\Country\CountryResource;
use App\MoonShine\Resources\Region\RegionResource;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Actions\FiltersAction;

/**
 * @extends ModelResource<City, CityIndexPage, CityFormPage, CityDetailPage>
 */
class CityResource extends ModelResource
{
    protected string $model = City::class;

    public function getTitle(): string
    {
        return __('moonshine.cities.Tablename');
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
        return [];
    }

    public function actions(): array
    {
        return [
            FiltersAction::make(),
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
