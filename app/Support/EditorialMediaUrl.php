<?php

namespace App\Support;

/** Resolve known first-party assets after the retired Railway hostname migration. */
class EditorialMediaUrl
{
    public static function current(?string $url): ?string
    {
        $parts = parse_url($url ?? '');
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || strtolower($parts['host'] ?? '') !== 'african-ranked-web-app-production.up.railway.app'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || ! preg_match('~^/(?:editorial-art|editorial-media)/~', $parts['path'] ?? '')) {
            return $url;
        }

        return rtrim(config('app.url'), '/').$parts['path']
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
