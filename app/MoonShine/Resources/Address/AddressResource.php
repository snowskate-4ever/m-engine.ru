<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Address;

use Illuminate\Database\Eloquent\Model;
use App\Models\Address;
use App\MoonShine\Resources\Address\Pages\AddressIndexPage;
use App\MoonShine\Resources\Address\Pages\AddressFormPage;
use App\MoonShine\Resources\Address\Pages\AddressDetailPage;
use App\MoonShine\Resources\Country\CountryResource;
use App\MoonShine\Resources\Region\RegionResource;
use App\MoonShine\Resources\City\CityResource;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Actions\FiltersAction;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\MorphTo;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;

/**
 * @extends ModelResource<Address, AddressIndexPage, AddressFormPage, AddressDetailPage>
 */
class AddressResource extends ModelResource
{
    protected string $model = Address::class;

    public string $title = 'Адреса';
    public static string $subTitle = 'Управление адресами';

    public function fields(): array
    {
        return [
            ID::make()->sortable()->showOnExport(),
            
            MorphTo::make('Владелец', 'addressable')
                ->types([
                    \App\Models\User::class => 'Пользователь',
                    \App\Models\Company::class => 'Компания',
                    \App\Models\Warehouse::class => 'Склад',
                ])
                ->nullable()
                ->searchable()
                ->showOnExport(),
            
            BelongsTo::make('Страна', 'country', fn($item) => $item->name, resource: CountryResource::class)
                ->required()
                ->searchable()
                ->showOnExport(),
            
            BelongsTo::make('Регион', 'region', fn($item) => $item->name, resource: RegionResource::class)
                ->nullable()
                ->searchable()
                ->showOnExport(),
            
            BelongsTo::make('Город', 'city', fn($item) => $item->name, resource: CityResource::class)
                ->nullable()
                ->searchable()
                ->showOnExport(),
            
            Text::make('Улица', 'street')
                ->nullable()
                ->showOnExport(),
            
            Text::make('Дом', 'house')
                ->nullable()
                ->showOnExport(),
            
            Text::make('Корпус/строение', 'building')
                ->nullable()
                ->showOnExport(),
            
            Text::make('Квартира/офис', 'apartment')
                ->nullable()
                ->showOnExport(),
            
            Text::make('Этаж', 'floor')
                ->nullable()
                ->showOnExport(),
            
            Text::make('Подъезд', 'entrance')
                ->nullable()
                ->showOnExport(),
            
            Text::make('Почтовый индекс', 'postal_code')
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
            
            Text::make('Дополнительная информация', 'additional_info')
                ->nullable()
                ->hideOnIndex()
                ->showOnExport(),
            
            Text::make('Ориентир', 'landmark')
                ->nullable()
                ->showOnExport(),
            
            Select::make('Тип адреса', 'address_type')
                ->options(Address::TYPES)
                ->default('home')
                ->showOnExport(),
            
            Text::make('Название', 'name')
                ->nullable()
                ->showOnExport()
                ->hint('Например: "Мой дом", "Офис"'),
            
            Text::make('Описание', 'description')
                ->nullable()
                ->hideOnIndex()
                ->showOnExport(),
            
            Switcher::make('Основной', 'is_primary')
                ->default(false)
                ->showOnExport(),
            
            Switcher::make('Активен', 'is_active')
                ->default(true)
                ->showOnExport(),
            
            Switcher::make('Подтвержден', 'is_verified')
                ->default(false)
                ->showOnExport(),
            
            Switcher::make('Публичный', 'is_public')
                ->default(true)
                ->showOnExport(),
        ];
    }

    public function rules($item): array
    {
        return [
            'addressable_id' => ['required'],
            'addressable_type' => ['required', 'string'],
            'country_id' => ['required', 'exists:countries,id'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'street' => ['nullable', 'string', 'max:255'],
            'house' => ['nullable', 'string', 'max:50'],
            'building' => ['nullable', 'string', 'max:50'],
            'apartment' => ['nullable', 'string', 'max:50'],
            'floor' => ['nullable', 'string', 'max:10'],
            'entrance' => ['nullable', 'string', 'max:10'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'additional_info' => ['nullable', 'string'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'address_type' => ['required', 'in:' . implode(',', array_keys(Address::TYPES))],
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
            'is_verified' => ['boolean'],
            'is_public' => ['boolean'],
        ];
    }

    public function search(): array
    {
        return [
            'id',
            'street',
            'house',
            'postal_code',
            'name',
        ];
    }

    public function filters(): array
    {
        return [
            BelongsTo::make('Страна', 'country', fn($item) => $item->name, resource: CountryResource::class)
                ->nullable()
                ->searchable(),
            
            BelongsTo::make('Город', 'city', fn($item) => $item->name, resource: CityResource::class)
                ->nullable()
                ->searchable(),
            
            Select::make('Тип адреса', 'address_type')
                ->options(Address::TYPES)
                ->nullable(),
            
            Switcher::make('Основной', 'is_primary'),
            Switcher::make('Активен', 'is_active'),
            Switcher::make('Подтвержден', 'is_verified'),
        ];
    }

    public function actions(): array
    {
        return [
            FiltersAction::make(trans('moonshine::ui.filters')),
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            
            Text::make('Владелец', function ($item) {
                if (!$item->addressable) {
                    return '-';
                }
                
                $type = match ($item->addressable_type) {
                    \App\Models\User::class => '👤',
                    \App\Models\Company::class => '🏢',
                    \App\Models\Warehouse::class => '📦',
                    default => '📝',
                };
                
                return $type . ' ' . ($item->addressable->name ?? $item->addressable->id);
            }),
            
            Text::make('Адрес', function ($item) {
                return $item->short_address;
            })->searchable(),
            
            Text::make('Тип', 'address_type')
                ->badge(fn($value) => match ($value) {
                    'home' => 'blue',
                    'work' => 'green',
                    'shipping' => 'orange',
                    'billing' => 'purple',
                    default => 'gray',
                }),
            
            Text::make('Город', function ($item) {
                return $item->city?->name ?? '-';
            }),
            
            Switcher::make('Основной', 'is_primary')
                ->updateOnPreview()
                ->badge(fn($status, $field) => $status ? 'green' : 'gray'),
            
            Switcher::make('Активен', 'is_active')
                ->updateOnPreview()
                ->badge(fn($status, $field) => $status ? 'green' : 'red'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Адрес';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Адреса';
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            AddressIndexPage::class,
            AddressFormPage::class,
            AddressDetailPage::class,
        ];
    }
}
