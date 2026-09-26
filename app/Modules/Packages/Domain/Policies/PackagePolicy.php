<?php

namespace App\Modules\Packages\Domain\Policies;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Packages\Domain\Models\Package;

class PackagePolicy
{
    /**
     * Determine whether the user can view any packages.
     */
    public function viewAny(User $user): bool
    {
        // All users (agents and guests) can browse active packages
        return $user->hasRole(['admin', 'agent', 'guest']);
    }

    /**
     * Determine whether the user can view a specific package.
     */
    public function view(?User $user, Package $package): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create packages.
     */
    public function create(User $user): bool
    {
        // Only agents or admins can create packages
        return $user->hasRole(['agent', 'admin']);
    }

    /**
     * Determine whether the user can update the package.
     */
    public function update(User $user, Package $package): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agent') && $user->agent) {
            // Agents can update their own packages
            return $package->agent_id === $user->agent->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the package.
     */
    public function delete(User $user, Package $package): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agent') && $user->agent) {
            return $package->agent_id === $user->agent->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the package.
     */
    public function restore(User $user, Package $package): bool
    {
        return $this->delete($user, $package);
    }

    /**
     * Determine whether the user can permanently delete the package.
     */
    public function forceDelete(User $user, Package $package): bool
    {
        return $this->delete($user, $package);
    }

    /**
     * Determine if a guest can book the package.
     */
    public function book(User $user, Package $package): bool
    {
        // Only guests can book, and only if package is active
        return $user->hasRole('guest') && $package->active;
    }
}
