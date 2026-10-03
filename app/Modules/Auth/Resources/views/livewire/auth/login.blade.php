<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public bool $showPasswordForm = false;

    public function showEmailLogin(): void
    {
        $this->showPasswordForm = true;
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $defaultRoute = Auth::user()?->hasRole('provider')
            ? route('provider.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        $this->redirectIntended(default: $defaultRoute, navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="flex w-full flex-col gap-7">
    <x-auth-header
        eyebrow="Welcome back"
        title="{{ __('Pick up where the reels left off.') }}"
        description="{{ __('Sign in to reach your saved stays, bookings and host dashboard.') }}"
    />

    @if ($errors->has('google'))
        <p class="auth-alert auth-alert-error" role="alert">{{ $errors->first('google') }}</p>
    @endif

    @if (session('status'))
        <p class="auth-alert auth-alert-ok" role="status">{{ session('status') }}</p>
    @endif

    <x-auth.google>{{ __('Continue with Google') }}</x-auth.google>

    @if (! $showPasswordForm)
        {{-- Guests arrive through Google; email sign-in is one tap away for
             hosts, agents and admins who need their workspace. --}}
        <div class="flex flex-col gap-4">
            <button
                type="button"
                wire:click="showEmailLogin"
                class="auth-btn"
            >
                {{ __('Sign in with email') }}
                <x-line-icon name="arrow" :size="17" />
            </button>

            <p class="text-center text-[11px] leading-relaxed text-muted">
                {{ __('Travelling with us? Google is all you need — no password to remember.') }}
            </p>
        </div>
    @else
        <p class="auth-divider">{{ __('email and password') }}</p>

        <form wire:submit="login" class="flex flex-col gap-5">
            <x-auth.field
                wire:model.live="email"
                id="email"
                label="{{ __('Email address') }}"
                type="email"
                required
                autocomplete="email"
                placeholder="you@example.com"
            />

            <x-auth.field
                wire:model.live="password"
                id="password"
                label="{{ __('Password') }}"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex cursor-pointer items-center gap-2.5 text-[12px] font-semibold text-ink">
                    <input type="checkbox" wire:model="remember" class="auth-check rounded">
                    {{ __('Remember me') }}
                </label>

                <a href="{{ route('password.request') }}" class="auth-link">
                    {{ __('Forgot your password?') }}
                </a>
            </div>

            <button
                type="submit"
                class="auth-btn"
                wire:loading.attr="disabled"
                wire:target="login"
            >
                <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
                <span wire:loading wire:target="login">{{ __('Logging in…') }}</span>
            </button>
        </form>

        <button
            type="button"
            wire:click="$set('showPasswordForm', false)"
            class="auth-link mx-auto"
        >{{ __('Use Google instead') }}</button>
    @endif

    <div class="mt-1 flex flex-col items-center gap-2 border-t border-line pt-6 text-center">
        <p class="auth-foot !mt-0">
            {{ __("Don't have an account?") }}
            <a href="{{ route('register') }}" class="auth-link">{{ __('Create one') }}</a>
        </p>

        <a href="{{ route('register.provider') }}" class="auth-link">
            {{ __('List your place as a provider') }}
        </a>
    </div>
</div>
