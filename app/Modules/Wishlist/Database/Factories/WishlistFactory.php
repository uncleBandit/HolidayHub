<?php

namespace App\Modules\Wishlist\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Wishlist\Domain\Models\Wishlist>
 */
class WishlistFactory extends Factory
{
    protected $model = \App\Modules\Wishlist\Domain\Models\Wishlist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
