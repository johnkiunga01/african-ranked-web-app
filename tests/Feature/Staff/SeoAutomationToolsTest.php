<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\ArticleResearch;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Services\SeoAutoFix;
use App\Services\SiteSettings;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SeoAutomationToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_api_fixes_create_a_private_revision_without_inventing_image_or_evidence(): void
    {
        Http::fake();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $admin);
        $old = $article->currentRevision;
        $changes = app(SeoAutoFix::class)->apply($admin, $article, $old->id, false);
        $article->refresh();
        $this->assertContains('focus_keyword', $changes);
        $this->assertLessThanOrEqual(65, mb_strlen($article->currentRevision->seo_title));
        $this->assertSame($old->body, array_slice($article->currentRevision->body, 0, count($old->body)));
        $this->assertContains('related_reading_link', $changes);
        $this->assertSame('link', $article->currentRevision->body[array_key_last($article->currentRevision->body)]['type']);
        $this->assertNull($article->currentRevision->image_alt);
        $this->assertNull($article->published_revision_id);
        $this->assertStringContainsString($old->source_notes, $article->currentRevision->source_notes);
        $this->assertStringContainsString('search demand has not been verified', $article->currentRevision->source_notes);
        Http::assertNothingSent();
    }

    public function test_gemini_fixes_are_key_protected_validated_and_do_not_fall_back_after_quota_error(): void
    {
        config(['services.gemini.key' => 'fixture-gemini-key']);
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $admin);
        ArticleResearch::create(['article_id' => $article->id, 'user_id' => $admin->id, 'keyword' => 'safari planning', 'location_code' => 2840, 'status' => 'completed', 'researched_at' => now(), 'brief' => ['keyword' => 'safari planning']]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode(['seo_title' => 'Safari planning guide', 'meta_description' => 'Explore the supplied safari planning guide.', 'focus_keyword' => 'safari planning'])]]]]]])]);
        $this->actingAs($admin)->post('/staff/articles/'.$article->id.'/seo-fix', ['revision_id' => $article->current_revision_id, 'provider' => 'gemini'])->assertRedirect();
        $this->assertSame('Safari planning guide', $article->fresh()->currentRevision->seo_title);
        $this->assertDatabaseHas('article_ai_requests', ['purpose' => 'seo_fix', 'provider' => 'gemini', 'status' => 'completed']);
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'fixture-gemini-key') && ! str_contains($request->url(), 'fixture-gemini-key'));
        Http::swap(new Factory);
        Http::fake(['*' => Http::response('private-provider-error', 429)]);
        $before = $article->fresh()->current_revision_id;
        $this->post('/staff/articles/'.$article->id.'/seo-fix', ['revision_id' => $before, 'provider' => 'gemini'])->assertSessionHasErrors('seo');
        $this->assertSame($before, $article->fresh()->current_revision_id);
        Http::assertSentCount(1);
    }

    public function test_authors_need_explicit_ai_access_and_cannot_use_other_authors_articles(): void
    {
        Http::fake();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $owner);
        $this->actingAs($owner)->post('/staff/articles/'.$article->id.'/seo-fix', ['revision_id' => $article->current_revision_id, 'provider' => 'gemini'])->assertForbidden();
        app(SiteSettings::class)->save('general', 0, ['author_ai_enabled' => true], $admin);
        $this->actingAs($other)->post('/staff/articles/'.$article->id.'/seo-fix', ['revision_id' => $article->current_revision_id, 'provider' => 'local'])->assertForbidden();
        $this->actingAs($owner)->get('/staff/articles/'.$article->id.'/promotion')->assertOk()->assertSee('Publish the article before sharing');
        Http::assertNothingSent();
    }
}
