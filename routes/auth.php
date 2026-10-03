<?php

use App\Modules\Auth\Presentation\Http\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', [\App\Modules\Auth\Presentation\Http\Controllers\GoogleAuthenticationController::class, 'redirect'])
        ->name('auth.google.redirect');

    Route::get('auth/google/callback', [\App\Modules\Auth\Presentation\Http\Controllers\GoogleAuthenticationController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('auth.google.callback');

    Volt::route('login', 'auth.login')
        ->name('login');

    Volt::route('register', 'auth.register')
        ->name('register');

    Volt::route('register/provider', 'auth.provider-register')
        ->name('register.provider');

    Route::post('register/provider', [\App\Modules\Auth\Presentation\Http\Controllers\ProviderRegistrationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.provider.store');

    Volt::route('forgot-password', 'auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'auth.reset-password')
        ->name('password.reset');

});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'auth.confirm-password')
        ->name('password.confirm');
});

Route::post('logout', App\Modules\Auth\Presentation\Livewire\Actions\Logout::class)
    ->name('logout');
