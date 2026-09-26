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

<div class="flex flex-col gap-6 p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700">
    <x-auth-header
        :title="__('Forgot Your Password? 🔑')"
        :description="__('No problem. Enter your email below and we will send you a password reset link.')"
    />

    <x-auth-session-status class="text-center text-teal-700 dark:text-teal-400 font-medium" :status="session('status')" />

    <form method="POST" wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            placeholder="you@example.com"
        />

        <flux:button type="submit" class="w-full bg-stone-700 hover:bg-stone-800 text-white font-semibold py-3 px-4 rounded-lg shadow-md transition-all duration-200">
            {{ __('Send Reset Link') }}
        </flux:button>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-500 dark:text-zinc-400">
        <span>{{ __('Remember your password?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Return to log in') }}</flux:link>
    </div>
</div>
