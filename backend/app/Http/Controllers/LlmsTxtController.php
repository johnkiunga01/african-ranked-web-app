<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\CanonicalUrl;

class LlmsTxtController extends Controller
{
    public function __invoke()
    {
        $brand = config('africaranked.brand');
        $lines = ["# {$brand['name']}", '', "> {$brand['positioning']}", ''];
        foreach (['about', 'methodology', 'editorial-policy', 'monetization-disclosure'] as $page) {
            if (is_file(config('cms.editorial_directory').'/'.$page.'.md')) {
                $lines[] = '- ['.$page.']('.CanonicalUrl::to('/'.$page).')';
            }
        }
        foreach (config('africaranked.categories') as $slug => $name) {
            $lines[] = '- ['.$name.']('.CanonicalUrl::to('/categories/'.$slug).')';
        }
        foreach (Article::indexable()->whereNull('canonical_article_id')->latest('published_at')->limit(10)->get() as $article) {
            $lines[] = '- ['.$article->title.']('.$article->url().')';
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
