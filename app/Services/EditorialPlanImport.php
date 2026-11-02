<?php

namespace App\Services;

use App\Models\Article;
use App\Models\AuditEvent;
use App\Models\EditorialBrief;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EditorialPlanImport
{
    public function commit(User $actor, array $intake): int
    {
        Gate::forUser($actor)->authorize('manage-staff');

        return DB::transaction(function () use ($actor, $intake) {
            User::lockForUpdate()->findOrFail($actor->id);
            foreach ($intake['records'] as $record) {
                $existing = EditorialBrief::where('plan_id', $record['plan_id'])->orWhere('slug', $record['slug'])->lockForUpdate()->get();
                foreach ($existing as $brief) {
                    if ($brief->plan_id !== $record['plan_id'] || $brief->slug !== $record['slug']) {
                        throw ValidationException::withMessages(['workbook' => 'An existing plan ID or slug has a different mapping. Resolve it before importing. No briefs were changed.']);
                    }
                    if (! filled($record['plan']['team_draft'] ?? '') && filled($brief->plan['team_draft'] ?? '')) {
                        $record['plan']['team_draft'] = $brief->plan['team_draft'];
                        $record['plan']['team_notes'] = $brief->plan['team_notes'] ?? '';
                        $record['plan']['team_status'] = $brief->plan['team_status'] ?? '';
                    }
                }
                // Re-imports update planning data only; article bodies, owners and publication stay untouched.
                $linked = $existing->first()?->article_id ?? Article::where('slug', $record['slug'])->value('id');
                EditorialBrief::updateOrCreate(['plan_id' => $record['plan_id']], $record + ['source_hash' => $intake['hash'], 'imported_by' => $actor->id, 'article_id' => $linked]);
            }
            AuditEvent::record($actor, 'editorial_plan.imported', $actor, ['sha256' => $intake['hash'], 'briefs' => count($intake['records']), 'team_drafts' => $intake['drafts']]);

            return count($intake['records']);
        });
    }

    public function start(User $actor, EditorialBrief $brief): Article
    {
        Gate::forUser($actor)->authorize('manage-staff');

        return DB::transaction(function () use ($actor, $brief) {
            $brief = EditorialBrief::lockForUpdate()->findOrFail($brief->id);
            if ($article = $brief->article ?? Article::where('slug', $brief->slug)->first()) {
                return $article; // Existing editorial work always wins over workbook content.
            }
            $article = app(EditorialWorkflow::class)->save($actor, [
                'slug' => $brief->slug, 'title' => $brief->title, 'excerpt' => '', 'category_slug' => $brief->category_slug,
                'focus_keyword' => $brief->keyword, 'body_markdown' => $brief->plan['team_draft'] ?? '',
                'source_notes' => $brief->instructions(), 'image' => null,
            ]);
            $brief->update(['article_id' => $article->id]);
            AuditEvent::record($actor, 'editorial_plan.draft_started', $article, ['plan_id' => $brief->plan_id, 'source_hash' => $brief->source_hash]);

            return $article;
        });
    }
}
