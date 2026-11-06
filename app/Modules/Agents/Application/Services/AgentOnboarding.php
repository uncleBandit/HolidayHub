<?php

namespace App\Modules\Agents\Application\Services;

use App\Modules\Administration\Application\Services\TenantVerificationService;
use App\Modules\Agents\Domain\Models\Agent;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * A signed-in traveller applies to sell packages as a travel agent.
 *
 * They get the `agent` role and an Agent profile straight away, unverified,
 * so they can start building packages; the application joins the admin
 * verification queue (TenantVerificationService, as hosts do), and approving
 * it there sets is_verified, which unlocks submitting packages for review.
 */
final class AgentOnboarding
{
    /**
     * @param  array{agency_name: string, first_name?: ?string, last_name?: ?string, phone?: ?string, agency_license?: ?string, business_type?: string, specialization?: ?string, country?: ?string, city?: ?string, bio?: ?string}  $details
     */
    public function apply(User $user, array $details): Agent
    {
        return DB::transaction(function () use ($user, $details): Agent {
            [$first, $last] = $this->names($user, $details);

            $agent = Agent::withTrashed()->firstOrNew(['user_id' => $user->id]);
            if ($agent->trashed()) {
                $agent->restore();
            }

            $agent->fill([
                'first_name' => $first,
                'last_name' => $last,
                'email' => $agent->email ?: $user->email,
                'phone' => filled($details['phone'] ?? null) ? trim($details['phone']) : $agent->phone,
                'agency_name' => trim($details['agency_name']),
                'agency_license' => filled($details['agency_license'] ?? null) ? trim($details['agency_license']) : null,
                'business_type' => in_array($details['business_type'] ?? null, ['individual', 'agency', 'corporate'], true) ? $details['business_type'] : 'agency',
                'specialization' => filled($details['specialization'] ?? null) ? trim($details['specialization']) : null,
                'country' => filled($details['country'] ?? null) ? trim($details['country']) : null,
                'city' => filled($details['city'] ?? null) ? trim($details['city']) : null,
                'bio' => filled($details['bio'] ?? null) ? Str::limit(trim($details['bio']), 250, '') : null,
                'active' => true,
            ]);
            $agent->save();

            $user->assignRole(Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']));

            app(TenantVerificationService::class)->apply(
                $user,
                $agent,
                'agent',
                displayName: $agent->agency_name,
                contactEmail: $agent->email,
            );

            return $agent->refresh();
        });
    }

    private function names(User $user, array $details): array
    {
        $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];

        return [
            trim((string) ($details['first_name'] ?? '')) ?: ($parts[0] ?? 'Agent'),
            trim((string) ($details['last_name'] ?? '')) ?: ($parts[1] ?? ($parts[0] ?? 'Agent')),
        ];
    }
}
