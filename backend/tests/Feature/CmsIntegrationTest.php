<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Source;
use App\Models\User;
use App\Services\PublishingGate;
use App\Services\SchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function article(string $type = 'guide', string $slug = 'test-guide'): Article
    {
        $category = Category::firstOrCreate(['slug' => 'wildlife'], ['name' => 'Wildlife & Safaris']);
        $author = Author::firstOrCreate(['slug' => 'test-author'], ['name' => 'Test Author', 'bio' => 'Synthetic test author.']);
        $reviewer = Author::firstOrCreate(['slug' => 'test-reviewer'], ['name' => 'Test Reviewer', 'bio' => 'Synthetic test reviewer.', 'is_reviewer' => true]);
        $a = Article::create([
            'slug' => $slug, 'type' => $type, 'status' => 'approved', 'title' => $type === 'ranking' ? 'Top 10 Test Places' : 'Test guide',
            'edition' => 'global', 'category_id' => $category->id, 'author_id' => $author->id, 'reviewer_id' => $reviewer->id,
            'body' => str_repeat('Test evidence supports this detailed synthetic editorial explanation. ', 180),
            'answer_block' => str_repeat('Test answer evidence supports these conclusions. ', 8),
            'key_facts' => [['label' => 'Test label', 'value' => 'Test value', 'verified_on' => now()->toDateString()]],
            'faq' => array_fill(0, 3, ['question' => 'Test question?', 'answer' => 'Test answer.']),
            'methodology_note' => 'Synthetic test methodology.',
        ]);
        $entity = Entity::firstOrCreate(['slug' => 'test-entity'], ['name' => 'Test entity', 'type' => 'park']);
        $a->entities()->attach($entity, ['role' => 'about']);
        for ($i = 1; $i <= 3; $i++) {
            $source = Source::firstOrCreate(['url' => "https://example.test/source-{$i}"], ['title' => "Test source {$i}", 'accessed_at' => now()]);
            $a->sources()->attach($source);
        }
        if ($type === 'ranking') {
            for ($i = 1; $i <= 10; $i++) {
                $a->items()->create(['entity_id' => $entity->id, 'position' => $i, 'scores' => ['test' => 8], 'score_total' => 8,
                    'blurb' => str_repeat('Test description gives sufficient synthetic evidence for this item. ', 8)]);
            }
        }

        return $a->fresh();
    }

    private function payload(): array
    {
        Category::firstOrCreate(['slug' => 'wildlife'], ['name' => 'Wildlife & Safaris']);

        return [
            'type' => 'guide', 'slug' => 'agent-guide', 'category' => 'wildlife', 'title' => 'Test agent guide',
            'dek' => 'Synthetic test description.', 'answer_block' => 'Draft answer.', 'body' => 'Draft body.',
            'key_facts' => [['label' => 'Test fact', 'value' => 'Test value', 'verified_on' => now()->toDateString()]],
            'faq' => array_fill(0, 3, ['question' => 'Test question?', 'answer' => 'Test answer.']),
            'sources' => array_map(fn ($i) => ['title' => "Test source {$i}", 'url' => "https://example.test/{$i}", 'accessed_at' => now()->toDateString()], range(1, 3)),
            'entities' => [['slug' => 'test-place', 'name' => 'Test place', 'type' => 'park', 'role' => 'about']],
            'needs_verification' => [],
        ];
    }

    private function token(array $abilities): string
    {
        return User::factory()->create()->createToken('test-agent', $abilities)->plainTextToken;
    }

    public function test_valid_guide_publishes_but_requires_separate_index_audit(): void
    {
        $a = $this->article()->publish();
        $this->assertSame(ArticleStatus::Published, $a->status);
        $this->assertFalse($a->indexable);
        $this->assertTrue($a->markIndexable()->fresh()->indexable);
    }

    public function test_top_ten_requires_ten_fully_scored_items(): void
    {
        $a = $this->article('ranking');
        $a->items()->where('position', 10)->delete();
        $this->assertStringContainsString('promises Top 10', implode(' ', app(PublishingGate::class)->evaluate($a)));
        $this->expectException(\RuntimeException::class);
        $a->publish();
    }

    public function test_short_body_cannot_publish_even_with_every_other_requirement(): void
    {
        $a = $this->article();
        $a->body = str_repeat('word ', 1299);
        $this->expectException(\RuntimeException::class);
        $a->publish();
    }

    public function test_direct_status_assignment_cannot_bypass_gates(): void
    {
        $a = $this->article();
        $this->expectException(\RuntimeException::class);
        $a->forceFill(['status' => 'published', 'body' => 'Thin'])->save();
    }

    public function test_indexable_assignment_requires_audit_method(): void
    {
        $a = $this->article()->publish();
        $this->expectException(\RuntimeException::class);
        $a->forceFill(['indexable' => true])->save();
    }

    public function test_near_duplicate_is_rejected(): void
    {
        $this->article()->publish();
        $duplicate = $this->article('guide', 'duplicate-guide');
        $this->assertStringContainsString('Too similar', implode(' ', app(PublishingGate::class)->evaluate($duplicate)));
    }

    public function test_sitemap_contains_only_published_indexable_and_canonical_records(): void
    {
        $visible = $this->article()->publish()->markIndexable();
        Article::withoutEvents(function () use ($visible) {
            foreach ([['draft', false, null], ['published', false, null], ['published', true, now()->addDay()], ['published', true, null]] as $i => [$status, $indexable, $future]) {
                $copy = $visible->replicate();
                $copy->slug = 'hidden-'.$i;
                $copy->status = $status;
                $copy->indexable = $indexable;
                if ($future) {
                    $copy->published_at = $future;
                }
                if ($i === 3) {
                    $copy->canonical_article_id = $visible->id;
                }
                $copy->save();
            }
        });
        $index = $this->get('/sitemap.xml')->assertOk();
        $this->assertNotFalse(simplexml_load_string($index->getContent()));
        $map = $this->get('/sitemaps/global-1.xml')->assertOk();
        $this->assertStringContainsString('/articles/test-guide', $map->getContent());
        $this->assertStringNotContainsString('hidden-', $map->getContent());
        $this->get('/sitemaps/global-0.xml')->assertNotFound();
        $this->get('/sitemaps/global-2.xml')->assertNotFound();
    }

    public function test_robots_and_noindex_headers_distinguish_production_and_tunnel(): void
    {
        $this->app->instance('env', 'production');
        config(['africaranked.brand.url' => 'https://africanranked.com', 'africaranked.bots.allow_training' => false]);
        $production = $this->get('https://africanranked.com/robots.txt')->assertOk();
        $production->assertSee('Allow: /', false)->assertSee('User-agent: GPTBot', false);
        $this->assertNull($production->headers->get('X-Robots-Tag'));
        foreach (['preview.tunnelmole.net', 'test.ngrok-free.app', 'staging.example.test'] as $host) {
            $response = $this->get('https://'.$host.'/robots.txt')->assertOk();
            $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
            $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }
    }

    public function test_absolute_urls_never_use_the_request_host(): void
    {
        config(['africaranked.brand.url' => 'https://africanranked.com']);
        $a = $this->article()->publish()->markIndexable();
        $this->get('https://preview.tunnelmole.net/sitemaps/global-1.xml')->assertSee('https://africanranked.com/articles/test-guide', false);
        $graph = app(SchemaBuilder::class)->forArticle($a->fresh());
        $this->assertStringNotContainsString('tunnelmole', json_encode($graph));
    }

    public function test_published_schema_roundtrips_as_json_and_matches_rendered_fields(): void
    {
        $a = $this->article('ranking')->publish();
        $graph = app(SchemaBuilder::class)->forArticle($a->fresh());
        $decoded = json_decode(json_encode($graph, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertCount(1, array_filter($decoded['@graph'], fn ($node) => $node['@type'] === 'Article'));
        $this->assertSame(10, collect($decoded['@graph'])->firstWhere('@type', 'ItemList')['numberOfItems']);
        $response = $this->get('/articles/'.$a->slug)->assertOk();
        $response->assertSee('application/ld+json', false)->assertSee('ar-cms-data', false);
        $this->get('/authors/test-author')->assertOk();
        $this->get('/e/test-entity')->assertOk();
    }

    public function test_agent_requires_bearer_token_with_only_drafts_create(): void
    {
        $payload = $this->payload();
        $this->postJson('/api/agent/drafts', $payload)->assertUnauthorized();
        foreach ([['articles:publish'], ['*'], ['drafts:create', 'articles:publish']] as $abilities) {
            $this->withToken($this->token($abilities))->postJson('/api/agent/drafts', $payload)->assertForbidden();
        }
    }

    public function test_agent_can_create_drafts_but_cannot_publish_or_index(): void
    {
        $payload = $this->payload();
        $token = $this->token(['drafts:create']);
        $this->withToken($token)->postJson('/api/agent/drafts', [...$payload, 'status' => 'published', 'indexable' => true])
            ->assertUnprocessable()->assertJsonValidationErrors(['status', 'indexable']);
        $response = $this->withToken($token)->postJson('/api/agent/drafts', $payload)->assertCreated()->assertJsonPath('status', 'draft');
        $article = Article::findOrFail($response->json('id'));
        $this->assertFalse($article->indexable);
        $this->assertNull($article->published_at);
        $this->assertNull(Entity::first()->verified_at);
        $this->get('/articles/'.$article->slug)->assertNotFound();
        $this->getJson('/api/content/articles')->assertJsonCount(0, 'articles');
        $this->withToken($token)->postJson('/api/agent/drafts', $payload)->assertConflict();
    }

    public function test_invalid_ranking_entity_rolls_back_entire_draft(): void
    {
        $payload = [...$this->payload(), 'type' => 'ranking', 'ranking_items' => [
            ['entity_slug' => 'missing-entity', 'position' => 1, 'scores' => ['test' => 8], 'score_total' => 8, 'blurb' => 'Test'],
        ]];
        $this->withToken($this->token(['drafts:create']))->postJson('/api/agent/drafts', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('articles', 0);
        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('entities', 0);
    }

    public function test_legacy_import_is_idempotent_and_preserves_copy_dates_and_public_urls(): void
    {
        $this->artisan('cms:import-legacy')->assertSuccessful();
        $source = json_decode(file_get_contents(database_path('seeders/legacy-content.json')), true);
        $this->assertDatabaseCount('articles', count($source['articles']));
        foreach ($source['articles'] as $legacy) {
            $a = Article::where('slug', $legacy['slug'])->firstOrFail();
            $this->assertSame('draft', $a->status->value);
            $this->assertFalse($a->indexable);
            $this->assertSame($legacy, $a->frontend_payload);
            $this->assertTrue($a->published_at->equalTo($legacy['datePublished']));
            $this->assertTrue($a->created_at->equalTo($legacy['datePublished']));
            $this->assertTrue($a->updated_at->equalTo($legacy['dateModified']));
            foreach (config('africaranked.editions') as $region) {
                if (in_array($region, config('africaranked.editions'), true)) {
                    $this->get(($region === 'global' ? '' : '/'.$region).'/articles/'.$a->slug)->assertOk();
                }
            }
        }
        $this->artisan('cms:import-legacy')->expectsOutput('Imported 0 articles as draft/non-indexable; legacy visibility preserved.')->assertSuccessful();
        $this->assertDatabaseCount('articles', count($source['articles']));
        $this->get('/sitemaps/global-1.xml')->assertDontSee('<url>', false);
        $this->get('/strategy')->assertOk();
        $this->get('/categories/wildlife')->assertOk();
        $this->get('/sitemaps/global.xml')->assertOk();
    }

    public function test_draft_revisions_of_legacy_articles_do_not_leak(): void
    {
        $this->artisan('cms:import-legacy')->assertSuccessful();
        $article = Article::first();
        $originalTitle = $article->frontend_payload['title'];
        $article->update(['title' => 'Private revised title', 'body' => 'Private draft body', 'answer_block' => 'Private answer']);
        $response = $this->getJson('/api/content/articles')->assertOk();
        $response->assertDontSee('Private revised title')->assertDontSee('Private draft body')->assertDontSee('Private answer');
        $this->assertSame($originalTitle, collect($response->json('articles'))->firstWhere('slug', $article->slug)['title']);
    }

    public function test_stale_article_is_deindexed_but_stays_public(): void
    {
        $article = $this->article()->publish()->markIndexable();
        $article->forceFill(['next_verification_at' => now()->subDay()])->save();
        $this->artisan('articles:flag-stale')->assertSuccessful();
        $this->assertSame(ArticleStatus::NeedsUpdate, $article->fresh()->status);
        $this->assertFalse($article->fresh()->indexable);
        $this->get('/articles/'.$article->slug)->assertOk();
        $this->get('/sitemaps/global-1.xml')->assertDontSee('/articles/test-guide', false);
    }

    public function test_llms_links_resolve_and_unwritten_editorial_pages_are_not_advertised(): void
    {
        $response = $this->get('/llms.txt')->assertOk();
        $this->assertStringContainsString(config('africaranked.brand.positioning'), $response->getContent());
        preg_match_all('/\]\((https?:\/\/[^)]+)\)/', $response->getContent(), $matches);
        foreach ($matches[1] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/methodology')->assertNotFound();
        $response->assertDontSee('/methodology');
    }

    public function test_owner_supplied_editorial_markdown_uses_existing_frontend_shell(): void
    {
        $dir = sys_get_temp_dir().'/ar-editorial-'.uniqid();
        mkdir($dir);
        config(['cms.editorial_directory' => $dir]);
        try {
            file_put_contents($dir.'/methodology.md', '# Test owner text');
            $this->get('/methodology')->assertOk()->assertSee('Test owner text');
            $this->get('/llms.txt')->assertSee('/methodology');
        } finally {
            unlink($dir.'/methodology.md');
            rmdir($dir);
        }
    }
}
