<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialWorkspaceDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_prioritises_real_editorial_work_instead_of_sample_metrics(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $admin);

        $this->actingAs($admin)->get('/staff')
            ->assertOk()
            ->assertSee('Your publication, at a glance.')
            ->assertSee('Recently updated')
            ->assertSee($article->currentRevision->title)
            ->assertSee('Review submissions')
            ->assertSee('aria-label="Staff navigation"', false)
            ->assertSee('More tools')
            ->assertDontSee('Preview samples')
            ->assertDontSee('Stage: review and development');
    }

    public function test_author_dashboard_only_lists_assigned_articles(): void
    {
        $owner = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $author = User::factory()->create();
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $owner);

        $this->actingAs($author)->get('/staff')
            ->assertOk()
            ->assertSee('New article')
            ->assertDontSee($article->currentRevision->title)
            ->assertDontSee('Edit homepage');

        $this->actingAs($author)->get('/staff/articles/create')->assertOk();
    }

    public function test_article_page_has_responsive_contents_and_real_discovery_destinations(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $seed = app(Catalog::class)->data('articles')[0];
        $article = app(SeedArticleImporter::class)->import($seed, $admin);
        $revision = $article->currentRevision;
        $revision->status = RevisionStatus::Published;
        $revision->published_at = now();
        $revision->save();
        $article->published_revision_id = $revision->id;
        $article->first_published_at = now();
        $article->save();

        $this->get('/articles/'.$article->slug)
            ->assertOk()
            ->assertSee('aria-label="Explore more African Ranked content"', false)
            ->assertSee('data-mobile-toc', false)
            ->assertSee('Article section navigation')
            ->assertSee('Explore topics')
            ->assertSee('href="/rankings"', false)
            ->assertSee('href="/categories/'.$seed['category'].'"', false)
            ->assertSee('Frequently asked questions');
    }

    public function test_library_preserves_advanced_actions_behind_one_menu(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $admin);
        $this->actingAs($admin)->get('/staff/articles')
            ->assertOk()
            ->assertSee('More actions')
            ->assertSee('Quick edit title and intro')
            ->assertSee('Review &amp; publish', false);
    }
}
