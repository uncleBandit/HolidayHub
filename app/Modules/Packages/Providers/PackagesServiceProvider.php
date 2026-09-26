<?php

namespace App\Modules\Packages\Providers;

use App\Modules\Packages\Domain\Policies\PackageFeaturePolicy;
use App\Modules\Packages\Domain\Policies\PackagePolicy;
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
class PackagesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(App\Modules\Packages\Domain\Models\Package::class, PackagePolicy::class);
        Gate::policy(App\Modules\Packages\Domain\Models\PackageFeature::class, PackageFeaturePolicy::class);
    }
}
