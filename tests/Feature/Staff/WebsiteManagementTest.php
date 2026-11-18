<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\Region;
use App\Models\SitePage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Services\SiteSettings;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublicationFixture;
use Tests\TestCase;

class WebsiteManagementTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $owner, string $slug): Article
    {
        $record = app(Catalog::class)->data('articles')[0];
        $record['slug'] = $slug;

        $article = app(SeedArticleImporter::class)->import($record, $owner);
        PublicationFixture::prepare($article);

        return $article;
    }

    public function test_website_controls_are_admin_only_and_featured_draft_never_leaks(): void
    {
        $author = User::factory()->create();
        $this->actingAs($author);
        foreach (['/staff/website/homepage', '/staff/website/regions', '/staff/pages', '/staff/website/settings'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin, 'featured-private-fixture');
        $this->actingAs($admin)->post('/staff/website/featured/'.$article->id, ['version' => 0, 'revision_id' => $article->current_revision_id])->assertRedirect();
        $this->assertSame($article->id, app(SiteSettings::class)->snapshot('homepage')['value']['featured']['global']);
        $this->get('/')->assertOk()->assertDontSee('/articles/featured-private-fixture');
        $this->post('/staff/website/featured/'.$article->id, ['version' => 0, 'revision_id' => $article->current_revision_id])->assertSessionHasErrors('version');
    }

    public function test_visible_publish_flow_publishes_exact_revision_and_automatic_homepage_selection_follows_it(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin, 'homepage-published-fixture');
        $this->actingAs($admin);
        $this->get('/staff/articles/'.$article->id.'/publish')->assertOk()->assertSee('Publish article');
        $this->post('/staff/website/featured/'.$article->id, ['version' => 0, 'revision_id' => $article->current_revision_id]);
        $this->post('/staff/articles/'.$article->id.'/publish-reviewed', ['revision_id' => $article->current_revision_id, 'editorial_approval' => 1])->assertRedirect('/staff/articles/'.$article->id.'/edit');
        $this->assertSame($article->current_revision_id, $article->fresh()->published_revision_id);
        $this->assertSame($article->slug, app(Catalog::class)->homepageArticles()->first()['slug']);
        $this->get('/articles/'.$article->slug)->assertOk();
    }

    public function test_quick_edit_keeps_body_and_live_revision_and_ignores_forged_publication_fields(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->article($admin, 'quick-edit-fixture');
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/publish-reviewed', ['revision_id' => $article->current_revision_id, 'editorial_approval' => 1]);
        $article->refresh();
        $old = $article->currentRevision;
        $this->put('/staff/article-quick-edit/'.$article->id, ['revision_id' => $old->id, 'title' => 'A new private working title', 'excerpt' => $old->excerpt, 'category_slug' => $old->category_slug, 'status' => 'published', 'user_id' => 99999])->assertRedirect();
        $article->refresh();
        $this->assertSame($old->body, $article->currentRevision->body);
        $this->assertSame($old->id, $article->published_revision_id);
        $this->assertSame('draft', $article->currentRevision->status->value);
        $this->assertSame($admin->id, $article->user_id);
        $this->get('/articles/'.$article->slug)->assertDontSee('A new private working title');
    }

    public function test_trash_does_not_resurrect_legacy_seed_and_restore_stays_private(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $author = User::factory()->create();
        $record = app(Catalog::class)->data('articles')[0];
        $article = app(SeedArticleImporter::class)->import($record, $author);
        PublicationFixture::prepare($article);
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/publish-reviewed', ['revision_id' => $article->current_revision_id, 'editorial_approval' => 1]);
        $this->actingAs($author)->post('/staff/article-lifecycle/'.$article->id.'/trash', ['revision_id' => $article->current_revision_id])->assertForbidden();
        $this->actingAs($admin)->post('/staff/article-lifecycle/'.$article->id.'/trash', ['revision_id' => $article->current_revision_id])->assertRedirect();
        $this->get('/articles/'.$article->slug)->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/articles/'.$article->slug);
        $this->get('/staff/articles?tab=trash')->assertOk()->assertSee('Restore draft');
        $this->post('/staff/article-lifecycle/'.$article->id.'/restore', ['revision_id' => $article->current_revision_id])->assertRedirect();
        $article->refresh();
        $this->assertNull($article->published_revision_id);
        $this->assertNull($article->trashed_at);
        $this->assertSame('draft', $article->currentRevision->status->value);
    }

    public function test_pages_have_real_draft_publication_navigation_and_withdrawal(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->actingAs($admin)->post('/staff/pages', ['slug' => 'about-fixture', 'title' => 'About fixture', 'description' => 'A fixture page.', 'body_markdown' => "## Our fixture\n\nA <script>alert(1)</script> example.", 'in_footer' => 1])->assertRedirect();
        $page = SitePage::firstOrFail();
        $revision = $page->current_revision_id;
        $this->get('/pages/about-fixture')->assertNotFound();
        $this->post('/staff/pages/'.$page->id.'/publish', ['revision_id' => $revision, 'confirm' => 1])->assertRedirect();
        $this->get('/pages/about-fixture')->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/')->assertSee('/pages/about-fixture');
        $this->get('/sitemap.xml')->assertSee('/pages/about-fixture');
        $this->put('/staff/pages/'.$page->id, ['revision_id' => $revision, 'title' => 'New private page title', 'description' => 'Private introduction', 'body_markdown' => 'Private replacement', 'in_footer' => 0])->assertRedirect();
        $this->get('/pages/about-fixture')->assertDontSee('New private page title');
        $this->post('/staff/pages/'.$page->id.'/unpublish', ['revision_id' => $page->fresh()->current_revision_id, 'confirm' => 1])->assertRedirect();
        $this->get('/pages/about-fixture')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/pages/about-fixture');
    }

    public function test_regions_are_editable_dynamic_and_removable_without_code_changes(): void
    {
        // Sitemap listing of country editions is opt-in; this test covers the opted-in behaviour.
        config(['seo.regional_editions_indexable' => true]);
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $data = ['code' => 'gh', 'name' => 'Ghana edition', 'country_code' => 'GH', 'locale' => 'en-GH', 'currency' => 'GHS', 'currency_symbol' => 'GH₵', 'flag' => '🇬🇭', 'tagline' => 'Africa-focused guides for Ghana readers', 'is_active' => 1, 'sort_order' => 20, 'newsletter_title' => 'Ghana reader updates', 'newsletter_subtitle' => 'New guides from Africa.'];
        $this->actingAs($admin)->post('/staff/website/regions', $data)->assertRedirect('/staff/website/regions');
        $region = Region::where('code', 'gh')->firstOrFail();
        $this->get('/gh')->assertOk()->assertSee('Ghana edition');
        $this->get('/sitemap.xml')->assertSee('/gh');
        $this->put('/staff/website/regions/'.$region->id, $data + ['version' => $region->version])->assertRedirect();
        $this->put('/staff/website/regions/'.$region->id, array_replace($data, ['version' => $region->fresh()->version, 'is_active' => 0]))->assertRedirect();
        $this->get('/gh')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/gh');
        $global = Region::where('code', 'global')->firstOrFail();
        $this->put('/staff/website/regions/'.$global->id, array_replace($data, ['locale' => 'en', 'is_active' => 0, 'version' => $global->version]))->assertSessionHasErrors('is_active');
    }

    public function test_official_contact_email_is_available_as_public_default_and_blank_setting_fallback(): void
    {
        $this->get('/')->assertOk()->assertSee('mailto:info@africanranked.com', false);
        SiteSetting::create([
            'key' => 'general', 'value' => ['contact_email' => ''], 'version' => 0,
        ]);
        $this->get('/')->assertOk()->assertSee('mailto:info@africanranked.com', false);
    }
}
