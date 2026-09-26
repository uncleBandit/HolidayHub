<?php

namespace App\Modules\Providers\Database\Factories;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Core Identity
            'user_id' => User::factory(),
            'company_name' => $this->faker->company.' Holidays',
            'contact_person' => $this->faker->name,
            'email' => $this->faker->unique()->companyEmail,
            'phone' => $this->faker->unique()->e164PhoneNumber,

            // Business Details
            'business_license' => strtoupper($this->faker->bothify('LIC-####')),
            'tax_number' => strtoupper($this->faker->bothify('TAX-#####')),
            'provider_type' => $this->faker->randomElement([
                'hotel', 'bnb', 'resort', 'apartment', 'hostel', 'villa', 'camp',
            ]),

            // Location
            'country' => $this->faker->country,
            'city' => $this->faker->city,
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude(-90, 90),
            'longitude' => $this->faker->longitude(-180, 180),

            // Profile & Verification
            'logo' => $this->faker->imageUrl(200, 200, 'business', true, 'logo'),
            'is_verified' => $this->faker->boolean(70), // 70% chance verified
            'verified_at' => $this->faker->optional()->dateTimeBetween('-2 years', 'now'),

            // Status
            'active' => $this->faker->boolean(90), // 90% active
        ];
    }

    /**
     * Indicate that the provider is verified.
     */
    public function verified(): static
    {
        return $this->state(fn () => [
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Indicate that the provider is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'active' => false,
        ]);
    }
}
