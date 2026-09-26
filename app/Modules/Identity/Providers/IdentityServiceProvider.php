<?php

namespace App\Modules\Identity\Providers;

use App\Modules\Identity\Domain\Models\Guest;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Policies\GuestPolicy;
use App\Modules\Identity\Domain\Policies\UserPolicy;
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
class IdentityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Guest::class, GuestPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
