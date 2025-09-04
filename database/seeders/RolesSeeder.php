<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'guest']);
        Role::firstOrCreate(['name' => 'provider']);
        Role::firstOrCreate(['name' => 'agent']);
        Role::firstOrCreate(['name' => 'admin']);
    }
}
