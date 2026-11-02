<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Author;
use App\Models\Correction;
use App\Models\Entity;
use App\Services\PublicArticleData;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FrontendController extends Controller
{
    public function __invoke(Request $request, PublicArticleData $data)
    {
        $segments = $request->segments();
        $edition = in_array($segments[0] ?? '', config('africaranked.editions'), true)
            ? array_shift($segments) : 'global';
        $catalog = $data->catalog($edition);
        $selected = null;
        if (($segments[0] ?? '') === 'articles' && isset($segments[1])) {
            $selected = collect($catalog)->firstWhere('slug', $segments[1]);
            abort_unless($selected, 404);
        }
        $page = null;
        if (count($segments) === 1 && in_array($segments[0], ['about', 'methodology', 'editorial-policy', 'monetization-disclosure'], true)) {
            $file = config('cms.editorial_directory').'/'.$segments[0].'.md';
            abort_unless(is_file($file), 404);
            $page = ['html' => Str::markdown(file_get_contents($file), ['html_input' => 'strip', 'allow_unsafe_links' => false])];
        }
        if (($segments[0] ?? '') === 'authors' && isset($segments[1])) {
            $author = Author::where('slug', $segments[1])->firstOrFail();
            abort_unless(Article::publiclyVisible()->where(fn ($q) => $q->where('author_id', $author->id)->orWhere('reviewer_id', $author->id))->exists(), 404);
            $page = ['title' => $author->name, 'html' => '<h1>'.e($author->name).'</h1><p>'.e($author->bio).'</p>'];
        }
        if (($segments[0] ?? '') === 'e' && isset($segments[1])) {
            $entity = Entity::where('slug', $segments[1])->firstOrFail();
            abort_unless(Article::publiclyVisible()->whereHas('entities', fn ($q) => $q->where('entities.id', $entity->id))->exists(), 404);
            $page = ['title' => $entity->name, 'html' => '<h1>'.e($entity->name).'</h1>'];
        }
        if (($segments[0] ?? '') === 'corrections') {
            $corrections = Correction::whereHas('article', fn ($q) => $q->publiclyVisible())->orderByDesc('corrected_at')->get();
            $page = ['title' => 'Corrections', 'html' => '<ul>'.$corrections->map(fn ($c) => '<li><time>'.e($c->corrected_at->toDateString()).'</time> '.e($c->summary).'</li>')->implode('').'</ul>'];
        }
        $file = config('cms.frontend_dist').'/index.html';
        abort_unless(is_file($file), 503, 'Build the existing frontend with npm run build.');
        $html = file_get_contents($file);
        $bootstrap = ['articles' => $catalog, 'page' => $page, 'edition' => $edition];
        $head = '<script id="ar-cms-data" type="application/json">'.json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).'</script>';
        if ($selected) {
            $head .= '<meta name="robots" content="'.($selected['cms']['indexable'] ? 'index, follow' : 'noindex, follow').'">';
            $html = preg_replace('/<meta\s+name="description"[^>]*>/i', '', $html);
            $head .= '<meta name="description" content="'.e($selected['excerpt']).'">';
            $html = preg_replace('/<title>.*?<\/title>/s', '<title>'.e($selected['title']).'</title>', $html);
            if (isset($selected['cms']['schema'])) {
                $head .= '<script type="application/ld+json" id="ar-cms-schema">'.json_encode($selected['cms']['schema'], JSON_HEX_TAG | JSON_HEX_AMP).'</script>';
            }
            $head .= '<link rel="canonical" href="'.e($selected['cms']['canonical']).'">';
        }
        if ($selected && ! $selected['cms']['indexable']) {
            $headers = ['X-Robots-Tag' => 'noindex, follow'];
        } else {
            $headers = [];
        }

        return response(str_replace('</head>', $head.'</head>', $html), 200, ['Content-Type' => 'text/html; charset=UTF-8', ...$headers]);
    }
}
