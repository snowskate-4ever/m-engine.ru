<?php

namespace App\Providers;

use App\Contracts\Billing\PaymentGatewayContract;
use App\Models\Event;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Models\Teacher;
use App\Observers\EventObserver;
use App\Policies\MusicianPolicy;
use App\Policies\PeformerPolicy;
use App\Policies\RehersalPolicy;
use App\Policies\SchoolPolicy;
use App\Policies\StudioPolicy;
use App\Policies\TeacherPolicy;
use App\Services\Billing\StubBillingPaymentGateway;
use App\Services\Billing\YooKassaPaymentGateway;
use Illuminate\Support\Facades\Gate;
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

        Gate::policy(Musician::class, MusicianPolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(Peformer::class, PeformerPolicy::class);
        Gate::policy(Studio::class, StudioPolicy::class);
        Gate::policy(Rehersal::class, RehersalPolicy::class);
        Gate::policy(School::class, SchoolPolicy::class);
    }
}
