<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

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

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
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

<div class="flex flex-col gap-6 p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700">

    <div class="flex flex-col gap-8">
        {{-- The header component for a clear, welcoming message --}}
        <x-auth-header title="{{ __('Welcome back') }}" description="{{ __('Log in to continue your journey.') }}" />

        {{-- Optional session status message (e.g., password reset success) --}}
        @if (session('status'))
            <div class="p-3 text-sm text-center text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        {{-- The main login form --}}
        <form wire:submit="login" class="flex flex-col gap-6">

            {{-- Email Input with an icon and a unique ID to link with the label --}}
            <x-text-input
                wire:model.live="email"
                id="email"
                label="{{ __('Email address') }}"
                type="email"
                required
                autocomplete="email"
                placeholder="your-email@example.com"
                icon="M16 12a4 4 0 10-8 0 4 4 0 008 0zM12 14a2 2 0 110-4 2 2 0 010 4zM21 12a9 9 0 11-18 0 9 9 0 0118 0zM10.552 16.58A6.47 6.47 0 0112 10.5a6.47 6.47 0 011.448 6.08A8.96 8.96 0 0112 21a8.96 8.96 0 01-1.448-4.42z"
            />

            {{-- Password Input with an icon and a unique ID --}}
            <x-text-input
                wire:model.live="password"
                id="password"
                label="{{ __('Password') }}"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                icon="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z"
            />

            {{-- Remember me checkbox and "Forgot Password" link --}}
            <div class="flex items-center justify-between">
                <x-checkbox-input wire:model="remember" label="{{ __('Remember me') }}" />
                <a href="{{ route('password.request') }}" class="text-sm text-tropical-blue hover:underline transition-colors duration-200">
                    {{ __('Forgot your password?') }}
                </a>
            </div>

            {{-- The dynamic action button with a loading state --}}
            <div class="mt-2">
                <x-primary-button
                    type="submit"
                    class="w-full"
                    wire:loading.attr="disabled"
                    wire:loading.class="cursor-not-allowed"
                    wire:target="login"
                    spinner="Logging in..."
                >
                    {{ __('Log in') }}
                </x-primary-button>
            </div>
        </form>

        {{-- The "Don't have an account?" link --}}
        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __("Don't have an account?") }}
            <a href="{{ route('register') }}" class="text-tropical-blue hover:underline font-semibold transition-colors duration-200">
                {{ __('Sign up') }}
            </a>
        </div>
    </div>
</div>
