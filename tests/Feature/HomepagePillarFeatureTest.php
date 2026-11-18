<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublicationFixture;
use Tests\TestCase;

class HomepagePillarFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function publish(User $admin, string $slug): Article
    {
        $record = app(Catalog::class)->data('articles')[0];
        $record['slug'] = $slug;
        $article = app(SeedArticleImporter::class)->import($record, $admin);
        PublicationFixture::prepare($article);
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/publish-reviewed', ['revision_id' => $article->current_revision_id, 'editorial_approval' => 1]);

        return $article->fresh();
    }

    public function test_without_a_dashboard_pick_the_pillar_guide_is_featured_not_the_newest_article(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->publish($admin, Catalog::HOMEPAGE_PILLARS[0]);
        $this->travel(1)->days();
        $this->publish($admin, 'a-newer-ordinary-guide');

        $this->assertSame(Catalog::HOMEPAGE_PILLARS[0], app(Catalog::class)->homepageArticles()->first()['slug']);
    }

    public function test_without_any_pillar_the_newest_article_is_still_used(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->publish($admin, 'an-older-ordinary-guide');
        $this->travel(1)->days();
        $this->publish($admin, 'a-newer-ordinary-guide');

        $this->assertSame('a-newer-ordinary-guide', app(Catalog::class)->homepageArticles()->first()['slug']);
    }
}
