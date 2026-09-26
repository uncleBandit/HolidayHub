<?php

namespace App\Modules\Administration\Application\Services;

use App\Modules\Administration\Domain\Contracts\VerifiableProfile;
use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Administration\Domain\Exceptions\InvalidTenantTransition;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Administration\Domain\Models\TenantVerification;
use App\Modules\Audit\Domain\Models\AuditLog;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The tenant onboarding and verification workflow.
 *
 * Every status change goes through here so that four things always happen
 * together and cannot drift apart:
 *
 *   1. the workflow rule is enforced (no approving a rejected application),
 *   2. the transition is appended to the tenant's history,
 *   3. the denormalised is_verified flag on the profile is kept in sync,
 *   4. a platform-wide audit log entry is written.
 *
 * Each method runs in a transaction, so a failure part-way through cannot leave
 * a tenant approved without a matching history row.
 */
final class TenantVerificationService
{
    /**
     * A tenant applies to sell services on the platform.
     *
     * Idempotent for a given profile: reapplying returns the existing
     * application rather than colliding with the unique morph index.
     */
    public function apply(
        User $user,
        Model $tenantable,
        string $type,
        ?string $displayName = null,
        ?string $contactEmail = null,
    ): Tenant {
        if ($existing = Tenant::where('tenantable_type', $tenantable->getMorphClass())
            ->where('tenantable_id', $tenantable->getKey())
            ->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $tenantable, $type, $displayName, $contactEmail): Tenant {
            $tenant = Tenant::create([
                'user_id' => $user->id,
                'type' => $type,
                'tenantable_type' => $tenantable->getMorphClass(),
                'tenantable_id' => $tenantable->getKey(),
                'display_name' => $displayName ?? $user->name,
                'contact_email' => $contactEmail ?? $user->email,
                'status' => TenantStatus::Pending,
                'submitted_at' => now(),
            ]);

            $this->record($tenant, null, TenantStatus::Pending, 'Application submitted.');

            $this->audit('tenant.applied', $tenant, ['type' => $type]);

            return $tenant;
        });
    }

    /**
     * An admin picks up an application and starts assessing it.
     *
     * Lets reviewers claim work in a shared queue without colliding.
     */
    public function startReview(Tenant $tenant, User $admin): Tenant
    {
        return $this->transition(
            tenant: $tenant,
            admin: $admin,
            to: TenantStatus::UnderReview,
            reason: null,
        );
    }

    /**
     * Grant marketplace access.
     */
    public function approve(Tenant $tenant, User $admin, ?string $reason = null, array $metadata = []): Tenant
    {
        return $this->transition(
            tenant: $tenant,
            admin: $admin,
            to: TenantStatus::Approved,
            reason: $reason,
            metadata: $metadata,
        );
    }

    /**
     * Refuse verification. A reason is mandatory: the tenant has to be told why.
     */
    public function reject(Tenant $tenant, User $admin, string $reason): Tenant
    {
        return $this->transition(
            tenant: $tenant,
            admin: $admin,
            to: TenantStatus::Rejected,
            reason: $reason,
            requireReason: true,
        );
    }

    /**
     * Withdraw access from an already-approved tenant.
     */
    public function suspend(Tenant $tenant, User $admin, string $reason): Tenant
    {
        return $this->transition(
            tenant: $tenant,
            admin: $admin,
            to: TenantStatus::Suspended,
            reason: $reason,
            requireReason: true,
        );
    }

    /**
     * Restore a suspended tenant to approved without a fresh application.
     */
    public function reinstate(Tenant $tenant, User $admin, ?string $reason = null): Tenant
    {
        return $this->transition(
            tenant: $tenant,
            admin: $admin,
            to: TenantStatus::Approved,
            reason: $reason,
        );
    }

    /**
     * A rejected tenant corrects their application and resubmits.
     *
     * Clears the previous rejection so the queue does not show a stale reason
     * against a live application.
     */
    public function reopen(Tenant $tenant, ?User $admin = null): Tenant
    {
        return DB::transaction(function () use ($tenant, $admin): Tenant {
            $this->assertTransition($tenant->status, TenantStatus::Pending);

            $from = $tenant->status;

            $tenant->forceFill([
                'status' => TenantStatus::Pending,
                'rejection_reason' => null,
                'submitted_at' => now(),
            ])->save();

            $this->record($tenant, $from, TenantStatus::Pending, 'Application resubmitted.');

            $this->audit('tenant.resubmitted', $tenant, ['previous_status' => $from->value], $admin);

            return $tenant;
        });
    }

    /**
     * Shared transition path for admin decisions.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function transition(
        Tenant $tenant,
        User $admin,
        TenantStatus $to,
        ?string $reason,
        bool $requireReason = false,
        array $metadata = [],
    ): Tenant {
        if ($requireReason && blank($reason)) {
            throw InvalidTenantTransition::reasonRequired($to);
        }

        return DB::transaction(function () use ($tenant, $admin, $to, $reason, $metadata): Tenant {
            $from = $tenant->status;

            $this->assertTransition($from, $to);

            $attributes = ['status' => $to];

            // Each terminal state owns exactly one timestamp and reason field,
            // so leaving a stale value on the row cannot misrepresent it.
            match ($to) {
                TenantStatus::Approved => $attributes += [
                    'verified_at' => now(),
                    'verified_by' => $admin->id,
                    'suspension_reason' => null,
                    'suspended_at' => null,
                ],
                TenantStatus::Rejected => $attributes += [
                    'rejection_reason' => $reason,
                    'verified_at' => null,
                    'verified_by' => null,
                ],
                TenantStatus::Suspended => $attributes += [
                    'suspension_reason' => $reason,
                    'suspended_at' => now(),
                ],
                default => null,
            };

            $tenant->forceFill($attributes)->save();

            $this->record($tenant, $from, $to, $reason, $admin, $metadata);
            $this->syncProfile($tenant);
            $this->audit('tenant.'.$to->value, $tenant, array_filter([
                'from' => $from->value,
                'reason' => $reason,
            ]), $admin);

            return $tenant;
        });
    }

    /**
     * @throws InvalidTenantTransition
     */
    private function assertTransition(TenantStatus $from, TenantStatus $to): void
    {
        if (! $from->canTransitionTo($to)) {
            throw InvalidTenantTransition::notAllowed($from, $to);
        }
    }

    /**
     * Mirror the decision onto the Provider/Agent profile.
     *
     * Consumers such as the B&B listing still read is_verified directly, so it
     * has to agree with the tenant's authoritative status.
     */
    private function syncProfile(Tenant $tenant): void
    {
        $profile = $tenant->tenantable;

        if (! $profile instanceof VerifiableProfile) {
            return;
        }

        if ($tenant->grantsMarketplaceAccess()) {
            $profile->markVerified($tenant->verified_at);
        } else {
            $profile->markUnverified();
        }

        $profile->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function record(
        Tenant $tenant,
        ?TenantStatus $from,
        TenantStatus $to,
        ?string $reason,
        ?User $admin = null,
        array $metadata = [],
    ): void {
        TenantVerification::create([
            'tenant_id' => $tenant->id,
            'admin_id' => $admin?->id,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function audit(string $action, Tenant $tenant, array $context = [], ?User $actor = null): void
    {
        AuditLog::create([
            'admin_id' => ($actor ?? auth()->user())?->id,
            'action' => $action,
            'subject_type' => $tenant->getMorphClass(),
            'subject_id' => $tenant->getKey(),
            'meta' => $context === [] ? null : $context,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);
    }
}
