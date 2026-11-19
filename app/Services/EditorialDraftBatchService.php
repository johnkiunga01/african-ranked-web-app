<?php

namespace App\Services;

use App\Jobs\CreateEditorialPlanDraft;
use App\Models\Article;
use App\Models\AuditEvent;
use App\Models\EditorialBrief;
use App\Models\EditorialDraftBatch;
use App\Models\EditorialDraftBatchItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EditorialDraftBatchService
{
    /** Return recent imported workbooks, without exposing research drafts publicly. */
    public function sources(): Collection
    {
        return EditorialBrief::query()
            ->select('source_hash')
            ->selectRaw('COUNT(*) as brief_count, MAX(updated_at) as last_imported_at')
            ->groupBy('source_hash')
            ->orderByDesc('last_imported_at')
            ->limit(25)
            ->get()
            ->map(function ($source) {
                $first = EditorialBrief::where('source_hash', $source->source_hash)->first();
                $source->filename = $first?->plan['file'] ?? 'Imported editorial workbook';

                return $source;
            });
    }

    /** Calculate the exact same safe eligibility as the queue operation. */
    public function inspect(string $sourceHash): array
    {
        $briefs = EditorialBrief::where('source_hash', $sourceHash)->orderBy('plan_id')->get();
        if ($briefs->isEmpty()) {
            throw ValidationException::withMessages(['source_hash' => 'The selected workbook was not found. Reload the editorial plan.']);
        }

        return $this->classify($briefs);
    }

    /** One deliberate, authorized action queues all eligible private drafts. */
    public function queue(User $actor, string $sourceHash): EditorialDraftBatch
    {
        Gate::forUser($actor)->authorize('manage-staff');

        return DB::transaction(function () use ($actor, $sourceHash) {
            // Serialize admin batch submissions and the same brief selection.
            User::lockForUpdate()->findOrFail($actor->id);
            $ongoing = EditorialDraftBatch::where('source_hash', $sourceHash)
                ->whereHas('items', fn ($query) => $query->whereIn('status', ['queued', 'running']))
                ->latest('id')->first();
            if ($ongoing) {
                return $ongoing;
            }

            $briefs = EditorialBrief::where('source_hash', $sourceHash)->orderBy('id')->lockForUpdate()->get();
            if ($briefs->isEmpty()) {
                throw ValidationException::withMessages(['source_hash' => 'The imported workbook is no longer available.']);
            }
            $inspection = $this->classify($briefs);
            $eligible = collect($inspection['items'])->where('status', 'ready');
            if ($eligible->isEmpty()) {
                throw ValidationException::withMessages(['source_hash' => 'No safe unlinked private drafts remain in this workbook. Existing articles and held topics were left unchanged.']);
            }

            $batch = EditorialDraftBatch::create([
                'user_id' => $actor->id,
                'session_version' => (int) $actor->session_version,
                'source_hash' => $sourceHash,
                'total' => $eligible->count(),
            ]);
            foreach ($eligible as $entry) {
                $item = $batch->items()->create(['editorial_brief_id' => $entry['brief']->id, 'status' => 'queued']);
                // Database queue rows and the batch must commit atomically.
                CreateEditorialPlanDraft::dispatch($item->id)->beforeCommit();
            }
            AuditEvent::record($actor, 'editorial_plan.draft_batch_queued', $batch, [
                'source_hash' => $sourceHash, 'total' => $batch->total,
            ]);

            return $batch;
        });
    }

    public function eligible(EditorialBrief $brief): bool
    {
        if ($brief->article_id || filled($brief->plan['related_existing_article_id'] ?? null)
            || ! filled($brief->plan['team_draft'] ?? '')) {
            return false;
        }
        $decision = mb_strtoupper(trim($brief->plan['fields']['Deduplication decision'] ?? ''));
        if ($decision !== '' && $decision !== 'CREATE_IF_UNIQUE') {
            return false;
        }

        return ! str_contains(mb_strtolower($brief->plan['team_status'] ?? ''), 'hold');
    }

    private function classify(Collection $briefs): array
    {
        $existingSlugs = Article::whereIn('slug', $briefs->pluck('slug')->all())->pluck('id', 'slug');
        $busy = EditorialDraftBatchItem::whereIn('editorial_brief_id', $briefs->pluck('id')->all())
            ->whereIn('status', ['queued', 'running'])->pluck('editorial_brief_id')->flip();
        $counts = ['ready' => 0, 'existing' => 0, 'related' => 0, 'hold' => 0, 'no_draft' => 0, 'active' => 0];
        $items = [];

        foreach ($briefs as $brief) {
            if ($brief->article_id || $existingSlugs->has($brief->slug)) {
                $status = 'existing';
            } elseif (filled($brief->plan['related_existing_article_id'] ?? null)) {
                $status = 'related';
            } elseif ($busy->has($brief->id)) {
                $status = 'active';
            } elseif (! filled($brief->plan['team_draft'] ?? '')) {
                $status = 'no_draft';
            } elseif (! $this->eligible($brief)) {
                $status = 'hold';
            } else {
                $status = 'ready';
            }
            $counts[$status]++;
            $items[] = ['brief' => $brief, 'status' => $status];
        }

        return ['counts' => $counts, 'items' => $items, 'total' => count($items)];
    }
}
