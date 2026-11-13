<?php

namespace Tests\Feature;

use App\Support\Catalog;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class PublishingTest extends TestCase
{
    private function document(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);

        return new DOMXPath($dom);
    }

    public function test_every_edition_is_server_rendered_with_reciprocal_home_alternates(): void
    {
        config(['seo.regional_editions_indexable' => true]);
        $catalog = app(Catalog::class);
        foreach ($catalog->regions() as $region) {
            $response = $this->get($catalog->path($region['code']))->assertOk()->assertSee('Explore rankings')->assertSee($region['name'].' edition');
            $xpath = $this->document($response->getContent());
            $this->assertSame(1, $xpath->query('//h1')->length);
            $this->assertSame(9, $xpath->query('//link[@rel="alternate"][@hreflang]')->length);
            $this->assertSame($catalog->url($catalog->path($region['code'])), $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
            $this->assertSame(6, $xpath->query('//*[@id="categories"]//a')->length);
        }
    }

    public function test_country_editions_are_held_out_of_the_index_by_default(): void
    {
        $this->assertFalse(config('seo.regional_editions_indexable'));
        $catalog = app(Catalog::class);
        foreach ($catalog->regions() as $region) {
            foreach (['', '/rankings', '/strategy'] as $suffix) {
                $response = $this->get($catalog->path($region['code'], $suffix))->assertOk();
                $xpath = $this->document($response->getContent());
                $this->assertSame(0, $xpath->query('//link[@hreflang]')->length);
                if ($region['code'] === 'global') {
                    $this->assertSame('index, follow, max-image-preview:large', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
                    $this->assertSame($catalog->url($catalog->path('global', $suffix)), $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
                } else {
                    $this->assertSame('noindex, follow', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
                    $this->assertSame(0, $xpath->query('//link[@rel="canonical"]')->length);
                }
            }
        }

        // Regional article URLs keep consolidating to the indexable global article.
        $article = $catalog->articles()->first();
        $xpath = $this->document($this->get('/ke/articles/'.$article['slug'])->assertOk()->getContent());
        $this->assertSame('index, follow, max-image-preview:large', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
        $this->assertSame($catalog->url('/articles/'.$article['slug']), $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));

        $locations = [];
        foreach (simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent())->url as $url) {
            $locations[] = (string) $url->loc;
        }
        $this->assertContains($catalog->url('/'), $locations);
        $this->assertContains($catalog->url('/rankings'), $locations);
        $this->assertContains($catalog->url('/strategy'), $locations);
        $this->assertContains($catalog->url('/about'), $locations);
        $this->assertContains($catalog->url('/plan-safari'), $locations);
        foreach ($catalog->regions() as $region) {
            if ($region['code'] !== 'global') {
                $this->assertNotContains($catalog->url('/'.$region['code']), $locations);
                $this->assertNotContains($catalog->url('/'.$region['code'].'/categories/wildlife'), $locations);
            }
        }
        $regional = simplexml_load_string($this->get('/sitemaps/ke.xml')->assertOk()->getContent());
        $this->assertCount(0, $regional->url);
    }

    public function test_articles_faqs_and_metadata_are_present_without_javascript(): void
    {
        $catalog = app(Catalog::class);
        foreach ($catalog->articles() as $article) {
            foreach (array_keys($catalog->regions()) as $region) {
                $response = $this->get($catalog->path($region, '/articles/'.$article['slug']))->assertOk()->assertSee($article['title']);
                $xpath = $this->document($response->getContent());
                $this->assertSame($article['title'], $xpath->evaluate('string(//h1)'));
                $this->assertSame($catalog->url('/articles/'.$article['slug']), $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
                $this->assertSame(0, $xpath->query('//link[@hreflang]')->length);
                $graph = json_decode($xpath->evaluate('string(//script[@type="application/ld+json"])'), true, 512, JSON_THROW_ON_ERROR)['@graph'];
                $schema = collect($graph)->firstWhere('@type', 'Article');
                $this->assertSame($article['title'], $schema['headline']);
                $this->assertSame($article['dateModified'], $schema['dateModified']);
                $crumbs = collect($graph)->firstWhere('@type', 'BreadcrumbList');
                $this->assertStringNotContainsString('/'.$region.'/'.$region.'/', json_encode($crumbs));
                foreach ($article['faqs'] ?? [] as $faq) {
                    $response->assertSee($faq['question'])->assertSee($faq['answer']);
                }
                $faqSchema = collect($graph)->firstWhere('@type', 'FAQPage');
                $this->assertCount(count($article['faqs'] ?? []), $faqSchema['mainEntity'] ?? []);
                foreach ($xpath->query('//nav[@aria-label="On this page"]//a') as $link) {
                    $id = substr($link->getAttribute('href'), 1);
                    $this->assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length);
                }
            }
        }
    }

    public function test_regional_categories_filter_content_and_empty_categories_are_not_indexed(): void
    {
        $catalog = app(Catalog::class);
        foreach ($catalog->regions() as $region) {
            foreach ($catalog->categories() as $category) {
                $articles = $catalog->articles($region['code'])->where('category', $category['slug']);
                $response = $this->get($catalog->path($region['code'], '/categories/'.$category['slug']))->assertOk()->assertSee($category['name']);
                $xpath = $this->document($response->getContent());
                $this->assertSame($articles->count(), $xpath->query('//article')->length);
                if ($articles->isEmpty()) {
                    $this->assertSame('noindex, follow', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
                    $this->assertSame(0, $xpath->query('//link[@rel="canonical"]')->length);
                } else {
                    foreach ($articles as $article) {
                        $response->assertSee($article['title']);
                    }
                }
            }
        }
    }

    public function test_unknown_routes_return_a_real_noindex_404(): void
    {
        foreach (['/missing', '/articles/missing', '/ke/articles/missing', '/categories/missing', '/zz', '/ke/articles/top-10-safari-destinations-africa-2026/extra'] as $path) {
            $response = $this->get($path)->assertNotFound()->assertSee('This page could not be found.');
            $this->assertStringContainsString('noindex, follow', $response->getContent());
        }
    }

    public function test_sitemap_feed_and_discovery_documents_are_valid_and_use_only_canonical_articles(): void
    {
        $catalog = app(Catalog::class);
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $locations = [];
        foreach ($xml->url as $url) {
            $location = (string) $url->loc;
            $locations[] = $location;
            $this->assertStringNotContainsString('/ke/articles/', $location);
            $path = parse_url($location, PHP_URL_PATH) ?: '/';
            $page = $this->get($path)->assertOk();
            $this->assertStringNotContainsString('content="noindex', $page->getContent());
        }
        $this->assertCount(count(array_unique($locations)), $locations);
        foreach ($catalog->articles() as $article) {
            $this->assertContains($catalog->url('/articles/'.$article['slug']), $locations);
        }
        $feed = $this->get('/feed.xml')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $rss = simplexml_load_string($feed->getContent());
        $this->assertCount($catalog->articles()->count(), $rss->channel->item);
        $this->get('/robots.txt')->assertOk()->assertSee($catalog->url('/sitemap.xml'));
        $this->get('/llms.txt')->assertOk()->assertSee('## Articles')->assertSee('Only published articles appear in this index.');
        $this->get('/sitemaps/ke.xml')->assertOk();
        $this->get('/sitemaps/invalid.xml')->assertNotFound();
    }

    public function test_public_robots_policy_disallows_staff_routes_but_preview_remains_fully_blocked(): void
    {
        config(['seo.preview' => false, 'review.enabled' => false]);
        app()->detectEnvironment(fn () => 'production');

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /staff')
            ->assertSee('Disallow: /newsletter')
            ->assertDontSee('Disallow: /'.PHP_EOL.'Sitemap:');

        config(['seo.preview' => true]);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
    }

    public function test_legacy_global_prefix_redirects_to_the_existing_global_url(): void
    {
        $this->get('/global')->assertRedirect('/')->assertStatus(301);
        $this->get('/global/articles/top-10-safari-destinations-africa-2026')->assertRedirect('/articles/top-10-safari-destinations-africa-2026')->assertStatus(301);
    }

    public function test_strategy_works_for_global_and_regional_editions(): void
    {
        $this->get('/strategy')->assertOk()->assertSee('Useful rankings. Clear answers.');
        $this->get('/ke/strategy')->assertOk()->assertSee('Kenya');
    }
}
