<?php

namespace App\Http\Middleware;

use App\Services\StaffTwoFactor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_active || ! $user->hasVerifiedEmail() || ($request->session()->has('staff_session_version') && $request->session()->get('staff_session_version') !== $user->session_version)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('staff.login')->withErrors(['email' => 'Your session is no longer available. Please sign in or contact your administrator.']);
        }
        $request->session()->put('staff_session_version', $user->session_version);
        if ($user->two_factor_confirmed_at && $request->session()->get('staff_mfa_verified') !== $user->session_version) {
            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }
            app(StaffTwoFactor::class)->startChallenge($request, $user);

            return redirect()->route('staff.two-factor.challenge');
        }
        if ($user->isSuperAdmin() && config('security.require_admin_two_factor') && ! $user->two_factor_confirmed_at && ! $request->routeIs('staff.security.*', 'password.confirm', 'password.confirm.submit', 'staff.logout')) {
            return redirect()->route('staff.security.index')->with('status', 'Two-factor authentication is required before using the administrator workspace.');
        }
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
