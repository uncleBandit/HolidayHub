<?php

namespace App\Modules\Reviews\Providers;

use App\Modules\Reviews\Domain\Policies\ReviewPolicy;
use App\Modules\Reviews\Domain\Policies\TestimonialPolicy;
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
class ReviewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(App\Modules\Reviews\Domain\Models\Review::class, ReviewPolicy::class);
        Gate::policy(App\Modules\Reviews\Domain\Models\Review::class, TestimonialPolicy::class);
    }
}
