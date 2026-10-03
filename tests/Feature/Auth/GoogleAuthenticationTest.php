<?php

use App\Modules\Administration\Database\Seeders\AdministrationAccessSeeder;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

function mockGoogleAccount(array $attributes = []): void
{
    $googleUser = GoogleUser::fake(array_merge([
        'id' => 'google-user-123',
        'name' => 'Google Guest',
        'email' => 'guest@example.com',
        'verified_email' => true,
    ], $attributes));

    $driver = Mockery::mock();
    $driver->shouldReceive('user')->once()->andReturn($googleUser);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($driver);
}

it('registers and signs in a guest using a verified Google identity', function (): void {
    mockGoogleAccount();

    $this->withSession(['google_account_type' => 'guest'])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard'));

    $user = User::where('email', 'guest@example.com')->firstOrFail();
    expect($user->google_id)->toBe('google-user-123')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasRole('guest'))->toBeTrue()
        ->and(Hash::check('not-the-google-password', $user->password))->toBeFalse();

    $this->assertAuthenticatedAs($user);
});

it('creates a pending provider application when using Google signup', function (): void {
    mockGoogleAccount([
        'id' => 'google-provider-456',
        'name' => 'Provider Owner',
        'email' => 'owner@example.com',
    ]);

    $this->withSession(['google_account_type' => 'provider'])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('provider.dashboard'));

    $user = User::where('email', 'owner@example.com')->firstOrFail();
    expect($user->hasRole('provider'))->toBeTrue()
        ->and($user->provider)->toBeInstanceOf(Provider::class)
        ->and(Tenant::where('user_id', $user->id)->value('status')->value)->toBe('pending');
});

it('supports email and password provider registration and creates a verification application', function (): void {
    $this->withoutMiddleware([ValidateCsrfToken::class, ThrottleRequests::class])
        ->post(route('register.provider.store'), [
            'name' => 'Provider Owner',
            'email' => 'password-provider@example.com',
            'password' => 'StrongPassword!123',
            'password_confirmation' => 'StrongPassword!123',
        ])->assertRedirect(route('provider.dashboard'));

    $user = User::where('email', 'password-provider@example.com')->firstOrFail();
    expect($user->hasRole('provider'))->toBeTrue()
        ->and($user->provider)->toBeInstanceOf(Provider::class)
        ->and(Tenant::where('user_id', $user->id)->value('status')->value)->toBe('pending');
});

it('does not allow Google sign-in for administrative accounts', function (): void {
    app(AdministrationAccessSeeder::class)->run();
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole(Role::findByName('platform_admin', 'web'));
    mockGoogleAccount([
        'id' => 'google-admin-789',
        'email' => 'admin@example.com',
    ]);

    $this->withSession(['google_account_type' => 'guest'])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    expect($admin->fresh()->google_id)->toBeNull()
        ->and($this->isAuthenticated())->toBeFalse();
});

it('refuses Google accounts without a verified email', function (): void {
    mockGoogleAccount([
        'verified_email' => false,
    ]);

    $this->withSession(['google_account_type' => 'guest'])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    expect(User::where('email', 'guest@example.com')->exists())->toBeFalse();
});

it('offers Google-only signup for guests and both signup methods for providers', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Sign up with Google')
        ->assertDontSee('name="password"', false);

    $this->get(route('register.provider'))
        ->assertOk()
        ->assertSee('Continue with Google')
        ->assertSee('name="password"', false);
});

it('makes Google the default login method and keeps credentials behind the staff/provider option', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Continue with Google')
        ->assertSee('Service provider or administrator? Sign in with email and password')
        ->assertDontSee('id="password"', false);

    Volt::test('auth.login')
        ->assertDontSee('id="password"', false)
        ->call('showEmailLogin')
        ->assertSee('id="password"', false);
});

it('retains email and password sign-in for service providers', function (): void {
    $user = User::factory()->create([
        'email' => 'provider@example.com',
        'password' => Hash::make('ProviderPassword!123'),
    ]);
    $user->assignRole(Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']));

    Volt::test('auth.login')
        ->call('showEmailLogin')
        ->set('email', 'provider@example.com')
        ->set('password', 'ProviderPassword!123')
        ->call('login')
        ->assertRedirect(route('provider.dashboard'));

    $this->assertAuthenticatedAs($user);
});
