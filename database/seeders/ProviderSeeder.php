<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Provider;
use App\Models\Hotel;
use App\Models\Amenity;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the 'provider' role exists
        $providerRole = Role::firstOrCreate(['name' => 'provider']);

        // Create demo providers
        for ($i = 1; $i <= 5; $i++) {
            // Create the user
            $user = User::create([
                'name' => "Provider $i",
                'email' => "provider{$i}@holidayhub.test",
                'password' => Hash::make('password123'), // default password
            ]);

            // Assign the 'provider' role
            $user->assignRole($providerRole);

            // Create the provider profile
            $provider = Provider::create([
                'user_id' => $user->id,
                'company_name' => "Provider Company $i",
                'phone' => '2547' . rand(10000000, 99999999),
                'bio' => "We are Provider $i, delivering exceptional holiday experiences!",
            ]);

            // Optionally create demo hotels for each provider
            for ($j = 1; $j <= 2; $j++) {
                $hotel = Hotel::create([
                    'provider_id' => $provider->id,
                    'name' => "Hotel {$i}-{$j}",
                    'location' => "City " . rand(1, 20),
                    'description' => "Luxurious Hotel {$i}-{$j} for unforgettable stays.",
                    'price_per_night' => rand(3000, 15000),
                    'is_featured' => rand(0, 1),
                ]);

                // Assign random amenities to hotel
                $amenities = Amenity::inRandomOrder()->take(rand(2, 5))->pluck('id');
                $hotel->amenities()->sync($amenities);
            }
        }
    }
}
