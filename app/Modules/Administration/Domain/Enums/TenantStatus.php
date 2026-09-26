<?php

namespace App\Modules\Administration\Domain\Enums;

/**
 * Lifecycle of a tenant — any party that supplies services on the platform
 * (accommodation providers, B&B owners, travel agents).
 *
 * A tenant is verified exactly once to become tradeable, but approval is
 * revocable: suspension returns an already-approved tenant to a non-tradeable
 * state without discarding the original decision.
 */
enum TenantStatus: string
{
    /** Applied to sell services; awaiting an admin decision. */
    case Pending = 'pending';

    /** An admin has opened the application and is actively reviewing it. */
    case UnderReview = 'under_review';

    /** Verified and permitted to list services on the platform. */
    case Approved = 'approved';

    /** Verification was refused. The tenant may correct and resubmit. */
    case Rejected = 'rejected';

    /** Was approved, then withdrawn by an admin. Not tradeable. */
    case Suspended = 'suspended';

    /**
     * Human label for tables, filters and admin screens.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::UnderReview => 'Under review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
        };
    }

    /**
     * Badge colour token, so status rendering stays consistent everywhere.
     *
     * Returns a plain token rather than a Filament enum so the domain layer
     * carries no dependency on the presentation framework.
     */
    public function color(): ?string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::UnderReview => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Suspended => 'gray',
        };
    }

    /**
     * Whether the tenant may currently list services on the platform.
     *
     * Only approval grants tradeable status; everything else, including a
     * suspended tenant, is off the marketplace.
     */
    public function grantsMarketplaceAccess(): bool
    {
        return $this === self::Approved;
    }

    /**
     * Whether an admin may still act on a tenant in this state.
     *
     * Every state has at least one outgoing transition, and each one is an
     * admin action: Pending and UnderReview can be approved or rejected,
     * Approved can be suspended to revoke a live approval, Rejected can be
     * reopened, and Suspended can be reinstated. So this is derived from
     * allowedTransitions() rather than restated as a hand-kept list, which is
     * what previously made it disagree with the state machine and silently
     * disable suspend() and reopen().
     */
    public function isActionable(): bool
    {
        return $this->allowedTransitions() !== [];
    }

    /**
     * States this status may legally move to.
     *
     * Encoding the workflow here keeps illegal transitions (e.g. approving an
     * already-rejected application without a reopen) out of the service layer.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::UnderReview, self::Approved, self::Rejected],
            self::UnderReview => [self::Approved, self::Rejected, self::Pending],
            self::Approved => [self::Suspended],
            self::Rejected => [self::Pending],
            self::Suspended => [self::Approved, self::Rejected],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Options for a Filament select, as value => label.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
