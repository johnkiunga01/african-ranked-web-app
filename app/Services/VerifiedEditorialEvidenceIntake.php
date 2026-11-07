<?php

namespace App\Services;

use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\AuditEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Audited, one-time handoff of checked factual evidence for the three
 * commissioned articles. A source access date records the actual research
 * session; it never impersonates a human editor's publication approval.
 */
class VerifiedEditorialEvidenceIntake
{
    private const TITLES = [
        50 => 'nollywood',
        51 => 'instruments',
        52 => 'fabrics',
    ];

    public function evidence(int $articleId): array
    {
        if (! isset(self::TITLES[$articleId])) {
            throw new RuntimeException('No checked editorial evidence for this article.');
        }

        $path = base_path("content/editorial/verified-evidence/{$articleId}.json");
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("Missing editorial evidence for article {$articleId}.");
        }

        $rawEvidence = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        $validated = app(EditorialEvidence::class)->validate($rawEvidence);
        $validated['verification_scope'] = $rawEvidence['verification_scope'] ?? null;
        $failures = app(EditorialEvidence::class)->inspect(array_merge($validated, [
            'image_rights' => 'Existing image right requires confirmation at named editorial review.',
        ]));
        if ($failures) {
            throw new RuntimeException("Incomplete evidence for article {$articleId}: ".implode('; ', $failures));
        }

        return $validated;
    }

    /**
     * Returns true only when an unchanged prepared revision #4 is eligible.
     * New review revisions preserve published pointers and every older version.
     */
    public function queue(int $articleId): bool
    {
        $needle = self::TITLES[$articleId] ?? null;
        if (! $needle) {
            return false;
        }

        $evidence = $this->evidence($articleId);

        return DB::transaction(function () use ($articleId, $needle, $evidence) {
            $article = Article::query()->whereKey($articleId)->lockForUpdate()->first();
            if (! $article || $article->trashed_at) {
                return false;
            }

            $previous = $article->currentRevision()->lockForUpdate()->first();
            if (! $previous
                || (int) $previous->number !== 4
                || ! str_contains(mb_strtolower($previous->title), $needle)
                || ! str_contains((string) $previous->source_notes, 'Prepared editorial handoff:')
                || preg_match_all('/[\pL\pN]+/u', (string) $previous->body_markdown) < 1300
                || ! in_array($previous->status, [RevisionStatus::InReview, RevisionStatus::Approved, RevisionStatus::Published], true)) {
                return false;
            }

            // Never silently discard a teammate's existing verified work.
            if (count($previous->editorial_evidence['sources'] ?? []) >= 5
                || count($previous->editorial_evidence['facts'] ?? []) >= 4
                || filled($previous->editorial_evidence['answer'] ?? null)) {
                return false;
            }

            $revision = $previous->replicate(['number', 'status', 'reviewed_by', 'reviewed_at', 'published_at', 'review_checklist', 'review_notes']);
            $revision->number = ((int) $article->revisions()->max('number')) + 1;
            $revision->created_by = null;
            $revision->status = RevisionStatus::InReview;
            $revision->editorial_evidence = array_merge($previous->editorial_evidence ?? [], $evidence);
            $revision->source_notes = trim((string) $previous->source_notes)
                ."\n\nVerified evidence handoff: specified fact-and-source records researched on "
                .$evidence['last_verified_at'].'. AI-assisted source checks do not constitute a named human review, '
                .'complete-article fact verification or media licence confirmation.';
            $revision->reviewed_by = null;
            $revision->reviewed_at = null;
            $revision->published_at = null;
            $revision->review_checklist = null;
            $revision->review_notes = null;
            $revision->save();

            $article->current_revision_id = $revision->id;
            // Never change the published revision pointer, even if an earlier
            // version has already been approved and published by the team.
            $article->save();

            AuditEvent::record(null, 'article.evidence_research_queued', $article, [
                'from_revision_id' => $previous->id,
                'review_revision_id' => $revision->id,
                'source_count' => count($evidence['sources']),
                'key_fact_count' => count($evidence['facts']),
                'last_verified_at' => $evidence['last_verified_at'],
                'human_approval' => false,
            ]);

            return true;
        });
    }
}
