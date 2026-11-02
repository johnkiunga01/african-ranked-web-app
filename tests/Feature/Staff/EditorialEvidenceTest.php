<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\ArticleRevision;
use App\Models\User;
use App\Services\AiUsage;
use App\Services\EditorialEvidence;
use App\Services\MarkdownBlocks;
use App\Services\PublicationQuality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_super_admin_allowance_expires_and_never_raises_author_allowance(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $author = User::factory()->create();
        config(['editorial.admin_daily_ai_limit' => 60, 'editorial.admin_daily_ai_limit_until' => now()->addMinute()->toIso8601String()]);
        $usage = app(AiUsage::class);
        $this->assertSame(60, $usage->limit($admin));
        $this->assertSame(5, $usage->limit($author));
        $this->travel(2)->minutes();
        $this->assertSame(20, $usage->limit($admin));
        config(['editorial.admin_daily_ai_limit_until' => 'invalid']);
        $this->assertSame(20, $usage->limit($admin));
    }

    private function evidence(): array
    {
        $sources = [];
        $facts = [];
        for ($i = 1; $i <= 5; $i++) {
            $sources[] = ['title' => 'Synthetic source '.$i, 'publisher' => 'Test publisher', 'url' => 'https://evidence.example.test/'.$i, 'accessed_at' => now()->format('Y-m-d')];
            if ($i < 5) {
                $facts[] = ['fact' => 'Synthetic fixture fact '.$i, 'source_url' => $sources[$i - 1]['url'], 'verified_at' => now()->format('Y-m-d')];
            }
        }

        return ['content_type' => 'guide', 'answer' => str_repeat('Answer ', 45), 'facts' => $facts, 'sources' => $sources, 'last_verified_at' => now()->format('Y-m-d'), 'image_rights' => 'Test fixture only; no actual media licence asserted.'];
    }

    public function test_facts_must_reference_recorded_sources_and_dates_cannot_be_future(): void
    {
        $evidence = $this->evidence();
        $this->assertSame([], app(EditorialEvidence::class)->inspect($evidence));
        $evidence['facts'][0]['source_url'] = 'https://unrecorded.example.test';
        $evidence['last_verified_at'] = now()->addDay()->format('Y-m-d');
        $this->assertCount(2, app(EditorialEvidence::class)->inspect($evidence));
    }

    public function test_ranked_items_and_modules_cannot_inflate_master_guide_body_minimum(): void
    {
        $body = '## Reporting'."\n\n".str_repeat('reporting ', 1299)."\n\n## 1. Synthetic item\n\n".str_repeat('ranking ', 200)."\n\n## Sources\n\n".str_repeat('source ', 200);
        $revision = new ArticleRevision(['title' => 'A supported comparison', 'excerpt' => 'Test summary', 'body' => app(MarkdownBlocks::class)->parse($body), 'faqs' => array_fill(0, 3, ['question' => 'A fixture question?', 'answer' => 'A fixture answer.']), 'editorial_evidence' => $this->evidence()]);
        $report = app(PublicationQuality::class)->inspect($revision);
        $this->assertSame(1299, $report['body_words']);
        $this->assertSame(5, $report['source_links']);
        $this->assertStringContainsString('1,300', implode(' ', $report['failures']));
    }

    public function test_public_evidence_escapes_unsafe_source_text(): void
    {
        $evidence = $this->evidence();
        $evidence['facts'][0]['fact'] = '<script>alert(1)</script>';
        $html = view('editorial.evidence', compact('evidence'))->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<table', $html);
    }
}
