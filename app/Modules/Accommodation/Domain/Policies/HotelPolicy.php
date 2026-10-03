<?php

namespace App\Modules\Accommodation\Domain\Policies;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Identity\Domain\Models\User;

class HotelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function view(User $user, Hotel $hotel): bool
    {
        return $hotel->accommodation?->isPublished() || $this->owns($user, $hotel);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function update(User $user, Hotel $hotel): bool
    {
        return $user->isPlatformAdmin() || $this->owns($user, $hotel);
    }

    public function delete(User $user, Hotel $hotel): bool
    {
        return $this->update($user, $hotel);
    }

    public function restore(User $user, Hotel $hotel): bool
    {
        return $this->update($user, $hotel);
    }

    public function forceDelete(User $user, Hotel $hotel): bool
    {
        return $user->isPlatformAdmin();
    }

    private function owns(User $user, Hotel $hotel): bool
    {
        return $this->isProvider($user)
            && (int) ($hotel->accommodation?->provider_id ?? $hotel->provider_id) === (int) $user->provider->id;
    }

    private function isProvider(User $user): bool
    {
        return $user->hasRole('provider') && $user->provider !== null;
    }
}
