<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\EditorialWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublicationFixture;
use Tests\TestCase;

class EditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $extra = []): array
    {
        return array_replace(['slug' => 'reviewed-travel-guide', 'title' => 'A carefully reviewed travel guide', 'excerpt' => 'A useful introduction for readers.', 'category_slug' => 'travel', 'body_markdown' => PublicationFixture::body('A practical introduction.'), 'faqs_text' => collect(PublicationFixture::faqs())->map(fn ($f) => $f['question']."\n".$f['answer'])->implode("\n\n"), 'image' => 'https://images.example.test/travel.jpg', 'source_notes' => 'Sources and image permission checked by the responsible reviewer.'], $extra);
    }

    private function createArticle(User $user, array $extra = []): Article
    {
        $this->actingAs($user)->post('/staff/articles', $this->data($extra))->assertRedirect();

        return Article::where('slug', $extra['slug'] ?? 'reviewed-travel-guide')->firstOrFail();
    }

    private function approveAndPublish(Article $article, User $author, User $admin): void
    {
        $id = $article->current_revision_id;
        $this->actingAs($author)->post('/staff/articles/'.$article->id.'/submit', ['revision_id' => $id])->assertRedirect();
        $checks = array_fill_keys(EditorialWorkflow::CHECKS, '1');
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/approve', $checks + ['revision_id' => $id])->assertRedirect();
        $this->post('/staff/articles/'.$article->id.'/publish', ['revision_id' => $id])->assertRedirect();
    }

    public function test_drafts_are_team_private_and_cannot_forge_publication_fields(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $article = $this->createArticle($author, ['user_id' => $other->id, 'status' => 'published', 'published_revision_id' => 100]);
        $this->assertSame($author->id, $article->user_id);
        $this->assertNull($article->published_revision_id);
        $this->get('/staff/articles/'.$article->id.'/preview')->assertOk()->assertSee('Private draft preview');
        $this->actingAs($other)->get('/staff/articles/'.$article->id.'/edit')->assertOk();
        $this->get('/staff/articles/'.$article->id.'/preview')->assertOk();
        $this->put('/staff/articles/'.$article->id, $this->data(['revision_id' => $article->current_revision_id]))->assertRedirect();
        $this->get('/staff/articles')->assertSee($article->currentRevision->title);
        auth()->logout();
        $this->get('/articles/'.$article->slug)->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee($article->slug);
        $this->get('/feed.xml')->assertDontSee($article->currentRevision->title);
    }

    public function test_author_cannot_approve_own_article_or_publish_or_unpublish(): void
    {
        $author = User::factory()->create();
        $article = $this->createArticle($author);
        foreach (['approve', 'publish', 'unpublish', 'request_changes'] as $action) {
            $this->post('/staff/articles/'.$article->id.'/'.$action, ['revision_id' => $article->current_revision_id])->assertForbidden();
        }
    }

    public function test_admin_must_review_and_confirm_checks_before_publication(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->createArticle($author);
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/publish', ['revision_id' => $article->current_revision_id])->assertSessionHasErrors('workflow');
        $this->actingAs($author)->post('/staff/articles/'.$article->id.'/submit', ['revision_id' => $article->current_revision_id])->assertRedirect();
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/approve', ['revision_id' => $article->current_revision_id])->assertSessionHasErrors(EditorialWorkflow::CHECKS);
        $this->assertNull($article->fresh()->published_revision_id);
    }

    public function test_review_publication_revisions_and_withdrawal_reach_public_discovery_safely(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->createArticle($author);
        $this->approveAndPublish($article, $author, $admin);
        $article->refresh();
        $publishedId = $article->published_revision_id;
        $this->get('/articles/'.$article->slug)->assertOk()->assertSee('A carefully reviewed travel guide')->assertSee($author->name);
        $this->get('/sitemap.xml')->assertSee($article->slug);
        $this->get('/feed.xml')->assertSee('A carefully reviewed travel guide');
        $this->actingAs($author)->put('/staff/articles/'.$article->id, $this->data(['revision_id' => $article->current_revision_id, 'title' => 'Private revised title']))->assertRedirect();
        $article->refresh();
        $this->assertSame($publishedId, $article->published_revision_id);
        $this->assertNotSame($publishedId, $article->current_revision_id);
        $this->get('/articles/'.$article->slug)->assertSee('A carefully reviewed travel guide')->assertDontSee('Private revised title');
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/publish', ['revision_id' => $article->current_revision_id])->assertSessionHasErrors('workflow');
        $this->post('/staff/articles/'.$article->id.'/unpublish', ['revision_id' => $article->current_revision_id])->assertRedirect();
        $this->get('/articles/'.$article->slug)->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee($article->slug);
        $this->assertTrue(AuditEvent::where('action', 'article.publish')->exists());
    }

    public function test_feedback_and_new_drafts_invalidate_prior_approval(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->createArticle($author);
        $this->post('/staff/articles/'.$article->id.'/submit', ['revision_id' => $article->current_revision_id]);
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/request_changes', ['revision_id' => $article->current_revision_id, 'review_notes' => 'Explain the complete ranking.'])->assertRedirect();
        $this->actingAs($author)->get('/staff/articles/'.$article->id.'/edit')->assertSee('Explain the complete ranking.');
        $this->put('/staff/articles/'.$article->id, $this->data(['revision_id' => $article->current_revision_id]))->assertRedirect();
        $article->refresh();
        $this->assertNull($article->currentRevision->review_checklist);
        $this->assertSame('draft', $article->currentRevision->status->value);
    }

    public function test_stale_edits_and_stale_review_actions_fail_without_overwriting(): void
    {
        $author = User::factory()->create();
        $article = $this->createArticle($author);
        $old = $article->current_revision_id;
        $this->put('/staff/articles/'.$article->id, $this->data(['revision_id' => $old, 'title' => 'New title']))->assertRedirect();
        $this->put('/staff/articles/'.$article->id, $this->data(['revision_id' => $old, 'title' => 'Stale title']))->assertSessionHasErrors('workflow');
        $this->post('/staff/articles/'.$article->id.'/submit', ['revision_id' => $old])->assertSessionHasErrors('workflow');
        $this->assertSame('New title', $article->fresh()->currentRevision->title);
        $this->assertSame(2, $article->revisions()->count());
    }

    public function test_html_is_escaped_and_unsafe_image_urls_are_rejected(): void
    {
        $author = User::factory()->create();
        $this->actingAs($author)->post('/staff/articles', $this->data(['image' => 'javascript:alert(1)']))->assertSessionHasErrors('image');
        $article = $this->createArticle($author, ['body_markdown' => '<script>alert("unsafe")</script>']);
        $this->get('/staff/articles/'.$article->id.'/preview')->assertDontSee('<script>alert(', false)->assertSee('<script>alert("unsafe")</script>');
    }

    public function test_submission_requires_content_image_and_source_notes(): void
    {
        $author = User::factory()->create();
        $article = $this->createArticle($author, ['image' => null, 'source_notes' => null]);
        $this->post('/staff/articles/'.$article->id.'/submit', ['revision_id' => $article->current_revision_id])->assertSessionHasErrors('workflow');
    }

    public function test_seed_import_is_idempotent_and_never_auto_publishes_unverified_articles(): void
    {
        $this->artisan('content:import-seeds')->assertSuccessful();
        $this->assertDatabaseCount('articles', count(json_decode(file_get_contents(base_path('content/articles.json')), true)));
        $this->assertDatabaseCount('article_revisions', count(json_decode(file_get_contents(base_path('content/articles.json')), true)));
        $this->artisan('content:import-seeds')->assertSuccessful();
        $this->assertDatabaseCount('articles', count(json_decode(file_get_contents(base_path('content/articles.json')), true)));
        $this->assertDatabaseCount('article_revisions', count(json_decode(file_get_contents(base_path('content/articles.json')), true)));
        $this->assertSame(0, Article::whereNotNull('published_revision_id')->count());
        $this->get('/articles/top-10-safari-destinations-africa-2026')->assertNotFound();
    }

    public function test_production_does_not_fall_back_to_unreviewed_legacy_json(): void
    {
        config(['app.env' => 'production']);
        $this->get('/articles/top-10-safari-destinations-africa-2026')->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('Top 10 Safari Destinations');
        config(['app.env' => 'prod']);
        $this->get('/articles/top-10-safari-destinations-africa-2026')->assertNotFound();
    }
}
