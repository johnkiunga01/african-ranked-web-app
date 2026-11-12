<?php

namespace Tests\Feature;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\User;
use App\Services\EditorialWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
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
        // Disposable keys make OAuth tests independent of production storage or CI secrets.
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $private);
        config(['passport.private_key' => $private, 'passport.public_key' => openssl_pkey_get_details($key)['key']]);
        $this->editor = User::factory()->create(['name' => 'Named Editor', 'role' => StaffRole::SuperAdmin]);
        $this->token = str_repeat('x', 64);
        config([
            'editorial.integration_enabled' => true,
            'editorial.integration_token_sha256' => hash('sha256', $this->token),
            'editorial.integration_actor_email' => $this->editor->email,
        ]);
    }

    public function test_mcp_discovery_requires_authentication_and_exposes_only_editorial_tools(): void
    {
        $message = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];
        config(['editorial.mcp_enabled' => true]);
        $this->postJson('/api/editorial/mcp', $message)->assertUnauthorized();
        $this->editor->forceFill(['two_factor_confirmed_at' => now()])->save();
        Passport::actingAs($this->editor, ['editorial'], 'editorial_oauth');
        $this->withEditorialToken()->postJson('/api/editorial/mcp', $message)
            ->assertOk()->assertJsonPath('result.tools.0.name', 'list_drafts')
            ->assertJsonPath('result.tools.1.name', 'get_article')
            ->assertJsonPath('result.tools.2.name', 'save_draft')
            ->assertJsonPath('result.tools.3.name', 'submit_for_review')
            ->assertJsonCount(4, 'result.tools');
    }

    public function test_mcp_refuses_an_author_even_with_an_editorial_token(): void
    {
        config(['editorial.mcp_enabled' => true]);
        $author = User::factory()->create(['role' => StaffRole::Author, 'two_factor_confirmed_at' => now()]);
        Passport::actingAs($author, ['editorial'], 'editorial_oauth');
        $this->withEditorialToken()->postJson('/api/editorial/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();
    }

    public function test_oauth_discovery_is_disabled_until_explicitly_enabled(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource')->assertNotFound();
        config(['editorial.mcp_enabled' => true]);
        $this->getJson('/.well-known/oauth-protected-resource')->assertOk()->assertJsonPath('scopes_supported.0', 'editorial');
        $this->getJson('/.well-known/oauth-authorization-server')->assertOk()->assertJsonPath('code_challenge_methods_supported.0', 'S256');
    }

    public function test_mcp_reads_fresh_drafts_and_rejects_publication_tools(): void
    {
        config(['editorial.mcp_enabled' => true]);
        $this->editor->forceFill(['two_factor_confirmed_at' => now()])->save();
        Passport::actingAs($this->editor, ['editorial'], 'editorial_oauth');
        $draft = $this->createDraft();
        $result = $this->withEditorialToken()->postJson('/api/editorial/mcp', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'get_article', 'arguments' => ['article_id' => $draft->id]]]);
        $result->assertOk()->assertJsonPath('result.isError', false);
        $this->assertSame($draft->id, json_decode($result->json('result.content.0.text'), true)['article_id']);
        $this->postJson('/api/editorial/mcp', ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'publish', 'arguments' => ['article_id' => $draft->id]]])->assertOk()->assertJsonPath('error.code', -32602);
        $this->assertNull($draft->fresh()->published_revision_id);
    }

    public function test_real_pkce_consent_token_exchange_and_authenticated_mcp_read(): void
    {
        config(['editorial.mcp_enabled' => true]);
        $this->editor->forceFill(['two_factor_confirmed_at' => now()])->save();
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Synthetic integration test', ['https://client.example.test/callback'], false);
        $verifier = str_repeat('a', 64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $parameters = ['client_id' => $client->id, 'redirect_uri' => 'https://client.example.test/callback', 'response_type' => 'code', 'scope' => 'editorial', 'state' => 'synthetic-state', 'code_challenge' => $challenge, 'code_challenge_method' => 'S256', 'resource' => url('/api/editorial/mcp')];
        $this->actingAs($this->editor)->withSession(['staff_mfa_verified' => $this->editor->session_version])->get('/oauth/authorize?'.http_build_query($parameters))->assertOk()->assertSee('Authorize editorial access');
        $authToken = session('authToken');
        $consent = $this->post('/oauth/authorize', ['auth_token' => $authToken, 'client_id' => $client->id, 'state' => 'synthetic-state'])->assertRedirect();
        parse_str(parse_url($consent->headers->get('Location'), PHP_URL_QUERY), $callback);
        $this->assertSame(url('/'), $callback['iss']);
        $token = $this->postJson('/api/editorial/oauth/token', ['grant_type' => 'authorization_code', 'client_id' => $client->id, 'redirect_uri' => 'https://client.example.test/callback', 'code_verifier' => $verifier, 'code' => $callback['code'], 'resource' => url('/api/editorial/mcp')])->assertOk()->json('access_token');
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/editorial/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertOk()->assertJsonCount(4, 'result.tools');
    }

    private function withEditorialToken(): static
    {
        return $this->withHeader('Authorization', 'Bearer '.(config('editorial.mcp_enabled') ? 'test.'.rtrim(strtr(base64_encode(json_encode(['iss' => url('/'), 'aud' => [url('/api/editorial/mcp')]])), '+/', '-_'), '=').'.test' : $this->token));
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
