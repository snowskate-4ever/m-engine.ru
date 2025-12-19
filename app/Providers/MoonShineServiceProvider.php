<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShine;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\Type\TypeResource;
use App\MoonShine\Resources\Resource\ResourceResource;
use App\MoonShine\Resources\Communication\CommunicationResource;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\Social\SocialResource;
use App\MoonShine\Resources\Event\EventResource;
use App\MoonShine\Resources\Manufacturer\ManufacturerResource;
use App\MoonShine\Resources\Room\RoomResource;
use App\MoonShine\Resources\Hardware\HardwareResource;
use App\MoonShine\Resources\Good\GoodResource;
use App\MoonShine\Resources\Category\CategoryResource;
use App\MoonShine\Resources\GoodCategory\GoodCategoryResource;
use App\MoonShine\Resources\Country\CountryResource;
use App\MoonShine\Resources\Region\RegionResource;
use App\MoonShine\Resources\City\CityResource;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                TypeResource::class,
                ResourceResource::class,
                CommunicationResource::class,
                UserResource::class,
                SocialResource::class,
                EventResource::class,
                ManufacturerResource::class,
                RoomResource::class,
                HardwareResource::class,
                GoodResource::class,
                CategoryResource::class,
                GoodCategoryResource::class,
                CountryResource::class,
                RegionResource::class,
                CityResource::class,
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
            ])
        ;
    }
}
