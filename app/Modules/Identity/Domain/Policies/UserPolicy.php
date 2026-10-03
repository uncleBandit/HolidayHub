<?php

namespace App\Modules\Identity\Domain\Policies;

use App\Modules\Identity\Domain\Models\User;

/**
 * Authorization for account administration.
 *
 * The Filament panel already refuses non-admins at canAccessPanel(), so these
 * checks are the second line of defence. They exist because the account
 * surface is the one place where an over-broad grant is immediately
 * dangerous: an admin who can edit any account can also grant themselves roles,
 * strip another admin's access, or reset the platform owner's password.
 *
 * Two rules are enforced here rather than in the UI, because a UI-only rule is
 * one API endpoint away from being bypassed:
 *
 *  1. An admin may not modify their own account through the admin surface.
 *     Self-editing is how an admin locks everyone out of the panel: demote
 *     yourself and the next person to log in cannot undo it.
 *  2. An admin may not delete any account. Accounts are deactivated, not
 *     destroyed, so booking history and audit trails keep their referent.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    /**
     * Editing other accounts, but never your own through this surface.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can('users.manage') && ! $this->isSelf($user, $model);
    }

    /**
     * Account deletion is never permitted from the admin panel.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Changing a password outside the account's own settings flow.
     */
    public function resetPassword(User $user, User $model): bool
    {
        return $user->can('users.manage') && ! $this->isSelf($user, $model);
    }

    /**
     * Granting or revoking roles, including one's own.
     */
    public function manageRoles(User $user, User $model): bool
    {
        return $user->can('users.roles.manage') && ! $this->isSelf($user, $model);
    }

    /**
     * Revoking the account's API tokens.
     */
    public function revokeTokens(User $user, User $model): bool
    {
        return $user->can('users.tokens.revoke') && ! $this->isSelf($user, $model);
    }

    /**
     * Overriding email verification by hand.
     */
    public function manageEmailVerification(User $user, User $model): bool
    {
        return $user->can('users.email.verify') && ! $this->isSelf($user, $model);
    }

    /**
     * Compare on the primary key rather than instance identity: a policy
     * receives a freshly hydrated model, so `$user === $model` is unreliable.
     */
    private function isSelf(User $actor, User $target): bool
    {
        return (int) $actor->getKey() === (int) $target->getKey();
    }
}
