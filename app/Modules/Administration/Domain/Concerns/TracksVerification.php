<?php

namespace App\Modules\Administration\Domain\Concerns;

use App\Modules\Administration\Domain\Contracts\VerifiableProfile;
use Illuminate\Support\Carbon;

/**
 * Default {@see VerifiableProfile} implementation.
 *
 * Provider and Agent both store the same two denormalised columns, so the
 * behaviour lives here once. Writes use forceFill because `is_verified` is not
 * user-assignable — it may only be changed by the verification workflow, and
 * keeping it out of $fillable is what stops a tenant self-approving through
 * mass assignment.
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait TracksVerification
{
    public function getIsVerifiedKey(): string
    {
        return 'is_verified';
    }

    public function isVerified(): bool
    {
        return (bool) $this->getAttribute($this->getIsVerifiedKey());
    }

    public function markVerified(?Carbon $at = null): void
    {
        $this->forceFill([
            $this->getIsVerifiedKey() => true,
            'verified_at' => $at ?? now(),
        ]);
    }

    public function markUnverified(): void
    {
        $this->forceFill([
            $this->getIsVerifiedKey() => false,
            'verified_at' => null,
        ]);
    }
}
