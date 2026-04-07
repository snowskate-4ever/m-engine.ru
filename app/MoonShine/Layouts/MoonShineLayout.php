<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Resources\Address\AddressResource;
use App\MoonShine\Resources\Ai\AiProviderResource;
use App\MoonShine\Resources\Ai\AiRequestLogResource;
use App\MoonShine\Resources\Ai\AiServerModelResource;
use App\MoonShine\Resources\Ai\AiSubscriptionTierResource;
use App\MoonShine\Resources\Ai\AgentToolInvocationResource;
use App\MoonShine\Resources\Ai\AiUsageLedgerResource;
use App\MoonShine\Resources\Ai\UserAiSubscriptionResource;
use App\MoonShine\Resources\Category\CategoryResource;
use App\MoonShine\Resources\City\CityResource;
use App\MoonShine\Resources\Communication\CommunicationResource;
use App\MoonShine\Resources\Country\CountryResource;
use App\MoonShine\Resources\Event\EventResource;
use App\MoonShine\Resources\Genre\GenreResource;
use App\MoonShine\Resources\Good\GoodResource;
use App\MoonShine\Resources\GoodCategory\GoodCategoryResource;
use App\MoonShine\Resources\Hardware\HardwareResource;
use App\MoonShine\Resources\Instrument\InstrumentResource;
use App\MoonShine\Resources\Manufacturer\ManufacturerResource;
use App\MoonShine\Resources\Messenger\MessengerConversationResource;
use App\MoonShine\Resources\Messenger\MessengerMessageResource;
use App\MoonShine\Resources\Region\RegionResource;
use App\MoonShine\Resources\Resource\ResourceResource;
use App\MoonShine\Resources\Room\RoomResource;
use App\MoonShine\Resources\Social\SocialResource;
use App\MoonShine\Resources\Type\TypeResource;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\ColorManager\ColorManager;
use MoonShine\ColorManager\Palettes\PurplePalette;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\Laravel\Resources\MoonShineUserResource;
use MoonShine\Laravel\Resources\MoonShineUserRoleResource;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;

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
            MenuGroup::make('Географические данные', [
                MenuItem::make(CountryResource::class)->icon('adjustments-horizontal'),
                MenuItem::make(RegionResource::class)->icon('adjustments-horizontal'),
                MenuItem::make(CityResource::class)->icon('adjustments-horizontal'),
                MenuItem::make(AddressResource::class)->icon('adjustments-horizontal'),
            ])->icon('adjustments-horizontal'),
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
            MenuGroup::make('Музыка', [
                MenuItem::make(InstrumentResource::class)->icon('musical-note'),
                MenuItem::make(GenreResource::class)->icon('musical-note'),
            ])->icon('musical-note'),
            MenuGroup::make(static fn () => __('moonshine.messenger.menu_group'), [
                MenuItem::make(MessengerConversationResource::class)->icon('chat-bubble-left-right'),
                MenuItem::make(MessengerMessageResource::class)->icon('inbox'),
            ])->icon('chat-bubble-left-right'),
            MenuGroup::make(static fn () => __('moonshine.ai.menu_group'), [
                MenuItem::make(AiProviderResource::class)->icon('server'),
                MenuItem::make(AiServerModelResource::class)->icon('cube'),
                MenuItem::make(AiSubscriptionTierResource::class)->icon('rectangle-stack'),
                MenuItem::make(UserAiSubscriptionResource::class)->icon('credit-card'),
                MenuItem::make(AiRequestLogResource::class)->icon('cpu-chip'),
                MenuItem::make(AiUsageLedgerResource::class)->icon('chart-bar'),
                MenuItem::make(AgentToolInvocationResource::class)->icon('command-line'),
            ])->icon('cpu-chip'),
        ];
    }

    public static function dashboard(): array
    {
        return [
            DashboardScreen::make()
                ->title('Панель управления')
                ->blocks([
                    DashboardBlock::make([
                        ValueMetric::make('Всего стран')
                            ->value(fn () => \App\Models\Country::count())
                            ->progress(fn () => \App\Models\Country::where('is_active', true)->count())
                            ->columnSpan(6),

                        ValueMetric::make('Всего регионов')
                            ->value(fn () => \App\Models\Region::count())
                            ->progress(fn () => \App\Models\Region::where('is_active', true)->count())
                            ->columnSpan(6),

                        ValueMetric::make('Всего городов')
                            ->value(fn () => \App\Models\City::count())
                            ->progress(fn () => \App\Models\City::where('is_active', true)->count())
                            ->columnSpan(6),

                        ValueMetric::make('Столицы')
                            ->value(fn () => \App\Models\City::where('is_capital', true)->count())
                            ->columnSpan(6),
                    ]),

                    DashboardBlock::make([
                        ResourcePreview::make(
                            new CountryResource,
                            'Последние добавленные страны',
                            \App\Models\Country::latest()->limit(5)
                        )->columnSpan(12),
                    ]),

                    DashboardBlock::make([
                        ResourcePreview::make(
                            new CityResource,
                            'Крупнейшие города по населению',
                            \App\Models\City::whereNotNull('population')->orderBy('population', 'desc')->limit(10)
                        )->columnSpan(12),
                    ]),
                ]),
        ];
    }

    /**
     * @param  ColorManager  $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }
}
