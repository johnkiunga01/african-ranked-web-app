<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\ArticleRecommendation;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\EditorialWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublicationFixture;
use Tests\TestCase;

class ProfileAndCollaborationTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $owner): Article
    {
        return app(EditorialWorkflow::class)->save($owner, [
            'slug' => 'shared-draft', 'title' => 'Shared editorial draft', 'excerpt' => 'Test-only introduction.',
            'category_slug' => 'travel', 'body_markdown' => PublicationFixture::body(),
            'faqs_text' => "Question one?\nAnswer one.\n\nQuestion two?\nAnswer two.\n\nQuestion three?\nAnswer three.",
            'image' => 'https://images.example.test/photo.jpg', 'source_notes' => 'Synthetic source notes.',
        ]);
    }

    public function test_staff_can_edit_only_their_own_profile_without_changing_access_or_published_bylines(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $article = $this->article($author);
        $byline = $article->currentRevision->author_name;
        $this->actingAs($author)->get('/staff/profile')->assertOk()->assertSee('Save profile');
        $this->put('/staff/profile', [
            'name' => 'African Ranked', 'job_title' => 'Editor', 'bio' => '<script>alert(1)</script>',
            'updated_at' => $author->updated_at->toISOString(),
            'id' => $other->id, 'email' => 'forged@example.test', 'role' => 'super_admin', 'is_active' => false,
        ])->assertRedirect('/staff/profile');
        $this->assertSame('African Ranked', $author->fresh()->name);
        $this->assertSame(StaffRole::Author, $author->fresh()->role);
        $this->assertTrue($author->fresh()->is_active);
        $this->assertNotSame('forged@example.test', $author->fresh()->email);
        $this->assertNotSame('African Ranked', $other->fresh()->name);
        $this->assertSame($byline, $article->currentRevision->fresh()->author_name);
        $this->get('/staff/profile')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->assertTrue(AuditEvent::where('action', 'staff.profile_updated')->exists());
    }

    public function test_stale_profile_updates_and_unauthenticated_requests_are_rejected(): void
    {
        $this->get('/staff/profile')->assertRedirect('/staff/login');
        $author = User::factory()->create();
        $this->actingAs($author)->put('/staff/profile', ['name' => 'Changed', 'job_title' => 'Editor', 'updated_at' => 'stale'])
            ->assertSessionHasErrors('name');
        $this->assertNotSame('Changed', $author->fresh()->name);
    }

    public function test_removal_revokes_access_preserves_editorial_records_and_requires_admin_confirmation(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $owner = User::factory()->create();
        $article = $this->article($owner);
        $this->actingAs($owner)->delete('/staff/users/'.$admin->id, ['version' => 1, 'confirm_removal' => 1])->assertForbidden();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        $this->delete('/staff/users/'.$owner->id, ['version' => 1])->assertSessionHasErrors('confirm_removal');
        $this->delete('/staff/users/'.$owner->id, ['version' => 1, 'confirm_removal' => 1])->assertRedirect();
        $this->assertFalse($owner->fresh()->is_active);
        $this->assertNotNull($owner->fresh()->removed_at);
        $this->assertSame(2, $owner->fresh()->session_version);
        $this->assertSame($owner->id, $article->fresh()->user_id);
        $this->assertSame($owner->id, $article->fresh()->author->id);
        $this->patch('/staff/users/'.$owner->id, ['role' => 'author', 'is_active' => 1, 'version' => 2])->assertSessionHasErrors('users');
        $this->post('/staff/users/'.$owner->id.'/restore', ['version' => 2])->assertRedirect();
        $this->assertNull($owner->fresh()->removed_at);
        $this->assertFalse($owner->fresh()->is_active);
        $this->delete('/staff/users/'.$admin->id, ['version' => 1, 'confirm_removal' => 1])->assertSessionHasErrors('users');
        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_authors_share_drafts_and_can_approve_another_authors_revision_but_cannot_publish(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $article = $this->article($owner);
        $this->actingAs($reviewer)->get('/staff/articles')->assertOk()->assertSee('Shared editorial draft');
        $this->get('/staff/articles/'.$article->id.'/edit')->assertOk();
        $article = app(EditorialWorkflow::class)->transition($owner, $article, 'submit', $article->current_revision_id);
        $this->actingAs($owner)->post('/staff/articles/'.$article->id.'/approve', [
            'revision_id' => $article->current_revision_id, 'facts' => 1, 'ranking' => 1, 'attribution' => 1, 'image_rights' => 1,
        ])->assertForbidden();
        $this->actingAs($reviewer)->post('/staff/articles/'.$article->id.'/approve', [
            'revision_id' => $article->current_revision_id, 'facts' => 1, 'ranking' => 1, 'attribution' => 1, 'image_rights' => 1,
        ])->assertRedirect();
        $this->assertSame(RevisionStatus::Approved, $article->fresh()->currentRevision->status);
        $this->assertSame($reviewer->id, $article->fresh()->currentRevision->reviewed_by);
        $this->assertNull($article->fresh()->published_revision_id);
        $this->post('/staff/articles/'.$article->id.'/publish', ['revision_id' => $article->current_revision_id])->assertForbidden();
    }

    public function test_bulk_action_gets_and_validation_failures_return_to_the_article_library(): void
    {
        $author = User::factory()->create();
        $this->actingAs($author)->get('/staff/article-batches')->assertRedirect('/staff/articles');
        $this->get('/staff/article-batches/execute')->assertRedirect('/staff/articles');
        $this->from('/staff/article-batches')->post('/staff/article-batches', ['operation' => 'submit'])
            ->assertRedirect('/staff/articles')->assertSessionHasErrors('selected');
        $this->from('/staff/article-batches')->post('/staff/article-batches/execute', ['operation' => 'gemini'])
            ->assertRedirect('/staff/articles')->assertSessionHasErrors('ai_consent');
        $this->postJson('/staff/article-batches', ['operation' => 'submit'])->assertUnprocessable();
    }

    public function test_author_cannot_publish_through_recommendation_acceptance(): void
    {
        $owner = User::factory()->create();
        $article = $this->article($owner);
        $request = ArticleRecommendation::create([
            'article_id' => $article->id, 'user_id' => $owner->id,
            'source_revision_id' => $article->current_revision_id, 'session_version' => $owner->session_version,
            'status' => 'ready', 'payload' => [], 'purpose' => 'editorial_recommendation', 'provider' => 'gemini',
        ]);
        $this->actingAs($owner)->post('/staff/articles/'.$article->id.'/suggestions/'.$request->id.'/accept', [
            'revision_id' => $article->current_revision_id, 'human_review' => 1,
        ])->assertForbidden();
        $this->assertNull($article->fresh()->published_revision_id);
    }

    public function test_collaborative_edits_create_a_private_revision_and_the_editor_cannot_self_approve(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $article = $this->article($owner);
        $prior = $article->current_revision_id;
        $article = app(EditorialWorkflow::class)->save($editor, [
            'revision_id' => $prior, 'title' => 'Edited together', 'excerpt' => 'Edited introduction.',
            'category_slug' => 'travel', 'body_markdown' => PublicationFixture::body(),
            'image' => 'https://images.example.test/photo.jpg', 'source_notes' => 'Synthetic reporting.',
        ], $article);
        $this->assertNotSame($prior, $article->current_revision_id);
        $this->assertSame($owner->id, $article->user_id);
        $this->assertSame($editor->id, $article->currentRevision->created_by);
        $this->assertNull($article->published_revision_id);
        $this->assertFalse($editor->can('review', $article));
        $this->assertFalse($editor->can('trash', $article));
    }
}
