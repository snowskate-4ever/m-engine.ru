<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\Palettes\PurplePalette;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use App\MoonShine\Resources\Type\TypeResource;
use MoonShine\MenuManager\MenuItem;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\Laravel\Resources\MoonShineUserResource;
use MoonShine\Laravel\Resources\MoonShineUserRoleResource;
use App\MoonShine\Resources\Resource\ResourceResource;
use App\MoonShine\Resources\Communication\CommunicationResource;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\Social\SocialResource;
use App\MoonShine\Resources\Event\EventResource;
use App\MoonShine\Resources\Place\PlaceResource;
use App\MoonShine\Resources\Dood\DoodResource;
use App\MoonShine\Resources\Manufacturer\ManufacturerResource;
use App\MoonShine\Resources\Room\RoomResource;
use App\MoonShine\Resources\Hardware\HardwareResource;
use App\MoonShine\Resources\Good\GoodResource;
use App\MoonShine\Resources\Category\CategoryResource;
use App\MoonShine\Resources\GoodCategory\GoodCategoryResource;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = PurplePalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    protected function menu(): array
    {
        return [
            MenuGroup::make(static fn () => __('moonshine::ui.resource.system'), [
                MenuItem::make(MoonShineUserResource::class),
                MenuItem::make(MoonShineUserRoleResource::class),
                MenuItem::make(TypeResource::class)->icon('adjustments-horizontal'),
                MenuItem::make(UserResource::class),
                MenuItem::make(SocialResource::class),
            ]),
            MenuGroup::make(static fn () => __('moonshine.system.catalog'), [
                MenuItem::make(GoodResource::class),
                MenuItem::make(ManufacturerResource::class),
                MenuItem::make(CategoryResource::class),
                MenuItem::make(GoodCategoryResource::class),
            ]),
            MenuGroup::make(static fn () => __('moonshine.system.rent'), [
                MenuItem::make(EventResource::class),
                MenuItem::make(RoomResource::class),
                MenuItem::make(HardwareResource::class),
            ]),
            MenuItem::make(ResourceResource::class),
            MenuItem::make(CommunicationResource::class),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }
}
