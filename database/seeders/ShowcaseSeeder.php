<?php

namespace Database\Seeders;

use App\Modules\Accommodation\Database\Seeders\ShowcaseStaySeeder;
use App\Modules\Activities\Database\Seeders\ShowcaseExperienceSeeder;
use App\Modules\Booking\Database\Seeders\ShowcaseBookingSeeder;
use App\Modules\Booking\Database\Seeders\ShowcaseHostBookingSeeder;
use App\Modules\Catalog\Database\Seeders\AmenitySeeder;
use App\Modules\Catalog\Database\Seeders\LuxuryAmenitySeeder;
use App\Modules\Destinations\Database\Seeders\ShowcaseDestinationSeeder;
use App\Modules\Identity\Database\Seeders\ShowcaseAccountSeeder;
use App\Modules\Media\Database\Seeders\ShowcaseReelSeeder;
use App\Modules\Packages\Database\Seeders\ShowcasePackageSeeder;
use App\Modules\Providers\Database\Seeders\ShowcaseProviderSeeder;
use App\Modules\Wishlist\Database\Seeders\ShowcaseWishlistSeeder;
use Illuminate\Database\Seeder;

/**
 * Showcase content for the redesigned public pages and the signed-in feed.
 *
 * Additive and idempotent: it never truncates or deletes, and every module
 * seeder it calls matches on slugs, emails or codes, so it can be run on top
 * of an existing dev database as often as needed:
 *
 *   php artisan db:seed --class="Database\Seeders\ShowcaseSeeder" --force
 *
 * Like DatabaseSeeder, this class only owns the cross-module ORDER; each step
 * lives with the module that owns its tables. It is deliberately not called
 * from DatabaseSeeder.
 */
class ShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AmenitySeeder::class,              // idempotent (firstOrCreate); stays need amenities
            LuxuryAmenitySeeder::class,        // full-service hotel facilities and in-room touches
            ShowcaseAccountSeeder::class,      // demo traveller, guest profiles, reviewer
            ShowcaseProviderSeeder::class,     // host brands that own the listings
            ShowcaseDestinationSeeder::class,
            ShowcaseStaySeeder::class,         // hotels, villas, B&Bs -> published accommodations
            ShowcaseExperienceSeeder::class,   // activities + future sessions
            ShowcaseReelSeeder::class,         // reels targeting the stays/activities above
            ShowcaseBookingSeeder::class,      // trips for verified travellers
            ShowcaseHostBookingSeeder::class,  // guests across every stay, for host dashboards
            ShowcasePackageSeeder::class,      // a verified agent, 3 journeys from the listings above, package bookings
            ShowcaseWishlistSeeder::class,     // saved stays for verified travellers
        ]);
    }
}
