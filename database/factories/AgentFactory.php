<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        $businessType = $this->faker->randomElement(['individual', 'agency', 'corporate']);

        return [
            // Identity
            'first_name'     => $this->faker->firstName(),
            'last_name'      => $this->faker->lastName(),
            'email'          => $this->faker->unique()->safeEmail(),
            'phone'          => $this->faker->unique()->e164PhoneNumber(),

            // Business/Agency
            'agency_name'    => in_array($businessType, ['agency', 'corporate'])
                                ? $this->faker->company()
                                : null,
            'agency_license' => in_array($businessType, ['agency', 'corporate'])
                                ? strtoupper(Str::random(3)) . '-' . $this->faker->numerify('#####')
                                : null,
            'specialization' => $this->faker->randomElement([
                                    'Hotels',
                                    'Resorts',
                                    'Bed & Breakfasts',
                                    'Tours',
                                    'Flights',
                                    'Cruises',
                                    'Villas'
                                ]),

            // Location
            'country'        => $this->faker->country(),
            'city'           => $this->faker->city(),
            'address'        => $this->faker->streetAddress(),

            // Commission
            'commission_rate' => $this->faker->randomFloat(2, 5, 25), // 5% - 25%
            'business_type'   => $businessType,

            // Profile & Verification
            'profile_photo'  => $this->faker->imageUrl(400, 400, 'people', true, 'agent'),
            'is_verified'    => $this->faker->boolean(70), // 70% chance verified
            'verified_at'    => $this->faker->boolean(70) ? now() : null,

            

            // Status
            'active'         => $this->faker->boolean(90), // 90% active
        ];
    }

    /**
     * State for verified agents
     */
    public function verified(): static
    {
        return $this->state(fn () => [
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * State for inactive agents
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'active' => false,
        ]);
    }
}
