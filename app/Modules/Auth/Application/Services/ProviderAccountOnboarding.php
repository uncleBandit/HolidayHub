<?php

namespace App\Modules\Auth\Application\Services;

use App\Modules\Administration\Application\Services\TenantVerificationService;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class ProviderAccountOnboarding
{
    public function apply(User $user): Provider
    {
        return DB::transaction(function () use ($user): Provider {
            $provider = Provider::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $user->name,
                    'contact_person' => $user->name,
                    'email' => $user->email,
                    'provider_type' => 'other',
                ],
            );

            $user->assignRole(Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']));

            app(TenantVerificationService::class)->apply(
                $user,
                $provider,
                'provider',
                displayName: $provider->company_name,
                contactEmail: $provider->email,
            );

            return $provider;
        });
    }
}
