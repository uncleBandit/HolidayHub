<?php

namespace App\Modules\Wishlist\Providers;

use App\Modules\Wishlist\Domain\Policies\WishlistPolicy;
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
class WishlistServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(App\Modules\Wishlist\Domain\Models\Wishlist::class, WishlistPolicy::class);
    }
}
