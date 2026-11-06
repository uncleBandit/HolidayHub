<?php

namespace App\Modules\Packages\Domain\Policies;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Packages\Domain\Models\Package;

/**
 * Agents build and manage their own packages; admins (the `admin` role or
 * anyone with admin panel access) can see and moderate every package. Guests
 * see and book live packages only.
 */
class PackagePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /** Live packages are public; drafts only to their agent and admins. */
    public function view(?User $user, Package $package): bool
    {
        if ($package->isPublished()) {
            return true;
        }

        return $user !== null && ($this->isAdmin($user) || $this->owns($user, $package));
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || ($user->hasRole('agent') && $user->agent !== null);
    }

    public function update(User $user, Package $package): bool
    {
        return $this->isAdmin($user) || $this->owns($user, $package);
    }

    public function delete(User $user, Package $package): bool
    {
        return $this->update($user, $package);
    }

    public function restore(User $user, Package $package): bool
    {
        return $this->update($user, $package);
    }

    public function forceDelete(User $user, Package $package): bool
    {
        return $this->isAdmin($user);
    }

    /** Admins moderate: approve, reject, suspend. */
    public function moderate(User $user, Package $package): bool
    {
        return $this->isAdmin($user);
    }

    /** Anyone signed in with a guest profile can book a live package. */
    public function book(User $user, Package $package): bool
    {
        return $package->isPublished() && $user->guest !== null;
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('admin.panel.access');
    }

    private function owns(User $user, Package $package): bool
    {
        return $user->hasRole('agent')
            && $user->agent !== null
            && (int) $package->agent_id === (int) $user->agent->id;
    }
}
