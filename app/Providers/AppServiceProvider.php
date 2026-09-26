<?php

namespace App\Providers;

use App\Modules\Booking\Application\Services\PolicyEngine;
use App\Modules\Payments\Domain\Contracts\PaymentGateway;
use App\Modules\Payments\Infrastructure\Gateways\NullPaymentGateway;
use App\Modules\Payments\Infrastructure\Gateways\StripeGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Middleware\RoleMiddleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PolicyEngine as a singleton
        $this->app->singleton(PolicyEngine::class, function ($app) {
            return new PolicyEngine;
        });

        if ($this->app->environment('local')) {
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }

        // Bind PaymentGateway interface to StripeGateway implementation
        $this->app->bind(PaymentGateway::class, function ($app) {
            if (env('BOOKING_TEST_MODE', true)) {
                return new NullPaymentGateway;
            }

            return new StripeGateway; // your real gateway
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Model::preventLazyLoading(true);

        // Register Spatie role middleware
        Route::aliasMiddleware('role', RoleMiddleware::class);

        // The polymorphic morph map is no longer declared here.
        //
        // It used to live in this method, which meant two authorities owned it:
        // this hardcoded list and the per-module `morph_map` in each
        // module.json. ModulesServiceProvider::registerMorphMap() runs later and
        // replaced the whole map, so every alias below was silently discarded
        // and only the manifest-declared ones survived. Each alias now lives in
        // the manifest of the module that owns the model, so a module that moves
        // or renames a model updates one file it already owns.
        // See App\Providers\ModulesServiceProvider::registerMorphMap().

    }
}
