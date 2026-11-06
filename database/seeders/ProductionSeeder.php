<?php

namespace Database\Seeders;

use App\Modules\Activities\Database\Seeders\ExperienceSeeder;
use App\Modules\Activities\Domain\Models\ActivityCategory;
use App\Modules\Catalog\Database\Seeders\AmenitySeeder;
use App\Modules\Catalog\Database\Seeders\LuxuryAmenitySeeder;
use App\Modules\Identity\Database\Seeders\AdminSeeder;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use Illuminate\Database\Seeder;

/**
 * What a live platform needs and nothing else: roles and admin permissions,
 * the amenity catalogue the listing wizards offer, and the experience
 * categories. No demo listings, guests or bookings (that is DatabaseSeeder).
 *
 * Safe to run on every deploy: every seeder here only adds what is missing.
 * The first admin account is created when ADMIN_EMAIL and ADMIN_PASSWORD are
 * set (see AdminSeeder).
 *
 *   php artisan db:seed --class=Database\\Seeders\\ProductionSeeder --force
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        // Also seeds the admin roles and permissions (AdministrationAccessSeeder).
        $this->call(AdminSeeder::class);

        $this->call(AmenitySeeder::class);
        $this->call(LuxuryAmenitySeeder::class);

        foreach (ExperienceSeeder::CATEGORIES as $slug => $attributes) {
            ActivityCategory::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $attributes['name'], 'sort_order' => $attributes['sort_order'], 'is_active' => true],
            );
        }
    }
}
