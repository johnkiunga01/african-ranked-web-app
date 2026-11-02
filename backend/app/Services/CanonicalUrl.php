<?php

namespace App\Services;

class CanonicalUrl
{
    public static function to(string $path): string
    {
        return rtrim(config('africaranked.brand.url'), '/').'/'.ltrim($path, '/');
    }
}
