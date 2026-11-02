<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Jobs\OptimiseArticleSeo;
use App\Jobs\ResearchArticleSeo;
use App\Models\Article;
use App\Models\ArticleResearch;
use App\Models\ArticleSeoBatch;
use App\Models\User;
use App\Services\ArticleResearcher;
use App\Services\OpenSeoMcp;
use App\Services\SeedArticleImporter;
use App\Services\SeoAutoFix;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BulkArticleTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $user, string $slug): Article
    {
        $seed = app(Catalog::class)->data('articles')[0];
        $seed['slug'] = $slug;

        return app(SeedArticleImporter::class)->import($seed, $user);
    }

    private function research(Article $article, User $user): void
    {
        ArticleResearch::create(['article_id' => $article->id, 'user_id' => $user->id, 'keyword' => 'researched safari query', 'location_code' => 2840, 'status' => 'completed', 'researched_at' => now(), 'brief' => app(ArticleResearcher::class)->brief('researched safari query', 2840, ['search_volume' => 123, 'keyword_difficulty' => 0], [['type' => 'organic', 'title' => 'Fixture competitor', 'domain' => 'competitor.example', 'url' => 'https://competitor.example/fixture', 'description' => 'Fixture snippet', 'organicRank' => 1]])]);
    }

    public function test_bulk_publication_is_explicit_atomic_and_idempotent(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $first = $this->article($admin, 'bulk-first');
        $second = $this->article($admin, 'bulk-second');
        $selection = ['operation' => 'publish', 'selected' => [$first->id => $first->current_revision_id, $second->id => $second->current_revision_id]];
        $this->actingAs($admin)->post('/staff/article-batches', $selection)->assertOk()->assertSee('Publish 2 articles');
        $this->assertNull($first->fresh()->published_revision_id);
        $this->post('/staff/article-batches/execute', $selection)->assertSessionHasErrors('editorial_approval');
        $selection['editorial_approval'] = 1;
        $bad = $selection;
        $bad['selected'][$second->id] = 999999;
        $this->post('/staff/article-batches/execute', $bad)->assertSessionHasErrors('selected');
        $this->assertNull($first->fresh()->published_revision_id);
        $this->post('/staff/article-batches/execute', $selection)->assertRedirect('/staff/articles?tab=published');
        $this->assertSame(RevisionStatus::Published, $first->fresh()->currentRevision->status);
        $this->assertSame($second->current_revision_id, $second->fresh()->published_revision_id);
        $publishedAt = $first->fresh()->currentRevision->published_at;
        $this->travel(10)->minutes();
        $this->post('/staff/article-batches/execute', $selection)->assertRedirect();
        $this->assertTrue($publishedAt->equalTo($first->fresh()->currentRevision->published_at));
        $this->actingAs(User::factory()->create())->post('/staff/article-batches', $selection)->assertForbidden();
    }

    public function test_queues_durable_jobs_without_provider_calls_and_rejects_duplicates(): void
    {
        Http::fake();
        config(['services.gemini.key' => 'fixture-key']);
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin, 'queued-fixture');
        $data = ['operation' => 'gemini', 'ai_consent' => 1, 'selected' => [$article->id => $article->current_revision_id]];
        $this->actingAs($admin)->post('/staff/article-batches/execute', $data)->assertRedirect('/staff/seo-batches/1');
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('article_seo_batch_items', 1);
        $this->post('/staff/article-batches/execute', $data)->assertSessionHasErrors('selected');
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('article_seo_batches', 1);
        Http::assertNothingSent();
        $this->getJson('/staff/seo-batches/1')->assertOk()->assertJsonPath('counts.queued', 1);
        $this->actingAs(User::factory()->create())->get('/staff/seo-batches/1')->assertForbidden();
    }

    public function test_reuses_openseo_brief_and_saves_gemini_draft_without_changing_live_version(): void
    {
        Queue::fake();
        RateLimiter::clear('gemini-bulk-provider');
        config(['services.gemini.key' => 'fixture-key']);
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin, 'researched-fixture');
        $prior = $article->currentRevision;
        $prior->update(['status' => 'published', 'published_at' => now()]);
        $article->forceFill(['published_revision_id' => $prior->id, 'first_published_at' => now()])->save();
        $this->research($article, $admin);
        $batch = ArticleSeoBatch::create(['user_id' => $admin->id, 'session_version' => $admin->session_version]);
        $item = $batch->items()->create(['article_id' => $article->id, 'source_revision_id' => $prior->id]);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode(['seo_title' => 'Clear safari search title', 'meta_description' => 'A clearer summary of the supplied safari article.', 'focus_keyword' => 'invented replacement query'])]]]]]])]);
        (new ResearchArticleSeo($item->id))->handle(app(ArticleResearcher::class));
        Queue::assertPushed(OptimiseArticleSeo::class);
        (new OptimiseArticleSeo($item->id))->handle(app(SeoAutoFix::class));
        $this->assertSame('completed', $item->fresh()->status);
        $this->assertSame('researched safari query', $article->fresh()->currentRevision->focus_keyword);
        $this->assertSame($prior->id, $article->fresh()->published_revision_id);
        $this->assertSame(RevisionStatus::Draft, $article->fresh()->currentRevision->status);
        Http::assertSent(function ($request) {
            $context = json_decode($request['contents'][0]['parts'][0]['text'], true);

            return $context['openseo_research']['metrics']['search_volume'] === 123 && $context['openseo_research']['metrics']['keyword_difficulty'] === 0;
        });
        (new OptimiseArticleSeo($item->id))->handle(app(SeoAutoFix::class));
        Http::assertSentCount(1);
    }

    public function test_stale_or_revoked_jobs_make_no_provider_call(): void
    {
        Http::fake();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin, 'stale-queued');
        $batch = ArticleSeoBatch::create(['user_id' => $admin->id, 'session_version' => $admin->session_version]);
        $item = $batch->items()->create(['article_id' => $article->id, 'source_revision_id' => $article->current_revision_id]);
        $admin->increment('session_version');
        (new ResearchArticleSeo($item->id))->handle(app(ArticleResearcher::class));
        $this->assertSame('skipped', $item->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_metadata_editor_is_stale_safe_and_preserves_structured_body(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin, 'metadata-fixture');
        $prior = $article->currentRevision;
        $this->actingAs($admin)->get('/staff/articles/'.$article->id.'/seo')->assertOk()->assertSee('Research & optimise with Gemini', false)->assertSee('Apply rule-based fixes');
        $data = ['revision_id' => $prior->id, 'seo_title' => 'A clearer title', 'meta_description' => 'A clearer description', 'image_alt' => 'Editor supplied description'];
        $this->put('/staff/articles/'.$article->id.'/seo-metadata', $data)->assertRedirect();
        $this->assertSame($prior->body, $article->fresh()->currentRevision->body);
        $this->assertSame('A clearer title', $article->fresh()->currentRevision->seo_title);
        $this->put('/staff/articles/'.$article->id.'/seo-metadata', $data)->assertSessionHasErrors('workflow');
    }

    public function test_research_uses_actual_metrics_and_preserves_missing_values(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin, 'research-fixture');
        $article->currentRevision->update(['focus_keyword' => 'known query']);
        $this->mock(OpenSeoMcp::class, function ($mock) {
            $mock->shouldReceive('research')->once()->with('get_keyword_metrics', \Mockery::on(fn ($args) => $args['keywords'] === ['known query'] && $args['locationCode'] === 2840))->andReturn(['keywords' => []]);
            $mock->shouldReceive('research')->once()->with('get_serp_results', \Mockery::any())->andReturn(['results' => [['keyword' => 'known query', 'ok' => true, 'items' => []]]]);
        });
        config(['services.openseo.key' => 'fixture-key']);
        $result = app(ArticleResearcher::class)->research($admin, $article);
        $this->assertNull($result->brief['metrics']['search_volume']);
        $this->assertSame('United States benchmark, not global search volume', $result->brief['market']['scope']);
        $this->assertSame($result->id, app(ArticleResearcher::class)->research($admin, $article)->id);
    }
}
