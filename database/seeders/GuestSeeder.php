<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Guest;

class GuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 20 fake guests
        Guest::factory()->count(20)->create();

        $this->command->info('Guests seeded successfully!');
    }
}
