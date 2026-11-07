<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleEvidencePlacementTest extends TestCase
{
    use RefreshDatabase;

    private function evidence(): array
    {
        return [
            'answer' => 'Short answer marker for reading order.',
            'facts' => [
                [
                    'fact' => 'Source-backed key fact marker.',
                    'source_url' => 'https://evidence.example.test/one',
                    'verified_at' => now()->toDateString(),
                ],
            ],
            'sources' => [
                [
                    'title' => 'Synthetic reference',
                    'publisher' => 'Fixture publisher',
                    'url' => 'https://evidence.example.test/one',
                    'accessed_at' => now()->toDateString(),
                ],
            ],
            'last_verified_at' => now()->toDateString(),
        ];
    }

    private function assertReadingOrder(string $html): void
    {
        // Exclude JSON-LD (including FAQPage schema) and unrelated page chrome.
        $start = strpos($html, '<article ');
        $this->assertNotFalse($start);
        $end = strpos($html, '</article>', $start);
        $this->assertNotFalse($end);
        $html = substr($html, $start, $end + strlen('</article>') - $start);

        $markers = [
            'Short answer marker for reading order.',
            'Body paragraph marker for reading order.',
            'Fixture FAQ heading for reading order?',
            'Source-backed key fact marker.',
            'Research references and access dates',
        ];

        $positions = [];
        foreach ($markers as $marker) {
            $position = strpos($html, $marker);
            $this->assertNotFalse($position, "Missing article section marker: {$marker}");
            $positions[] = $position;
        }

        $this->assertSame($positions, collect($positions)->sort()->values()->all(),
            'The short answer must precede the body; key facts must follow the body and FAQs, before research references.');
        $this->assertSame(1, substr_count($html, 'Source-backed key fact marker.'));
    }

    public function test_public_article_places_key_facts_after_body_and_faqs(): void
    {
        $owner = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $owner);
        $revision = $article->currentRevision;

        $revision->body = [['type' => 'paragraph', 'text' => 'Body paragraph marker for reading order.']];
        $revision->faqs = [['question' => 'Fixture FAQ heading for reading order?', 'answer' => 'Fixture answer.']];
        $revision->editorial_evidence = $this->evidence();
        $revision->status = RevisionStatus::Published;
        $revision->published_at = now();
        $revision->save();

        $article->published_revision_id = $revision->id;
        $article->first_published_at = now();
        $article->save();

        $html = $this->get('/articles/'.$article->slug)->assertOk()->getContent();
        $this->assertReadingOrder($html);
    }

    public function test_private_draft_preview_uses_the_same_reading_order(): void
    {
        $owner = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $owner);
        $revision = $article->currentRevision;

        $revision->body = [['type' => 'paragraph', 'text' => 'Body paragraph marker for reading order.']];
        $revision->faqs = [['question' => 'Fixture FAQ heading for reading order?', 'answer' => 'Fixture answer.']];
        $revision->editorial_evidence = $this->evidence();
        $revision->save();

        $html = $this->actingAs($owner)->get('/staff/articles/'.$article->id.'/preview')->assertOk()->getContent();
        $this->assertReadingOrder($html);
    }

    public function test_evidence_can_render_only_the_requested_section(): void
    {
        $evidence = $this->evidence();

        $answer = view('editorial.evidence', ['evidence' => $evidence, 'section' => 'answer'])->render();
        $this->assertStringContainsString('Short answer marker for reading order.', $answer);
        $this->assertStringNotContainsString('Source-backed key fact marker.', $answer);

        $facts = view('editorial.evidence', ['evidence' => $evidence, 'section' => 'facts'])->render();
        $this->assertStringContainsString('Source-backed key fact marker.', $facts);
        $this->assertStringNotContainsString('Short answer marker for reading order.', $facts);
    }
}
