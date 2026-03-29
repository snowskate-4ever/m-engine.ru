<?php

namespace App\Providers;

use App\Contracts\Billing\PaymentGatewayContract;
use App\Models\Event;
use App\Observers\EventObserver;
use App\Services\Billing\StubBillingPaymentGateway;
use App\Services\Billing\YooKassaPaymentGateway;
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
    }
}
