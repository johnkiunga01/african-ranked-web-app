<?php

namespace App\Services;

use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ArticleMediaAsset;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EditorialWorkflow
{
    public const CHECKS = ['facts', 'ranking', 'attribution', 'image_rights'];

    public function save(User $actor, array $data, ?Article $article = null): Article
    {
        Gate::forUser($actor)->authorize($article ? 'update' : 'create', $article ?? Article::class);

        return DB::transaction(function () use ($actor, $data, $article) {
            if ($article) {
                $article = Article::lockForUpdate()->findOrFail($article->id);
                $this->checkRevision($article, $data['revision_id'] ?? null);
            } else {
                $article = new Article(['slug' => $data['slug']]);
                $article->user_id = $actor->id;
                $article->save();
            }
            if ($article->trashed_at) {
                throw ValidationException::withMessages(['workflow' => 'Restore this article from Trash before editing.']);
            }
            $owner = $article->author;
            $prior = $article->currentRevision;
            $body = ! empty($data['preserve_body']) && $prior ? $prior->body : app(MarkdownBlocks::class)->parse($data['body_markdown']);
            // Preserve author captions and credits for first-party uploaded image blocks.
            foreach ($body as &$block) {
                if (($block['type'] ?? null) !== 'image') continue;
                if (! preg_match('~/editorial-media/([a-f0-9-]{36})\\.webp$~', $block['url'] ?? '', $m)) continue;
                $asset = ArticleMediaAsset::find($m[1]);
                if (! $asset) continue;
                if (filled($asset->caption)) $block['caption'] = $asset->caption;
                if (filled($asset->credit)) $block['credit'] = $asset->credit;
                $block['source'] = null;
            }
            unset($block);
            $revision = $article->revisions()->create([
                'created_by' => $actor->id, 'number' => ($article->revisions()->max('number') ?? 0) + 1,
                'status' => RevisionStatus::Draft, 'title' => $data['title'], 'excerpt' => $data['excerpt'],
                'category_slug' => $data['category_slug'], 'body_markdown' => $data['body_markdown'], 'body' => $body,
                'faqs' => array_key_exists('faqs_text', $data) ? app(FaqEditor::class)->parse($data['faqs_text'] ?? '') : $prior?->faqs, 'regions' => array_key_exists('all_editions', $data) ? ($data['all_editions'] ? null : ($data['regions'] ?? [])) : $prior?->regions,
                'seo_title' => array_key_exists('seo_title', $data) ? $data['seo_title'] : $prior?->seo_title, 'meta_description' => array_key_exists('meta_description', $data) ? $data['meta_description'] : $prior?->meta_description,
                'focus_keyword' => array_key_exists('focus_keyword', $data) ? $data['focus_keyword'] : $prior?->focus_keyword, 'image_alt' => array_key_exists('image_alt', $data) ? $data['image_alt'] : $prior?->image_alt,
                'image' => $data['image'] ?? null, 'source_notes' => $data['source_notes'] ?? null,
                'editorial_evidence' => $data['editorial_evidence'] ?? $prior?->editorial_evidence,
                'author_name' => $owner?->name ?? 'Unassigned author', 'author_title' => $owner?->job_title ?? 'Contributor',
                'read_time' => max(1, (int) ceil(str_word_count($data['body_markdown']) / 200)),
            ]);
            $article->current_revision_id = $revision->id;
            $article->save();
            AuditEvent::record($actor, 'article.draft_saved', $article, ['revision' => $revision->number]);

            return $article->fresh(['currentRevision', 'publishedRevision', 'author']);
        });
    }

    public function transition(User $actor, Article $article, string $action, int $expectedRevision, array $data = []): Article
    {
        Gate::forUser($actor)->authorize($action === 'submit' ? 'update' : ($action === 'publish' || $action === 'unpublish' ? 'publish' : 'review'), $article);

        return DB::transaction(function () use ($actor, $article, $action, $expectedRevision, $data) {
            $article = Article::lockForUpdate()->findOrFail($article->id);
            $this->checkRevision($article, $expectedRevision);
            if ($article->trashed_at) {
                throw ValidationException::withMessages(['workflow' => 'Restore this article from Trash before publishing or reviewing.']);
            }
            $revision = $article->currentRevision()->lockForUpdate()->firstOrFail();
            if ($action === 'submit') {
                $this->requireStatus($revision, [RevisionStatus::Draft, RevisionStatus::ChangesRequested]);
                if (! $revision->body || ! $revision->source_notes || ! $revision->image) {
                    throw ValidationException::withMessages(['workflow' => 'Add article content, a hero image, and source/research notes before submitting.']);
                }
                $revision->status = RevisionStatus::InReview;
            } elseif ($action === 'request_changes') {
                $this->requireStatus($revision, [RevisionStatus::InReview, RevisionStatus::Approved]);
                $revision->status = RevisionStatus::ChangesRequested;
                $revision->review_notes = $data['review_notes'];
                $revision->review_checklist = null;
                $revision->reviewed_by = null;
                $revision->reviewed_at = null;
            } elseif ($action === 'approve') {
                $this->requireStatus($revision, [RevisionStatus::InReview]);
                foreach (self::CHECKS as $check) {
                    if (! filter_var($data[$check] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        throw ValidationException::withMessages(['workflow' => 'Complete every editorial review check before approval.']);
                    }
                }
                $revision->status = RevisionStatus::Approved;
                $revision->review_checklist = array_fill_keys(self::CHECKS, true);
                $revision->reviewed_by = $actor->id;
                $revision->reviewed_at = now();
                $revision->review_notes = $data['review_notes'] ?? null;
            } elseif ($action === 'publish') {
                $this->requireStatus($revision, [RevisionStatus::Approved]);
                if (! $article->author?->is_active || ! $article->author?->hasVerifiedEmail()) {
                    throw ValidationException::withMessages(['workflow' => 'Assign an active, verified author before publishing.']);
                }
                foreach (self::CHECKS as $check) {
                    if (empty($revision->review_checklist[$check])) {
                        throw ValidationException::withMessages(['workflow' => 'Complete editorial review before publishing.']);
                    }
                }
                app(PublicationQuality::class)->assertPublishable($revision);
                $revision->status = RevisionStatus::Published;
                $revision->published_at = now();
                $article->first_published_at ??= now();
                $article->published_revision_id = $revision->id;
            } elseif ($action === 'unpublish') {
                abort_unless($article->published_revision_id, 422);
                $article->published_revision_id = null;
                // A withdrawn published revision must be reviewed again before republication.
                if ($revision->status === RevisionStatus::Published) {
                    $revision->status = RevisionStatus::ChangesRequested;
                    $revision->review_checklist = null;
                    $revision->reviewed_at = null;
                    $revision->reviewed_by = null;
                }
            } else {
                abort(404);
            }
            $revision->save();
            $article->save();
            AuditEvent::record($actor, 'article.'.$action, $article, ['revision' => $revision->number]);

            return $article->fresh();
        });
    }

    private function checkRevision(Article $article, mixed $expected): void
    {
        if ((int) $expected !== (int) $article->current_revision_id) {
            throw ValidationException::withMessages(['workflow' => 'This article changed in another session. Reload before saving or reviewing.']);
        }
    }

    private function requireStatus(ArticleRevision $revision, array $allowed): void
    {
        if (! in_array($revision->status, $allowed, true)) {
            throw ValidationException::withMessages(['workflow' => 'That action is not available in the current review state.']);
        }
    }
}
