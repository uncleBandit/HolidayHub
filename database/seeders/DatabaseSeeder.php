<?php

namespace Database\Seeders;

use App\Modules\Activities\Database\Seeders\ActivitySeeder;
use App\Modules\Administration\Database\Seeders\TenantSeeder;
use App\Modules\Agents\Database\Seeders\AgentSeeder;
use App\Modules\Booking\Database\Seeders\BookingSeeder;
use App\Modules\Catalog\Database\Seeders\AmenitySeeder;
use App\Modules\Catalog\Database\Seeders\OfferSeeder;
use App\Modules\Destinations\Database\Seeders\DestinationSeeder;
use App\Modules\Identity\Database\Seeders\AdminSeeder;
use App\Modules\Identity\Database\Seeders\GuestSeeder;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Providers\Database\Seeders\ProviderSeeder;
use App\Modules\Reviews\Database\Seeders\ReviewSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Cross-module seed orchestrator.
 *
 * Every seeder itself lives with the module that owns the tables it populates,
 * under App\Modules\<Module>\Database\Seeders. This class stays at the
 * application level on purpose: it is the only place that knows the global
 * seeding ORDER, and that order is a real cross-module constraint (roles before
 * admins, amenities before destinations, guests before bookings, bookings before
 * reviews).
 *
 * It deliberately does not let ModuleRegistry decide the order. Registry
 * discovery order is alphabetical, which would seed bookings before the guests
 * and destinations they depend on.
 *
 * To seed one module in isolation: php artisan module:seed <module-id>
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $this->logSection('Identity (roles, guests, admin)');
            $this->call(RoleSeeder::class);
            $this->call(GuestSeeder::class);
            $this->call(AdminSeeder::class);

            $this->logSection('Providers');
            $this->call(ProviderSeeder::class);

            $this->logSection('Agents');
            $this->call(AgentSeeder::class);

            $this->logSection('Amenities');
            $this->call(AmenitySeeder::class);

            $this->logSection('Destinations');
            $this->call(DestinationSeeder::class);

            $this->logSection('Bookings & Reviews');
            $this->call(BookingSeeder::class);
            $this->call(ReviewSeeder::class);

            $this->logSection('Offers');
            $this->call(OfferSeeder::class);

            $this->logSection('Activities');
            $this->call(ActivitySeeder::class);

            // After providers and agents: a tenant is an application *from* one
            // of those profiles, so it cannot be seeded before they exist.
            $this->logSection('Tenants (verification queue)');
            $this->call(TenantSeeder::class);

            $this->command->info('✅ Database seeded successfully with realistic holiday booking data!');
        } catch (\Throwable $e) {
            Log::error('DatabaseSeeder failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function logSection(string $name): void
    {
        $this->command->info("🔹 Seeding {$name}...");
        Log::info("Seeding section: {$name}");
    }
}
