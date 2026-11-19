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
    /**
     * Read-only reconciliation for the preview. An old plan with the same slug
     * is the same editorial topic, even when a later workbook assigns a new ID.
     */
    public function inspect(array $records): array
    {
        $byId = EditorialBrief::whereIn('plan_id', array_column($records, 'plan_id'))->get()->keyBy('plan_id');
        $bySlug = EditorialBrief::whereIn('slug', array_column($records, 'slug'))->get()->keyBy('slug');
        $articleSlugs = Article::whereIn('slug', array_column($records, 'slug'))->pluck('id', 'slug');
        $counts = ['new' => 0, 'existing' => 0, 'reused_slug' => 0, 'linked_article' => 0, 'conflict' => 0];
        $items = [];

        foreach ($records as $record) {
            $plan = $byId->get($record['plan_id']);
            $slug = $bySlug->get($record['slug']);
            $status = 'new';
            $matchedId = null;

            if (($plan && $plan->slug !== $record['slug'])
                || ($plan && $slug && $plan->id !== $slug->id)) {
                $status = 'conflict';
                $matchedId = $plan?->plan_id;
            } elseif ($plan) {
                $status = 'existing';
                $matchedId = $plan->plan_id;
            } elseif ($slug) {
                $status = 'reused_slug';
                $matchedId = $slug->plan_id;
            } elseif ($articleSlugs->has($record['slug'])) {
                $status = 'linked_article';
            }

            $counts[$status]++;
            $items[] = ['status' => $status, 'matched_id' => $matchedId];
        }

        return ['counts' => $counts, 'items' => $items];
    }

    /**
     * Reconcile by immutable slug. Preserve existing plan IDs, linked articles,
     * already-written team drafts and every editorial/publication revision.
     */
    public function commit(User $actor, array $intake): array
    {
        Gate::forUser($actor)->authorize('manage-staff');

        return DB::transaction(function () use ($actor, $intake) {
            User::lockForUpdate()->findOrFail($actor->id);
            $counts = ['new' => 0, 'existing' => 0, 'reused_slug' => 0, 'linked_article' => 0];

            foreach ($intake['records'] as $record) {
                $byId = EditorialBrief::where('plan_id', $record['plan_id'])->lockForUpdate()->first();
                $bySlug = EditorialBrief::where('slug', $record['slug'])->lockForUpdate()->first();

                // Never silently repurpose an established ID for a different URL.
                if ($byId && $byId->slug !== $record['slug']) {
                    throw ValidationException::withMessages([
                        'workbook' => "Plan {$record['plan_id']} already belongs to {$byId->slug}, not {$record['slug']}. No briefs were changed.",
                    ]);
                }
                if ($byId && $bySlug && $byId->id !== $bySlug->id) {
                    throw ValidationException::withMessages([
                        'workbook' => "Plan {$record['plan_id']} and slug {$record['slug']} belong to different existing briefs. No briefs were changed.",
                    ]);
                }

                $existing = $byId ?? $bySlug;
                $matchedBySlug = ! $byId && (bool) $bySlug;

                if ($existing && filled($existing->plan['team_draft'] ?? '')) {
                    // A later research workbook must not overwrite a team's
                    // authored manuscript, even if it also contains draft text.
                    $record['plan']['team_draft'] = $existing->plan['team_draft'];
                    $record['plan']['team_notes'] = $existing->plan['team_notes'] ?? '';
                    $record['plan']['team_status'] = $existing->plan['team_status'] ?? '';
                }

                if ($matchedBySlug) {
                    // The incoming workbook ID is provenance, not a new primary
                    // key. Keep the original ID and the existing CMS association.
                    $record['plan']['source_workbook_plan_id'] = $record['plan_id'];
                }

                $articleId = $existing?->article_id ?? Article::where('slug', $record['slug'])->value('id');
                $values = $record + [
                    'source_hash' => $intake['hash'],
                    'imported_by' => $actor->id,
                    'article_id' => $articleId,
                ];

                if ($existing) {
                    $values['plan_id'] = $existing->plan_id;
                    $existing->update($values);
                    $counts[$matchedBySlug ? 'reused_slug' : 'existing']++;
                } else {
                    EditorialBrief::create($values);
                    $counts[$articleId ? 'linked_article' : 'new']++;
                }
            }

            AuditEvent::record($actor, 'editorial_plan.imported', $actor, [
                'sha256' => $intake['hash'],
                'briefs' => count($intake['records']),
                'team_drafts' => $intake['drafts'],
                'reconciliation' => $counts,
            ]);

            return $counts;
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
