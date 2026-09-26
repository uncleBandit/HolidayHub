<?php

namespace App\Modules\Audit\Database\Factories;

use App\Modules\Audit\Domain\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Audit\Domain\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => null,
            'action' => $this->faker->randomElement([
                'tenant.applied',
                'tenant.approved',
                'tenant.rejected',
                'tenant.suspended',
            ]),
            'subject_type' => null,
            'subject_id' => null,
            'meta' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * An entry tied to a specific record.
     */
    public function forSubject(object $subject, ?string $action = null): static
    {
        return $this->state(fn (): array => [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action ?? $this->faker->randomElement([
                'tenant.approved',
                'tenant.rejected',
                'tenant.suspended',
            ]),
        ]);
    }
}
