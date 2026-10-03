<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {};
?>

<div class="flex w-full flex-col gap-7">
    <x-auth-header
        eyebrow="For hosts"
        title="{{ __('Fill the rooms. We’ll film the stay.') }}"
        description="{{ __('Create your provider account, then send your business through review. Approved hosts get a reel, a booking page and a dashboard.') }}"
    />

    @if ($errors->has('google'))
        <p class="auth-alert auth-alert-error" role="alert">{{ $errors->first('google') }}</p>
    @endif

    <x-auth.google :href="route('auth.google.redirect', ['type' => 'provider'])">
        {{ __('Continue with Google') }}
    </x-auth.google>

    <p class="auth-divider">{{ __('or apply with email') }}</p>

    <form method="POST" action="{{ route('register.provider.store') }}" class="flex flex-col gap-5">
        @csrf

        <x-auth.field
            id="name"
            name="name"
            label="{{ __('Your name') }}"
            type="text"
            required
            autocomplete="name"
            placeholder="Amina Odhiambo"
            value="{{ old('name') }}"
        />

        <x-auth.field
            id="email"
            name="email"
            label="{{ __('Email address') }}"
            type="email"
            required
            autocomplete="email"
            placeholder="you@example.com"
            value="{{ old('email') }}"
        />

        <x-auth.field
            id="password"
            name="password"
            label="{{ __('Password') }}"
            type="password"
            required
            autocomplete="new-password"
            placeholder="••••••••"
        />

        <x-auth.field
            id="password_confirmation"
            name="password_confirmation"
            label="{{ __('Confirm password') }}"
            type="password"
            required
            autocomplete="new-password"
            placeholder="••••••••"
        />

        <button type="submit" class="auth-btn">
            {{ __('Send application for review') }}
            <x-line-icon name="arrow" :size="17" />
        </button>
    </form>

    <p class="auth-foot">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="auth-link">{{ __('Log in') }}</a>
    </p>
</div>