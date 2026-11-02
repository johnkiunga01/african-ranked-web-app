<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\ArticleResearch;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Services\SiteSettings;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthorAiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function setupArticle(): array
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $author = User::factory()->create();
        app(SiteSettings::class)->save('general', 0, ['author_ai_enabled' => true], $admin);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $author);
        $article->currentRevision->update(['focus_keyword' => 'synthetic editorial test', 'source_notes' => 'Synthetic private test notes, not verified reporting.']);
        ArticleResearch::create(['article_id' => $article->id, 'user_id' => $admin->id, 'keyword' => 'synthetic editorial test', 'location_code' => 2840, 'status' => 'completed', 'researched_at' => now(), 'brief' => ['keyword' => 'synthetic editorial test', 'metrics' => ['search_volume' => null]]]);

        return [$author, $article->fresh()];
    }

    private function input(Article $article): array
    {
        return ['provider' => 'gemini', 'revision_id' => $article->current_revision_id, 'brief' => 'Clarify this supplied test draft without adding unsupported facts, citations or claims.', 'confirm_external' => 1];
    }

    public function test_opted_in_author_can_use_explicit_gemini_provider_without_openai_or_publication(): void
    {
        config(['services.gemini.key' => 'synthetic-private-gemini-key', 'services.gemini.model' => 'test-model', 'services.openai.key' => null]);
        [$author, $article] = $this->setupArticle();
        $sourceId = $article->current_revision_id;
        $draft = ['title' => 'Synthetic rewritten draft', 'excerpt' => 'Synthetic summary.', 'seo_title' => 'Synthetic title', 'meta_description' => 'Synthetic description.', 'body_markdown' => '## Synthetic section'."\n\nA test-only draft.", 'faqs' => []];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($draft)]]]]]])]);
        $this->actingAs($author)->withSession(['auth.password_confirmed_at' => time()])->post('/staff/articles/'.$article->id.'/curate', $this->input($article))->assertRedirect();
        $article->refresh();
        $this->assertNull($article->published_revision_id);
        $this->assertNull($article->currentRevision->reviewed_at);
        $this->assertSame('draft', $article->currentRevision->status->value);
        $this->assertSame('Synthetic rewritten draft', $article->currentRevision->title);
        $this->assertStringContainsString('Provider: gemini', $article->currentRevision->source_notes);
        $this->assertDatabaseHas('article_generations', ['revision_id' => $sourceId, 'model' => 'test-model', 'status' => 'completed']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://generativelanguage.googleapis.com/') && $request['generationConfig']['maxOutputTokens'] === 8192);
        $this->get('/staff/articles/'.$article->id.'/edit')->assertOk()->assertSee('Google Gemini')->assertDontSee('synthetic-private-gemini-key');
        $this->get('/articles/'.$article->slug)->assertNotFound();
        $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => time()])->post('/staff/articles/'.$article->id.'/curate', $this->input($article))->assertForbidden();
        Http::assertSentCount(1);
    }

    public function test_missing_selected_provider_does_not_fall_back_to_another_provider(): void
    {
        config(['services.gemini.key' => null, 'services.openai.key' => 'synthetic-other-provider-key']);
        [$author, $article] = $this->setupArticle();
        Http::fake();
        $this->actingAs($author)->withSession(['auth.password_confirmed_at' => time()])->post('/staff/articles/'.$article->id.'/curate', $this->input($article))->assertSessionHasErrors('generation');
        Http::assertNothingSent();
        $this->assertSame(1, $article->revisions()->count());
    }

    public function test_invalid_gemini_result_saves_no_partial_draft_and_exposes_no_key(): void
    {
        config(['services.gemini.key' => 'synthetic-private-gemini-key']);
        [$author, $article] = $this->setupArticle();
        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"title":"Incomplete response"}']]]]]])]);
        $this->actingAs($author)->withSession(['auth.password_confirmed_at' => time()])->post('/staff/articles/'.$article->id.'/curate', $this->input($article))->assertSessionHasErrors('generation');
        $this->assertSame(1, $article->revisions()->count());
        $this->assertNull($article->fresh()->published_revision_id);
        $this->assertDatabaseHas('article_generations', ['status' => 'failed']);
        $this->get('/staff/articles/'.$article->id.'/edit')->assertDontSee('synthetic-private-gemini-key');
    }

    public function test_disabled_author_ai_still_blocks_every_provider(): void
    {
        [$author, $article] = $this->setupArticle();
        app(SiteSettings::class)->save('general', 1, ['author_ai_enabled' => false], User::where('role', 'super_admin')->firstOrFail());
        Http::fake();
        $this->actingAs($author)->withSession(['auth.password_confirmed_at' => time()])->post('/staff/articles/'.$article->id.'/curate', $this->input($article))->assertForbidden();
        Http::assertNothingSent();
    }
}
