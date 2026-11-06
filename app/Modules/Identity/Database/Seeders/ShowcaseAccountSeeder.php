<?php

namespace App\Modules\Identity\Database\Seeders;

use App\Modules\Identity\Domain\Models\Guest;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Accounts the showcase data hangs off.
 *
 * - demo@holidayhub.test / "password": a verified traveller in Nairobi.
 * - A guest profile for every other email-verified, non-admin user, so their
 *   dashboard and "For you" feed have trips and saved stays to render.
 * - The reviewer recorded on showcase approvals. An existing platform admin is
 *   preferred; otherwise a login-less curator account is created (random
 *   password, no role, unverified email) purely so verification history and
 *   moderation audit rows point at a real user.
 *
 * Idempotent: users match on email, guests on user_id. Existing guest
 * city/country values are never overwritten.
 */
class ShowcaseAccountSeeder extends Seeder
{
    public const DEMO_EMAIL = 'demo@holidayhub.test';

    public const CURATOR_EMAIL = 'curator@holidayhub.test';

    public function run(): void
    {
        $guestRole = Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']);

        $demo = User::firstOrCreate(
            ['email' => self::DEMO_EMAIL],
            ['name' => 'Wanjiku Demo', 'password' => Hash::make('password')],
        );

        if (! Hash::check('password', $demo->password)) {
            $demo->forceFill(['password' => Hash::make('password')]);
        }
        if ($demo->email_verified_at === null) {
            $demo->forceFill(['email_verified_at' => now()]);
        }
        if ($demo->isDirty()) {
            $demo->save();
        }
        if (! $demo->hasRole($guestRole)) {
            $demo->assignRole($guestRole);
        }

        $demoGuest = $this->ensureGuest($demo, ['first_name' => 'Wanjiku', 'last_name' => 'Demo', 'phone' => '+254700100100']);
        // The demo profile is ours, so it is pinned to Nairobi even if it was blank.
        $demoGuest->forceFill(['preferred_currency' => 'KES', 'nationality' => $demoGuest->nationality ?: 'Kenyan']);
        if ($demoGuest->isDirty()) {
            $demoGuest->save();
        }

        $profiles = 0;
        foreach (self::travellers() as $user) {
            $this->ensureGuest($user);
            $profiles++;
        }

        self::reviewer();

        $this->command?->info(sprintf('Demo traveller %s ready; %d verified traveller profiles ensured.', self::DEMO_EMAIL, $profiles));
    }

    /**
     * Every email-verified user that is not a platform admin.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function travellers()
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->orderBy('id')
            ->get()
            ->reject(fn (User $user) => $user->isPlatformAdmin())
            ->values();
    }

    public static function reviewer(): User
    {
        if (Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            $admin = User::role('super_admin')->orderBy('id')->first();
            if ($admin) {
                return $admin;
            }
        }

        return User::firstOrCreate(
            ['email' => self::CURATOR_EMAIL],
            ['name' => 'HolidayHub Showcase Curator', 'password' => Hash::make(Str::random(48))],
        );
    }

    private function ensureGuest(User $user, array $defaults = []): Guest
    {
        $guest = Guest::withTrashed()->firstOrNew(['user_id' => $user->id]);

        if (! $guest->exists) {
            [$first, $last] = array_pad(explode(' ', trim($user->name), 2), 2, '');
            $guest->fill([
                'first_name' => $defaults['first_name'] ?? ($first ?: 'Traveller'),
                'last_name' => $defaults['last_name'] ?? ($last ?: 'Guest'),
                'email' => $user->email,
                'phone' => $defaults['phone'] ?? null,
                'preferred_currency' => 'KES',
            ]);
        }

        if ($guest->trashed()) {
            $guest->deleted_at = null;
        }
        if (blank($guest->city)) {
            $guest->city = 'Nairobi';
        }
        if (blank($guest->country)) {
            $guest->country = 'Kenya';
        }

        if (! $guest->exists || $guest->isDirty()) {
            $guest->save();
        }

        return $guest;
    }
}
