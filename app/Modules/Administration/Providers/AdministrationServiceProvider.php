<?php

namespace App\Modules\Administration\Providers;

use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Administration\Domain\Policies\TenantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Administration module's authorisation.
 *
 * The Filament panel provider is registered in bootstrap/providers.php rather
 * than here: Filament builds panel routes during its own boot, which happens
 * before module providers are discovered, so registering the panel from a
 * module service provider is already too late. See bootstrap/providers.php.
 */
class AdministrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Tenant::class, TenantPolicy::class);
    }
}
