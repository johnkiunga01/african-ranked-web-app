<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EditorialMcpEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('editorial.mcp_enabled'), 404);

        if ($request->filled('resource')) {
            abort_unless($request->input('resource') === url('/api/editorial/mcp'), 400);
        }
        $response = $next($request);
        $location = $response->headers->get('Location');
        if ($request->is('oauth/authorize') && $location && preg_match('/[?&](?:code|error)=/', $location)) {
            $response->headers->set('Location', $location.(str_contains($location, '?') ? '&' : '?').'iss='.rawurlencode(url('/')));
        }

        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
