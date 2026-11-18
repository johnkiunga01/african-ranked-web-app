<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FreshHomepage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        // Homepage copy and featured articles are edited directly in the CMS.
        // Intermediate caches and installed PWAs must not retain old HTML.
        $response->headers->set('Cache-Control', 'no-store, max-age=0');

        return $response;
    }
}
