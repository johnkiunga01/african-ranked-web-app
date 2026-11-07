<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleVisuals;
use App\Services\InlineEditorialMarkdown;
use App\Services\PreparedEditorialIntake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreparedEditorialIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_three_commissioned_manuscripts_have_full_body_faqs_and_source_links(): void
    {
        foreach ([50, 51, 52] as $id) {
            $draft = app(PreparedEditorialIntake::class)->manuscript($id);
            $this->assertGreaterThanOrEqual(1300, $draft['body_words']);
            $this->assertGreaterThanOrEqual(5, count($draft['sources']));
            $this->assertGreaterThanOrEqual(3, count($draft['faqs']));
            $this->assertGreaterThanOrEqual(6, collect($draft['body'])->where('type', 'heading')->count());
            $this->assertGreaterThanOrEqual(5, collect($draft['body'])->where('type', 'link')->count());
            $this->assertStringNotContainsString('Editorial acceptance checklist', $draft['body_markdown']);
            $this->assertStringNotContainsString('not saved to CMS', $draft['body_markdown']);
        }
    }

    public function test_queues_only_matching_short_unpublished_draft_and_preserves_revision_history(): void
    {
        $author = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = new Article(['slug' => 'nollywood-explained']);
        $article->id = 50;
        $article->user_id = $author->id;
        $article->save();

        $prior = $article->revisions()->create([
            'number' => 1,
            'created_by' => $author->id,
            'status' => RevisionStatus::Draft,
            'title' => 'Nollywood Explained and Africa\'s Biggest Film Industries',
            'excerpt' => 'Original brief explanation.',
            'category_slug' => 'music',
            'body_markdown' => "## Introduction\n\nA short inherited seed.",
            'body' => [['type' => 'paragraph', 'text' => 'A short inherited seed.']],
            'faqs' => [],
            'image' => 'https://example.test/editorial-art/nollywood-explained/cover.svg',
            'author_name' => $author->name,
            'author_title' => 'Contributor',
            'read_time' => 1,
            'source_notes' => 'Imported seed has no completed fact verification.',
        ]);
        $article->current_revision_id = $prior->id;
        $article->save();

        $intake = app(PreparedEditorialIntake::class);
        $this->assertTrue($intake->queue(50));
        $article->refresh();
        $revision = $article->currentRevision;

        $this->assertSame(RevisionStatus::InReview, $revision->status);
        $this->assertSame(2, $revision->number);
        $this->assertNull($revision->created_by);
        $this->assertNull($revision->reviewed_by);
        $this->assertNull($revision->reviewed_at);
        $this->assertNull($revision->published_at);
        $this->assertNull($article->published_revision_id);
        $this->assertSame($prior->id, $prior->fresh()->id);
        $this->assertSame(RevisionStatus::Draft, $prior->fresh()->status);
        $this->assertCount(4, $revision->faqs);
        $this->assertGreaterThan(1300, str_word_count($revision->body_markdown));
        $this->assertFalse($intake->queue(50));
        $this->assertSame(2, $article->revisions()->count());

        $this->actingAs($author)->get('/staff/articles/'.$article->id.'/preview')
            ->assertOk()
            ->assertSee('In review')
            ->assertSee('The home-video boom')
            ->assertSee('Sources and further reading')
            ->assertSee('Editorial checks')
            ->assertDontSee('Submit for review');
    }

    public function test_rejects_mismatched_id_and_preserves_longer_existing_editorial_draft(): void
    {
        $author = User::factory()->create();
        $article = new Article(['slug' => 'another-article']);
        $article->id = 51;
        $article->user_id = $author->id;
        $article->save();
        $prior = $article->revisions()->create([
            'number' => 1, 'status' => RevisionStatus::Draft,
            'title' => 'A different article', 'excerpt' => 'An unrelated text.',
            'category_slug' => 'music', 'body_markdown' => 'Unrelated',
            'body' => [['type' => 'paragraph', 'text' => 'Unrelated']],
            'faqs' => [], 'image' => 'https://example.test/photo.jpg',
            'author_name' => $author->name, 'author_title' => 'Contributor',
            'read_time' => 1,
        ]);
        $article->current_revision_id = $prior->id;
        $article->save();

        $this->assertFalse(app(PreparedEditorialIntake::class)->queue(51));
        $this->assertSame($prior->id, $article->fresh()->current_revision_id);
    }

    public function test_inline_links_are_safe_and_generated_inline_cover_variants_are_suppressed(): void
    {
        $renderer = app(InlineEditorialMarkdown::class);
        $html = $renderer->render('See [UNESCO](https://unesco.org/report) and **sources** <script>alert(1)</script>.');
        $this->assertStringContainsString('href="https://unesco.org/report"', $html);
        $this->assertStringContainsString('<strong>sources</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('href="javascript:', $renderer->render('[bad](javascript:alert(1))'));

        $images = app(ArticleVisuals::class);
        $hero = 'https://africa.example/editorial-art/nollywood-explained/cover.svg';
        $this->assertFalse($images->showInline($hero, 'https://africa.example/editorial-art/nollywood-explained/detail-1.svg'));
        $this->assertFalse($images->showInline($hero, $hero));
        $this->assertTrue($images->showInline($hero, 'https://example.test/relevant-photo.jpg'));
        $this->assertFalse($images->showInline($hero, 'https://example.test/relevant-photo.jpg', ['https://example.test/relevant-photo.jpg']));
    }
}
