<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Resources\Address\AddressResource;
use App\MoonShine\Resources\Ai\AgentToolInvocationResource;
use App\MoonShine\Resources\Ai\AiProviderResource;
use App\MoonShine\Resources\Ai\AiRequestLogResource;
use App\MoonShine\Resources\Ai\AiServerModelResource;
use App\MoonShine\Resources\Ai\AiSubscriptionTierResource;
use App\MoonShine\Resources\Ai\AiUsageLedgerResource;
use App\MoonShine\Resources\Ai\UserAiSubscriptionResource;
use App\MoonShine\Resources\AuthAttempt\AuthAttemptResource;
use App\MoonShine\Resources\AuthChannel\AuthChannelResource;
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
use App\MoonShine\Resources\Messenger\MessengerConversationUserResource;
use App\MoonShine\Resources\Messenger\MessengerMessageAttachmentResource;
use App\MoonShine\Resources\Messenger\MessengerMessageResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\MusicEcosystem\ModerationAuditResource;
use App\MoonShine\Resources\MusicEcosystem\MusicianResource;
use App\MoonShine\Resources\MusicEcosystem\MusicSchoolResource;
use App\MoonShine\Resources\MusicEcosystem\MusicStudioResource;
use App\MoonShine\Resources\MusicEcosystem\PeformerResource as MusicPeformerResource;
use App\MoonShine\Resources\MusicEcosystem\ProducerCenterResource as MusicProducerCenterResource;
use App\MoonShine\Resources\MusicEcosystem\PublicProfileReportResource;
use App\MoonShine\Resources\MusicEcosystem\RecordLabelResource as MusicRecordLabelResource;
use App\MoonShine\Resources\MusicEcosystem\RehersalResource;
use App\MoonShine\Resources\MusicEcosystem\TeacherResource as MusicTeacherResource;
use App\MoonShine\Resources\Region\RegionResource;
use App\MoonShine\Resources\Resource\ResourceResource;
use App\MoonShine\Resources\Room\RoomResource;
use App\MoonShine\Resources\Shop\ShopItemResource;
use App\MoonShine\Resources\Shop\ShopOrderItemResource;
use App\MoonShine\Resources\Shop\ShopOrderResource;
use App\MoonShine\Resources\Shop\ShopResource;
use App\MoonShine\Resources\Social\SocialResource;
use App\MoonShine\Resources\Type\TypeResource;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\VkTracking\VkTrackingResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

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
                AddressResource::class,
                InstrumentResource::class,
                GenreResource::class,
                AuthAttemptResource::class,
                AuthChannelResource::class,
                VkTrackingResource::class,
                MessengerConversationResource::class,
                MessengerMessageResource::class,
                MessengerConversationUserResource::class,
                MessengerMessageAttachmentResource::class,
                AiProviderResource::class,
                AiServerModelResource::class,
                AiRequestLogResource::class,
                AiSubscriptionTierResource::class,
                UserAiSubscriptionResource::class,
                AiUsageLedgerResource::class,
                AgentToolInvocationResource::class,
                ShopOrderResource::class,
                ShopResource::class,
                ShopItemResource::class,
                ShopOrderItemResource::class,
                MusicianResource::class,
                MusicTeacherResource::class,
                MusicPeformerResource::class,
                MusicStudioResource::class,
                RehersalResource::class,
                MusicSchoolResource::class,
                MusicRecordLabelResource::class,
                MusicProducerCenterResource::class,
                PublicProfileReportResource::class,
                ModerationAuditResource::class,
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
            ]);
    }
}
