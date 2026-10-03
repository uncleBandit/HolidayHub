<?php

namespace App\Modules\Accommodation\Domain\Policies;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Identity\Domain\Models\User;

class AccommodationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('accommodations.view') || $this->isProvider($user);
    }

    public function view(User $user, Accommodation $accommodation): bool
    {
        return $accommodation->isPublished() || $user->can('accommodations.view') || $this->owns($user, $accommodation);
    }

    public function create(User $user): bool
    {
        return $this->isProvider($user);
    }

    public function update(User $user, Accommodation $accommodation): bool
    {
        return $this->owns($user, $accommodation);
    }

    public function delete(User $user, Accommodation $accommodation): bool
    {
        return $this->update($user, $accommodation);
    }

    public function restore(User $user, Accommodation $accommodation): bool
    {
        return $this->update($user, $accommodation);
    }

    public function forceDelete(User $user, Accommodation $accommodation): bool
    {
        return false;
    }

    public function approve(User $user, ?Accommodation $accommodation = null): bool
    {
        return $user->can('accommodations.approve');
    }

    public function reject(User $user, ?Accommodation $accommodation = null): bool
    {
        return $user->can('accommodations.reject');
    }

    public function suspend(User $user, ?Accommodation $accommodation = null): bool
    {
        return $user->can('accommodations.suspend');
    }

    private function owns(User $user, Accommodation $accommodation): bool
    {
        return $this->isProvider($user) && $user->provider->is($accommodation->provider);
    }

    private function isProvider(User $user): bool
    {
        return $user->hasRole('provider') && $user->provider !== null;
    }
}
