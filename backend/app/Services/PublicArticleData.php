<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Support\Str;

class PublicArticleData
{
    public function forArticle(Article $article): array
    {
        $legacy = $article->frontend_payload ?? [];
        // Draft revisions must never leak through the legacy-visibility exception.
        $reviewed = in_array($article->status, [ArticleStatus::Published, ArticleStatus::NeedsUpdate], true)
            && $article->published_at?->isPast();
        if (! $reviewed && $article->legacy_public) {
            return [...$legacy, 'cms' => ['indexable' => false, 'canonical' => $article->url()]];
        }

        return [
            ...$legacy,
            'slug' => $article->slug,
            'title' => $article->title,
            'excerpt' => $article->dek ?? '',
            'category' => $article->category->slug,
            'author' => $article->author?->name ?? '',
            'authorTitle' => $article->author?->credentials ?? ($legacy['authorTitle'] ?? ''),
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
            'readTime' => max(1, (int) ceil($article->word_count / 200)),
            'image' => $legacy['image'] ?? '',
            'body' => [],
            'faqs' => $article->faq ?? [],
            'regions' => $article->edition === 'global' ? ($legacy['regions'] ?? ['global']) : [$article->edition],
            'cms' => [
                'indexable' => $article->status === ArticleStatus::Published && $article->indexable,
                'canonical' => $article->canonicalUrl(),
                'schema' => app(SchemaBuilder::class)->forArticle($article),
                'bodyHtml' => Str::markdown($article->body ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]),
                'answerBlock' => $article->answer_block,
                'keyFacts' => $article->key_facts ?? [],
                'items' => $article->items->map(fn ($i) => [
                    'position' => $i->position, 'name' => $i->entity->name,
                    'scores' => $i->scores, 'total' => $i->score_total, 'blurb' => $i->blurb,
                ])->values()->all(),
                'methodology' => $article->methodology_note,
                'sources' => $article->sources->map(fn ($s) => [
                    'title' => $s->title, 'url' => $s->url, 'accessedAt' => $s->accessed_at?->toDateString(),
                ])->values()->all(),
                'corrections' => $article->corrections->map(fn ($c) => [
                    'summary' => $c->summary, 'correctedAt' => $c->corrected_at->toDateString(),
                ])->values()->all(),
                'disclosure' => $article->disclosure,
            ],
        ];
    }

    public function catalog(string $edition): array
    {
        $articles = Article::publiclyVisible()->with(['category', 'author', 'reviewer', 'entities', 'sources', 'items.entity', 'corrections', 'canonical'])
            ->whereIn('edition', array_unique(['global', $edition]))->get();

        // A regional record takes precedence over its global version.
        return $articles->sortBy(fn ($a) => $a->edition === 'global' ? 0 : 1)
            ->mapWithKeys(fn ($a) => [$a->slug => $this->forArticle($a)])->values()->all();
    }
}
