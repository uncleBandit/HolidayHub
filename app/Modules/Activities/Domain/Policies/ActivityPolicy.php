<?php

namespace App\Modules\Activities\Domain\Policies;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Identity\Domain\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('activities.view') || $this->isProvider($user);
    }

    public function view(User $user, Activity $activity): bool
    {
        return $activity->isPublished() || $user->can('activities.view') || $this->owns($user, $activity);
    }

    public function create(User $user): bool
    {
        return $this->isProvider($user);
    }

    public function update(User $user, Activity $activity): bool
    {
        return $this->owns($user, $activity);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->owns($user, $activity);
    }

    public function restore(User $user, Activity $activity): bool
    {
        return $this->owns($user, $activity);
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        return $user->can('activities.moderate');
    }

    public function submit(User $user, Activity $activity): bool
    {
        return $this->owns($user, $activity) || $user->isPlatformAdmin();
    }

    public function moderate(User $user): bool
    {
        return $user->can('activities.moderate');
    }

    public function approve(User $user): bool
    {
        return $user->can('activities.approve');
    }

    public function reject(User $user): bool
    {
        return $user->can('activities.reject');
    }

    public function suspend(User $user): bool
    {
        return $user->can('activities.suspend');
    }

    private function owns(User $user, Activity $activity): bool
    {
        return $this->isProvider($user)
            && $activity->provider_id !== null
            && $user->provider->is($activity->provider);
    }

    private function isProvider(User $user): bool
    {
        return $user->hasRole('provider') && $user->provider?->active === true;
    }
}
