<?php

namespace App\Modules\Audit\Providers;

use App\Modules\Audit\Domain\Policies\AuditLogPolicy;
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
class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(App\Modules\Audit\Domain\Models\AuditLog::class, AuditLogPolicy::class);
    }
}
