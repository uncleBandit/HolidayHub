<?php

namespace App\Modules\Administration\Database\Seeders;

use App\Modules\Administration\Application\Services\TenantVerificationService;
use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Agents\Domain\Models\Agent;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Seeds tenants across every verification state.
 *
 * The point is not the rows, it is coverage: the review queue, the status
 * filter, the "awaiting review" count on the list page and the dashboard
 * counters all read differently depending on which states exist, so every
 * state is represented.
 *
 * This is self-contained on purpose. It builds its own providers and agents
 * rather than borrowing the ones ProviderSeeder/AgentSeeder make, so
 * `php artisan module:seed administration` works on an otherwise empty
 * database instead of silently skipping everything.
 *
 * Idempotent: keyed on the profile, so re-running updates nothing and
 * duplicates nothing.
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->resolveAdmin();
        $service = app(TenantVerificationService::class);

        // One tenant per state, two for Pending, because that is the queue the
        // review screen is built around. Keying on the state rather than on a
        // row id is what makes this idempotent: a re-run finds the tenant
        // already sitting in that state and leaves it alone, instead of
        // appending another one every time.
        $plan = [
            ['provider', Provider::class, TenantStatus::Pending],
            ['provider', Provider::class, TenantStatus::Pending],
            ['provider', Provider::class, TenantStatus::UnderReview],
            ['provider', Provider::class, TenantStatus::Approved],
            ['agent', Agent::class, TenantStatus::Rejected],
            ['agent', Agent::class, TenantStatus::Suspended],
        ];

        $made = 0;

        foreach ($plan as $index => [$type, $profileClass, $target]) {
            if ($this->alreadyCovered($target, $index)) {
                continue;
            }

            $profile = $this->nextProfile($profileClass);

            if ($profile === null) {
                continue;
            }

            // apply() writes the Pending row plus the "submitted" history entry,
            // then the real service drives it to the target state. Going through
            // the service rather than writing the row directly is deliberate:
            // it is the only way the seeded data exercises the actual workflow,
            // including the denormalised is_verified sync on the profile.
            $tenant = $service->apply(
                user: $profile->user,
                tenantable: $profile,
                type: $type,
                displayName: $profile->company_name ?? $profile->user->name,
            );

            if ($tenant->status !== $target) {
                $this->advance($service, $tenant, $target, $admin);
            }

            $made++;
        }

        $this->command?->info(sprintf(
            '  Tenants: %d total (%d pending, %d under review, %d approved, %d rejected, %d suspended) — %d created this run',
            Tenant::withTrashed()->count(),
            Tenant::pending()->count(),
            Tenant::where('status', TenantStatus::UnderReview)->count(),
            Tenant::approved()->count(),
            Tenant::where('status', TenantStatus::Rejected)->count(),
            Tenant::where('status', TenantStatus::Suspended)->count(),
            $made,
        ));
    }

    /**
     * Whether the plan slot is already satisfied.
     *
     * Pending appears twice in the plan, so the second slot only counts as
     * covered once there are two pending tenants; otherwise a re-run would see
     * one pending row, decide slot 0 is filled, and then create another one
     * every time.
     */
    private function alreadyCovered(TenantStatus $status, int $slot): bool
    {
        $need = $status === TenantStatus::Pending ? 2 : 1;

        return Tenant::where('status', $status->value)->count() >= $need
            || ($status !== TenantStatus::Pending && $slot === 0
                && Tenant::where('status', $status->value)->exists());
    }

    /**
     * An unlinked profile of the requested kind, created on demand.
     *
     * Reads the linked keys out of the tenants table and filters in PHP. The
     * obvious alternatives are both worse: whereDoesntHaveMorph('tenantable')
     * needs a tenantable() relation on Provider and Agent, and adding one would
     * make those modules import Administration purely so a seeder could run;
     * and a correlated whereColumn against a namespaced model produces SQL that
     * Postgres rejects, because the model name is not a valid quoted identifier.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $profileClass
     */
    private function nextProfile(string $profileClass): ?Model
    {
        $alias = (new $profileClass)->getMorphClass();

        $linked = Tenant::withTrashed()
            ->where('tenantable_type', $alias)
            ->pluck('tenantable_id')
            ->all();

        $existing = $profileClass::query()
            // AppServiceProvider turns on preventLazyLoading and the caller reads
            // $profile->user, so the relation has to be loaded up front.
            ->with('user')
            ->whereNotIn((new $profileClass)->getKeyName(), $linked)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        // Nothing spare: make one rather than skipping the slot, so a standalone
        // `module:seed administration` on an empty database still produces a
        // tenant in every state.
        return $profileClass::factory()->create();
    }

    /**
     * Walk a freshly-applied tenant to the requested state via the service,
     * recording each hop so the verification history is coherent.
     */
    private function advance(
        TenantVerificationService $service,
        Tenant $tenant,
        TenantStatus $target,
        ?User $admin,
    ): void {
        $admin ??= $this->resolveAdmin();

        // Every transition below records an admin decision, so an admin is
        // required. Without one, leave the tenant Pending rather than
        // fabricating a decision against a null actor.
        if ($admin === null) {
            return;
        }

        if ($target === TenantStatus::UnderReview) {
            $service->startReview($tenant, $admin);

            return;
        }

        if ($target === TenantStatus::Rejected) {
            $service->reject($tenant, $admin, 'Registration documents could not be confirmed.');

            return;
        }

        if ($target === TenantStatus::Suspended) {
            $service->approve($tenant, $admin);
            $service->suspend($tenant, $admin, 'Suspended pending a compliance check.');

            return;
        }

        if ($target === TenantStatus::Approved) {
            $service->approve($tenant, $admin);
        }
    }

    /**
     * The admin whose id lands in verified_by, so the seeded rows are not
     * attributed to a fabricated actor.
     */
    private function resolveAdmin(): ?User
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();
    }
}
