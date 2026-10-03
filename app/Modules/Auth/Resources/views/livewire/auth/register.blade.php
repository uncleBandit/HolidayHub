<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {};
?>

<div class="flex w-full flex-col gap-7">
    <x-auth-header
        eyebrow="Join HolidayHub"
        title="{{ __('Your next stay starts with one tap.') }}"
        description="{{ __('Create an account to save the reels you love, follow hosts and book in a few taps.') }}"
    />

    @if ($errors->has('google'))
        <p class="auth-alert auth-alert-error" role="alert">{{ $errors->first('google') }}</p>
    @endif

    <div class="flex flex-col gap-4">
        <x-auth.google>{{ __('Sign up with Google') }}</x-auth.google>

        <a href="{{ route('register.provider') }}" class="auth-btn auth-btn-ghost">
            <x-line-icon name="bed" :size="17" />
            {{ __('Apply as a service provider') }}
        </a>
    </div>

    <ul class="flex flex-col gap-2.5 border-t border-line pt-6 text-[12px] leading-relaxed text-muted">
        <li class="flex items-start gap-2.5">
            <x-line-icon name="play" :size="15" class="mt-0.5 text-clay" />
            {{ __('Watch every stay as a reel before you book it.') }}
        </li>
        <li class="flex items-start gap-2.5">
            <x-line-icon name="bookmark" :size="15" class="mt-0.5 text-clay" />
            {{ __('Save stays and hosts to compare later.') }}
        </li>
        <li class="flex items-start gap-2.5">
            <x-line-icon name="bell" :size="15" class="mt-0.5 text-clay" />
            {{ __('Get told the moment a saved stay drops its rate.') }}
        </li>
    </ul>

    <p class="auth-foot">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="auth-link">{{ __('Log in') }}</a>
    </p>
</div>