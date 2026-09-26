<?php

namespace App\Modules\Administration\Database\Factories;

use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Administration\Domain\Models\TenantVerification;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Administration\Domain\Models\TenantVerification>
 */
class TenantVerificationFactory extends Factory
{
    protected $model = TenantVerification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'admin_id' => User::factory(),
            'from_status' => TenantStatus::Pending,
            'to_status' => TenantStatus::Approved,
            'reason' => $this->faker->optional()->sentence(),
            'metadata' => null,
        ];
    }

    /**
     * An event the tenant triggered themselves, e.g. submitting an application.
     */
    public function initiatedByTenant(): static
    {
        return $this->state(fn (): array => [
            'admin_id' => null,
            'from_status' => null,
            'to_status' => TenantStatus::Pending,
            'reason' => 'Application submitted.',
        ]);
    }
}
