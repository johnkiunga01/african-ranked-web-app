<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\CanonicalUrl;
use Illuminate\Http\Response;

/** Segmented sitemaps. Only published AND indexable articles are listed. */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $size = config('africaranked.sitemap_page_size');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach (config('africaranked.editions') as $edition) {
            $count = Article::indexable()->whereNull('canonical_article_id')->where('edition', $edition)->count();
            for ($page = 1; $page <= (int) ceil($count / $size); $page++) {
                $xml .= '<sitemap><loc>'.e(CanonicalUrl::to("/sitemaps/{$edition}-{$page}.xml")).'</loc></sitemap>';
            }
        }

        return response($xml.'</sitemapindex>', 200, ['Content-Type' => 'application/xml']);
    }

    public function show(string $edition, int $page): Response
    {
        abort_unless(in_array($edition, config('africaranked.editions'), true), 404);

        abort_if($page < 1, 404);
        $size = config('africaranked.sitemap_page_size');
        $articles = Article::indexable()
            ->whereNull('canonical_article_id')->where('edition', $edition)
            ->orderBy('id')
            ->skip(($page - 1) * $size)->take($size)
            ->get(['id', 'slug', 'edition', 'canonical_article_id', 'last_verified_at', 'updated_at']);

        abort_if($articles->isEmpty() && $page > 1, 404);

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($articles as $a) {
            // Regional copies that canonicalize elsewhere stay out of the sitemap.
            if ($a->canonical_article_id) {
                continue;
            }
            $lastmod = ($a->last_verified_at ?? $a->updated_at)->toAtomString();
            $xml .= '<url><loc>'.e($a->url())."</loc><lastmod>{$lastmod}</lastmod></url>";
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml']);
    }
}
