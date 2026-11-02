<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ProviderCredential;
use App\Models\User;
use App\Services\ProviderCredentials;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EditorialAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_require_admin_confirmation_are_encrypted_and_never_flashed_or_rendered(): void
    {
        config(['services.openseo.key' => null]);
        $secret = 'fixture-only-secret-123456789';
        $this->actingAs(User::factory()->create())->put('/staff/provider-credentials/openseo', ['secret' => $secret])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => StaffRole::SuperAdmin]));
        $this->put('/staff/provider-credentials/openseo', ['secret' => $secret])->assertRedirect('/staff/confirm-password');
        $this->withSession(['auth.password_confirmed_at' => time()]);
        $this->put('/staff/provider-credentials/openseo', ['secret' => $secret])->assertRedirect('/staff/growth/integrations');
        $this->assertNotSame($secret, DB::table('provider_credentials')->value('secret'));
        $this->assertArrayNotHasKey('secret', ProviderCredential::first()->toArray());
        $this->assertSame($secret, app(ProviderCredentials::class)->key('openseo'));
        $this->get('/staff/growth/integrations')->assertOk()->assertDontSee($secret);
        $this->from('/staff/growth/integrations')->put('/staff/provider-credentials/openseo', ['secret' => $secret."\ninvalid"])->assertSessionHasErrors('secret')->assertSessionMissing('_old_input.secret');
        $this->assertSame($secret, app(ProviderCredentials::class)->key('openseo'));
        $this->delete('/staff/provider-credentials/openseo')->assertRedirect();
        $this->assertNull(app(ProviderCredentials::class)->key('openseo'));
        $this->assertDatabaseHas('audit_events', ['action' => 'credential.updated']);
        $this->assertDatabaseHas('audit_events', ['action' => 'credential.removed']);
    }

    public function test_repository_sync_assigns_new_drafts_idempotently_without_overwriting_editorial_changes(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->artisan('content:sync', ['--owner' => $admin->email])->assertSuccessful();
        $total = count(app(Catalog::class)->data('articles'));
        $this->assertDatabaseCount('articles', $total);
        $article = Article::firstOrFail();
        $article->currentRevision->update(['title' => 'An editor changed this title']);
        $this->artisan('content:sync', ['--owner' => $admin->email])->assertSuccessful();
        $this->assertDatabaseCount('article_revisions', $total);
        $this->assertSame('An editor changed this title', $article->fresh()->currentRevision->title);
        $this->assertSame($admin->id, $article->user_id);
        $this->assertSame(0, Article::whereNotNull('published_revision_id')->count());
        $this->assertNotNull($article->currentRevision->seo_title);
    }

    public function test_onsite_optimization_creates_private_metadata_revision_and_preserves_published_pointer(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $admin);
        $prior = $article->currentRevision;
        ArticleRevision::whereKey($prior->id)->update(['seo_title' => null, 'meta_description' => null, 'status' => RevisionStatus::Published, 'published_at' => now()]);
        $article->published_revision_id = $prior->id;
        $article->first_published_at = now();
        $article->save();
        $this->artisan('content:optimize-onsite')->assertSuccessful();
        $article->refresh();
        $this->assertSame($prior->id, $article->published_revision_id);
        $this->assertSame(RevisionStatus::Draft, $article->currentRevision->status);
        $this->assertSame($prior->body, $article->currentRevision->body);
        $this->assertNotNull($article->currentRevision->seo_title);
        $this->assertNull($article->currentRevision->reviewed_at);
        $this->artisan('content:optimize-onsite')->assertSuccessful();
        $this->assertSame(2, $article->revisions()->count());
    }
}
