<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EditorialMcpEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('editorial.mcp_enabled'), 404);

        return $next($request)->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
