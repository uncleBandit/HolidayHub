<?php

namespace App\Modules\Accommodation\Domain\Policies;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Identity\Domain\Models\User;

class BeadandBreakfastPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function view(User $user, BedAndBreakfast $bedAndBreakfast): bool
    {
        return $bedAndBreakfast->accommodation?->isPublished() || $this->owns($user, $bedAndBreakfast);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function update(User $user, BedAndBreakfast $bedAndBreakfast): bool
    {
        return $user->isPlatformAdmin() || $this->owns($user, $bedAndBreakfast);
    }

    public function delete(User $user, BedAndBreakfast $bedAndBreakfast): bool
    {
        return $this->update($user, $bedAndBreakfast);
    }

    public function restore(User $user, BedAndBreakfast $bedAndBreakfast): bool
    {
        return $this->update($user, $bedAndBreakfast);
    }

    public function forceDelete(User $user, BedAndBreakfast $bedAndBreakfast): bool
    {
        return $user->isPlatformAdmin();
    }

    private function owns(User $user, BedAndBreakfast $bedAndBreakfast): bool
    {
        return $this->isProvider($user)
            && (int) ($bedAndBreakfast->accommodation?->provider_id ?? $bedAndBreakfast->provider_id) === (int) $user->provider->id;
    }

    private function isProvider(User $user): bool
    {
        return $user->hasRole('provider') && $user->provider !== null;
    }
}
