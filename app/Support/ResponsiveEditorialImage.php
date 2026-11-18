<?php

namespace App\Support;

class ResponsiveEditorialImage
{
    public static function srcset(?string $url): ?string
    {
        $parts = $url ? parse_url($url) : false;
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'images.pexels.com' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }
        parse_str($parts['query'] ?? '', $query);
        $candidates = [];
        foreach ([480, 800, 1200, 1400] as $width) {
            $query['w'] = $width;
            $candidates[] = 'https://images.pexels.com'.($parts['path'] ?? '/').'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986).' '.$width.'w';
        }

        return implode(', ', $candidates);
    }
}
