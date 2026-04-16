<?php

namespace App\Providers;

use App\Contracts\Billing\PaymentGatewayContract;
use App\Contracts\PlatformPayments\PlatformAcquiringDriverContract;
use App\Listeners\LogNotificationFailed;
use App\Listeners\RecordQueueJobFailedMetric;
use App\Listeners\Notifications\BroadcastDatabaseNotification;
use App\Listeners\Notifications\MirrorDatabaseNotificationToSystemChat;
use App\Models\ConcertVenue;
use App\Models\Event;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\PublicProfileReport;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\Studio;
use App\Models\Teacher;
use App\Observers\EventObserver;
use App\Observers\MusicProfileModerationAuditObserver;
use App\Observers\PublicMusicCatalogCacheObserver;
use App\Observers\PublicProfileReportAuditObserver;
use App\Observers\ShopOrderObserver;
use App\Policies\ConcertVenuePolicy;
use App\Policies\MusicianPolicy;
use App\Policies\PeformerPolicy;
use App\Policies\ProducerCenterPolicy;
use App\Policies\RecordLabelPolicy;
use App\Policies\RehersalPolicy;
use App\Policies\SchoolPolicy;
use App\Policies\ShopOrderPolicy;
use App\Policies\ShopPolicy;
use App\Policies\StudioPolicy;
use App\Policies\TeacherPolicy;
use App\Services\Billing\StubBillingPaymentGateway;
use App\Services\Billing\YooKassaPaymentGateway;
use App\Services\PlatformPayments\StubPlatformAcquiringDriver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayContract::class, function ($app) {
            return match ((string) config('billing.payment_gateway', 'stub')) {
                'yookassa' => $app->make(YooKassaPaymentGateway::class),
                default => $app->make(StubBillingPaymentGateway::class),
            };
        });

        $this->app->singleton(PlatformAcquiringDriverContract::class, function ($app) {
            return match ((string) config('platform_payments.driver', 'stub')) {
                default => $app->make(StubPlatformAcquiringDriver::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Увеличиваем лимит выполнения для тяжёлых страниц (login с Flux-компонентами и т.д.)
        if (php_sapi_name() !== 'cli') {
            set_time_limit(90);
        }
        Event::observe(EventObserver::class);
        ShopOrder::observe(ShopOrderObserver::class);

        $publicMusicProfileModels = [
            Musician::class,
            Teacher::class,
            Peformer::class,
            Studio::class,
            Rehersal::class,
            ConcertVenue::class,
            School::class,
            RecordLabel::class,
            ProducerCenter::class,
            Shop::class,
        ];
        foreach ($publicMusicProfileModels as $modelClass) {
            $modelClass::observe(PublicMusicCatalogCacheObserver::class);
            $modelClass::observe(MusicProfileModerationAuditObserver::class);
        }
        PublicProfileReport::observe(PublicProfileReportAuditObserver::class);

        Gate::policy(Musician::class, MusicianPolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(Peformer::class, PeformerPolicy::class);
        Gate::policy(Studio::class, StudioPolicy::class);
        Gate::policy(Rehersal::class, RehersalPolicy::class);
        Gate::policy(ConcertVenue::class, ConcertVenuePolicy::class);
        Gate::policy(School::class, SchoolPolicy::class);
        Gate::policy(RecordLabel::class, RecordLabelPolicy::class);
        Gate::policy(ProducerCenter::class, ProducerCenterPolicy::class);
        Gate::policy(Shop::class, ShopPolicy::class);
        Gate::policy(ShopOrder::class, ShopOrderPolicy::class);

        EventFacade::listen(NotificationSent::class, BroadcastDatabaseNotification::class);
        EventFacade::listen(NotificationSent::class, MirrorDatabaseNotificationToSystemChat::class);
        EventFacade::listen(NotificationFailed::class, LogNotificationFailed::class);
        EventFacade::listen(JobFailed::class, RecordQueueJobFailedMetric::class);

        RateLimiter::for('integration', function (Request $request): Limit {
            $token = $request->attributes->get('integration_api_token');
            $perMinute = $token?->rate_limit_per_minute
                ?? (int) config('integration.default_rate_limit_per_minute', 120);

            return Limit::perMinute(max(10, min(6000, $perMinute)))->by(
                $token ? 'int_token:'.$token->id : 'int_ip:'.$request->ip(),
            );
        });

        RateLimiter::for('integration-webhook', function (Request $request): Limit {
            $perMinute = (int) config('integration.webhooks_rate_limit_per_minute', 120);

            return Limit::perMinute(max(1, min(6000, $perMinute)))->by(
                'int_webhook:'.$request->ip(),
            );
        });
    }
}
