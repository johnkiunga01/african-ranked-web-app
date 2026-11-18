<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EditorialMcpAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('editorial.mcp_enabled'), 404);
        $actor = $request->bearerToken() ? Auth::guard('editorial_oauth')->user() : null;
        // TokenGuard has already verified signature, expiry and revocation before claims are read.
        $parts = explode('.', $request->bearerToken() ?? '');
        $claims = count($parts) === 3 ? json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) : null;
        $audienceValid = is_array($claims) && ($claims['iss'] ?? null) === url('/') && in_array(url('/api/editorial/mcp'), (array) ($claims['aud'] ?? []), true);
        if (! $audienceValid || ! $actor || ! $actor->isSuperAdmin() || ! $actor->two_factor_confirmed_at || ! $actor->tokenCan('editorial')) {
            return response()->json(['error' => 'invalid_token'], 401)->header('WWW-Authenticate', 'Bearer resource_metadata="'.url('/.well-known/oauth-protected-resource').'"')->header('Cache-Control', 'no-store');
        }
        $request->attributes->set('editorial_actor', $actor);

        return $next($request)->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
