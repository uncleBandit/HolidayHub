<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the 'admin' role exists
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Create the master admin user
        $admin = User::create([
            'name' => 'Master Admin',
            'email' => 'admin@holidayhub.test',
            'password' => Hash::make('password'), // change to secure password
        ]);

        // Assign the admin role
        $admin->assignRole($adminRole);

        // Optionally, you can seed additional admin profile info here
        $admin->profile()->create([
            'phone' => '254700000000',
            'bio' => 'Master administrator with full access to the HolidayHub system.',
        ]);
    }
}
