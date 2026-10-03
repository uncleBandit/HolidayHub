<?php

namespace App\Modules\Media\Database\Factories;

use App\Modules\Media\Domain\Enums\MediaPostStatus;
use App\Modules\Media\Domain\Enums\MediaPostType;
use App\Modules\Media\Domain\Models\MediaPost;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaPostFactory extends Factory
{
    protected $model = MediaPost::class;

    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'type' => MediaPostType::Reel,
            'title' => fake()->sentence(4),
            'caption' => fake()->sentence(),
            'status' => MediaPostStatus::Draft,
            'visibility' => 'public',
        ];
    }
}
