<?php

namespace App\Modules\Administration\Domain\Contracts;

/**
 * A business profile that can be verified for marketplace access.
 *
 * Provider and Agent both carry `is_verified` / `verified_at`, but neither owns
 * the decision — that lives on the Tenant row. These columns are denormalised
 * copies kept in sync by the TenantVerificationService, and this contract is
 * what lets that sync stay type-safe instead of relying on duck typing.
 */
interface VerifiableProfile
{
    public function getIsVerifiedKey(): string;

    public function isVerified(): bool;

    public function markVerified(?\Illuminate\Support\Carbon $at = null): void;

    public function markUnverified(): void;
}
