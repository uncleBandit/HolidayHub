<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Provider;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

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
                'email' => $user->email,
            ]);
        }
    }
}
