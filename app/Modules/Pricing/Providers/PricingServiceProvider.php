<?php

namespace App\Modules\Pricing\Providers;

use App\Modules\Pricing\Application\Services\PricingEngine;
use App\Modules\Pricing\Domain\Models\SeasonalRate;
use App\Modules\Pricing\Domain\Policies\SeasonalRatePolicy;
use App\Shared\Domain\Contracts\NightlyPricer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Registers this module's authorization policies and its pricing contract.
 *
 * Laravel's policy auto-discovery only scans app/Policies, so once policies
 * live inside modules they must be bound explicitly. Without this, Gate falls
 * back to allowing everything for the affected models, which is a silent
 * authorization failure rather than a visible error.
 */
class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Lets consumers such as the Availability module's booking calendar ask for
        // a nightly price without importing this module, which would put the
        // dependency back the way it was before PricingEngine was extracted.
        $this->app->bind(NightlyPricer::class, PricingEngine::class);
    }

    public function boot(): void
    {
        Gate::policy(SeasonalRate::class, SeasonalRatePolicy::class);
    }
}
