<?php

namespace App\Modules\Providers\Database\Seeders;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * The three host brands that own the showcase catalogue.
 *
 * Reels print the provider's company name, so the showcase stays and
 * experiences are owned by believable brands rather than "Provider Company 3".
 * Keyed by a short handle the other showcase seeders use to look them up.
 *
 * Idempotent: users and providers are matched on email.
 */
class ShowcaseProviderSeeder extends Seeder
{
    public const PROVIDERS = [
        'coast' => [
            'email' => 'hosts@swahilicoast.holidayhub.test',
            'name' => 'Amina Mwakio',
            'company_name' => 'Swahili Coast Stays',
            'phone' => '+254700100201',
            'provider_type' => 'resort',
            'city' => 'Diani',
            'country' => 'Kenya',
            'bio' => 'Family-run beach hotels, villas and guesthouses from Diani to Lamu, with the dhow captains and cooks we have worked with for years.',
        ],
        'safari' => [
            'email' => 'hosts@savannahsummit.holidayhub.test',
            'name' => 'Daniel Ole Sankale',
            'company_name' => 'Savannah & Summit Co.',
            'phone' => '+254700100202',
            'provider_type' => 'camp',
            'city' => 'Nanyuki',
            'country' => 'Kenya',
            'bio' => 'Safari lodges, highland farm stays and guided drives and treks across the Mara, Amboseli, Tsavo and Mount Kenya.',
        ],
        'world' => [
            'email' => 'hosts@wayfarer.holidayhub.test',
            'name' => 'Grace Wanjiru',
            'company_name' => 'Wayfarer Collection',
            'phone' => '+254700100203',
            'provider_type' => 'other',
            'city' => 'Nairobi',
            'country' => 'Kenya',
            'bio' => 'A small, personally inspected collection of stays and experiences beyond East Africa: Kyoto, the Ligurian coast and Morocco.',
        ],
    ];

    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        foreach (self::PROVIDERS as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make(Str::random(40))],
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            $provider = Provider::withTrashed()->firstOrNew(['email' => $data['email']]);
            $provider->fill([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'contact_person' => $data['name'],
                'phone' => $data['phone'],
                'bio' => $data['bio'],
                'provider_type' => $data['provider_type'],
                'city' => $data['city'],
                'country' => $data['country'],
                'active' => true,
            ]);
            $provider->forceFill([
                'is_verified' => true,
                'verified_at' => $provider->verified_at ?? now(),
                'deleted_at' => null,
            ]);

            if (! $provider->exists || $provider->isDirty()) {
                $provider->save();
            }
        }

        $this->command?->info('Showcase providers ready: '.implode(', ', array_column(self::PROVIDERS, 'company_name')).'.');
    }

    public static function provider(string $handle): Provider
    {
        return Provider::query()->where('email', self::PROVIDERS[$handle]['email'])->firstOrFail();
    }
}
