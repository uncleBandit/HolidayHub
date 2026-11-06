<?php

namespace App\Modules\Administration\Database\Factories;

use App\Modules\Administration\Application\Services\PlatformSettings;
use App\Modules\Administration\Domain\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformSetting>
 */
class PlatformSettingFactory extends Factory
{
    protected $model = PlatformSetting::class;

    /**
     * A saved setting with its default value; override key/value as needed.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = $this->faker->randomElement(array_keys(PlatformSettings::DEFAULTS));

        return [
            'key' => $key,
            'value' => PlatformSettings::DEFAULTS[$key],
            'updated_by' => null,
        ];
    }
}
