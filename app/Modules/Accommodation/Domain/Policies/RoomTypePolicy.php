<?php

namespace App\Modules\Accommodation\Domain\Policies;

use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Identity\Domain\Models\User;

class RoomTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function view(User $user, RoomType $roomType): bool
    {
        return $roomType->hotel?->accommodation?->isPublished() || $this->owns($user, $roomType);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $user->isPlatformAdmin() || $this->owns($user, $roomType);
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return $this->update($user, $roomType);
    }

    public function restore(User $user, RoomType $roomType): bool
    {
        return $this->update($user, $roomType);
    }

    public function forceDelete(User $user, RoomType $roomType): bool
    {
        return $user->isPlatformAdmin();
    }

    private function owns(User $user, RoomType $roomType): bool
    {
        return $this->isProvider($user)
            && (int) ($roomType->hotel?->accommodation?->provider_id ?? $roomType->hotel?->provider_id) === (int) $user->provider->id;
    }

    private function isProvider(User $user): bool
    {
        return $user->hasRole('provider') && $user->provider !== null;
    }
}
