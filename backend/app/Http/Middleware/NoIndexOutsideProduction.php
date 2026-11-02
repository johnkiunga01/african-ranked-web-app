<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Stops staging/tunnel hosts (tunnelmole, ngrok, etc.) from ever being indexed. */
class NoIndexOutsideProduction
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $canonicalHost = parse_url(config('africaranked.brand.url'), PHP_URL_HOST);
        if (! app()->environment('production') || $request->getHost() !== $canonicalHost) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }
}
