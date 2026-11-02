<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use App\Services\SiteSettings;
use App\Support\Catalog;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PageController extends Controller
{
    public function __construct(private Catalog $catalog, private Seo $seo) {}

    private function render(string $type, ?string $region, array $data = []): View
    {
        $r = $this->catalog->region($region);

        return view('pages.'.$type, $data + [
            'site' => app(SiteSettings::class)->snapshot('general')['value'],
            'homepage' => app(SiteSettings::class)->snapshot('homepage')['value'],
            'footerPages' => $this->catalog->publishedPages()->filter(fn ($page) => $page->publishedRevision->in_footer),
            'catalog' => $this->catalog, 'region' => $r, 'regions' => $this->catalog->regions(),
            'categories' => $this->catalog->categories(), 'basePath' => $this->catalog->path($r['code']) === '/' ? '' : '/'.$r['code'],
            'pageSuffix' => match ($type) {
                'article' => '/articles/'.$data['article']['slug'],
                'category' => '/categories/'.$data['category']['slug'],
                'strategy' => '/strategy',
                'rankings' => '/rankings',
                default => '',
            },
            'seo' => $this->seo->page($type, $r, $data['article'] ?? null, $data['category'] ?? null),
        ]);
    }

    public function legacyGlobal(Request $request): RedirectResponse
    {
        return redirect('/'.ltrim(substr($request->path(), strlen('global')), '/'), 301);
    }

    public function home(?string $region = null): View
    {
        return $this->render('home', $region, ['articles' => $this->catalog->homepageArticles($region ?? 'global')]);
    }

    public function rankings(Request $request, ?string $region = null): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:guide'],
        ]);
        $query = trim((string) ($filters['q'] ?? ''));
        $type = $filters['type'] ?? null;
        $articles = $this->catalog->articles($region ?? 'global');
        if ($type === 'guide') {
            $articles = $articles->filter(function ($article) {
                $editorialType = $article['editorialEvidence']['content_type'] ?? null;
                if ($editorialType) return $editorialType === 'guide';

                return (bool) preg_match('/(?:guide|explained|best-time|how-to|vs-|cost|when-to|packing)/i', $article['slug']);
            });
        }
        if ($query !== '') {
            $articles = $articles->filter(fn ($article) =>
                mb_stripos(implode(' ', [$article['title'] ?? '', $article['excerpt'] ?? '', $article['category'] ?? '']), $query) !== false);
        }

        return $this->render('rankings', $region, [
            'articles' => $articles->sortByDesc('datePublished')->values(),
            'searchQuery' => $query,
            'articleType' => $type,
        ]);
    }

    public function category(string $slug, ?string $region = null): View
    {
        return $this->render('category', $region, [
            'category' => $this->catalog->category($slug),
            'articles' => $this->catalog->articles($region ?? 'global')->where('category', $slug)->values(),
        ]);
    }

    public function article(string $slug, ?string $region = null): View
    {
        $article = $this->catalog->article($slug);

        return $this->render('article', $region, [
            'article' => $article, 'category' => $this->catalog->category($article['category']),
            'related' => $this->catalog->articles($region ?? 'global')->where('category', $article['category'])->where('slug', '!=', $slug)->take(3),
        ]);
    }

    public function regionalCategory(string $region, string $slug): View
    {
        return $this->category($slug, $region);
    }

    public function regionalArticle(string $region, string $slug): View
    {
        return $this->article($slug, $region);
    }

    public function strategy(?string $region = null): View|RedirectResponse
    {
        $page = Schema::hasTable('site_pages') ? SitePage::where('slug', 'editorial-approach')->first() : null;
        if ($page?->published_revision_id) {
            return app(ContentPageController::class)->show('editorial-approach', $this->catalog, $this->seo);
        }
        if ($page?->first_published_at) {
            abort(404);
        }

        return $this->render('strategy', $region);
    }
}
