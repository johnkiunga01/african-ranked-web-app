<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EditorialApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $hash = strtolower(trim((string) config('editorial.integration_token_sha256')));
        $email = trim((string) config('editorial.integration_actor_email'));
        if (! config('editorial.integration_enabled') || ! preg_match('/^[a-f0-9]{64}$/', $hash) || ! $email) {
            return $this->deny('Editorial integration is not configured.', 503);
        }
        $token = $request->bearerToken();
        if (! is_string($token) || strlen($token) < 32 || strlen($token) > 256
            || ! hash_equals($hash, hash('sha256', $token))) {
            return $this->deny('Unauthenticated.', 401);
        }
        $actor = User::where('email', $email)->first();
        if (! $actor?->isSuperAdmin()) {
            return $this->deny('Editorial integration actor is unavailable.', 503);
        }
        $request->attributes->set('editorial_actor', $actor);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }

    private function deny(string $message, int $status): Response
    {
        return response()->json(['message' => $message], $status)
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
