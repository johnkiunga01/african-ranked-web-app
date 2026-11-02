<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\User;
use App\Services\EditorialWorkflow;
use App\Services\MarkdownBlocks;
use App\Services\PublicationQuality;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\Support\PublicationFixture;
use Tests\TestCase;

class PublicationQualityTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $user, string $slug = 'quality-guide'): Article
    {
        $record = app(Catalog::class)->data('articles')[0];
        $record['slug'] = $slug;
        $article = app(SeedArticleImporter::class)->import($record, $user);
        PublicationFixture::prepare($article);

        return $article->fresh();
    }

    private function revision(array $extra = []): ArticleRevision
    {
        return new ArticleRevision(array_replace(['title' => 'A guide', 'excerpt' => 'Test summary',
            'body' => app(MarkdownBlocks::class)->parse(PublicationFixture::body()),
            'faqs' => PublicationFixture::faqs()], $extra));
    }

    public function test_body_source_and_faq_thresholds_are_enforced_without_claiming_fact_verification(): void
    {
        $quality = app(PublicationQuality::class);
        $good = $quality->inspect($this->revision());
        $this->assertEmpty($good['failures']);
        $this->assertStringContainsString('Human source', $good['verification']);
        $bad = $quality->inspect($this->revision(['body' => [['type' => 'paragraph', 'text' => str_repeat('word ', 1299)]], 'faqs' => []]));
        $this->assertSame(1299, $bad['body_words']);
        $this->assertCount(3, $bad['failures']);
    }

    public function test_private_notes_duplicate_links_and_internal_links_are_not_three_citations(): void
    {
        $blocks = array_fill(0, 3, ['type' => 'link', 'text' => 'Test source', 'url' => 'https://source.example.test/page#fragment']);
        $blocks[] = ['type' => 'link', 'text' => 'Internal', 'url' => config('app.url').'/articles/other'];
        $report = app(PublicationQuality::class)->inspect($this->revision(['body' => $blocks, 'source_notes' => 'https://another.example.test/notes']));
        $this->assertSame(1, $report['source_links']);
    }

    public function test_top_ten_requires_each_distinct_position_and_a_full_explanation(): void
    {
        $quality = app(PublicationQuality::class);
        $good = $this->revision(['title' => 'Top 10 Synthetic Entries']);
        $this->assertEmpty($quality->inspect($good)['failures']);
        $good->body = array_values(array_filter($good->body, fn ($b) => ! str_starts_with($b['text'] ?? '', '10. Synthetic entry')));
        $report = $quality->inspect($good);
        $this->assertStringContainsString('promises 10', implode(' ', $report['failures']));
        $duplicate = $this->revision(['title' => 'Top 10 Synthetic Entries']);
        $body = $duplicate->body;
        $body[] = ['type' => 'heading', 'text' => '1. Repeated', 'level' => 2];
        $duplicate->body = $body;
        $this->assertContains('Ranking positions must be distinct.', $quality->inspect($duplicate)['failures']);
    }

    public function test_placeholders_are_rejected_but_ordinary_bracketed_prose_is_not(): void
    {
        $revision = $this->revision();
        $revision->excerpt = 'Unfinished [insert source here]';
        $this->assertContains('Remove unresolved placeholder text before publication.', app(PublicationQuality::class)->inspect($revision)['failures']);
        $revision->excerpt = 'A supported [editorial note]';
        $this->assertEmpty(app(PublicationQuality::class)->inspect($revision)['failures']);
    }

    public function test_one_click_publication_cannot_bypass_structural_gates_or_expose_drafts(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin);
        $article->currentRevision->update(['body' => [['type' => 'paragraph', 'text' => 'Thin draft.']]]);
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/publish-reviewed', [
            'revision_id' => $article->current_revision_id, 'editorial_approval' => 1,
        ])->assertSessionHasErrors('workflow');
        $this->assertNull($article->fresh()->published_revision_id);
        $this->assertSame('draft', $article->fresh()->currentRevision->status->value);
        $this->get('/articles/'.$article->slug)->assertNotFound();
    }

    public function test_standard_publication_cannot_bypass_quality_checks_after_approval(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin);
        $workflow = app(EditorialWorkflow::class);
        $id = $article->current_revision_id;
        $workflow->transition($admin, $article, 'submit', $id);
        $workflow->transition($admin, $article, 'approve', $id, array_fill_keys(EditorialWorkflow::CHECKS, true));
        $article->currentRevision->update(['faqs' => []]);
        $this->expectException(ValidationException::class);
        $workflow->transition($admin, $article, 'publish', $id);
    }

    public function test_failed_bulk_publication_rolls_back_every_record(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $good = $this->article($admin, 'good');
        $bad = $this->article($admin, 'bad');
        $bad->currentRevision->update(['faqs' => []]);
        $this->actingAs($admin)->post('/staff/article-batches/execute', ['operation' => 'publish', 'editorial_approval' => 1,
            'selected' => [$good->id => $good->current_revision_id, $bad->id => $bad->current_revision_id]])->assertSessionHasErrors('workflow');
        $this->assertSame(0, Article::whereNotNull('published_revision_id')->count());
        $this->assertSame('draft', $good->fresh()->currentRevision->status->value);
    }

    public function test_author_sees_only_their_own_readiness_and_admin_sees_all(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $mine = $this->article($author, 'mine');
        $theirs = $this->article($other, 'theirs');
        $mine->currentRevision->update(['title' => 'My private title']);
        $theirs->currentRevision->update(['title' => 'Other private title']);
        $this->actingAs($author)->get('/staff/content-readiness')->assertOk()->assertSee('My private title')->assertDontSee('Other private title');
        $this->actingAs($admin)->get('/staff/content-readiness')->assertOk()->assertSee('Other private title');
    }

    public function test_readiness_pagination_renders_without_leaking_other_authors(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        for ($i = 0; $i < 26; $i++) {
            $this->article($admin, 'paged-'.$i);
        }
        $this->actingAs($admin)->get('/staff/content-readiness?page=2')->assertOk()->assertSee('Page 2 of 2');
    }

    public function test_repository_audit_is_read_only_and_does_not_call_any_provider(): void
    {
        Http::preventStrayRequests();
        $this->artisan('content:audit', ['--repository' => true, '--json' => true])->assertFailed();
        $this->assertDatabaseCount('articles', 0);
        $this->assertDatabaseCount('article_revisions', 0);
    }

    public function test_preview_robots_blocks_crawling_while_production_allows_crawling(): void
    {
        config(['seo.preview' => true]);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /', false)->assertDontSee('Allow: /', false);
        $original = $this->app['env'];
        $this->app['env'] = 'production';
        config(['seo.preview' => false, 'review.enabled' => false, 'app.url' => 'https://publisher.example.test']);
        try {
            $this->get('https://publisher.example.test/robots.txt')->assertOk()->assertSee('Allow: /', false);
        } finally {
            $this->app['env'] = $original;
        }
    }
}
