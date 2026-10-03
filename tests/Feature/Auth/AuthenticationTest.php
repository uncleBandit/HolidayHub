<?php

use App\Modules\Identity\Domain\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Livewire\Volt\Volt;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    Volt::test('auth.login')
        ->call('showEmailLogin')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    Volt::test('auth.login')
        ->call('showEmailLogin')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->withoutMiddleware(ValidateCsrfToken::class)
        ->actingAs($user)
        ->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
