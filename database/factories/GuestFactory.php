<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->unique()->safeEmail,
            'phone'      => $this->faker->phoneNumber,
            'date_of_birth' => $this->faker->date(),
            'gender'     => $this->faker->randomElement(['male', 'female', 'non-binary', 'prefer_not_to_say']),
            'nationality' => $this->faker->country,
            'verified'   => $this->faker->boolean(30), // 30% chance verified
            'preferred_language' => $this->faker->randomElement(['en', 'fr', 'es', 'de']),
            'preferred_currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'loyalty_tier' => $this->faker->randomElement(['standard', 'silver', 'gold', 'platinum']),
            'loyalty_points' => $this->faker->numberBetween(0, 5000),
            'address_line1' => $this->faker->streetAddress,
            'city'          => $this->faker->city,
            'state'         => $this->faker->state,
            'postal_code'   => $this->faker->postcode,
            'country'       => $this->faker->country,
        ];
    }
}
