<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleSeoAnalysis;
use App\Services\EditorialWorkflow;
use App\Services\MarkdownBlocks;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublicationFixture;
use Tests\TestCase;

class AdvancedEditorialTest extends TestCase
{
    use RefreshDatabase;

    private function seedArticle(User $owner, string $slug = 'advanced-guide'): Article
    {
        $record = app(Catalog::class)->data('articles')[0];
        $record['slug'] = $slug;

        return app(SeedArticleImporter::class)->import($record, $owner);
    }

    private function editData(Article $article): array
    {
        return ['revision_id' => $article->current_revision_id, 'title' => 'Top 10 Practical Safari Checks', 'excerpt' => 'A practical editorial test.', 'category_slug' => 'travel', 'body_markdown' => "## 1. Plan carefully\n\n[Related article](/articles/missing-guide)\n\n[Unsafe](javascript:alert(1))", 'image' => 'https://example.test/image.jpg', 'image_alt' => 'A test image description', 'source_notes' => 'Test source notes only.', 'focus_keyword' => 'safari checks', 'seo_title' => 'Practical safari checks', 'meta_description' => 'A concise test search description.', 'faqs_text' => "What should I check?\nCheck the evidence."];
    }

    public function test_both_catalogs_paginate_and_filter_with_stable_sorting(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $author = User::factory()->create();
        $this->actingAs($admin);
        $this->get('/staff/articles?per_page=10&examples_page=2&sort=title')->assertOk()->assertViewHas('examples', fn ($items) => $items->total() === 13 && $items->count() === 3);
        for ($i = 0; $i < 12; $i++) {
            $this->seedArticle($i < 6 ? $admin : $author, 'pagination-guide-'.$i);
        }
        $this->get('/staff/articles?per_page=10&page=2&source=cms&sort=title_desc')->assertOk()->assertViewHas('articles', fn ($items) => $items->total() === 12 && $items->count() === 2);
        $this->get('/staff/articles?author='.$author->id)->assertViewHas('articles', fn ($items) => $items->total() === 6);
        $this->actingAs($author)->get('/staff/articles?author='.$admin->id)->assertViewHas('articles', fn ($items) => $items->total() === 6);
        $this->get('/staff/articles?per_page=10000')->assertSessionHasErrors('per_page');
    }

    public function test_article_library_search_finds_current_draft_body_and_introduction_without_exposing_other_authors(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $author = User::factory()->create();
        $article = $this->seedArticle($admin, 'searchable-private-guide');
        $revision = $article->currentRevision;
        $revision->body_markdown = 'Private body mentions the rarephraseelephantpath.';
        $revision->excerpt = 'An introduction covering the rarephrasewarmcamps.';
        $revision->save();

        $this->actingAs($admin)->get('/staff/articles?q=rarephraseelephantpath')
            ->assertOk()->assertViewHas('articles', fn ($items) => $items->total() === 1);
        $this->get('/staff/articles?q=rarephrasewarmcamps')
            ->assertOk()->assertViewHas('articles', fn ($items) => $items->total() === 1);

        $this->actingAs($author)->get('/staff/articles?q=rarephraseelephantpath')
            ->assertOk()->assertViewHas('articles', fn ($items) => $items->total() === 0);
    }

    public function test_seo_fields_and_faqs_stay_private_until_approved_publication_and_can_be_cleared(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = $this->seedArticle($admin);
        $this->actingAs($admin)->put('/staff/articles/'.$article->id, $this->editData($article))->assertRedirect();
        $article->refresh();
        $revision = $article->currentRevision;
        $this->assertSame('Practical safari checks', $revision->seo_title);
        $this->get('/articles/'.$article->slug)->assertNotFound();
        $workflow = app(EditorialWorkflow::class);
        $workflow->transition($admin, $article, 'submit', $revision->id);
        $workflow->transition($admin, $article, 'approve', $revision->id, array_fill_keys(EditorialWorkflow::CHECKS, true));
        PublicationFixture::prepare($article);
        $workflow->transition($admin, $article, 'publish', $revision->id);
        $this->get('/articles/'.$article->slug)->assertOk()->assertSee('<title>Practical safari checks</title>', false)->assertSee('A concise test search description.')->assertSee('alt="A test image description"', false)->assertSee('What should I check?')->assertDontSee('focus_keyword');
        $article->refresh();
        $data = $this->editData($article);
        $data['seo_title'] = null;
        $data['faqs_text'] = '';
        $this->put('/staff/articles/'.$article->id, $data)->assertRedirect();
        $this->assertSame('Top 10 Practical Safari Checks | African Ranked', $article->fresh()->currentRevision->seo_title);
        $this->assertSame([], $article->fresh()->currentRevision->faqs);
        $this->get('/articles/'.$article->slug)->assertSee('<title>Practical safari checks</title>', false);
    }

    public function test_analysis_detects_incomplete_rankings_and_nonpublic_links_without_executing_unsafe_urls(): void
    {
        $owner = User::factory()->create();
        $article = $this->seedArticle($owner);
        $this->actingAs($owner)->put('/staff/articles/'.$article->id, $this->editData($article))->assertRedirect();
        $article->refresh();
        $analysis = app(ArticleSeoAnalysis::class)->analyze($article, $article->currentRevision);
        $labels = array_column($analysis['findings'], 'label');
        $this->assertContains('Ranking completeness needs checking', $labels);
        $this->assertContains('Internal article target is not public', $labels);
        $this->assertSame(1, $analysis['linkCount']);
        $this->get('/staff/articles/'.$article->id.'/seo')->assertOk()->assertSee('Saved revision')->assertSee('Search appearance preview');
        $this->actingAs(User::factory()->create())->get('/staff/articles/'.$article->id.'/seo')->assertForbidden();
        $blocks = app(MarkdownBlocks::class)->parse("[Unsafe](javascript:alert(1))\n\n[Protocol-relative](//evil.test)\n\n[Good](https://example.test/source)");
        $this->assertSame(['paragraph', 'paragraph', 'link'], array_column($blocks, 'type'));
    }

    public function test_revision_restore_is_scoped_stale_safe_and_creates_unapproved_copy(): void
    {
        $owner = User::factory()->create();
        $article = $this->seedArticle($owner);
        $old = $article->currentRevision;
        $this->actingAs($owner)->put('/staff/articles/'.$article->id, $this->editData($article))->assertRedirect();
        $article->refresh();
        $currentId = $article->current_revision_id;
        $this->get('/staff/articles/'.$article->id.'/revisions/'.$old->id)->assertOk()->assertSee('Changed');
        $other = $this->seedArticle($owner, 'other-scope');
        $this->post('/staff/articles/'.$article->id.'/revisions/'.$other->current_revision_id.'/restore', ['revision_id' => $currentId])->assertNotFound();
        $this->post('/staff/articles/'.$article->id.'/revisions/'.$old->id.'/restore', ['revision_id' => $currentId])->assertRedirect();
        $article->refresh();
        $this->assertSame($old->body, $article->currentRevision->body);
        $this->assertSame(RevisionStatus::Draft, $article->currentRevision->status);
        $this->assertNull($article->currentRevision->reviewed_at);
        $this->assertSame(3, $article->currentRevision->number);
        $this->post('/staff/articles/'.$article->id.'/revisions/'.$old->id.'/restore', ['revision_id' => $currentId])->assertSessionHasErrors('revision_id');
        $this->actingAs(User::factory()->create())->get('/staff/articles/'.$article->id.'/revisions/'.$old->id)->assertForbidden();
    }

    public function test_bulk_changes_roll_back_the_whole_batch_on_stale_or_unauthorized_items(): void
    {
        $owner = User::factory()->create();
        $first = $this->seedArticle($owner);
        $second = $this->seedArticle($owner, 'second-batch');
        $this->actingAs($owner);
        $this->post('/staff/article-batches', ['operation' => 'submit', 'confirm' => 1, 'selected' => [$first->id => $first->current_revision_id, $second->id => 999999]])->assertSessionHasErrors('workflow');
        $this->assertSame(RevisionStatus::Draft, $first->fresh()->currentRevision->status);
        $this->post('/staff/article-batches', ['operation' => 'submit', 'confirm' => 1, 'selected' => [$first->id => $first->current_revision_id, $second->id => $second->current_revision_id]])->assertRedirect();
        $this->assertSame(RevisionStatus::InReview, $first->fresh()->currentRevision->status);
        $this->post('/staff/article-batches', ['operation' => 'unpublish', 'confirm' => 1, 'selected' => [$first->id => $first->current_revision_id]])->assertForbidden();
    }

    public function test_invalid_faq_input_does_not_save_a_partial_revision(): void
    {
        $owner = User::factory()->create();
        $article = $this->seedArticle($owner);
        $data = $this->editData($article);
        $data['faqs_text'] = 'A question without its answer';
        $this->actingAs($owner)->put('/staff/articles/'.$article->id, $data)->assertSessionHasErrors('faqs_text');
        $this->assertSame(1, $article->revisions()->count());
    }
}
