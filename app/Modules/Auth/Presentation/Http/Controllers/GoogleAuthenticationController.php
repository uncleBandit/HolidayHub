<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\Services\ProviderAccountOnboarding;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Spatie\Permission\Models\Role;

class GoogleAuthenticationController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $accountType = $request->query('type', 'guest');
        abort_unless(in_array($accountType, ['guest', 'provider'], true), 400);

        $request->session()->put('google_account_type', $accountType);

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $accountType = $request->session()->pull('google_account_type');
        if (! in_array($accountType, ['guest', 'provider'], true)) {
            return redirect()->route('login')->withErrors([
                'google' => 'Your Google sign-in session expired. Please try again.',
            ]);
        }

        if ($request->filled('error')) {
            return $this->failedSignIn($accountType, 'Google sign-in was cancelled. You can try again when ready.');
        }

        try {
            /** @var GoogleUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return $this->failedSignIn($accountType, 'Your Google sign-in session expired. Please try again.');
        }
        $email = Str::lower((string) $googleUser->getEmail());

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $this->hasVerifiedEmail($googleUser)) {
            return $this->failedSignIn($accountType, 'Google did not provide a verified email address for this account.');
        }

        $user = DB::transaction(function () use ($googleUser, $email, $accountType): ?User {
            $linkedUser = User::query()
                ->where('google_id', $googleUser->getId())
                ->lockForUpdate()
                ->first();
            $emailUser = User::query()->where('email', $email)->lockForUpdate()->first();

            if ($linkedUser && $emailUser && $linkedUser->isNot($emailUser)) {
                abort(409, 'This Google account is linked to a different account.');
            }

            $user = $linkedUser ?? $emailUser;

            if ($user?->can('admin.panel.access')) {
                return null;
            }

            if (! $user) {
                $user = User::create([
                    'name' => $googleUser->getName() ?: $email,
                    'email' => $email,
                    'password' => Str::random(64),
                    'google_id' => $googleUser->getId(),
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            } else {
                if ($user->google_id !== null && $user->google_id !== $googleUser->getId()) {
                    abort(409, 'This email is already linked to a different Google account.');
                }

                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }

            if ($accountType === 'provider') {
                app(ProviderAccountOnboarding::class)->apply($user);
            } else {
                $user->assignRole(Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']));
            }

            return $user;
        });

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'google' => 'Administrator accounts must sign in with their email and password.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $defaultRoute = $accountType === 'provider'
            ? route('provider.dashboard')
            : route('dashboard');

        return redirect()->intended($defaultRoute);
    }

    private function hasVerifiedEmail(GoogleUser $user): bool
    {
        return filter_var($user->user['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function failedSignIn(string $accountType, string $message): RedirectResponse
    {
        $route = $accountType === 'provider' ? 'register.provider' : 'login';

        return redirect()->route($route)->withErrors(['google' => $message]);
    }
}
