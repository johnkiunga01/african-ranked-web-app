<?php

namespace Tests\Feature;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_public_content_is_fetchable_but_remains_noindex(): void
    {
        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';
        $origin = 'https://african-ranked-web-app-production.up.railway.app';
        config([
            'app.url' => $origin,
            'seo.preview' => false,
            'review.enabled' => false,
            'seo.indexing_enabled' => false,
        ]);

        try {
            $robots = $this->get($origin.'/robots.txt')->assertOk()
                ->assertSee('User-agent: *')
                ->assertSee('Allow: /')
                ->assertSee('Disallow: /staff')
                ->assertSee('Disallow: /newsletter');
            $this->assertStringNotContainsString("Disallow: /\n", $robots->getContent());

            $this->get($origin.'/')->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex, follow, noarchive')
                ->assertSee('content="noindex, follow, noarchive"', false)
                ->assertSee('rel="canonical"', false);

            $this->get($origin.'/llms.txt')->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex, follow, noarchive')
                ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
                ->assertSee('# African Ranked')
                ->assertSee('## Articles');

            $this->get($origin.'/sitemap.xml')->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex, follow, noarchive');

            $this->get($origin.'/staff/login')->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        } finally {
            $this->app['env'] = $originalEnvironment;
        }
    }

    public function test_search_indexing_is_only_enabled_by_a_separate_release_flag(): void
    {
        config(['seo.preview' => false, 'review.enabled' => false, 'seo.indexing_enabled' => true]);
        $this->get('/')->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertSee('content="index, follow, max-image-preview:large"', false);

        config(['seo.indexing_enabled' => false]);
        $this->get('/')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow, noarchive')
            ->assertSee('content="noindex, follow, noarchive"', false);
    }

    public function test_llms_file_includes_published_articles_and_never_private_drafts(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $records = app(Catalog::class)->data('articles');
        $published = app(SeedArticleImporter::class)->import($records[0], $admin);
        $draft = app(SeedArticleImporter::class)->import($records[1], $admin);
        $revision = $published->currentRevision;
        $revision->status = RevisionStatus::Published;
        $revision->published_at = now();
        $revision->save();
        $published->published_revision_id = $revision->id;
        $published->first_published_at = now();
        $published->save();

        $this->get('/llms.txt')->assertOk()
            ->assertSee('# African Ranked')
            ->assertSee($published->slug)
            ->assertDontSee($draft->slug)
            ->assertDontSee('/staff/articles/')
            ->assertSee('Only published articles appear in this index.');
    }

    public function test_review_preview_still_blocks_all_crawlers_even_with_indexing_flag(): void
    {
        config(['seo.preview' => true, 'review.enabled' => true, 'seo.indexing_enabled' => true]);
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n");
        $this->get('/')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
