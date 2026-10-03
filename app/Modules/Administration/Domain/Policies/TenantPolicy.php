<?php

namespace App\Modules\Administration\Domain\Policies;

use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Identity\Domain\Models\User;

/**
 * Authorisation for the tenant verification workflow.
 *
 * The Filament panel already restricts the whole admin surface to holders of
 * the Spatie `admin` role, so these methods are a second line of defence rather
 * than the primary gate. They still matter: a future API endpoint or console
 * command calling the verification service must be authorised too, and must not
 * assume that reaching it implies admin rights.
 */
class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tenants.view');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->can('tenants.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return false;
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        // Verification history is the platform's record of a commercial
        // decision, so applications are never destroyed. Suspend instead.
        return false;
    }

    /**
     * Whether this user may decide this application right now.
     *
     * Combines role with the workflow's own state rules so a decided
     * application cannot be re-decided without an explicit reopen or reinstate.
     */
    public function decide(User $user, Tenant $tenant): bool
    {
        return $tenant->isActionable() && $user->can('tenants.review');
    }

    public function approve(User $user, Tenant $tenant): bool
    {
        return $tenant->isActionable() && $user->can('tenants.approve');
    }

    public function reject(User $user, Tenant $tenant): bool
    {
        return $tenant->isActionable() && $user->can('tenants.reject');
    }

    public function suspend(User $user, Tenant $tenant): bool
    {
        return $tenant->isActionable() && $user->can('tenants.suspend');
    }

    /**
     * Restoring a suspended tenant, or reopening a rejected one.
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->can('tenants.restore');
    }
}
