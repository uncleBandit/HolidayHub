<?php

namespace App\Modules\Accommodation\Database\Factories;

use App\Modules\Accommodation\Domain\Models\PropertyScene;
use App\Modules\Accommodation\Domain\Models\Villa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyScene>
 */
class PropertySceneFactory extends Factory
{
    protected $model = PropertyScene::class;

    /**
     * A photo scene in a villa's reel walk.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sceneable_type' => (new Villa)->getMorphClass(),
            'sceneable_id' => Villa::factory(),
            'position' => $this->faker->numberBetween(1, 9),
            'space' => $this->faker->randomElement(array_keys(PropertyScene::SPACES)),
            'title' => $this->faker->optional()->sentence(3),
            'caption' => $this->faker->optional()->sentence(),
            'media_path' => 'scenes/'.$this->faker->uuid().'.jpg',
            'media_type' => 'photo',
        ];
    }

    /** A film scene. */
    public function film(): static
    {
        return $this->state(fn () => [
            'media_path' => 'scenes/'.$this->faker->uuid().'.mp4',
            'media_type' => 'video',
        ]);
    }
}
