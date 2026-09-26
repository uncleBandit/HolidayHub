<?php

namespace App\Modules\Administration\Domain\Exceptions;

use App\Modules\Administration\Domain\Enums\TenantStatus;
use DomainException;

/**
 * Raised when a tenant status change is not permitted by the workflow.
 *
 * This is a domain rule violation, not a programming error, so it extends
 * DomainException and is expected to be caught at an application boundary such
 * as a Filament action or an API request.
 */
final class InvalidTenantTransition extends DomainException
{
    public static function notAllowed(TenantStatus $from, TenantStatus $to): self
    {
        return new self(sprintf(
            'A tenant cannot move from [%s] to [%s]. Allowed: %s.',
            $from->value,
            $to->value,
            implode(', ', array_map(
                static fn (TenantStatus $case): string => $case->value,
                $from->allowedTransitions(),
            )) ?: 'none',
        ));
    }

    public static function reasonRequired(TenantStatus $to): self
    {
        return new self(sprintf(
            'A reason is required when moving a tenant to [%s].',
            $to->value,
        ));
    }
}
