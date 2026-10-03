<?php

namespace App\Modules\Accommodation\Domain\Policies;

use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Identity\Domain\Models\User;

class VillaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function view(User $user, Villa $villa): bool
    {
        return $villa->accommodation?->isPublished() || $this->owns($user, $villa);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $this->isProvider($user);
    }

    public function update(User $user, Villa $villa): bool
    {
        return $user->isPlatformAdmin() || $this->owns($user, $villa);
    }

    public function delete(User $user, Villa $villa): bool
    {
        return $this->update($user, $villa);
    }

    public function restore(User $user, Villa $villa): bool
    {
        return $this->update($user, $villa);
    }

    public function forceDelete(User $user, Villa $villa): bool
    {
        return $user->isPlatformAdmin();
    }

    private function owns(User $user, Villa $villa): bool
    {
        return $this->isProvider($user)
            && (int) ($villa->accommodation?->provider_id ?? $villa->provider_id) === (int) $user->provider->id;
    }

    private function isProvider(User $user): bool
    {
        return $user->hasRole('provider') && $user->provider !== null;
    }
}
