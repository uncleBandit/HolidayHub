<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The MVP demo environment's data: everything a live platform needs
 * (ProductionSeeder: roles, admin permissions, the first admin, amenities,
 * categories) plus the curated showcase (ShowcaseSeeder: hosts, stays,
 * experiences, reels, bookings, journeys, wishlists).
 *
 * Deploys run it through `php artisan demo:seed`, which only seeds an empty
 * database unless told to reset, so redeploying never duplicates the demo.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductionSeeder::class,
            ShowcaseSeeder::class,
        ]);
    }
}
