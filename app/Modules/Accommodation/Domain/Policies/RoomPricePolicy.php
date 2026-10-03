<?php

namespace App\Modules\Accommodation\Domain\Policies;

use App\Modules\Accommodation\Domain\Models\RoomPrice;
use App\Modules\Identity\Domain\Models\User;

class RoomPricePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function view(User $user, RoomPrice $roomPrice): bool
    {
        return $roomPrice->room?->hotel?->accommodation?->isPublished() || $this->owns($user, $roomPrice);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function update(User $user, RoomPrice $roomPrice): bool
    {
        return $user->isPlatformAdmin() || $this->owns($user, $roomPrice);
    }

    public function delete(User $user, RoomPrice $roomPrice): bool
    {
        return $this->update($user, $roomPrice);
    }

    public function restore(User $user, RoomPrice $roomPrice): bool
    {
        return $this->update($user, $roomPrice);
    }

    public function forceDelete(User $user, RoomPrice $roomPrice): bool
    {
        return $user->isPlatformAdmin();
    }

    private function owns(User $user, RoomPrice $roomPrice): bool
    {
        return $this->isProvider($user)
            && (int) ($roomPrice->room?->hotel?->accommodation?->provider_id ?? $roomPrice->room?->hotel?->provider_id) === (int) $user->provider->id;
    }

    private function isProvider(User $user): bool
    {
        return $user->hasRole('provider') && $user->provider !== null;
    }
}
