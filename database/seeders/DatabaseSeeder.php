<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $this->logSection('Roles');
            $this->call(RoleSeeder::class);

            $this->logSection('Admin');
            $admin = $this->seedAdmin();

             $this->logSection('Providers');
            $this->call(ProviderSeeder::class);

            $this->logSection('Agents');
            $this->call(AgentSeeder::class);

            $this->logSection('Amenities');
            $this->call(AmenitySeeder::class);

            $this->logSection('Destinations');
            $this->call(DestinationSeeder::class);

            $this->logSection('Guests');
            $this->call(GuestSeeder::class);

            $this->logSection('Bookings & Reviews');
            $this->call(BookingSeeder::class);
            $this->call(ReviewSeeder::class);

            $this->logSection('Offers');
            $this->call(OfferSeeder::class);

            $this->logSection('Activities');
            $this->call(ActivitySeeder::class);

            $this->command->info('✅ Database seeded successfully with realistic holiday booking data!');
        } catch (\Throwable $e) {
            Log::error('DatabaseSeeder failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function logSection(string $name): void
    {
        $this->command->info("🔹 Seeding {$name}...");
        Log::info("Seeding section: {$name}");
    }

    protected function seedAdmin(): User
    {
        $admin = User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $admin->assignRole('admin');

        return $admin;
    }
}
