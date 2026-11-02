<?php

namespace App\Services;

use App\Enums\ArticleType;
use App\Models\Article;

/**
 * Builds one linked JSON-LD @graph per page so entities resolve by @id.
 * The markup only describes what is visible on the page.
 */
class SchemaBuilder
{
    public function organization(): array
    {
        $b = config('africaranked.brand');

        return [
            '@type' => 'Organization',
            '@id' => $b['url'].'/#org',
            'name' => $b['name'],
            'alternateName' => $b['alternate_name'],
            'url' => $b['url'],
            'description' => $b['positioning'],
        ];
    }

    public function website(): array
    {
        $b = config('africaranked.brand');

        return [
            '@type' => 'WebSite',
            '@id' => $b['url'].'/#site',
            'url' => $b['url'],
            'name' => $b['name'],
            'inLanguage' => 'en',
            'publisher' => ['@id' => $b['url'].'/#org'],
        ];
    }

    public function forArticle(Article $a): array
    {
        $b = config('africaranked.brand');
        $url = $a->canonicalUrl();
        $graph = [$this->organization(), $this->website()];

        $article = [
            '@type' => 'Article',
            '@id' => $url.'#article',
            'headline' => $a->title,
            'description' => $a->dek,
            'url' => $url,
            'inLanguage' => 'en',
            'isAccessibleForFree' => true,
            'datePublished' => optional($a->published_at)->toIso8601String(),
            'dateModified' => optional($a->last_verified_at ?? $a->updated_at)->toIso8601String(),
            'publisher' => ['@id' => $b['url'].'/#org'],
            'isPartOf' => ['@id' => $b['url'].'/#site'],
            'mainEntityOfPage' => $url,
            'about' => $a->entities->where('pivot.role', 'about')->map(fn ($e) => array_filter([
                '@type' => 'Thing',
                '@id' => $e->url().'#thing',
                'name' => $e->name,
                'url' => $e->url(),
                'sameAs' => $e->same_as,
            ]))->values()->all(),
            'citation' => $a->sources->map(fn ($s) => array_filter([
                '@type' => 'CreativeWork',
                'name' => $s->title,
                'url' => $s->url,
                'publisher' => $s->publisher ? ['@type' => 'Organization', 'name' => $s->publisher] : null,
            ]))->values()->all(),
        ];

        if ($a->author) {
            $article['author'] = ['@type' => 'Person', 'name' => $a->author->name, 'url' => $a->author->url()];
        }
        if ($a->reviewer) {
            $article['reviewedBy'] = ['@type' => 'Person', 'name' => $a->reviewer->name, 'url' => $a->reviewer->url()];
        }
        if ($a->type === ArticleType::Sponsored && $a->sponsor_name) {
            $article['sponsor'] = ['@type' => 'Organization', 'name' => $a->sponsor_name];
        }

        $graph[] = array_filter($article, fn ($v) => $v !== null && $v !== []);

        // Breadcrumbs
        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $url.'#breadcrumbs',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $b['url']],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $a->category->name, 'item' => CanonicalUrl::to('/categories/'.$a->category->slug)],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $a->title, 'item' => $url],
            ],
        ];

        // Ranked list
        if ($a->type === ArticleType::Ranking && $a->items->isNotEmpty()) {
            $graph[] = [
                '@type' => 'ItemList',
                '@id' => $url.'#list',
                'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
                'numberOfItems' => $a->items->count(),
                'itemListElement' => $a->items->map(fn ($i) => [
                    '@type' => 'ListItem',
                    'position' => $i->position,
                    'name' => $i->entity->name,
                    'url' => $i->entity->url(),
                ])->all(),
            ];
        }

        // FAQ (only entries visible on the page)
        $faq = array_values(array_filter($a->faq ?? [], fn ($q) => ! empty($q['question']) && ! empty($q['answer'])));
        if ($faq) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $url.'#faq',
                'mainEntity' => array_map(fn ($q) => [
                    '@type' => 'Question',
                    'name' => $q['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q['answer']],
                ], $faq),
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    public function toScriptTag(array $graph): string
    {
        $json = json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

        return '<script type="application/ld+json">'.$json.'</script>';
    }
}
