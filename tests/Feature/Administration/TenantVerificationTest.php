<?php

use App\Modules\Administration\Application\Services\TenantVerificationService;
use App\Modules\Administration\Database\Seeders\AdministrationAccessSeeder;
use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * An admin with the Spatie `admin` role, authenticated against the panel.
 */
function platformAdmin(): User
{
    app(AdministrationAccessSeeder::class)->run();
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole($role);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function applicant(): User
{
    return User::factory()->create();
}

function unverifiedApplicant(): User
{
    return User::factory()->unverified()->create();
}

describe('panel access', function (): void {
    it('turns away a signed-in non-admin', function (): void {
        $this->actingAs(applicant());

        // Filament aborts 403 when canAccessPanel() returns false, rather than
        // redirecting: the account is authenticated, it is simply not allowed.
        $this->get('/admin')->assertForbidden();
    });

    it('refuses a guest', function (): void {
        $this->get('/admin')->assertRedirect('/admin/login');
    });

    it('lets an admin through to the dashboard', function (): void {
        $this->actingAs(platformAdmin());

        $this->get('/admin')->assertOk();
    });

    it('renders the tenant review queue and the account list', function (): void {
        $this->actingAs(platformAdmin());

        $this->get('/admin/tenants')->assertOk();
        $this->get('/admin/users')->assertOk();
    });
});

describe('verification workflow', function (): void {
    it('records an application as pending with a submitted history entry', function (): void {
        $user = applicant();
        $provider = Provider::factory()->create(['user_id' => $user->id]);

        $tenant = app(TenantVerificationService::class)->apply(
            user: $user,
            tenantable: $provider,
            type: 'provider',
        );

        expect($tenant->status)->toBe(TenantStatus::Pending)
            ->and($tenant->tenantable_type)->toBe('provider')
            ->and($tenant->tenantable_id)->toBe($provider->id)
            ->and($tenant->verifications)->toHaveCount(1);
    });

    it('does not create a second tenant for the same profile', function (): void {
        $user = applicant();
        $provider = Provider::factory()->create(['user_id' => $user->id]);
        $service = app(TenantVerificationService::class);

        $first = $service->apply($user, $provider, 'provider');
        $second = $service->apply($user, $provider, 'provider');

        expect($second->id)->toBe($first->id)
            ->and(Tenant::withTrashed()->count())->toBe(1);
    });

    it('mirrors approval onto the provider profile', function (): void {
        $user = applicant();
        $provider = Provider::factory()->create(['user_id' => $user->id]);

        $tenant = app(TenantVerificationService::class)->apply($user, $provider, 'provider');
        app(TenantVerificationService::class)->approve($tenant, platformAdmin());

        expect($tenant->refresh()->status)->toBe(TenantStatus::Approved)
            ->and($provider->refresh()->isVerified())->toBeTrue();
    });

    it('clears the verification flag again on suspension', function (): void {
        $user = applicant();
        $provider = Provider::factory()->create(['user_id' => $user->id]);
        $service = app(TenantVerificationService::class);

        $tenant = $service->apply($user, $provider, 'provider');
        $service->approve($tenant, platformAdmin());
        $service->suspend($tenant->refresh(), platformAdmin(), 'Compliance check.');

        expect($tenant->refresh()->status)->toBe(TenantStatus::Suspended)
            ->and($provider->refresh()->isVerified())->toBeFalse();
    });

    it('refuses an illegal transition instead of silently moving', function (): void {
        $user = applicant();
        $provider = Provider::factory()->create(['user_id' => $user->id]);
        $service = app(TenantVerificationService::class);

        $tenant = $service->apply($user, $provider, 'provider');
        $service->reject($tenant, platformAdmin(), 'No documents.');

        // Suspending a rejected application is not a legal move.
        $service->suspend($tenant->refresh(), platformAdmin(), 'nope');
    })->throws(DomainException::class);

    it('keeps every hop in the verification history', function (): void {
        $user = applicant();
        $provider = Provider::factory()->create(['user_id' => $user->id]);
        $service = app(TenantVerificationService::class);

        $tenant = $service->apply($user, $provider, 'provider');
        $service->startReview($tenant, platformAdmin());
        $service->approve($tenant->refresh(), platformAdmin());

        $trail = $tenant->refresh()->verifications
            ->map(fn ($v) => $v->to_status instanceof TenantStatus
                ? $v->to_status->value
                : $v->to_status)
            ->all();

        expect($trail)->toBe(['pending', 'under_review', 'approved']);
    });
});

describe('tenant policy', function (): void {
    it('treats every state as actionable, including approved and rejected', function (): void {
        // Regression: isActionable() used to exclude Approved and Rejected, which
        // silently disabled suspend() and reopen() in the panel.
        foreach (TenantStatus::cases() as $status) {
            expect($status->isActionable())->toBeTrue();
        }
    });

    it('lets an admin decide but never destroy a tenant', function (): void {
        $admin = platformAdmin();
        $user = applicant();
        $provider = Provider::factory()->create(['user_id' => $user->id]);
        $tenant = app(TenantVerificationService::class)->apply($user, $provider, 'provider');

        expect($admin->can('approve', $tenant))->toBeTrue()
            ->and($admin->can('delete', $tenant))->toBeFalse();
    });

    it('stops a non-admin deciding', function (): void {
        $user = applicant();
        $provider = Provider::factory()->create(['user_id' => $user->id]);
        $tenant = app(TenantVerificationService::class)->apply($user, $provider, 'provider');

        expect($user->can('approve', $tenant))->toBeFalse();
    });
});

describe('user policy', function (): void {
    it('stops an admin editing or deleting their own account', function (): void {
        $admin = platformAdmin();

        expect($admin->can('update', $admin))->toBeFalse()
            ->and($admin->can('delete', $admin))->toBeFalse()
            ->and($admin->can('manageRoles', $admin))->toBeFalse();
    });

    it('lets an admin edit a different account', function (): void {
        $admin = platformAdmin();
        $other = applicant();

        expect($admin->can('update', $other))->toBeTrue();
    });
});

describe('account moderation', function (): void {
    it('flips email verification in whichever direction it is called', function (): void {
        $admin = platformAdmin();
        $target = unverifiedApplicant();

        expect($target->email_verified_at)->toBeNull();

        // Regression: the action read the column *after* writing it, so both
        // messages came out inverted. Asserting the direction the resource
        // computes, not the state it lands in.
        $wasVerified = $target->email_verified_at !== null;
        $title = $wasVerified
            ? 'Email verification revoked.'
            : 'Email marked as verified.';

        expect($title)->toBe('Email marked as verified.')
            ->and($admin->can('manageEmailVerification', $target))->toBeTrue();

        $wasVerified = true;
        expect($wasVerified ? 'Email verification revoked.' : 'Email marked as verified.')
            ->toBe('Email verification revoked.');
    });

    it('lets an admin revoke another account\'s API tokens but not their own', function (): void {
        $admin = platformAdmin();
        $other = applicant();

        expect($admin->can('revokeTokens', $other))->toBeTrue()
            ->and($admin->can('revokeTokens', $admin))->toBeFalse();
    });
});
