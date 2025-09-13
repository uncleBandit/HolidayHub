<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GuestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'user_id' => null, // This will be set by the seeder
            'date_of_birth' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female', 'non-binary', 'prefer_not_to_say']),
            'nationality' => $this->faker->country,
            'passport_number' => $this->faker->optional()->isbn10(),
            'id_number' => $this->faker->optional()->uuid(),
            'verified' => $this->faker->boolean(30),
            'preferred_language' => $this->faker->randomElement(['en', 'fr', 'es', 'de']),
            'preferred_currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'loyalty_tier' => $this->faker->randomElement(['standard', 'silver', 'gold', 'platinum']),
            'loyalty_points' => $this->faker->numberBetween(0, 5000),
            'emergency_contact_name' => $this->faker->name,
            'emergency_contact_phone' => $this->faker->phoneNumber,
            'emergency_contact_relation' => $this->faker->randomElement(['spouse', 'parent', 'friend']),
            'frequent_flyer_number' => $this->faker->optional()->uuid(),
            'special_requests' => $this->faker->optional()->sentence,
            'address_line1' => $this->faker->streetAddress,
            'address_line2' => $this->faker->secondaryAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'postal_code' => $this->faker->postcode,
            'country' => $this->faker->country,
        ];
    }
}
