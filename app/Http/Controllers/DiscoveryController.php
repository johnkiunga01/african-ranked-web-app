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

        // Public reading and AI-agent retrieval are allowed even while noindex stays active.
        // Disallowing these URLs would stop compliant crawlers from seeing the noindex header.
        $rules = "User-agent: *\nAllow: /\nDisallow: /newsletter\nDisallow: /staff\n"
            ."Sitemap: ".$this->catalog->url('/sitemap.xml')."\n";

        return response($rules, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function feed(): Response
    {
        return response()->view('discovery.feed', ['catalog' => $this->catalog, 'articles' => $this->catalog->articles()->sortByDesc('datePublished')])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function llms(): Response
    {
        $c = $this->catalog;
        // The CMS catalog returns published revisions only; never expose private staff drafts.
        $text = "# African Ranked\n\n"
            ."> Independent Africa-focused rankings and practical guides to travel, wildlife, food, music, business and technology.\n\n"
            ."African Ranked publishes English-language editorial articles about African destinations, cultures, industries and people. "
            ."Read the full linked articles for context, source attribution and last-verified dates. "
            ."This discovery file is not a license to republish content or a guarantee of AI citations.\n\n"
            ."## Start here\n\n"
            ."- [Rankings and guides](".$c->url('/rankings')."): Browse published articles by topic.\n"
            ."- [Editorial approach](".$c->url('/strategy')."): How African Ranked approaches research and presentation.\n\n"
            ."## Articles\n\n";

        foreach ($c->articles()->sortByDesc('dateModified') as $article) {
            $title = str_replace(['[', ']', "\r", "\n"], ['', '', ' ', ' '], (string) $article['title']);
            $excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $article['excerpt'])) ?? '');
            $text .= '- ['.$title.']('.$c->url('/articles/'.$article['slug']).'): '.$excerpt."\n";
        }

        $text .= "\n## Machine-readable discovery\n\n"
            ."- [RSS feed](".$c->url('/feed.xml')."): Newly published articles.\n"
            ."- [XML sitemap](".$c->url('/sitemap.xml')."): Canonical public page URLs.\n\n"
            ."Only published articles appear in this index. Drafts, staff pages and personal information are excluded. "
            ."Verify material claims using the references on the article pages before quoting or summarizing.\n";

        return response($text)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
