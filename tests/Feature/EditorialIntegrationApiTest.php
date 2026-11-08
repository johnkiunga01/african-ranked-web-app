<?php

namespace Tests\Feature;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\User;
use App\Services\EditorialWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublicationFixture;
use Tests\TestCase;

class EditorialIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->editor = User::factory()->create(['name' => 'Named Editor', 'role' => StaffRole::SuperAdmin]);
        $this->token = str_repeat('x', 64);
        config([
            'editorial.integration_enabled' => true,
            'editorial.integration_token_sha256' => hash('sha256', $this->token),
            'editorial.integration_actor_email' => $this->editor->email,
        ]);
    }

    private function withEditorialToken(): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token);
    }

    private function createDraft(string $slug = 'untouched-test-draft'): Article
    {
        return app(EditorialWorkflow::class)->save($this->editor, [
            'slug' => $slug, 'title' => 'A supported travel guide', 'excerpt' => 'A synthetic test introduction.',
            'category_slug' => 'travel', 'body_markdown' => PublicationFixture::body(),
            'faqs_text' => collect(PublicationFixture::faqs())->map(fn ($faq) => $faq['question']."\n".$faq['answer'])->implode("\n\n"),
            'image' => 'https://images.example.test/'.$slug.'.jpg',
            'image_alt' => 'An example photograph for the test',
            'source_notes' => 'Synthetic test research records only.',
        ]);
    }

    private function evidence(): array
    {
        $date = now()->format('Y-m-d');
        $sources = [];
        $facts = [];
        for ($i = 1; $i <= 5; $i++) {
            $url = 'https://sources.example.test/'.$i;
            $sources[] = ['title' => 'Synthetic source '.$i, 'publisher' => 'Test publisher',
                'url' => $url, 'accessed_at' => $date];
            if ($i <= 4) {
                $facts[] = ['fact' => 'Fixture fact '.$i, 'source_url' => $url, 'verified_at' => $date];
            }
        }

        return [
            'answer' => str_repeat('Evidence ', 45), 'content_type' => 'guide',
            'last_verified_at' => $date, 'image_rights' => 'Synthetic test licence description only.',
            'facts' => $facts, 'sources' => $sources,
        ];
    }

    private function draftPayload(Article $article): array
    {
        $revision = $article->currentRevision;

        return [
            'expected_revision_id' => $article->current_revision_id,
            'title' => 'A substantial verified guide',
            'excerpt' => $revision->excerpt, 'category_slug' => 'travel',
            'body_markdown' => $revision->body_markdown,
            'faqs_text' => collect(PublicationFixture::faqs())->map(fn ($faq) => $faq['question']."\n".$faq['answer'])->implode("\n\n"),
            'evidence' => $this->evidence(),
        ];
    }

    public function test_api_is_disabled_by_default_and_requires_a_correct_bearer_token(): void
    {
        config(['editorial.integration_enabled' => false]);
        $this->getJson('/api/editorial/v1/drafts')->assertStatus(503);
        config(['editorial.integration_enabled' => true]);
        $this->getJson('/api/editorial/v1/drafts')->assertUnauthorized();
        $this->withHeader('Authorization', 'Bearer '.str_repeat('a', 64))
            ->getJson('/api/editorial/v1/drafts')->assertUnauthorized();
    }

    public function test_non_verified_or_non_admin_actor_cannot_use_the_api(): void
    {
        $this->editor->email_verified_at = null;
        $this->editor->save();
        $this->withEditorialToken()->getJson('/api/editorial/v1/drafts')->assertStatus(503);
    }

    public function test_inventory_excludes_previous_review_and_publication(): void
    {
        $fresh = $this->createDraft();
        $reviewed = $this->createDraft('previous-review');
        $reviewed->currentRevision->update(['status' => RevisionStatus::InReview]);
        $published = $this->createDraft('published-draft');
        $published->published_revision_id = $published->current_revision_id;
        $published->save();

        $this->withEditorialToken()->getJson('/api/editorial/v1/drafts?limit=2')
            ->assertOk()->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.article_id', $fresh->id)
            ->assertJsonPath('items.0.revision_id', $fresh->current_revision_id);
    }

    public function test_save_is_revision_safe_and_keeps_old_content_and_history(): void
    {
        $article = $this->createDraft();
        $old = $article->current_revision_id;
        $this->withEditorialToken()->getJson('/api/editorial/v1/articles/'.$article->id)->assertOk()
            ->assertJsonPath('current_revision_id', $old);
        $payload = $this->draftPayload($article);
        $this->withEditorialToken()->putJson('/api/editorial/v1/articles/'.$article->id.'/draft', $payload)
            ->assertCreated()->assertJsonPath('status', 'draft');
        $article->refresh();
        $this->assertNotSame($old, $article->current_revision_id);
        $this->assertSame(2, $article->revisions()->count());
        $this->withEditorialToken()->putJson('/api/editorial/v1/articles/'.$article->id.'/draft', $payload)
            ->assertUnprocessable();
        $this->assertSame('A substantial verified guide', $article->fresh()->currentRevision->title);
    }

    public function test_submit_enforces_quality_then_reaches_real_in_review_without_publishing(): void
    {
        $article = $this->createDraft();
        $url = '/api/editorial/v1/articles/'.$article->id;
        $this->withEditorialToken()->postJson($url.'/submit',
            ['expected_revision_id' => $article->current_revision_id])->assertUnprocessable();
        $this->withEditorialToken()->putJson($url.'/draft', $this->draftPayload($article))->assertCreated();
        $article->refresh();
        $this->withEditorialToken()->postJson($url.'/submit',
            ['expected_revision_id' => $article->current_revision_id])->assertOk()
            ->assertJsonPath('status', 'in_review');
        $article->refresh();
        $this->assertSame(RevisionStatus::InReview, $article->currentRevision->status);
        $this->assertNull($article->published_revision_id);
        $this->withEditorialToken()->getJson($url)->assertOk()->assertJsonPath('status', 'in_review');
        $this->withEditorialToken()->getJson('/api/editorial/v1/drafts')->assertJsonCount(0, 'items');
        $this->withEditorialToken()->putJson($url.'/draft', $this->draftPayload($article))->assertUnprocessable();
        $this->withEditorialToken()->postJson($url.'/publish',
            ['expected_revision_id' => $article->current_revision_id])->assertNotFound();
    }

    public function test_submit_blocks_incorrect_verification_date(): void
    {
        $article = $this->createDraft();
        $payload = $this->draftPayload($article);
        $payload['evidence']['last_verified_at'] = now()->subDay()->format('Y-m-d');
        $this->withEditorialToken()->putJson('/api/editorial/v1/articles/'.$article->id.'/draft', $payload)
            ->assertCreated();
        $article->refresh();
        $this->withEditorialToken()->postJson('/api/editorial/v1/articles/'.$article->id.'/submit',
            ['expected_revision_id' => $article->current_revision_id])->assertUnprocessable();
        $this->assertSame(RevisionStatus::Draft, $article->fresh()->currentRevision->status);
    }
}
