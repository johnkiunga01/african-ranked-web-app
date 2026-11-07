<?php

namespace Tests\Feature\Staff;

use App\Jobs\PrepareArticleRecommendation;
use App\Models\Article;
use App\Models\ArticleRecommendation;
use App\Models\ArticleResearch;
use App\Models\User;
use App\Services\ArticleRecommendations;
use App\Services\ArticleResearcher;
use App\Services\EditorialWorkflow;
use App\Services\GeminiArticleCurator;
use App\Services\RecommendationReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecommendationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function article(?User $owner = null, string $slug = 'recommendation-test'): Article
    {
        $owner ??= User::factory()->create();
        $sources = [];
        $facts = [];
        for ($i = 1; $i <= 5; $i++) {
            $sources[] = ['title' => 'Test source '.$i, 'publisher' => 'Synthetic publisher', 'url' => 'https://source.example.test/'.$i, 'accessed_at' => now()->toDateString()];
            if ($i <= 4) {
                $facts[] = ['fact' => 'Synthetic test fact '.$i, 'source_url' => $sources[$i - 1]['url'], 'verified_at' => now()->toDateString()];
            }
        }
        $body = "## Reporting\n\n".str_repeat('Synthetic reporting exercises the private suggestion and acceptance workflow safely. ', 160);
        foreach ($sources as $s) {
            $body .= "\n\n[{$s['title']}]({$s['url']})";
        }

        return app(EditorialWorkflow::class)->save($owner, ['slug' => $slug, 'title' => 'A synthetic guide', 'excerpt' => 'Synthetic summary.',
            'category_slug' => 'travel', 'body_markdown' => $body, 'image' => 'https://images.example.test/'.$slug.'.jpg',
            'source_notes' => 'Test-only source and permission notes.', 'faqs_text' => "Question one?\nTest answer one.\n\nQuestion two?\nTest answer two.\n\nQuestion three?\nTest answer three.",
            'editorial_evidence' => ['content_type' => 'guide', 'answer' => str_repeat('Answer ', 45), 'sources' => $sources, 'facts' => $facts,
                'last_verified_at' => now()->toDateString(), 'image_rights' => 'Synthetic test permission, not a real licence.']]);
    }

    private function start(Article $article, ?User $actor = null): ArticleRecommendation
    {
        Queue::fake();
        config(['services.gemini.key' => 'test-only-key', 'services.openseo.key' => 'test-only-research']);

        return app(ArticleRecommendations::class)->request($actor ?? $article->author, $article, $article->current_revision_id);
    }

    private function draft(Article $article): array
    {
        $r = $article->currentRevision;

        return ['title' => 'A clearer synthetic guide', 'excerpt' => 'A clearer test-only summary.',
            'body_markdown' => $r->body_markdown, 'seo_title' => 'Clear test guide', 'meta_description' => 'Test search preview.',
            'faqs_text' => collect($r->faqs)->map(fn ($faq) => $faq['question']."\n".$faq['answer'])->implode("\n\n")];
    }

    private function ready(Article $article): ArticleRecommendation
    {
        $request = $this->start($article);
        $request->update(['status' => 'ready', 'payload' => $request->payload + ['draft' => $this->draft($article), 'changes' => ['Opening summary']]]);

        return $request;
    }

    public function test_author_requests_one_background_suggestion_without_forming_a_brief_or_choosing_provider(): void
    {
        $article = $this->article();
        $owner = $article->author;
        $this->start($article);
        Queue::assertPushed(PrepareArticleRecommendation::class, 1);
        $again = app(ArticleRecommendations::class)->request($owner, $article, $article->current_revision_id);
        $this->assertSame(1, ArticleRecommendation::count());
        $this->assertSame('queued', $again->status);
        $this->assertNull($article->fresh()->published_revision_id);
        $this->assertSame(1, $article->revisions()->count());
        $this->actingAs($owner)->get('/staff/articles/'.$article->id.'/suggestions/'.$again->id)->assertOk()->assertSee('Preparing your suggestion');
    }

    public function test_research_and_generation_are_orchestrated_without_changing_the_article(): void
    {
        $article = $this->article();
        $request = $this->start($article);
        $research = ArticleResearch::create(['article_id' => $article->id, 'user_id' => $article->user_id, 'keyword' => 'test research', 'location_code' => 2840, 'status' => 'completed', 'researched_at' => now(), 'brief' => []]);
        $this->mock(ArticleResearcher::class)->shouldReceive('research')->once()->withArgs(fn ($actor, $a, $recommended) => $actor->id === $article->user_id && $a->id === $article->id && $recommended)->andReturn($research);
        $this->mock(GeminiArticleCurator::class)->shouldReceive('generate')->once()->withArgs(fn ($a, $brief) => $a->id === $article->id && str_contains($brief, 'editorial guidelines'))->andReturn($this->draft($article));
        (new PrepareArticleRecommendation($request->id))->handle(app(ArticleResearcher::class));
        $this->assertSame('ready', $request->fresh()->status);
        $this->assertSame('A synthetic guide', $article->fresh()->currentRevision->title);
        $this->assertSame(1, $article->revisions()->count());
        $this->assertNull($article->fresh()->published_revision_id);
    }

    public function test_cached_research_is_reused_without_paid_research_requests(): void
    {
        Http::preventStrayRequests();
        $article = $this->article();
        $saved = ArticleResearch::create(['article_id' => $article->id, 'user_id' => $article->user_id, 'keyword' => 'cached', 'location_code' => 2840, 'status' => 'completed', 'researched_at' => now(), 'brief' => []]);
        $this->assertSame($saved->id, app(ArticleResearcher::class)->research($article->author, $article, true)->id);
    }

    public function test_author_accepts_and_publishes_only_their_stored_suggestion_and_preserves_original_history(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $original = $article->current_revision_id;
        $this->actingAs($article->author)->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $original, 'human_review' => 1])->assertRedirect();
        $article->refresh();
        $this->assertNotSame($original, $article->published_revision_id);
        $this->assertSame('published', $article->publishedRevision->status->value);
        $this->assertSame($article->user_id, $article->publishedRevision->reviewed_by);
        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertSame('A synthetic guide', $article->revisions()->findOrFail($original)->title);
        $this->get('/articles/'.$article->slug)->assertOk()->assertSee('A clearer synthetic guide');
        $this->post('/staff/articles/'.$article->id.'/publish', ['revision_id' => $article->current_revision_id])->assertForbidden();
    }

    public function test_accept_is_idempotent_and_preserves_first_publication_date(): void
    {
        $article = $this->article();
        $article->forceFill(['first_published_at' => now()->subMonth()])->save();
        $date = $article->first_published_at;
        $request = $this->ready($article);
        $first = app(ArticleRecommendations::class)->accept($article->author, $request, $article->current_revision_id);
        $this->travel(10)->minutes();
        $second = app(ArticleRecommendations::class)->accept($article->author, $request->fresh(), $request->source_revision_id);
        $this->assertSame($first->published_revision_id, $second->published_revision_id);
        $this->assertSame(2, $article->revisions()->count());
        $this->assertTrue($second->first_published_at->equalTo($date));
    }

    public function test_other_authors_cannot_read_request_or_accept_a_foreign_article(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $other = User::factory()->create();
        $this->actingAs($other)->get('/staff/articles/'.$article->id.'/suggestions/'.$request->id)->assertForbidden();
        $this->post('/staff/articles/'.$article->id.'/suggestions', ['revision_id' => $article->current_revision_id, 'consent' => 1])->assertForbidden();
        $this->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $article->current_revision_id, 'human_review' => 1])->assertForbidden();
        $this->assertNull($article->fresh()->published_revision_id);
    }

    public function test_accept_requires_explicit_human_review_and_never_trusts_client_draft_fields(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $this->actingAs($article->author)->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $article->current_revision_id])->assertSessionHasErrors('human_review');
        $this->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $article->current_revision_id, 'human_review' => 1, 'title' => 'Forged incoming title', 'body_markdown' => 'Forged body'])->assertRedirect();
        $this->assertSame('A clearer synthetic guide', $article->fresh()->publishedRevision->title);
    }

    public function test_stale_edit_or_reassignment_cannot_publish_a_suggestion(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $data = $this->draft($article) + ['revision_id' => $article->current_revision_id, 'category_slug' => 'travel', 'image' => $article->currentRevision->image, 'source_notes' => 'Test notes'];
        app(EditorialWorkflow::class)->save($article->author, $data, $article);
        $this->actingAs($article->author)->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $request->source_revision_id, 'human_review' => 1])->assertSessionHasErrors('suggestion');
        $this->assertNull($article->fresh()->published_revision_id);
        $this->assertSame(2, $article->revisions()->count());
    }

    public function test_incomplete_evidence_or_a_thin_proposal_cannot_publish_even_when_accepted(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $payload = $request->payload;
        $payload['draft']['body_markdown'] = 'Thin draft';
        $request->update(['payload' => $payload]);
        $this->actingAs($article->author)->get('/staff/articles/'.$article->id.'/suggestions/'.$request->id)->assertOk()->assertSee('Finish these checks before publishing')->assertSee('Add more evidence-backed detail');
        $this->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $article->current_revision_id, 'human_review' => 1])->assertSessionHasErrors('suggestion');
        $this->assertNull($article->fresh()->published_revision_id);
        $this->assertSame(1, $article->revisions()->count());
    }

    public function test_worker_checks_current_permissions_and_does_not_call_providers_after_access_changes(): void
    {
        Http::preventStrayRequests();
        $article = $this->article();
        $request = $this->start($article);
        $article->author->forceFill(['is_active' => false])->save();
        (new PrepareArticleRecommendation($request->id))->handle(app(ArticleResearcher::class));
        $this->assertSame('stale', $request->fresh()->status);
    }

    public function test_provider_failures_do_not_save_partial_results_retry_or_publish(): void
    {
        $article = $this->article();
        $request = $this->start($article);
        $this->mock(ArticleResearcher::class)->shouldReceive('research')->once()->andThrow(new \RuntimeException('private provider key / upstream private body'));
        (new PrepareArticleRecommendation($request->id))->handle(app(ArticleResearcher::class));
        $this->assertSame('failed', $request->fresh()->status);
        $this->assertArrayNotHasKey('draft', $request->fresh()->payload);
        $this->assertStringNotContainsString('private provider key', json_encode($request->fresh()->payload));
        $this->assertSame(1, $article->revisions()->count());
        Queue::assertPushed(PrepareArticleRecommendation::class, 1);
    }

    public function test_discard_keeps_the_original_and_cannot_later_be_accepted(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $this->actingAs($article->author)->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/discard')->assertRedirect();
        $this->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $article->current_revision_id, 'human_review' => 1])->assertSessionHasErrors('suggestion');
        $this->assertSame('A synthetic guide', $article->fresh()->currentRevision->title);
    }

    public function test_save_and_improve_keeps_unsaved_work_even_when_providers_are_unavailable(): void
    {
        config(['services.gemini.key' => null, 'services.openai.key' => null]);
        $article = $this->article();
        $data = $this->draft($article) + ['revision_id' => $article->current_revision_id, 'next_step' => 'recommend', 'category_slug' => 'travel', 'image' => $article->currentRevision->image, 'source_notes' => 'Saved original notes'];
        $this->actingAs($article->author)->put('/staff/articles/'.$article->id, $data)->assertRedirect('/staff/articles/'.$article->id.'/edit')->assertSessionHasErrors('suggestion');
        $this->assertSame('A clearer synthetic guide', $article->fresh()->currentRevision->title);
        $this->assertNull($article->fresh()->published_revision_id);
    }

    public function test_editor_offers_simple_save_and_improve_actions_while_preserving_advanced_tools(): void
    {
        $article = $this->article();
        $this->actingAs($article->author)->get('/staff/articles/'.$article->id.'/edit')->assertOk()->assertSee('Improve article')->assertSee('Save private draft')
            ->assertSee('Drafting provider')->assertSee('Editorial brief')->assertSee('SEO &amp; content checks', false);
    }

    public function test_incomplete_suggestion_can_be_saved_for_editing_without_publishing(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $payload = $request->payload;
        $payload['draft']['body_markdown'] = 'An unfinished private suggestion.';
        $request->update(['payload' => $payload]);
        $this->actingAs($article->author)->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/draft', ['revision_id' => $article->current_revision_id])->assertRedirect();
        $this->assertSame('draft_saved', $request->fresh()->status);
        $this->assertNull($article->fresh()->published_revision_id);
        $this->assertSame('An unfinished private suggestion.', $article->fresh()->currentRevision->body_markdown);
        $this->assertNull($article->fresh()->currentRevision->reviewed_at);
    }

    public function test_accept_preserves_existing_live_version_when_any_publication_check_fails(): void
    {
        $article = $this->article();
        $prior = $article->currentRevision;
        $prior->forceFill(['status' => 'published', 'published_at' => now()])->save();
        $article->forceFill(['published_revision_id' => $prior->id, 'first_published_at' => now()->subMonth()])->save();
        $request = $this->ready($article);
        $payload = $request->payload;
        $payload['draft']['faqs_text'] = '';
        $request->update(['payload' => $payload]);
        $this->actingAs($article->author)->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $prior->id, 'human_review' => 1])->assertSessionHasErrors('suggestion');
        $this->assertSame($prior->id, $article->fresh()->published_revision_id);
        $this->assertSame(1, $article->revisions()->count());
    }

    public function test_owner_and_session_changes_invalidate_generated_suggestions(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $originalOwner = $article->author;
        $article->forceFill(['user_id' => User::factory()->create()->id])->save();
        $this->actingAs($article->fresh()->author)->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $request->source_revision_id, 'human_review' => 1])->assertSessionHasErrors('suggestion');
        $this->assertNull($article->fresh()->published_revision_id);
    }

    public function test_other_article_recommendation_ids_cannot_be_substituted(): void
    {
        $owner = User::factory()->create();
        $first = $this->article($owner, 'first');
        $request = $this->ready($first);
        $second = $this->article($owner, 'second');
        $this->actingAs($owner)->get('/staff/articles/'.$second->id.'/suggestions/'.$request->id)->assertNotFound();
        $this->post('/staff/articles/'.$second->id.'/suggestions/'.$request->id.'/accept', ['revision_id' => $second->current_revision_id, 'human_review' => 1])->assertNotFound();
    }

    public function test_daily_allowance_is_reserved_once_for_each_saved_source(): void
    {
        $owner = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->start($this->article($owner, 'allowance-'.$i));
            $this->travel(61)->seconds();
        }
        $article = $this->article($owner, 'allowance-last');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Your suggestion allowance is used up');
        $this->start($article);
    }

    public function test_new_source_links_and_missing_photos_require_review(): void
    {
        $article = $this->article();
        $draft = $this->draft($article);
        $draft['body_markdown'] .= "\n\n[Unverified new source](https://new.example.test/report)";
        $this->assertContains('A new source link needs your verification before publication.', app(RecommendationReadiness::class)->blockers($article, $draft));
        $original = $article->currentRevision;
        $original->update(['body' => [...$original->body, ['type' => 'image', 'url' => 'https://image.example.test/one.jpg', 'alt' => 'Test photo', 'caption' => 'Original caption']]]);
        $article->unsetRelation('currentRevision');
        $this->assertContains('Keep the original article photos before accepting this suggestion.', app(RecommendationReadiness::class)->blockers($article, $draft));
    }

    public function test_stored_suggestion_html_is_escaped_in_the_review_screen(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $payload = $request->payload;
        $payload['draft']['excerpt'] = '<script>unsafe-content()</script>';
        $request->update(['payload' => $payload]);
        $this->actingAs($article->author)->get('/staff/articles/'.$article->id.'/suggestions/'.$request->id)->assertOk()->assertSee('&lt;script&gt;unsafe-content()', false)->assertDontSee('<script>unsafe-content()', false);
    }

    public function test_progress_response_does_not_expose_private_payload_or_provider_settings(): void
    {
        $article = $this->article();
        $request = $this->ready($article);
        $this->actingAs($article->author)->getJson('/staff/articles/'.$article->id.'/suggestions/'.$request->id)->assertOk()->assertJsonPath('status', 'ready')->assertJsonMissingPath('payload')->assertJsonMissingPath('provider');
    }
}
