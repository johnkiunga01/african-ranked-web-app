<?php

namespace App\Support;

use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Region;
use App\Models\SitePage;
use App\Services\SiteSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class Catalog
{
    private array $data = [];

    public function data(string $name): array
    {
        return $this->data[$name] ??= json_decode(file_get_contents(base_path("content/{$name}.json")), true, 512, JSON_THROW_ON_ERROR);
    }

    public function regions(): array
    {
        if (Schema::hasTable('regions') && Region::query()->exists()) {
            return Region::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()->mapWithKeys(fn ($region) => [$region->code => $region->publicRecord()])->all();
        }

        return $this->data('regions');
    }

    public function region(?string $code = null): array
    {
        return $this->regions()[$code ?? 'global'] ?? abort(404);
    }

    public function categories(): Collection
    {
        if (Schema::hasTable('categories') && Category::query()->exists()) {
            return Category::orderBy('id')->get()->map(fn ($category) => $category->only(['slug', 'name', 'description', 'icon', 'color']));
        }

        return collect($this->data('categories'));
    }

    public function category(string $slug): array
    {
        return $this->categories()->firstWhere('slug', $slug) ?? abort(404);
    }

    public function articles(string $region = 'global'): Collection
    {
        $legacy = in_array(config('app.env'), ['local', 'testing', 'review'], true) && config('editorial.legacy_preview') ? collect($this->data('articles')) : collect();
        $published = collect();
        if (Schema::hasTable('articles')) {
            // Any CMS record masks a seed with the same slug, including withdrawn/draft records.
            $cmsSlugs = Article::pluck('slug');
            $legacy = $legacy->reject(fn ($record) => $cmsSlugs->contains($record['slug']));
            $published = Article::whereNull('trashed_at')->whereNotNull('published_revision_id')->with('publishedRevision')->get()
                ->filter(fn ($article) => $article->publishedRevision?->status === RevisionStatus::Published && $article->first_published_at)
                ->map(fn ($article) => $article->publishedRevision->publicRecord($article));
        }

        return $published->concat($legacy)->filter(fn ($article) => $region === 'global' || ! isset($article['regions']) || in_array($region, $article['regions'], true))->values();
    }

    public function homepageArticles(string $region = 'global'): Collection
    {
        $articles = $this->articles($region)->sortByDesc('datePublished')->values();
        $settings = app(SiteSettings::class)->snapshot('homepage')['value'];
        $selected = $settings['featured'][$region] ?? $settings['featured']['global'] ?? null;
        $slug = $selected ? Article::whereNull('trashed_at')->whereKey($selected)->value('slug') : null;
        $featured = $slug ? $articles->firstWhere('slug', $slug) : null;

        return $featured ? collect([$featured])->concat($articles->reject(fn ($a) => $a['slug'] === $slug))->values() : $articles;
    }

    public function usesLegacyStrategy(): bool
    {
        return ! Schema::hasTable('site_pages') || ! SitePage::where('slug', 'editorial-approach')->whereNotNull('first_published_at')->exists();
    }

    public function publishedPages(): Collection
    {
        if (! Schema::hasTable('site_pages')) {
            return collect();
        }

        return SitePage::whereNull('trashed_at')->whereNotNull('published_revision_id')->with('publishedRevision')->get()->filter(fn ($page) => $page->publishedRevision && $page->publishedRevision->published_at);
    }

    public function article(string $slug): array
    {
        return $this->articles()->firstWhere('slug', $slug) ?? abort(404);
    }

    public function path(string $region = 'global', string $path = ''): string
    {
        return ($region === 'global' ? '' : '/'.$region).$path ?: '/';
    }

    public function url(string $path = '/'): string
    {
        return rtrim(config('app.url'), '/').($path === '/' ? '' : $path);
    }

    public function headingId(int $index): string
    {
        return 'section-'.$index;
    }
}
