<?php

namespace App\Modules\Destinations\Providers;

use App\Modules\Destinations\Domain\Policies\DestinationPolicy;
use App\Modules\Destinations\Domain\Policies\DestinationReviewPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Registers this module's authorization policies.
 *
 * Laravel's policy auto-discovery only scans app/Policies, so once policies
 * live inside modules they must be bound explicitly. Without this, Gate falls
 * back to allowing everything for the affected models, which is a silent
 * authorization failure rather than a visible error.
 */
class DestinationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(App\Modules\Destinations\Domain\Models\Destination::class, DestinationPolicy::class);
        Gate::policy(App\Modules\Destinations\Domain\Models\Destination::class, DestinationReviewPolicy::class);
    }
}
