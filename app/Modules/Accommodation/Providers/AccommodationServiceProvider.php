<?php

namespace App\Modules\Accommodation\Providers;

use App\Modules\Accommodation\Domain\Policies\AccommodationPolicy;
use App\Modules\Accommodation\Domain\Policies\BeadandBreakfastPolicy;
use App\Modules\Accommodation\Domain\Policies\HotelPolicy;
use App\Modules\Accommodation\Domain\Policies\RoomPolicy;
use App\Modules\Accommodation\Domain\Policies\RoomPricePolicy;
use App\Modules\Accommodation\Domain\Policies\RoomTypePolicy;
use App\Modules\Accommodation\Domain\Policies\VillaPolicy;
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
class AccommodationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(App\Modules\Accommodation\Domain\Models\Accommodation::class, AccommodationPolicy::class);
        Gate::policy(App\Modules\Accommodation\Domain\Models\BedAndBreakfast::class, BeadandBreakfastPolicy::class);
        Gate::policy(App\Modules\Accommodation\Domain\Models\Hotel::class, HotelPolicy::class);
        Gate::policy(App\Modules\Accommodation\Domain\Models\Room::class, RoomPolicy::class);
        Gate::policy(App\Modules\Accommodation\Domain\Models\RoomPrice::class, RoomPricePolicy::class);
        Gate::policy(App\Modules\Accommodation\Domain\Models\RoomType::class, RoomTypePolicy::class);
        Gate::policy(App\Modules\Accommodation\Domain\Models\Villa::class, VillaPolicy::class);
    }
}
