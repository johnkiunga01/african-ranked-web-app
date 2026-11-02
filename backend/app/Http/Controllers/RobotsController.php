<?php

namespace App\Http\Controllers;

use App\Services\CanonicalUrl;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [];
        $canonicalHost = parse_url(config('africaranked.brand.url'), PHP_URL_HOST);

        // Anything that is not the production host gets a blanket disallow.
        if (! app()->environment('production') || request()->getHost() !== $canonicalHost) {
            $lines = ['User-agent: *', 'Disallow: /'];
        } else {
            $lines = ['User-agent: *', 'Allow: /', 'Disallow: /admin', 'Disallow: /api/', ''];

            if (! config('africaranked.bots.allow_training')) {
                foreach (config('africaranked.bots.training') as $bot) {
                    $lines[] = "User-agent: {$bot}";
                    $lines[] = 'Disallow: /';
                    $lines[] = '';
                }
            }

            $lines[] = 'Sitemap: '.CanonicalUrl::to('/sitemap.xml');
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
