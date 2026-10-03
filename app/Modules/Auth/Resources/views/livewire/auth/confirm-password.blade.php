<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>
<div class="flex w-full flex-col gap-7">
    <x-auth-header
        eyebrow="Secure area"
        title="{{ __('Confirm your password') }}"
        description="{{ __('One more step before you continue. This keeps your bookings and payouts yours.') }}"
    />

    @if (session('status'))
        <p class="auth-alert auth-alert-ok" role="status">{{ session('status') }}</p>
    @endif

    <form method="POST" wire:submit="confirmPassword" class="flex flex-col gap-5">
        <x-auth.field
            wire:model="password"
            label="{{ __('Password') }}"
            type="password"
            required
            autocomplete="current-password"
            placeholder="••••••••"
        />

        <button type="submit" class="auth-btn" wire:loading.attr="disabled" wire:target="confirmPassword">
            {{ __('Confirm and continue') }}
            <x-line-icon name="arrow" :size="17" />
        </button>
    </form>
</div>
