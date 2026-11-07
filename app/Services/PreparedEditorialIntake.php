<?php

namespace App\Services;

use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\AuditEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * One-time, revision-preserving intake of three explicitly commissioned
 * research manuscripts. In review is not editorial approval or publication.
 */
class PreparedEditorialIntake
{
    private const ARTICLES = [
        50 => 'nollywood',
        51 => 'instruments',
        52 => 'fabrics',
    ];
    private const SEARCH_METADATA = [
        50 => [
            'Nollywood Explained: Nigeria and Africa\'s Film Industries',
            'Learn how Nollywood developed, why film-industry size is hard to compare, and how African screen sectors differ.',
        ],
        51 => [
            'Traditional African Instruments: Mbira, Kora, Balafon and Drums',
            'Explore the mbira, kora, balafon and talking drum, with documented histories, performance traditions and sources.',
        ],
        52 => [
            'African Fabrics Explained: Kente, Ankara, Kitenge and More',
            'Discover how kente, Ankara, kitenge and kanga differ in construction, history, cultural use and provenance.',
        ],
    ];

    public function manuscript(int $articleId): array
    {
        if (! isset(self::ARTICLES[$articleId])) {
            throw new RuntimeException('No prepared manuscript for this article.');
        }

        $path = base_path("content/editorial/handoff/{$articleId}.md");
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("Missing prepared manuscript {$articleId}.");
        }

        // Repository document structure: title, private editorial metadata,
        // substantive body, FAQ section, source register, editor-only checklist.
        $parts = preg_split('/\R\s*\R/u', trim($raw));
        array_shift($parts); // Markdown H1
        array_shift($parts); // Research metadata; never public body copy.
        $remaining = implode("\n\n", $parts);

        $split = preg_split('/^## Frequently asked questions\s*$/mu', $remaining, 2);
        if (count($split) !== 2) {
            throw new RuntimeException("Missing FAQ section for {$articleId}.");
        }
        $body = trim($split[0]);

        if (! preg_match('/\A(.*?)^## (?:Source register|Sources for)[^\r\n]*\R(.*?)(?=^## |\z)/msu', $split[1], $matches)) {
            throw new RuntimeException("Missing source register for {$articleId}.");
        }

        preg_match_all('/^\*\*([^\r\n*]+\?)\*\*\s*\R+([^\r\n]+)/mu', $matches[1], $questions, PREG_SET_ORDER);
        $faqs = array_map(fn (array $row) => ['question' => trim($row[1]), 'answer' => trim($row[2])], $questions);

        preg_match_all('/^\s*\d+\.\s+\[([^\]\r\n]+)\]\((https:\/\/[^\s)]+)\)/mu', $matches[2], $citations, PREG_SET_ORDER);
        $sources = [];
        foreach ($citations as $row) {
            $sources[$row[2]] = ['title' => trim($row[1]), 'url' => $row[2]];
        }
        if (count($sources) < 5 || count($faqs) < 3) {
            throw new RuntimeException("Article {$articleId} lacks five source links or three FAQs.");
        }

        $body .= "\n\n## Sources and further reading\n\n".implode("\n\n", array_map(
            fn (array $source) => '['.$source['title'].']('.$source['url'].')',
            array_values($sources)
        ));
        $blocks = app(MarkdownBlocks::class)->parse($body);
        $words = preg_match_all('/[\pL\pN]+(?:[’\x{0027}-][\pL\pN]+)*/u', $split[0]);
        if ($words < 1300) {
            throw new RuntimeException("Article {$articleId} substantive body has only {$words} words.");
        }

        return ['body_markdown' => $body, 'body' => $blocks, 'faqs' => $faqs, 'sources' => array_values($sources), 'body_words' => $words];
    }

    /**
     * Returns true only if an eligible short original was replaced.
     * Skips already reviewed, published, long or unexpected articles.
     */
    public function queue(int $articleId): bool
    {
        $expected = self::ARTICLES[$articleId] ?? null;
        if (! $expected) {
            return false;
        }

        // Validate source outside of the write transaction.
        $prepared = $this->manuscript($articleId);

        return DB::transaction(function () use ($articleId, $expected, $prepared) {
            $article = Article::query()->whereKey($articleId)->lockForUpdate()->first();
            if (! $article || $article->trashed_at || $article->published_revision_id) {
                return false;
            }

            $prior = $article->currentRevision;
            if (! $prior
                || ! in_array($prior->status, [RevisionStatus::Draft, RevisionStatus::ChangesRequested], true)
                || ! str_contains(mb_strtolower($prior->title), $expected)
                || ! filled($prior->image)) {
                return false;
            }

            // Do not overwrite independent later editorial work or repeat intake.
            if (preg_match_all('/[\pL\pN]+/u', (string) $prior->body_markdown) >= 1300
                || str_contains((string) $prior->source_notes, 'Prepared editorial handoff:')) {
                return false;
            }

            $revision = $prior->replicate(['number', 'status', 'reviewed_by', 'reviewed_at', 'published_at', 'review_checklist', 'review_notes']);
            $revision->number = ((int) $article->revisions()->max('number')) + 1;
            $revision->created_by = null; // System import; do not impersonate a human author.
            $revision->status = RevisionStatus::InReview;
            [$revision->seo_title, $revision->meta_description] = self::SEARCH_METADATA[$articleId];
            $revision->body_markdown = $prepared['body_markdown'];
            $revision->body = $prepared['body'];
            $revision->faqs = $prepared['faqs'];
            $revision->read_time = max(1, (int) ceil($prepared['body_words'] / 200));
            $revision->source_notes = trim((string) $prior->source_notes)
                ."\n\nPrepared editorial handoff: source-backed research revision from GitHub. "
                .'Every claim, reference, author attribution, visual right and source-access date requires independent human review. '
                .'In Review is NOT an approval or a claim that sources were personally checked.';
            $revision->editorial_evidence = [
                'content_type' => 'guide',
                'image_rights' => str_contains((string) $prior->image, '/editorial-art/')
                    ? 'African Ranked code-generated editorial illustration; not documentary photography. Reviewer must confirm attribution and suitability.'
                    : null,
                'facts' => [],
                'sources' => [],
            ];
            $revision->reviewed_by = null;
            $revision->reviewed_at = null;
            $revision->review_checklist = null;
            $revision->review_notes = null;
            $revision->published_at = null;
            $revision->save();

            $article->current_revision_id = $revision->id;
            $article->save();

            AuditEvent::record(null, 'article.prepared_review_queued', $article, [
                'original_revision_id' => $prior->id,
                'review_revision_id' => $revision->id,
                'source' => 'content/editorial/handoff/'.$articleId.'.md',
                'verified_by_human' => false,
                'published' => false,
            ]);

            return true;
        });
    }
}
