<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_lists_repository_articles_with_search_and_filters(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->actingAs($admin);
        $this->artisan('content:sync', ['--owner' => $admin->email])->assertSuccessful();
        $seed = app(Catalog::class)->data('articles')[0];
        $this->get('/staff/articles?per_page=25')->assertOk()->assertSee($seed['title'])->assertDontSee('Import to CMS');
        $this->get('/staff/articles?q=nonexistent-title')->assertOk()->assertDontSee($seed['title']);
        $this->get('/staff/articles?source=cms&per_page=25')->assertOk()->assertSee($seed['title']);
        $this->get('/staff/articles?status=invalid')->assertSessionHasErrors('status');
    }

    public function test_import_requires_admin_and_acknowledgment_and_never_overwrites_cms_content(): void
    {
        $seed = app(Catalog::class)->data('articles')[0];
        $url = '/staff/article-imports/'.$seed['slug'];
        $this->actingAs(User::factory()->create())->post($url, ['understand_private' => 1])->assertForbidden();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->actingAs($admin)->post($url)->assertSessionHasErrors('understand_private');
        $this->post($url, ['understand_private' => 1])->assertRedirect();
        $article = Article::firstOrFail();
        $this->assertSame($admin->id, $article->user_id);
        $this->assertNull($article->published_revision_id);
        $this->assertSame($seed['body'], $article->currentRevision->body);
        $this->post($url, ['understand_private' => 1])->assertRedirect();
        $this->assertDatabaseCount('articles', 1);
        $this->assertDatabaseCount('article_revisions', 1);
        $this->assertDatabaseHas('audit_events', ['action' => 'article.imported']);
        $this->get('/articles/'.$seed['slug'])->assertNotFound();
        $this->get('/staff/articles?source=cms&sort=title')->assertOk()->assertSee($seed['title']);
    }

    public function test_assignment_creates_a_new_private_revision_preserves_published_content_and_rejects_stale_updates(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $author = User::factory()->create();
        $other = User::factory()->create();
        $seed = app(Catalog::class)->data('articles')[0];
        $article = app(SeedArticleImporter::class)->import($seed, $admin);
        $prior = $article->currentRevision;
        $prior->status = RevisionStatus::Published;
        $prior->published_at = now();
        $prior->save();
        $article->published_revision_id = $prior->id;
        $article->first_published_at = now();
        $article->save();
        $url = '/staff/article-assignments/'.$article->id;
        $this->actingAs($author)->patch($url, ['user_id' => $author->id, 'revision_id' => $prior->id])->assertForbidden();
        $this->actingAs($admin)->patch($url, ['user_id' => $author->id, 'revision_id' => $prior->id])->assertRedirect();
        $article->refresh();
        $this->assertSame($author->id, $article->user_id);
        $this->assertSame($prior->id, $article->published_revision_id);
        $this->assertSame(RevisionStatus::Draft, $article->currentRevision->status);
        $this->assertSame($seed['body'], $article->currentRevision->body);
        $this->assertSame($admin->name, $prior->fresh()->author_name);
        $this->patch($url, ['user_id' => $other->id, 'revision_id' => $prior->id])->assertSessionHasErrors('assignment');
        $this->actingAs($author)->get('/staff/articles')->assertSee($seed['title']);
        $this->actingAs($other)->get('/staff/articles')->assertSee($seed['title']);
    }
}
