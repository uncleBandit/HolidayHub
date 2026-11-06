<?php

use App\Modules\Identity\Domain\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('the old dashboard address leads to the For you feed', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertRedirect('/for-you');
});

test('authenticated users can visit their For you feed', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});
