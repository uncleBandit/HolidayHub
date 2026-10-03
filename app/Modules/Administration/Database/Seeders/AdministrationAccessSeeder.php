<?php

namespace App\Modules\Administration\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdministrationAccessSeeder extends Seeder
{
    private const PERMISSIONS = [
        'admin.panel.access',
        'users.view', 'users.manage', 'users.roles.manage', 'users.email.verify', 'users.tokens.revoke',
        'users.suspend', 'users.restore', 'users.impersonate',
        'tenants.view', 'tenants.review', 'tenants.approve', 'tenants.reject', 'tenants.suspend', 'tenants.restore',
        'providers.view', 'providers.verify', 'providers.suspend',
        'accommodations.view', 'accommodations.approve', 'accommodations.reject', 'accommodations.suspend',
        'activities.view', 'activities.moderate', 'activities.approve', 'activities.reject', 'activities.suspend',
        'media.view', 'media.moderate',
        'reviews.view', 'reviews.moderate',
        'bookings.view', 'bookings.create', 'bookings.update', 'bookings.cancel', 'bookings.refund', 'bookings.override',
        'payments.view', 'payments.refund', 'payments.reconcile',
        'payouts.view', 'payouts.manage',
        'reports.view', 'analytics.view',
        'settings.view', 'settings.manage', 'audit.view', 'system.view',
    ];

    /** @var array<string, array<int, string>> */
    private const ROLE_PERMISSIONS = [
        'platform_admin' => [
            'admin.panel.access', 'users.view', 'users.manage', 'users.email.verify', 'users.tokens.revoke',
            'users.suspend', 'users.restore', 'tenants.view', 'tenants.review', 'tenants.approve',
            'tenants.reject', 'tenants.suspend', 'tenants.restore', 'providers.view', 'providers.verify',
            'providers.suspend', 'accommodations.view', 'accommodations.approve', 'accommodations.reject',
            'accommodations.suspend', 'activities.view', 'activities.moderate', 'activities.approve',
            'activities.reject', 'activities.suspend', 'media.view', 'media.moderate', 'reviews.view',
            'reviews.moderate', 'bookings.view', 'bookings.cancel', 'payments.view', 'payouts.view',
            'reports.view', 'analytics.view', 'audit.view', 'system.view',
        ],
        'operations_manager' => [
            'admin.panel.access', 'tenants.view', 'tenants.review', 'tenants.approve', 'tenants.reject',
            'tenants.suspend', 'tenants.restore', 'providers.view', 'providers.verify', 'providers.suspend',
            'accommodations.view', 'accommodations.approve', 'accommodations.reject', 'accommodations.suspend',
            'activities.view', 'activities.moderate', 'activities.approve', 'activities.reject',
            'activities.suspend', 'media.view', 'media.moderate', 'reviews.view', 'reviews.moderate',
            'bookings.view', 'reports.view', 'audit.view',
        ],
        'booking_manager' => ['admin.panel.access', 'bookings.view', 'bookings.update', 'bookings.cancel'],
        'finance_manager' => [
            'admin.panel.access', 'bookings.view', 'payments.view', 'payments.refund',
            'payments.reconcile', 'payouts.view', 'payouts.manage', 'reports.view', 'audit.view',
        ],
        'content_manager' => [
            'admin.panel.access', 'accommodations.view', 'accommodations.approve', 'accommodations.reject',
            'accommodations.suspend', 'activities.view', 'activities.moderate', 'activities.approve',
            'activities.reject', 'activities.suspend', 'media.view', 'media.moderate',
            'reviews.view', 'reviews.moderate',
        ],
        'customer_support' => [
            'admin.panel.access', 'users.view', 'users.tokens.revoke', 'bookings.view', 'bookings.cancel',
            'reviews.view',
        ],
        'analyst' => ['admin.panel.access', 'reports.view', 'analytics.view'],
        'auditor' => ['admin.panel.access', 'audit.view', 'reports.view'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)
            ->mapWithKeys(fn (string $name) => [
                $name => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']),
            ]);

        foreach (self::ROLE_PERMISSIONS as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->syncPermissions($permissions->only($rolePermissions)->values());
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions($permissions->values());

        // Keep existing deployments working while they migrate from the
        // original role to explicit operational roles.
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
            ->syncPermissions($permissions->only(self::ROLE_PERMISSIONS['platform_admin'])->values());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
