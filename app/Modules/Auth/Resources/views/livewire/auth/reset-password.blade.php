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
<div class="flex w-full flex-col gap-7">
    <x-auth-header
        eyebrow="Almost there"
        title="{{ __('Choose a new password.') }}"
        description="{{ __('Pick something you have not used before, then confirm it to keep your account safe.') }}"
    />

    @if (session('status'))
        <p class="auth-alert auth-alert-ok" role="status">{{ session('status') }}</p>
    @endif

    <form method="POST" wire:submit="resetPassword" class="flex flex-col gap-5">
        <x-auth.field
            wire:model="email"
            label="{{ __('Email address') }}"
            type="email"
            required
            autocomplete="email"
            placeholder="you@example.com"
        />

        <x-auth.field
            wire:model="password"
            label="{{ __('New password') }}"
            type="password"
            required
            autocomplete="new-password"
            placeholder="{{ __('Create a strong password') }}"
        />

        <x-auth.field
            wire:model="password_confirmation"
            label="{{ __('Confirm new password') }}"
            type="password"
            required
            autocomplete="new-password"
            placeholder="{{ __('Re-enter your new password') }}"
        />

        <button type="submit" class="auth-btn" wire:loading.attr="disabled" wire:target="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">{{ __('Reset password') }}</span>
            <span wire:loading wire:target="resetPassword">{{ __('Saving…') }}</span>
        </button>
    </form>
</div>
