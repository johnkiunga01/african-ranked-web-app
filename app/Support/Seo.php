<?php

namespace App\Support;

use App\Services\SiteSettings;

class Seo
{
    public function __construct(private Catalog $catalog) {}

    public function page(string $type, array $region, ?array $article = null, ?array $category = null): array
    {
        $c = $this->catalog;
        $siteName = app(SiteSettings::class)->snapshot('general')['value']['name'];
        $code = $region['code'];
        $suffix = match ($type) {
            'article' => '/articles/'.$article['slug'],
            'category' => '/categories/'.$category['slug'],
            'strategy' => '/strategy',
            'about' => '/about',
            'safari-planner' => '/plan-safari',
            'rankings' => '/rankings',
            default => '',
        };
        $path = $c->path($code, $suffix);
        // Regional article bodies are identical: consolidate their ranking signals.
        $canonical = $c->url($type === 'article' ? '/articles/'.$article['slug'] : $path);
        $title = match ($type) {
            'article' => ($article['seoTitle'] ?? null) ?: $article['title'].' | '.$siteName,
            'category' => $category['name'].' | '.$siteName.($code === 'global' ? '' : ' '.$region['name']),
            'strategy' => 'Our Editorial Approach | '.$siteName,
            'about' => 'About Us | '.$siteName,
            'safari-planner' => 'Plan Your African Safari | '.$siteName,
            'rankings' => 'African Rankings & Guides | '.$siteName.($code === 'global' ? '' : ' '.$region['name']),
            'not-found' => 'Page not found | '.$siteName,
            default => $siteName.' — The Best of Africa, Ranked'.($code === 'global' ? '' : ' | '.$region['name']),
        };
        $description = match ($type) {
            'home' => 'Discover the best of Africa through researched rankings and practical guides to destinations, wildlife, cuisine, music, technology and business.',
            'strategy' => 'How to read African Ranked rankings: our approach to research, comparison criteria, source dates, commercial transparency and corrections.',
            'about' => 'Meet African Ranked: rankings and practical guides to African travel, wildlife, food, music, technology and business.',
            'safari-planner' => 'Plan your African safari with published guides to destinations, seasons, budgets, packing and wildlife. Choose where to start and explore at your own pace.',
            'rankings' => 'Explore researched African rankings and practical guides to travel, wildlife, food, music, technology and business.',
            default => ($article['metaDescription'] ?? null) ?: ($article['excerpt'] ?? $category['description'] ?? $region['tagline']),
        };
        // Regional landing pages have distinct edition URLs; keep their search snippets
        // informative and unique without changing the underlying editorial claims.
        if ($code !== 'global') {
            $description = match ($type) {
                'home' => 'Explore African destinations, wildlife, food, music, business and technology through the '.$region['name'].' edition of '.$siteName.'.',
                'category' => 'Browse '.$category['name'].' rankings and guides about Africa in the '.$region['name'].' edition of '.$siteName.'.',
                'rankings' => 'Discover researched African rankings and practical guides in the '.$region['name'].' edition of '.$siteName.'.',
                default => $description,
            };
        }
        $image = $type === 'home'
            ? $c->url('/images/african-ranked-savannah-hero-desktop.webp')
            : ($article['image'] ?? $c->articles($code)->first()['image'] ?? $c->articles()->first()['image'] ?? $c->url('/image-placeholder.svg'));
        $breadcrumbs = [['name' => 'Home', 'url' => $c->path($code)]];
        if ($category) {
            $breadcrumbs[] = ['name' => $category['name'], 'url' => $c->path($code, '/categories/'.$category['slug'])];
        }
        if ($article) {
            $breadcrumbs[] = ['name' => $article['title'], 'url' => $path];
        }
        $alternates = [];
        $regionalIndexable = (bool) config('seo.regional_editions_indexable');
        if ($regionalIndexable && in_array($type, ['home', 'category', 'rankings'], true)) {
            foreach ($c->regions() as $alternate) {
                if ($category && ! $c->articles($alternate['code'])->contains('category', $category['slug'])) {
                    continue;
                }
                $alternates[] = ['locale' => $alternate['locale'], 'url' => $c->url($c->path($alternate['code'], $suffix))];
            }
            $alternates[] = ['locale' => 'x-default', 'url' => $c->url($suffix ?: '/')];
        }
        $organization = ['@type' => 'Organization', '@id' => $c->url().'/#organization', 'name' => $siteName, 'url' => $c->url()];
        $schemas = [
            $organization,
            ['@type' => 'WebSite', '@id' => $c->url().'/#website', 'name' => $siteName, 'url' => $c->url(), 'publisher' => ['@id' => $organization['@id']], 'inLanguage' => 'en'],
            ['@type' => 'WebPage', '@id' => $canonical.'#webpage', 'url' => $canonical, 'name' => $title, 'description' => $description, 'isPartOf' => ['@id' => $c->url().'/#website'], 'inLanguage' => 'en'],
        ];
        if ($article) {
            $schemas[] = [
                '@type' => 'Article', '@id' => $canonical.'#article', 'headline' => $article['title'],
                'description' => $article['excerpt'], 'image' => [$article['image']],
                'datePublished' => $article['datePublished'], 'dateModified' => $article['dateModified'],
                'author' => ['@type' => 'Person', 'name' => $article['author'], 'jobTitle' => $article['authorTitle']],
                'publisher' => ['@id' => $organization['@id']], 'mainEntityOfPage' => ['@id' => $canonical.'#webpage'],
                'articleSection' => $category['name'], 'inLanguage' => 'en',
            ];
            if (! empty($article['faqs'])) {
                $schemas[] = ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($faq) => [
                    '@type' => 'Question', 'name' => $faq['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ], $article['faqs'])];
            }
        } elseif (in_array($type, ['home', 'category', 'rankings'], true)) {
            $items = $c->articles($code)->when($category, fn ($items) => $items->where('category', $category['slug']))->values();
            $schemas[] = ['@type' => 'ItemList', 'itemListElement' => $items->map(fn ($item, $index) => [
                '@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['title'], 'url' => $c->url('/articles/'.$item['slug']),
            ])->all()];
        }
        if (count($breadcrumbs) > 1) {
            $schemas[] = ['@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($item, $index) => [
                '@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name'], 'item' => $c->url($item['url']),
            ], $breadcrumbs, array_keys($breadcrumbs))];
        }
        $robots = $type === 'not-found' || ($type === 'category' && ! $c->articles($code)->contains('category', $category['slug'])) ? 'noindex, follow' : 'index, follow, max-image-preview:large';

        // Near-duplicate country editions stay crawlable (follow) but out of the index.
        if (! $regionalIndexable && $code !== 'global' && $type !== 'article') {
            $robots = 'noindex, follow';
        }

        if (str_starts_with($robots, 'noindex')) {
            $alternates = [];
        }

        return compact('siteName', 'title', 'description', 'image', 'canonical', 'alternates', 'breadcrumbs', 'robots') + [
            'type' => $type === 'article' ? 'article' : 'website',
            'jsonLd' => ['@context' => 'https://schema.org', '@graph' => $schemas],
        ];
    }
}
