<?php

use App\Modules\Administration\Database\Seeders\AdministrationAccessSeeder;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Audit\Domain\Models\AuditLog;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('grants admin panel access without granting every operational permission', function (): void {
    app(AdministrationAccessSeeder::class)->run();

    $finance = User::factory()->create();
    $finance->assignRole(Role::findByName('finance_manager', 'web'));

    expect($finance->can('admin.panel.access'))->toBeTrue()
        ->and($finance->can('payments.refund'))->toBeTrue()
        ->and($finance->can('activities.approve'))->toBeFalse()
        ->and($finance->can('users.roles.manage'))->toBeFalse();

    $this->actingAs($finance)
        ->get('/admin/activity-moderations')
        ->assertForbidden();

    $this->actingAs($finance)
        ->get('/admin/booking-operations')
        ->assertOk();
});

it('records actor, reason, state change and request context in immutable audit entries', function (): void {
    $actor = User::factory()->create();
    $subject = User::factory()->create();

    request()->headers->set('X-Request-Id', 'req-admin-test-42');
    $log = app(AuditRecorder::class)->record(
        'users.suspended',
        $subject,
        $actor,
        before: ['suspended' => false],
        after: ['suspended' => true],
        reason: 'Repeated policy violations.',
    );

    expect($log->admin_id)->toBe($actor->id)
        ->and($log->subject_type)->toBe($subject->getMorphClass())
        ->and($log->before)->toBe(['suspended' => false])
        ->and($log->after)->toBe(['suspended' => true])
        ->and($log->reason)->toBe('Repeated policy violations.')
        ->and($log->request_id)->toBe('req-admin-test-42')
        ->and($log->correlation_id)->not->toBeEmpty();

    expect(fn () => $log->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class, 'Audit records are immutable.');
    expect(fn () => $log->delete())
        ->toThrow(LogicException::class, 'Audit records are immutable.');

    expect(AuditLog::query()->whereKey($log->id)->value('reason'))
        ->toBe('Repeated policy violations.');
});
