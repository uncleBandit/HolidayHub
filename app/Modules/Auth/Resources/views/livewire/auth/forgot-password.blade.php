<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}; ?>
<div class="flex w-full flex-col gap-7">
    <x-auth-header
        eyebrow="Password help"
        title="{{ __('Forgot your password?') }}"
        description="{{ __('No problem. Leave your email and we will send a reset link straight to your inbox.') }}"
    />

    @if (session('status'))
        <p class="auth-alert auth-alert-ok" role="status">{{ session('status') }}</p>
    @endif

    <form method="POST" wire:submit="sendPasswordResetLink" class="flex flex-col gap-5">
        <x-auth.field
            wire:model="email"
            label="{{ __('Email address') }}"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="you@example.com"
        />

        <button type="submit" class="auth-btn" wire:loading.attr="disabled" wire:target="sendPasswordResetLink">
            <span wire:loading.remove wire:target="sendPasswordResetLink">{{ __('Send reset link') }}</span>
            <span wire:loading wire:target="sendPasswordResetLink">{{ __('Sending…') }}</span>
        </button>
    </form>

    <p class="auth-foot">
        {{ __('Remembered it after all?') }}
        <a href="{{ route('login') }}" class="auth-link" wire:navigate>{{ __('Back to log in') }}</a>
    </p>
</div>
