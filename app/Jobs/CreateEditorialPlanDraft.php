<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\EditorialBrief;
use App\Models\EditorialDraftBatchItem;
use App\Services\EditorialDraftBatchService;
use App\Services\EditorialPlanImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class CreateEditorialPlanDraft implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 90;

    public bool $failOnTimeout = true;

    public function __construct(public int $itemId)
    {
        $this->onConnection('database');
    }

    public function handle(EditorialPlanImport $importer, EditorialDraftBatchService $service): void
    {
        if (! EditorialDraftBatchItem::whereKey($this->itemId)->where('status', 'queued')
            ->update(['status' => 'running', 'started_at' => now()])) {
            return;
        }

        try {
            $result = DB::transaction(function () use ($importer, $service): array {
                $item = EditorialDraftBatchItem::with('batch.user')->find($this->itemId);
                if (! $item || ! $item->batch || ! $item->batch->user) {
                    return ['skipped', 'Batch owner is no longer available.', null];
                }
                $actor = $item->batch->user;
                if (! $actor->is_active || ! $actor->hasVerifiedEmail()
                    || (int) $actor->session_version !== (int) $item->batch->session_version
                    || ! Gate::forUser($actor)->allows('manage-staff')) {
                    return ['skipped', 'Authorisation changed after the batch was queued.', null];
                }

                $brief = EditorialBrief::whereKey($item->editorial_brief_id)->lockForUpdate()->first();
                if (! $brief || $brief->source_hash !== $item->batch->source_hash) {
                    return ['skipped', 'The imported brief changed after the batch was queued.', null];
                }
                if (! $service->eligible($brief)) {
                    return ['skipped', 'Already linked, held, or no private team draft is available.', null];
                }
                if (Article::where('slug', $brief->slug)->exists()) {
                    return ['skipped', 'An article with this slug already exists. No revision was changed.', null];
                }

                // start() uses the normal workflow, creating exactly one draft
                // revision. It never calls review, approval, or publication.
                $article = $importer->start($actor, $brief);
                if ((int) $brief->fresh()->article_id !== (int) $article->id || $article->published_revision_id) {
                    return ['skipped', 'An existing article was found. No publication decision changed.', null];
                }

                return ['created', 'Private draft created; editorial review remains required.', $article->id];
            });

            $this->finish(...$result);
        } catch (UniqueConstraintViolationException) {
            $this->finish('skipped', 'A concurrent article or association already exists. No duplicate was created.');
        } catch (Throwable) {
            // Avoid leaking private research, credentials, SQL or exception traces.
            $this->finish('failed', 'Draft creation stopped. Inspect this brief before retrying it manually.');
        }
    }

    private function finish(string $status, string $message, ?int $articleId = null): void
    {
        EditorialDraftBatchItem::whereKey($this->itemId)->whereIn('status', ['queued', 'running'])->update([
            'status' => $status,
            'message' => $message,
            'article_id' => $articleId,
            'finished_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->finish('failed', 'The worker stopped. No automatic retry was attempted.');
    }
}
