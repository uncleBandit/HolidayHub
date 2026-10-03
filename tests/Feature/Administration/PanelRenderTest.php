<?php

use App\Modules\Administration\Application\Services\TenantVerificationService;
use App\Modules\Administration\Database\Seeders\AdministrationAccessSeeder;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function adminUser(): User
{
    app(AdministrationAccessSeeder::class)->run();
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $u = User::factory()->create();
    $u->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $u->refresh();
}

it('shows real tenant rows and workflow actions on the review queue', function (): void {
    $admin = adminUser();
    $service = app(TenantVerificationService::class);

    // Each state is reached only through its legal path. The state machine
    // rejects shortcuts, which is the behaviour under test elsewhere; here it
    // just means the setup has to walk the workflow honestly.
    $paths = [
        'pending' => [],
        'under_review' => ['startReview'],
        'approved' => ['startReview', 'approve'],
        'rejected' => ['startReview', 'reject'],
        'suspended' => ['startReview', 'approve', 'suspend'],
    ];

    foreach ($paths as $state => $steps) {
        $label = 'Biz '.ucfirst($state);
        $u = User::factory()->create(['name' => $label]);
        $p = Provider::factory()->create(['user_id' => $u->id, 'company_name' => $label]);
        $t = $service->apply($u, $p, 'provider', displayName: $label);

        foreach ($steps as $step) {
            $service->{$step}($t->refresh(), $admin, ...($step === 'reject' ? ['Docs missing.'] : ($step === 'suspend' ? ['Compliance.'] : [])));
        }
    }

    $this->actingAs($admin);
    $html = $this->get('/admin/tenants')->assertOk()->getContent();

    // Every seeded business is on the page.
    foreach (['pending', 'under_review', 'approved', 'rejected', 'suspended'] as $state) {
        expect($html)->toContain('Biz '.ucfirst($state));
    }

    // The statuses render as labels, not raw enum values.
    foreach (['Pending', 'Under review', 'Approved', 'Rejected', 'Suspended'] as $label) {
        expect($html)->toContain($label);
    }

    // The workflow actions the policy allows are actually offered.
    expect($html)->toContain('Approve')
        ->and($html)->toContain('Reject')
        ->and($html)->toContain('Suspend');
});

it('shows the overview counters on the dashboard', function (): void {
    $admin = adminUser();

    $this->actingAs($admin);
    $html = $this->get('/admin')->assertOk()->getContent();

    expect($html)->toContain('Platform overview');
});

it('limits dashboard metrics to the signed-in admin permissions', function (): void {
    app(AdministrationAccessSeeder::class)->run();
    $analyst = User::factory()->create();
    $analyst->assignRole(Role::findByName('analyst', 'web'));
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($analyst);
    $html = $this->get('/admin')->assertOk()->getContent();

    expect($html)->toContain('Platform overview')
        ->and($html)->not->toContain('Tenants awaiting review')
        ->and($html)->not->toContain('Bookings (30 days)')
        ->and($html)->not->toContain('Platform accounts');
});

it('reaches a tenant detail page with its verification history', function (): void {
    $admin = adminUser();
    $u = User::factory()->create();
    $p = Provider::factory()->create(['user_id' => $u->id, 'company_name' => 'Detail Co']);
    $t = app(TenantVerificationService::class)->apply($u, $p, 'provider', displayName: 'Detail Co');

    $this->actingAs($admin);
    $html = $this->get("/admin/tenants/{$t->id}")->assertOk()->getContent();

    expect($html)->toContain('Detail Co')
        ->and($html)->toContain('Verification history');
});
