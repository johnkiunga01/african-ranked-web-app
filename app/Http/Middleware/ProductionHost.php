<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductionHost
{
    public function handle(Request $request, Closure $next): Response
    {
        // Platform health probes may use an internal host; this exception cannot invoke staff actions.
        if ($request->isMethod('GET') && $request->path() === 'up') {
            return $next($request);
        }
        if (! app()->environment(['local', 'review', 'testing'])) {
            $hosts = array_merge([(string) parse_url(config('app.url'), PHP_URL_HOST)], config('security.additional_hosts', []));
            $hosts = array_map('strtolower', $hosts);
            abort_unless(in_array(strtolower($request->getHost()), $hosts, true), 400, 'Unrecognized host.');
        }

        return $next($request);
    }
}
