<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class GuestSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the 'guest' role exists
        $guestRole = Role::firstOrCreate(['name' => 'guest']);

        // 1️⃣ Test Guest (for easy logins in dev/demo)
        $testUser = User::firstOrCreate(
            ['email' => 'testguest@example.com'],
            [
                'name'     => 'Test Guest',
                'password' => Hash::make('password123'),
            ]
        );

        $testUser->assignRole($guestRole);

        Guest::firstOrCreate(
            ['user_id' => $testUser->id],
            [
                'first_name' => 'Test',
                'last_name'  => 'Guest',
                'phone'      => '2547' . rand(10000000, 99999999),
                'email'      => $testUser->email,
            ]
        );

        // 2️⃣ Additional demo guests
        $guestCount = 20;

        for ($i = 1; $i <= $guestCount; $i++) {
            $user = User::firstOrCreate(
                ['email' => "guest{$i}@holidayhub.test"],
                [
                    'name'     => "Guest $i",
                    'password' => Hash::make('password123'),
                ]
            );

            $user->assignRole($guestRole);

            Guest::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => "Guest",
                    'last_name'  => "$i",
                    'phone'      => '2547' . rand(10000000, 99999999),
                    'email'      => $user->email,
                ]
            );
        }

        $this->command->info('✅ Guests seeded successfully!');
    }
}
