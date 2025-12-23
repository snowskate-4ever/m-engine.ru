<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Country;

use Illuminate\Database\Eloquent\Model;
use App\Models\Country;
use App\MoonShine\Resources\Country\Pages\CountryIndexPage;
use App\MoonShine\Resources\Country\Pages\CountryFormPage;
use App\MoonShine\Resources\Country\Pages\CountryDetailPage;
use App\MoonShine\Resources\Region\RegionResource;
use App\MoonShine\Resources\City\CityResource;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\Resources\Resource;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Actions\FiltersAction;

/**
 * @extends ModelResource<Country, CountryIndexPage, CountryFormPage, CountryDetailPage>
 */
class CountryResource extends ModelResource
{
    protected string $model = Country::class;

    public static string $subTitle = 'Управление странами';

    public function getTitle(): string
    {
        return __('moonshine.countries.Tablename');
    }

    

    public function search(): array
    {
        return ['id', 'name', 'code'];
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
