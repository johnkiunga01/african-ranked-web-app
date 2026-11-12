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
            $canonicalHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
            $incomingHost = strtolower($request->getHost());
            // Only the exact www alias of the configured canonical host may redirect.
            // Do not silently forward POST requests (including login or CMS writes).
            if ($canonicalHost !== '' && $incomingHost === 'www.'.$canonicalHost) {
                abort_unless($request->isMethod('GET') || $request->isMethod('HEAD'), 400);

                return redirect()->to('https://'.$canonicalHost.$request->getRequestUri(), 301);
            }
            $hosts = array_merge([(string) parse_url(config('app.url'), PHP_URL_HOST)], config('security.additional_hosts', []));
            $hosts = array_map('strtolower', $hosts);
            abort_unless(in_array(strtolower($request->getHost()), $hosts, true), 400, 'Unrecognized host.');
        }

        return $next($request);
    }
}
