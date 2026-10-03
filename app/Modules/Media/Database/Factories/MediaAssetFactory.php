<?php

namespace App\Modules\Media\Database\Factories;

use App\Modules\Media\Domain\Enums\MediaAssetStatus;
use App\Modules\Media\Domain\Enums\MediaAssetType;
use App\Modules\Media\Domain\Models\MediaAsset;
use App\Modules\Media\Domain\Models\MediaPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        return [
            'media_post_id' => MediaPost::factory(),
            'type' => MediaAssetType::Original,
            'disk' => 'local',
            'path' => 'media/'.fake()->uuid().'.mp4',
            'mime_type' => 'video/mp4',
            'size_bytes' => fake()->numberBetween(1024, 10_485_760),
            'status' => MediaAssetStatus::Ready,
        ];
    }
}
