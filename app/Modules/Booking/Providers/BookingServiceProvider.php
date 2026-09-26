<?php

namespace App\Modules\Booking\Providers;

use App\Modules\Booking\Domain\Policies\BookingPolicy;
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
class BookingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(App\Modules\Booking\Domain\Models\Booking::class, BookingPolicy::class);
    }
}
