<?php

namespace App\Modules\Packages\Domain\Policies;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Packages\Domain\Models\PackageFeature;

class PackageFeaturePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PackageFeature $packageFeature): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PackageFeature $packageFeature): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PackageFeature $packageFeature): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PackageFeature $packageFeature): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PackageFeature $packageFeature): bool
    {
        return false;
    }
}
