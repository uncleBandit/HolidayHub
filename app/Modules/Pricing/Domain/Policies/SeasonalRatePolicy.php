<?php

namespace App\Modules\Pricing\Domain\Policies;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Pricing\Domain\Models\SeasonalRate;

class SeasonalRatePolicy
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
    public function view(User $user, SeasonalRate $seasonalRate): bool
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
    public function update(User $user, SeasonalRate $seasonalRate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SeasonalRate $seasonalRate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SeasonalRate $seasonalRate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SeasonalRate $seasonalRate): bool
    {
        return false;
    }
}
