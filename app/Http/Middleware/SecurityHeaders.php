<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(24));
        $request->attributes->set('csp_nonce', $nonce);
        $response = $next($request);
        $script = "'self' 'nonce-{$nonce}'";
        $connect = "'self'";
        if (config('analytics.enabled')) {
            // Consent-gated GA4; allow the Google hosts only when the feature is enabled.
            $script .= ' https://www.googletagmanager.com';
            $connect .= ' https://www.google-analytics.com https://region1.google-analytics.com';
        }
        if (app()->environment('local') && is_file(public_path('hot'))) {
            $url = trim(file_get_contents(public_path('hot')));
            $parts = parse_url($url);
            if (isset($parts['scheme'], $parts['host']) && in_array($parts['scheme'], ['http', 'https'], true)) {
                $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
                $script .= ' '.$origin;
                $connect .= ' '.$origin.' '.($parts['scheme'] === 'https' ? 'wss' : 'ws').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
            }
        }
        $form = "'self'";
        if ($request->is('oauth/authorize') && config('editorial.mcp_enabled')) {
            $form .= ' https://chatgpt.com/connector_platform_oauth_redirect';
        }
        $policy = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action {$form}; script-src {$script}; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' https: data:; connect-src {$connect}; media-src 'self' https:; frame-src 'none'";
        $response->headers->set('Content-Security-Policy', $policy);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        if (app()->environment('production') && str_starts_with(config('app.url'), 'https://')) {
            // Subdomain coverage/preload need a separate infrastructure decision.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
