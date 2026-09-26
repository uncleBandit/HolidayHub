<?php

namespace App\Modules\Administration\Database\Factories;

use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Agents\Domain\Models\Agent;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Administration\Domain\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'provider',
            'tenantable_type' => (new Provider)->getMorphClass(),
            'tenantable_id' => Provider::factory(),
            'display_name' => $this->faker->company(),
            'contact_email' => $this->faker->unique()->companyEmail(),
            'status' => TenantStatus::Pending,
            'submitted_at' => now(),
        ];
    }

    /**
     * An agent applying to sell services, rather than an accommodation provider.
     */
    public function agent(): static
    {
        return $this->state(fn (): array => [
            'type' => 'agent',
            'tenantable_type' => (new Agent)->getMorphClass(),
            'tenantable_id' => Agent::factory(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => TenantStatus::Pending,
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => TenantStatus::Approved,
            'verified_at' => now(),
            'verified_by' => User::factory(),
            'rejection_reason' => null,
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);
    }

    public function rejected(?string $reason = null): static
    {
        return $this->state(fn (): array => [
            'status' => TenantStatus::Rejected,
            'rejection_reason' => $reason ?? $this->faker->sentence(),
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    public function suspended(?string $reason = null): static
    {
        return $this->state(fn (): array => [
            'status' => TenantStatus::Suspended,
            'suspension_reason' => $reason ?? $this->faker->sentence(),
            'suspended_at' => now(),
        ]);
    }
}
