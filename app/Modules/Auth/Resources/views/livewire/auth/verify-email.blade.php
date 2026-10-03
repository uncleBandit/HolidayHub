<?php

use App\Modules\Auth\Presentation\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>
<div class="flex w-full flex-col gap-7">
    <x-auth-header
        eyebrow="One last tap"
        title="{{ __('Confirm your email.') }}"
        description="{{ __('We sent you a link. Open it and your account is ready for its first stay.') }}"
    />

    @if (session('status') == 'verification-link-sent')
        <p class="auth-alert auth-alert-ok" role="status">
            {{ __('A fresh verification link is on its way to the email you signed up with.') }}
        </p>
    @endif

    <div class="flex flex-col gap-4">
        <button type="button" wire:click="sendVerification" class="auth-btn" wire:loading.attr="disabled" wire:target="sendVerification">
            {{ __('Resend verification email') }}
        </button>

        <button type="button" wire:click="logout" class="auth-link mx-auto">
            {{ __('Log out') }}
        </button>
    </div>
</div>
