<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('guest registration directs users to Google instead of collecting a password', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Sign up with Google')
        ->assertDontSee('name="password"', false);
});
