<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Agent;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the 'agent' role exists
        $agentRole = Role::firstOrCreate(['name' => 'agent']);

        // Create demo agents
        for ($i = 1; $i <= 5; $i++) {
            // Create the user
            $user = User::create([
                'name' => "Agent $i",
                'email' => "agent{$i}@holidayhub.test",
                'password' => Hash::make('password123'), // default password
            ]);

            // Assign the 'agent' role
            $user->assignRole($agentRole);

            // Create the agent profile
            $agent = Agent::create([
                'user_id' => $user->id,
                'phone' => '2547' . rand(10000000, 99999999),
                'bio' => "Hi, I'm Agent $i, your holiday specialist!",
            ]);

            // Optionally, create some demo packages for each agent
            for ($j = 1; $j <= 3; $j++) {
                Package::create([
                    'agent_id' => $agent->id,
                    'title' => "Package {$i}-{$j}",
                    'destination_id' => rand(1, 10), // assuming destinations seeded
                    'price' => rand(5000, 20000),
                    'description' => "Exciting holiday package {$i}-{$j}.",
                    'available_from' => now()->addDays(rand(1, 30)),
                    'available_to' => now()->addDays(rand(31, 60)),
                    'is_featured' => rand(0, 1),
                ]);
            }
        }
    }
}
