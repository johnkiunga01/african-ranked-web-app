<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use App\Services\SiteSettings;
use App\Support\Catalog;
use App\Support\Seo;

class ContentPageController extends Controller
{
    public function show(string $slug, Catalog $catalog, Seo $seo)
    {
        $page = SitePage::where('slug', $slug)->whereNull('trashed_at')->whereNotNull('published_revision_id')->with('publishedRevision')->firstOrFail();
        if (request()->getPathInfo() !== $page->publicPath()) {
            return redirect($page->publicPath(), 301);
        }
        $revision = $page->publishedRevision;
        abort_unless($revision?->published_at, 404);
        $region = $catalog->region();
        $metadata = $seo->page('strategy', $region);
        $canonical = $catalog->url($page->publicPath());
        $name = app(SiteSettings::class)->snapshot('general')['value']['name'];
        $metadata['title'] = $revision->title.' | '.$name;
        $metadata['description'] = $revision->description;
        $metadata['canonical'] = $canonical;
        $metadata['alternates'] = [];
        foreach ($metadata['jsonLd']['@graph'] as &$node) {
            if (($node['@type'] ?? '') === 'WebPage') {
                $node['@id'] = $canonical.'#webpage';
                $node['url'] = $canonical;
                $node['name'] = $metadata['title'];
                $node['description'] = $revision->description;
            }
        }
        unset($node);

        return view($slug === 'editorial-approach' ? 'pages.strategy' : 'pages.content', ['page' => $page, 'revision' => $revision, 'catalog' => $catalog, 'region' => $region, 'regions' => $catalog->regions(), 'categories' => $catalog->categories(), 'basePath' => '', 'pageSuffix' => $slug === 'editorial-approach' ? '/strategy' : $page->publicPath(), 'seo' => $metadata, 'site' => app(SiteSettings::class)->snapshot('general')['value'], 'footerPages' => $catalog->publishedPages()->filter(fn ($p) => $p->publishedRevision->in_footer)]);
    }
}
