<?php

namespace App\Providers;

use App\Services\Engines\PolicyEngine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\Gateways\StripeGateway;
use Livewire\Volt\Volt;
use App\Services\Payments\NullPaymentGateway;





class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PolicyEngine as a singleton
        $this->app->singleton(PolicyEngine::class, function ($app) {
            return new PolicyEngine();
        });

        if ($this->app->environment('local')) {
        $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }

        // Bind PaymentGateway interface to StripeGateway implementation
         $this->app->bind(PaymentGateway::class, function ($app) {
        if (env('BOOKING_TEST_MODE', true)) {
            return new NullPaymentGateway();
        }
        return new StripeGateway(); // your real gateway
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

        Relation::enforceMorphMap([
        'hotel'     => \App\Models\Hotel::class,
        'room_type' => \App\Models\RoomType::class,
        'room'      => \App\Models\Room::class,
        'package'   => \App\Models\Package::class,
        'bed_and_breakfast' => \App\Models\BedAndBreakfast::class,
        'villa'     => \App\Models\Villa::class,
        'review'    => \App\Models\Review::class,
        'user'      => \App\Models\User::class,
        'activity'  => \App\Models\Activity::class,
        'seasonal_rate' => \App\Models\SeasonalRate::class,
        'booking'   => \App\Models\Booking::class,
        'destination' => \App\Models\Destination::class,
        'accommodation' => \App\Models\Accommodation::class,
        // add more bookables here...
    ]);




    }
}
