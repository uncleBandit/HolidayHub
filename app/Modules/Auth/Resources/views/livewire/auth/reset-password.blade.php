<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PasswordReset) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6 p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700">
    <x-auth-header
        :title="__('Update Your Password 🔐')"
        :description="__('Enter your email and new password to secure your account.')"
    />

    <x-auth-session-status class="text-center text-teal-700 dark:text-teal-400 font-medium" :status="session('status')" />

    <form method="POST" wire:submit="resetPassword" class="flex flex-col gap-6">
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autocomplete="email"
            placeholder="you@example.com"
        />

        <flux:input
            wire:model="password"
            :label="__('New password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Create a strong password')"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm new password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Re-enter your password')"
            viewable
        />

        <div class="flex items-center justify-end mt-2">
            <flux:button type="submit" class="w-full bg-stone-700 hover:bg-stone-800 text-white font-semibold py-3 px-4 rounded-lg shadow-md transition-all duration-200">
                {{ __('Reset Password') }}
            </flux:button>
        </div>
    </form>
</div>
