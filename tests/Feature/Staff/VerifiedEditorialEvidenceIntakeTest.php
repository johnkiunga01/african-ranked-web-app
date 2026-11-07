<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\User;
use App\Services\EditorialEvidence;
use App\Services\VerifiedEditorialEvidenceIntake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedEditorialEvidenceIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_three_articles_have_real_dated_sources_facts_and_separate_direct_answers(): void
    {
        foreach ([50, 51, 52] as $articleId) {
            $evidence = app(VerifiedEditorialEvidenceIntake::class)->evidence($articleId);
            $words = preg_match_all('/[\pL\pN]+(?:[’\x{0027}-][\pL\pN]+)*/u', $evidence['answer']);

            $this->assertGreaterThanOrEqual(40, $words);
            $this->assertLessThanOrEqual(60, $words);
            $this->assertGreaterThanOrEqual(5, count($evidence['sources']));
            $this->assertGreaterThanOrEqual(4, count($evidence['facts']));
            $this->assertLessThanOrEqual(8, count($evidence['facts']));
            $this->assertSame('2026-11-07', $evidence['last_verified_at']);
            $sourceUrls = array_column($evidence['sources'], 'url');
            $this->assertCount(count(array_unique($sourceUrls)), $sourceUrls);
            foreach ($evidence['sources'] as $source) {
                $this->assertNotEmpty($source['publisher']);
                $this->assertStringStartsWith('https://', $source['url']);
                $this->assertSame('2026-11-07', $source['accessed_at']);
            }
            foreach ($evidence['facts'] as $fact) {
                $this->assertContains($fact['source_url'], $sourceUrls);
                $this->assertSame('2026-11-07', $fact['verified_at']);
            }
            $this->assertSame([], app(EditorialEvidence::class)->inspect(array_merge($evidence, [
                'image_rights' => 'Original editorial illustration, pending named human confirmation of authorship and rights.',
            ])));
        }
    }

    private function article(int $id, bool $alreadyPublished = false): Article
    {
        $owner = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = new Article(['slug' => 'evidence-review-'.$id]);
        $article->id = $id;
        $article->user_id = $owner->id;
        $article->save();

        $titles = [
            50 => 'Nollywood Explained and Africa Film Industries',
            51 => 'Traditional African Instruments: A Guide',
            52 => 'African Fabrics and Prints: Kente and Ankara',
        ];

        $revision = $article->revisions()->create([
            'number' => 4,
            'created_by' => $owner->id,
            'status' => $alreadyPublished ? RevisionStatus::Published : RevisionStatus::InReview,
            'title' => $titles[$id],
            'excerpt' => 'The original short introduction remains unchanged.',
            'category_slug' => 'music',
            'body_markdown' => "## Research\n\n".str_repeat('Original researched story evidence. ', 600),
            'body' => [['type' => 'paragraph', 'text' => 'Original researched story evidence.']],
            'faqs' => [['question' => 'Original question?', 'answer' => 'Original answer.']],
            'image' => 'https://example.test/editorial-art/sample/cover.svg',
            'author_name' => $owner->name,
            'author_title' => 'Contributor',
            'read_time' => 10,
            'source_notes' => 'Prepared editorial handoff: source-backed research revision from GitHub.',
            'editorial_evidence' => [
                'content_type' => 'guide',
                'answer' => null,
                'sources' => [],
                'facts' => [],
                'image_rights' => 'Original illustration; publication licence still requires human review.',
            ],
            'published_at' => $alreadyPublished ? now() : null,
        ]);
        $article->current_revision_id = $revision->id;
        if ($alreadyPublished) {
            $article->published_revision_id = $revision->id;
            $article->first_published_at = now();
        }
        $article->save();

        return $article;
    }

    public function test_prepared_review_revision_receives_evidence_without_changing_original(): void
    {
        $article = $this->article(50);
        $priorId = $article->current_revision_id;

        $this->assertTrue(app(VerifiedEditorialEvidenceIntake::class)->queue(50));

        $article->refresh();
        $revision = $article->currentRevision;
        $this->assertSame(5, $revision->number);
        $this->assertSame(RevisionStatus::InReview, $revision->status);
        $this->assertSame('2026-11-07', $revision->editorial_evidence['last_verified_at']);
        $this->assertCount(6, $revision->editorial_evidence['facts']);
        $this->assertCount(6, $revision->editorial_evidence['sources']);
        $this->assertNull($revision->reviewed_by);
        $this->assertNull($revision->reviewed_at);
        $this->assertNull($revision->published_at);
        $this->assertNull($article->published_revision_id);
        $this->assertSame(RevisionStatus::InReview, $article->revisions()->findOrFail($priorId)->status);
        $this->assertSame('The original short introduction remains unchanged.', $revision->excerpt);
        $this->assertFalse(app(VerifiedEditorialEvidenceIntake::class)->queue(50));
        $this->assertSame(2, $article->revisions()->count());

        $this->actingAs($article->author)->get('/staff/articles/'.$article->id.'/edit')
            ->assertOk()->assertSee('Short introduction')->assertSee('Direct answer (40–60 words)')
            ->assertSee($revision->editorial_evidence['answer']);
        $this->get('/staff/articles/'.$article->id.'/preview')->assertOk()
            ->assertSee('The short answer')->assertSee($revision->editorial_evidence['answer']);
    }

    public function test_published_article_remains_live_while_new_evidence_enters_review(): void
    {
        $article = $this->article(52, true);
        $publicId = $article->published_revision_id;

        $this->assertTrue(app(VerifiedEditorialEvidenceIntake::class)->queue(52));
        $article->refresh();

        $this->assertSame($publicId, $article->published_revision_id);
        $this->assertSame(RevisionStatus::Published, $article->publishedRevision->status);
        $this->assertSame(RevisionStatus::InReview, $article->currentRevision->status);
        $this->assertNull($article->currentRevision->reviewed_by);
        $this->assertNull($article->currentRevision->published_at);
    }

    public function test_does_not_change_teammates_newer_or_unexpected_revisions(): void
    {
        $article = $this->article(51);
        $prior = $article->currentRevision;
        $prior->number = 5;
        $prior->save();
        $this->assertFalse(app(VerifiedEditorialEvidenceIntake::class)->queue(51));
        $this->assertSame($prior->id, $article->fresh()->current_revision_id);
        $this->assertSame(1, $article->revisions()->count());
    }
}
