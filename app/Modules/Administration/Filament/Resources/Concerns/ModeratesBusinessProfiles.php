<?php

namespace App\Modules\Administration\Filament\Resources\Concerns;

use App\Modules\Administration\Application\Services\TenantVerificationService;
use App\Modules\Administration\Domain\Contracts\VerifiableProfile;
use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Audit\Application\Services\AuditRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Verification shared by the Provider and Agent admin screens.
 *
 * The Tenant row owns the verification decision, and is_verified on the
 * profile is its denormalised copy. So when a profile has an open tenant
 * application that can still be approved, verifying goes through the
 * TenantVerificationService (which writes the tenant history and syncs the
 * flag). Without one, the profile flag is set directly. Either way the admin
 * decision is written to the audit log under the profile's own action name.
 */
trait ModeratesBusinessProfiles
{
    /**
     * @param  Model&VerifiableProfile  $profile
     */
    protected static function setProfileVerification(Model $profile, bool $verified, ?string $reason, string $auditPrefix): void
    {
        DB::transaction(function () use ($profile, $verified, $reason, $auditPrefix): void {
            /** @var Model&VerifiableProfile $locked */
            $locked = $profile->newQuery()->lockForUpdate()->findOrFail($profile->getKey());
            $before = self::verificationState($locked);

            $tenant = Tenant::query()
                ->where('tenantable_type', $locked->getMorphClass())
                ->where('tenantable_id', $locked->getKey())
                ->first();

            if ($verified && $tenant !== null && $tenant->status->canTransitionTo(TenantStatus::Approved)) {
                app(TenantVerificationService::class)->approve($tenant, auth()->user(), $reason);
                $locked->refresh();
            } else {
                $verified ? $locked->markVerified() : $locked->markUnverified();
                $locked->save();
            }

            app(AuditRecorder::class)->record(
                $auditPrefix.($verified ? '.verified' : '.unverified'),
                $locked,
                auth()->user(),
                before: $before,
                after: self::verificationState($locked),
                reason: $reason,
                meta: $tenant ? ['tenant_id' => $tenant->id, 'tenant_status' => $tenant->fresh()?->status?->value] : [],
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected static function verificationState(Model $profile): array
    {
        return [
            'is_verified' => (bool) $profile->getAttribute('is_verified'),
            'verified_at' => $profile->getAttribute('verified_at')?->toIso8601String(),
        ];
    }
}
