<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\ArticleImageReuseGuard;
use App\Services\PublicationQuality;
use App\Support\Catalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CMS inventory audit. It does not fetch external references and cannot
 * independently attest claims, image licensing, or real browser accessibility.
 */
class CrawlPublishedEditorial extends Command
{
    protected $signature = 'ops:content-crawl {--strict : Return nonzero when structural issues are found}';

    protected $description = 'Read-only published-article audit of metadata, alt text, duplicates, internal links and editorial structure.';

    public function handle(ArticleImageReuseGuard $reuse, PublicationQuality $quality, Catalog $catalog): int
    {
        $published = Article::whereNull('trashed_at')->whereNotNull('published_revision_id')
            ->with('publishedRevision')->get();
        $slugs = $published->pluck('slug')->flip();
        $categories = $catalog->categories()->pluck('slug')->flip();
        $firstImageOwner = [];
        $issues = [];
        $internalLinks = 0;
        $imageCount = 0;
        $checked = 0;
        foreach ($published as $article) {
            $rev = $article->publishedRevision;
            if (! $rev) {
                $issues[] = ['slug' => $article->slug, 'issue' => 'published_revision_missing'];

                continue;
            }
            $checked++;
            if (! filled($rev->seo_title)) {
                $issues[] = ['slug' => $article->slug, 'issue' => 'seo_title_missing'];
            }
            if (! filled($rev->meta_description)) {
                $issues[] = ['slug' => $article->slug, 'issue' => 'meta_description_missing'];
            }
            if (! filled($rev->image_alt)) {
                $issues[] = ['slug' => $article->slug, 'issue' => 'cover_alt_missing'];
            }
            if (! filled($rev->image)) {
                $issues[] = ['slug' => $article->slug, 'issue' => 'cover_missing'];
            }
            if (! isset($categories[$rev->category_slug])) {
                $issues[] = ['slug' => $article->slug, 'issue' => 'category_unrecognized'];
            }
            $images = [['url' => $rev->image, 'alt' => $rev->image_alt, 'role' => 'cover']];
            foreach ($rev->body ?? [] as $block) {
                if (($block['type'] ?? null) === 'image') {
                    $images[] = ['url' => $block['url'] ?? null, 'alt' => $block['alt'] ?? null, 'role' => 'inline'];
                }
                if (($block['type'] ?? null) !== 'link') {
                    continue;
                }
                $url = $block['url'] ?? null;
                if (! is_string($url)) {
                    continue;
                }
                $path = parse_url($url, PHP_URL_PATH);
                $host = parse_url($url, PHP_URL_HOST);
                $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
                if ($host && $host !== $appHost) {
                    continue;
                }
                if (! is_string($path)) {
                    continue;
                }
                if (preg_match('~^/(?:[a-z]{2}/)?articles/([a-z0-9-]+)/?$~', $path, $m)) {
                    $internalLinks++;
                    if (! isset($slugs[$m[1]])) {
                        $issues[] = ['slug' => $article->slug, 'issue' => 'link_to_unpublished_article', 'target' => $m[1]];
                    }
                } elseif (preg_match('~^/(?:[a-z]{2}/)?categories/([a-z0-9-]+)/?$~', $path, $m)) {
                    $internalLinks++;
                    if (! isset($categories[$m[1]])) {
                        $issues[] = ['slug' => $article->slug, 'issue' => 'link_to_unknown_category', 'target' => $m[1]];
                    }
                }
            }
            $withinArticle = [];
            foreach ($images as $img) {
                $url = $img['url'] ?? null;
                if (! is_string($url) || ! str_starts_with($url, 'https://')) {
                    $issues[] = ['slug' => $article->slug, 'issue' => 'image_not_https', 'role' => $img['role']];

                    continue;
                }
                $imageCount++;
                if (! filled($img['alt'])) {
                    $issues[] = ['slug' => $article->slug, 'issue' => 'image_alt_missing', 'role' => $img['role']];
                }
                $id = $reuse->key($url);
                if (! $id || isset($withinArticle[$id])) {
                    continue;
                }
                $withinArticle[$id] = true;
                if (isset($firstImageOwner[$id]) && $firstImageOwner[$id] !== $article->slug) {
                    $issues[] = ['slug' => $article->slug, 'issue' => 'image_reused_across_articles', 'original' => $firstImageOwner[$id]];
                } else {
                    $firstImageOwner[$id] = $article->slug;
                }
            }
            try {
                $inspection = $quality->inspect($rev);
                if ($inspection['failures']) {
                    $issues[] = ['slug' => $article->slug, 'issue' => 'publication_quality_failed', 'failures' => count($inspection['failures'])];
                }
            } catch (Throwable) {
                $issues[] = ['slug' => $article->slug, 'issue' => 'quality_inspection_error'];
            }
        }
        $counts = array_count_values(array_column($issues, 'issue'));
        $receipt = [
            'published_checked' => $checked,
            'total_published' => $published->count(),
            'images_checked' => $imageCount,
            'internal_links_checked' => $internalLinks,
            'structural_issue_count' => count($issues),
            'issue_counts' => $counts,
            'examples' => array_slice($issues, 0, 18),
            'limitations' => 'No external URLs fetched; independent fact/media-rights and browser accessibility review remains outstanding.',
        ];
        $message = 'AR_CONTENT_CRAWL '.json_encode($receipt, JSON_UNESCAPED_SLASHES);
        $this->line($message);
        Log::info($message);

        return $this->option('strict') && $issues !== [] ? self::FAILURE : self::SUCCESS;
    }
}
