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
                'about' => '/about',
                'safari-planner' => '/plan-safari',
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
                if ($editorialType) {
                    return $editorialType === 'guide';
                }

                return (bool) preg_match('/(?:guide|explained|best-time|how-to|vs-|cost|when-to|packing)/i', $article['slug']);
            });
        }
        if ($query !== '') {
            $articles = $articles->filter(fn ($article) => mb_stripos(implode(' ', [$article['title'] ?? '', $article['excerpt'] ?? '', $article['category'] ?? '']), $query) !== false);
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
            'related' => $this->catalog->articles($region ?? 'global')->where('category', $article['category'])->where('slug', '!=', $slug)->sortByDesc('datePublished')->take(3)->values(),
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

    public function safariPlanner(): View
    {
        $published = $this->catalog->articles()->keyBy('slug');
        $steps = collect([
            ['title' => 'Choose your kind of safari', 'description' => 'Start with your experience level, travel style and the people coming with you.', 'slugs' => ['how-to-choose-an-african-safari', 'best-safari-destinations-africa-first-timers', 'best-family-safari-destinations-africa', 'best-self-drive-safari-destinations-africa', 'top-10-safari-destinations-africa-2026']],
            ['title' => 'Find the season that fits', 'description' => 'Compare the places and experiences that matter to you before fixing your dates.', 'slugs' => ['best-time-to-visit-african-safari-by-month', 'best-time-to-visit-kruger', 'best-time-to-visit-masai-mara', 'best-time-to-visit-serengeti', 'best-time-to-visit-okavango-delta', 'great-migration-guide-best-months-viewing-spots']],
            ['title' => 'Build the whole-trip budget', 'description' => 'Consider the full itinerary. Confirm current prices and inclusions with the relevant authority or provider.', 'slugs' => ['africa-safari-cost-by-destination', 'affordable-safari-parks-ranked', 'gorilla-trekking-permit-cost-how-to-book']],
            ['title' => 'Prepare for the experience', 'description' => 'Read the practical guide, then explore the wildlife and park comparisons that suit your interests.', 'slugs' => ['safari-packing-etiquette-first-timer-guide', 'big-five-parks-ranked-where-to-see-all-five', 'best-places-to-see-big-cats-africa', 'best-gorilla-trekking-africa-ranked', 'serengeti-vs-masai-mara', 'kruger-vs-masai-mara']],
        ])->map(function ($step) use ($published) {
            $step['articles'] = collect($step['slugs'])->map(fn ($slug) => $published->get($slug))->filter()->values();

            return $step;
        })->filter(fn ($step) => $step['articles']->isNotEmpty())->values();

        return $this->render('safari-planner', null, ['steps' => $steps]);
    }

    public function about(): View|RedirectResponse
    {
        $page = Schema::hasTable('site_pages') ? SitePage::where('slug', 'about-us')->first() : null;
        if ($page?->published_revision_id && ! $page->trashed_at) {
            return redirect($page->publicPath(), 301);
        }
        if ($page?->first_published_at) {
            abort(404);
        }

        return $this->render('about', null);
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
