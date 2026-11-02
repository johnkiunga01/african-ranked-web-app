<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewedPublisher
{
    public function publish(User $actor, Article $article, int $expected): Article
    {
        Gate::forUser($actor)->authorize('publish', $article);

        return DB::transaction(function () use ($actor, $article, $expected) {
            $article = Article::lockForUpdate()->findOrFail($article->id);
            if ($article->trashed_at || (int) $article->current_revision_id !== $expected) {
                throw ValidationException::withMessages(['workflow' => 'An article changed or moved to Trash. Reload the list before publishing.']);
            }
            // Resubmitting the same approved publication must not change its date.
            if ((int) $article->published_revision_id === $expected) {
                return $article;
            }
            $workflow = app(EditorialWorkflow::class);
            $state = $article->currentRevision->status->value;
            if (in_array($state, ['draft', 'changes_requested'], true)) {
                $workflow->transition($actor, $article, 'submit', $expected);
                $state = 'in_review';
            }
            if ($state === 'in_review') {
                $workflow->transition($actor, $article, 'approve', $expected, array_fill_keys(EditorialWorkflow::CHECKS, true));
            }

            return $workflow->transition($actor, $article, 'publish', $expected);
        });
    }
}
