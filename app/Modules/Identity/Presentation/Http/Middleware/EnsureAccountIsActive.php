<?php

namespace App\Modules\Identity\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of an account an admin has suspended.
 *
 * Sign-in is refused up front (LoginRequest), but a suspended user may still
 * hold an open session, a "remember me" cookie or have arrived through Google
 * sign-in, so every web and API request re-checks the account.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isSuspended') || ! $user->isSuspended()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            $user->currentAccessToken()?->delete();

            return response()->json(['message' => 'This account has been suspended.'], Response::HTTP_FORBIDDEN);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'This account has been suspended. If you think this is a mistake, contact '.app(\App\Modules\Administration\Application\Services\PlatformSettings::class)->get('support.email').'.',
        ]);
    }
}
