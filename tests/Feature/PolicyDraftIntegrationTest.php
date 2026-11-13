<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\SitePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyDraftIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function authorizeIntegration(): void
    {
        $actor = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        config(['editorial.integration_enabled' => true, 'editorial.integration_actor_email' => $actor->email,
            'editorial.integration_token_sha256' => hash('sha256', str_repeat('x', 64))]);
        $this->withHeader('Authorization', 'Bearer '.str_repeat('x', 64));
    }

    private function payload(?int $revision = null): array
    {
        return ['expected_revision_id' => $revision, 'title' => 'Privacy Policy', 'description' => 'A test-only policy draft.',
            'body_markdown' => "## Test disclosure\n\nSynthetic text for an isolated test, not legal advice.", 'in_footer' => true,
            'published_revision_id' => 999, 'confirm' => 1];
    }

    public function test_policy_intake_is_disabled_by_default_and_requires_authenticated_super_admin(): void
    {
        $this->authorizeIntegration();
        $this->getJson('/api/policies/v1/pages')->assertNotFound();
        config(['editorial.policy_draft_integration_enabled' => true]);
        $this->withHeader('Authorization', 'Bearer invalid')->getJson('/api/policies/v1/pages')->assertUnauthorized();
        $this->assertDatabaseCount('site_pages', 0);
    }

    public function test_three_allowlisted_policy_drafts_are_real_cms_records_without_publication(): void
    {
        $this->authorizeIntegration();
        config(['editorial.policy_draft_integration_enabled' => true]);
        foreach (['privacy-policy', 'terms-of-use', 'cookies-and-analytics'] as $slug) {
            $r = $this->postJson('/api/policies/v1/pages/'.$slug.'/draft', $this->payload())->assertCreated()
                ->assertJsonPath('status', 'draft')->assertJsonPath('published_revision_id', null);
            $this->getJson('/api/policies/v1/pages/'.$r->json('id'))->assertOk()->assertJsonPath('content_sha256', hash('sha256', $this->payload()['body_markdown']));
            $this->get('/api/policies/v1/pages/'.$r->json('id').'/preview')->assertOk()->assertSee('Test disclosure')
                ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $this->get('/pages/'.$slug)->assertNotFound();
        }
        $this->assertDatabaseCount('site_pages', 3);
        $this->assertDatabaseCount('site_page_revisions', 3);
        $this->assertDatabaseCount('articles', 0);
        $this->postJson('/api/policies/v1/pages/unrelated/draft', $this->payload())->assertNotFound();
    }

    public function test_revision_conflicts_and_publication_routes_are_rejected(): void
    {
        $this->authorizeIntegration();
        config(['editorial.policy_draft_integration_enabled' => true]);
        $first = $this->postJson('/api/policies/v1/pages/privacy-policy/draft', $this->payload())->assertCreated();
        $this->postJson('/api/policies/v1/pages/privacy-policy/draft', $this->payload())->assertUnprocessable();
        $this->postJson('/api/policies/v1/pages/privacy-policy/draft', $this->payload($first->json('revision_id')))->assertCreated();
        $this->assertDatabaseCount('site_page_revisions', 2);
        $this->postJson('/api/policies/v1/pages/'.$first->json('id').'/publish', ['confirm' => 1])->assertNotFound();
        $this->assertNull(SitePage::first()->published_revision_id);
    }
}
