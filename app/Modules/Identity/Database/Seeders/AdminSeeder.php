<?php

namespace App\Modules\Identity\Database\Seeders;

use App\Modules\Administration\Database\Seeders\AdministrationAccessSeeder;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;
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
        $this->call(AdministrationAccessSeeder::class);

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) && blank($password)) {
            $this->command?->warn('Admin account not seeded: set ADMIN_EMAIL and ADMIN_PASSWORD explicitly.');

            return;
        }

        if (blank($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_string($password) || strlen($password) < 16) {
            throw new InvalidArgumentException('Set a valid ADMIN_EMAIL and an ADMIN_PASSWORD of at least 16 characters.');
        }

        $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Platform Administrator', 'password' => $password],
        );

        if (! $admin->hasRole($adminRole)) {
            $admin->assignRole($adminRole);
        }
    }
}
