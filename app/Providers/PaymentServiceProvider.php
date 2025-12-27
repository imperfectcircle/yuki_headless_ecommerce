<?php

namespace App\Providers;

use App\Domains\Payments\Paypal\PaypalPaymentProvider;
use App\Domains\Payments\Resolvers\PaymentProviderResolver;
use App\Domains\Payments\Stripe\StripePaymentProvider;
use Illuminate\Support\ServiceProvider;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentProviderResolver::class, function (){
            return new PaymentProviderResolver([
                'stripe' => StripePaymentProvider::class,
                'paypal' => PaypalPaymentProvider::class,
            ]);
        });

        $this->app->singleton(PayPalHttpClient::class, function () {
            $env = config('services.paypal.mode') === 'live'
                ? new ProductionEnvironment(
                    config('services.paypal.client_id'),
                    config('services.paypal.secret'),
                )
                : new SandboxEnvironment(
                    config('services.paypal.client_id'),
                    config('services.paypal.secret'),
                );

            return new PayPalHttpClient($env);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
