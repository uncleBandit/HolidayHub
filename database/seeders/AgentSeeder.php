<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Agent;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the 'agent' role exists
        $agentRole = Role::firstOrCreate(['name' => 'agent']);

        // ---------
        // 1️⃣ Test Agent
        // ---------
        $testUser = User::firstOrCreate(
            ['email' => 'testagent@example.com'],
            [
                'name' => 'Test Agent',
                'password' => Hash::make('password123'),
            ]
        );

        $testUser->assignRole($agentRole);

        Agent::firstOrCreate(
            ['user_id' => $testUser->id],
            [
                'phone'          => '2547' . rand(10000000, 99999999),
                'bio'            => "Hi, I'm Test Agent, your holiday specialist!",
                'agency_name'    => 'Test Agency',
                'commission_rate'=> 10,
                'first_name'     => 'Test',
                'last_name'      => 'Agent',
                'active'         => true,
                'email'          => $testUser->email,
            ]
        );

        // ---------
        // 2️⃣ Additional demo agents
        // ---------
        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrCreate(
                ['email' => "agent{$i}@holidayhub.test"],
                [
                    'name'     => "Agent $i",
                    'password' => Hash::make('password123'),
                ]
            );

            $user->assignRole($agentRole);

            Agent::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone'          => '2547' . rand(10000000, 99999999),
                    'bio'            => "Hi, I'm Agent $i, your holiday specialist!",
                    'first_name'     => 'Agent',
                    'last_name'      => "$i",
                    'agency_name'    => "Agency $i",
                    'commission_rate'=> rand(5, 15),
                    'active'         => true,
                    'email'          => $user->email,
                ]
            );
        }
    }
}
