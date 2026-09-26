<?php

namespace App\Modules\Identity\Database\Seeders;

use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * NOTE: the `phone`/`bio` block that used to live here called
     * `$admin->profile()->create(...)`. User::profile() is an argument-less
     * MorphTo and there is no profiles table in any migration, so that call
     * could only ever throw BadMethodCallException. AdminSeeder is not on the
     * DatabaseSeeder path, which is why the bug stayed latent.
     */
    public function run(): void
    {
        // Ensure the 'admin' role exists. Declared first in module.json's
        // "seeders" list, but repeated here so the seeder is safe standalone.
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // firstOrCreate, not create: `php artisan module:seed` is meant to be
        // re-runnable against an already-populated database, and users.email is
        // unique, so a plain create() makes the command fail on its second run.
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Master Admin',
                'password' => Hash::make('password'), // change to secure password
            ],
        );

        // Assign the admin role
        if (! $admin->hasRole($adminRole)) {
            $admin->assignRole($adminRole);
        }
    }
}
