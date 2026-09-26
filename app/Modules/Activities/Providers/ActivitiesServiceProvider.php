<?php

namespace App\Modules\Activities\Providers;

use App\Modules\Activities\Domain\Policies\ActivityPolicy;
use App\Modules\Activities\Domain\Policies\ActivityReviewPolicy;
use App\Modules\Activities\Domain\Policies\ExperiencePolicy;
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
class ActivitiesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(App\Modules\Activities\Domain\Models\Activity::class, ActivityPolicy::class);
        Gate::policy(App\Modules\Activities\Domain\Models\Activity::class, ActivityReviewPolicy::class);
        Gate::policy(App\Modules\Activities\Domain\Models\Experience::class, ExperiencePolicy::class);
    }
}
