<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\ArticleResearch;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GptCurationTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $owner): Article
    {
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $owner);
        $article->currentRevision->update(['focus_keyword' => 'evidence-led safari planning', 'source_notes' => 'Fixture supplied sources only. No actual research.']);

        ArticleResearch::create(['article_id' => $article->id, 'user_id' => $owner->id, 'keyword' => 'evidence-led safari planning', 'location_code' => 2840, 'status' => 'completed', 'researched_at' => now(), 'brief' => ['keyword' => 'evidence-led safari planning', 'metrics' => ['search_volume' => 123]]]);

        return $article->fresh();
    }

    private function requestData(Article $article): array
    {
        return ['revision_id' => $article->current_revision_id, 'brief' => 'Improve the clarity of the supplied fixture without inventing claims or sources.', 'confirm_external' => 1];
    }

    public function test_gpt_creates_only_a_validated_private_revision_and_preserves_published_version(): void
    {
        config(['services.openai.key' => 'fake-openai-test-key']);
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin);
        $old = $article->currentRevision;
        $old->update(['status' => RevisionStatus::Published, 'published_at' => now()]);
        $article->published_revision_id = $old->id;
        $article->first_published_at = now();
        $article->save();
        $draft = ['title' => 'A clearer fixture draft', 'excerpt' => 'Fixture introduction.', 'seo_title' => 'Fixture search title', 'meta_description' => 'Fixture description.', 'body_markdown' => "## Fixture section\n\nA safe fixture paragraph.", 'faqs' => []];
        Http::fake(['api.openai.com/*' => Http::response(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($draft)]]]]])]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->post('/staff/articles/'.$article->id.'/curate', $this->requestData($article))->assertRedirect();
        $article->refresh();
        $this->assertSame('A clearer fixture draft', $article->currentRevision->title);
        $this->assertSame(RevisionStatus::Draft, $article->currentRevision->status);
        $this->assertSame($old->id, $article->published_revision_id);
        $this->assertNull($article->currentRevision->reviewed_at);
        $this->assertDatabaseHas('article_generations', ['status' => 'completed', 'result_revision_id' => $article->current_revision_id]);
        Http::assertSent(function ($request) {
            $context = json_decode($request['input'], true);

            return $request->url() === 'https://api.openai.com/v1/responses' && $request['store'] === false && $context['openseo_metrics']['metrics']['search_volume'] === 123 && $request['text']['format']['strict'] === true;
        });
        $this->get('/staff/articles/'.$article->id.'/edit')->assertDontSee('fake-openai-test-key');
    }

    public function test_missing_credentials_or_author_role_never_calls_provider(): void
    {
        Http::fake();
        $owner = User::factory()->create();
        $article = $this->article($owner);
        $this->actingAs($owner)->post('/staff/articles/'.$article->id.'/curate', $this->requestData($article))->assertForbidden();
        config(['services.openai.key' => null]);
        $this->actingAs(User::factory()->create(['role' => StaffRole::SuperAdmin]))->withSession(['auth.password_confirmed_at' => time()])->post('/staff/articles/'.$article->id.'/curate', $this->requestData($article))->assertSessionHasErrors('generation');
        Http::assertNothingSent();
    }

    public function test_provider_failure_is_sanitized_and_does_not_retry_or_save_content(): void
    {
        config(['services.openai.key' => 'fake-sensitive-key']);
        Http::fake(['*' => Http::response('fake-sensitive-key upstream-secret-content', 500)]);
        $owner = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($owner);
        $this->actingAs($owner)->withSession(['auth.password_confirmed_at' => time()]);
        $this->from('/staff/articles/'.$article->id.'/edit')->post('/staff/articles/'.$article->id.'/curate', $this->requestData($article))->assertSessionHasErrors('generation');
        $this->get('/staff/articles/'.$article->id.'/edit')->assertDontSee('fake-sensitive-key')->assertDontSee('upstream-secret-content');
        $this->post('/staff/articles/'.$article->id.'/curate', $this->requestData($article))->assertSessionHasErrors('generation');
        Http::assertSentCount(1);
        $this->assertSame(1, $article->revisions()->count());
        $this->assertDatabaseHas('article_generations', ['status' => 'failed']);
    }
}
