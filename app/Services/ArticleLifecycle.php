<?php

namespace App\Services;

use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ArticleLifecycle
{
    public function change(User $actor, Article $article, string $action, int $expected): void
    {
        Gate::forUser($actor)->authorize($action === 'restore' ? 'restore' : 'trash', $article);
        DB::transaction(function () use ($actor, $article, $action, $expected) {
            $article = Article::lockForUpdate()->findOrFail($article->id);
            Gate::forUser($actor)->authorize($action === 'restore' ? 'restore' : 'trash', $article);
            if ((int) $article->current_revision_id !== $expected) {
                throw ValidationException::withMessages(['article' => 'This article changed. Reload before changing it.']);
            }
            if ($action === 'trash') {
                $article->trashed_at = now();
                $article->published_revision_id = null;
            } else {
                $revision = $article->currentRevision->replicate(['number', 'status', 'reviewed_by', 'reviewed_at', 'published_at', 'review_checklist', 'review_notes']);
                $revision->number = $article->revisions()->max('number') + 1;
                $revision->status = RevisionStatus::Draft;
                $revision->created_by = $actor->id;
                $revision->save();
                $article->current_revision_id = $revision->id;
                $article->trashed_at = null;
                $article->published_revision_id = null;
            }
            $article->save();
            AuditEvent::record($actor, $action === 'trash' ? 'article.trashed' : 'article.untrashed', $article);
        });
    }
}
