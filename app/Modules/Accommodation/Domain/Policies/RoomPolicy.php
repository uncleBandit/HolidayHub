<?php

namespace App\Modules\Accommodation\Domain\Policies;

use App\Modules\Accommodation\Domain\Models\Room;
use App\Modules\Identity\Domain\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function view(User $user, Room $room): bool
    {
        return $room->hotel?->accommodation?->isPublished() || $this->owns($user, $room);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function update(User $user, Room $room): bool
    {
        return $user->isPlatformAdmin() || $this->owns($user, $room);
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->update($user, $room);
    }

    public function restore(User $user, Room $room): bool
    {
        return $this->update($user, $room);
    }

    public function forceDelete(User $user, Room $room): bool
    {
        return $user->isPlatformAdmin();
    }

    private function owns(User $user, Room $room): bool
    {
        return $this->isProvider($user)
            && (int) ($room->hotel?->accommodation?->provider_id ?? $room->hotel?->provider_id) === (int) $user->provider->id;
    }

    private function isProvider(User $user): bool
    {
        return $user->hasRole('provider') && $user->provider !== null;
    }
}
