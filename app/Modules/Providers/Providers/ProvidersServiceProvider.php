<?php

namespace App\Modules\Providers\Providers;

use App\Modules\Providers\Domain\Policies\ProviderPolicy;
use App\Modules\Providers\Domain\Policies\ServicePolicy;
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
class ProvidersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(App\Modules\Providers\Domain\Models\Provider::class, ProviderPolicy::class);
        Gate::policy(App\Modules\Providers\Domain\Models\Service::class, ServicePolicy::class);
    }
}
