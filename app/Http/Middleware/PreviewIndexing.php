<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('staff', 'staff/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        if (config('seo.preview')) {
            // Keep canonical/schema markup inspectable while preventing preview indexing.
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            if (config('review.enabled')) {
                $response->headers->set('Cache-Control', 'private, no-store');
            }
        }

        return $response;
    }
}
