<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\User;
use App\Services\EditorialWorkflow;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublicationFixture;
use Tests\TestCase;

class SafariPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_planner_links_only_to_available_published_guides(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $seed = app(Catalog::class)->data('articles')[0];
        $seed['slug'] = 'how-to-choose-an-african-safari';
        $seed['title'] = 'Published planning fixture';
        $article = app(SeedArticleImporter::class)->import($seed, $admin);
        PublicationFixture::prepare($article);
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/publish-reviewed', ['revision_id' => $article->current_revision_id, 'editorial_approval' => 1])->assertRedirect();
        $seed['slug'] = 'best-family-safari-destinations-africa';
        $seed['title'] = 'Private planning fixture';
        app(SeedArticleImporter::class)->import($seed, $admin);
        app(EditorialWorkflow::class)->save($admin, ['revision_id' => $article->fresh()->current_revision_id, 'title' => 'Newer private planning title', 'excerpt' => 'Private summary', 'category_slug' => 'wildlife', 'body_markdown' => 'Private replacement.'], $article->fresh());
        $this->get('/plan-safari')->assertOk()->assertSee('Published planning fixture')
            ->assertDontSee('Private planning fixture')->assertDontSee('Newer private planning title')
            ->assertSee('href="/articles/how-to-choose-an-african-safari"', false)
            ->assertDontSee('href="/articles/best-family-safari-destinations-africa"', false);
        $this->get('/sitemap.xml')->assertSee('/plan-safari');
        $this->get('/')->assertSee('href="/plan-safari"', false);
        $article->fresh()->forceFill(['published_revision_id' => null])->save();
        $this->get('/plan-safari')->assertOk()->assertDontSee('Published planning fixture');
    }
}
