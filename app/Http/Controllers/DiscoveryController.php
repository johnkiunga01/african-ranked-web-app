<?php

namespace App\Http\Controllers;

use App\Support\Catalog;
use Illuminate\Http\Response;

class DiscoveryController extends Controller
{
    public function __construct(private Catalog $catalog) {}

    public function sitemap(?string $region = null): Response
    {
        $c = $this->catalog;
        if ($region !== null) {
            $c->region($region);
        }
        $codes = $region === null ? array_keys($c->regions()) : [$region];
        $urls = [];
        foreach ($codes as $code) {
            $articles = $c->articles($code);
            $latest = $articles->max('dateModified');
            $urls[] = ['loc' => $c->url($c->path($code)), 'lastmod' => $latest];
            foreach ($c->categories() as $category) {
                $matching = $articles->where('category', $category['slug']);
                if ($matching->isNotEmpty()) {
                    $urls[] = ['loc' => $c->url($c->path($code, '/categories/'.$category['slug'])), 'lastmod' => $matching->max('dateModified')];
                }
            }
        }
        if ($region === null || $region === 'global') {
            foreach ($c->articles() as $article) {
                $urls[] = ['loc' => $c->url('/articles/'.$article['slug']), 'lastmod' => $article['dateModified']];
            }
        }

        if ($region === null || $region === 'global') {
            foreach ($c->publishedPages() as $page) {
                $urls[] = ['loc' => $c->url($page->publicPath()), 'lastmod' => $page->publishedRevision->published_at->toIso8601String()];
            }
        }

        return response()->view('discovery.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        if (! app()->environment('production') || config('seo.preview') || config('review.enabled')) {
            return response("User-agent: *\nDisallow: /\nSitemap: ".$this->catalog->url('/sitemap.xml')."\n", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        return response("User-agent: *\nAllow: /\nDisallow: /newsletter\nSitemap: ".$this->catalog->url('/sitemap.xml')."\n", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function feed(): Response
    {
        return response()->view('discovery.feed', ['catalog' => $this->catalog, 'articles' => $this->catalog->articles()->sortByDesc('datePublished')])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function llms(): Response
    {
        $c = $this->catalog;
        $text = "# African Ranked\n\n> Africa-focused rankings covering travel, food, music, business, technology, and wildlife.\n\n## Articles\n";
        foreach ($c->articles() as $article) {
            $text .= '- ['.$article['title'].']('.$c->url('/articles/'.$article['slug']).'): '.$article['excerpt']."\n";
        }
        $text .= "\n## Editorial information\n- [Editorial approach](".$c->url('/strategy').")\n- [RSS feed](".$c->url('/feed.xml').")\n\nInherited editorial claims and quotations require source verification. This index is a discovery aid; it does not confer factual validation, reuse permission, or guaranteed inclusion in AI answers.\n";

        return response($text)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
