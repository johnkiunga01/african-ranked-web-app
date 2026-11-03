<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\User;
use App\Services\LaunchReadiness;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_metric_matches_the_articles_draft_filter(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $admin);

        $dashboard = $this->actingAs($admin)->get('/staff')->assertOk()
            ->assertSee('Drafts to complete')
            ->assertSee('href="'.route('staff.articles.index', ['tab' => 'drafts']).'"', false);

        $this->assertMatchesRegularExpression('/Drafts to complete<\/span>\s*<strong[^>]*>1<\/strong>/s', $dashboard->getContent());
        $this->get('/staff/articles?tab=drafts')->assertOk()->assertSee($article->currentRevision->title);
    }

    public function test_review_queue_updates_from_actual_current_revision_status(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $admin);
        $revision = $article->currentRevision;
        $revision->status = RevisionStatus::InReview;
        $revision->save();

        $dashboard = $this->actingAs($admin)->get('/staff')->assertOk()
            ->assertSee('Articles awaiting review')
            ->assertSee($revision->title)
            ->assertSee('Review release checks');

        $this->assertMatchesRegularExpression('/Drafts to complete<\/span>\s*<strong[^>]*>0<\/strong>/s', $dashboard->getContent());
        $this->get('/staff/articles?tab=review')->assertOk()->assertSee($revision->title);
        $this->get('/staff/articles?tab=drafts')->assertOk()->assertDontSee($revision->title);
    }

    public function test_authors_track_their_own_submissions_without_admin_review_tools(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $author = User::factory()->create();
        app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $admin);

        $this->actingAs($author)->get('/staff')->assertOk()
            ->assertSee('Track my submissions')
            ->assertDontSee('Review submissions')
            ->assertDontSee('Edit homepage')
            ->assertDontSee('Review release checks');
    }

    public function test_railway_https_is_accepted_without_disabling_review_protection(): void
    {
        config([
            'app.url' => 'https://african-ranked-web-app-production.up.railway.app',
            'review.enabled' => true,
            'seo.preview' => true,
        ]);
        $report = app(LaunchReadiness::class)->report();
        $checks = collect($report['checks'])->keyBy('name');

        $this->assertTrue($checks['Approved HTTPS production origin']['passed']);
        $this->assertFalse($checks['Public indexing release approved']['passed']);
        $this->assertCount(9, $report['manual_gates']);
    }
}
