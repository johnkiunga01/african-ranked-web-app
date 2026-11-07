<?php

namespace App\Jobs;

use App\Exceptions\OpenSeoConnectionException;
use App\Models\ArticleSeoBatchItem;
use App\Services\ArticleResearcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Gate;
use Throwable;

class ResearchArticleSeo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 90;

    public bool $failOnTimeout = true;

    public function __construct(public int $itemId)
    {
        $this->onConnection('database');
    }

    public function handle(ArticleResearcher $researcher): void
    {
        $item = ArticleSeoBatchItem::with(['article', 'batch.user'])->find($this->itemId);
        if (! $item || $item->status !== 'queued') {
            return;
        }
        $actor = $item->batch->user;
        if (! $actor || (int) $actor->session_version !== (int) $item->batch->session_version || ! Gate::forUser($actor)->allows('curate', $item->article) || (int) $item->article->current_revision_id !== (int) $item->source_revision_id) {
            $item->update(['status' => 'skipped', 'message' => 'The revision or access changed. No research request was made.', 'finished_at' => now()]);

            return;
        }
        if (! ArticleSeoBatchItem::whereKey($item->id)->where('status', 'queued')->update(['status' => 'researching', 'started_at' => now()])) {
            return;
        }
        $item->refresh();
        try {
            $researcher->research($actor, $item->article);
            $item->update(['status' => 'queued', 'message' => 'OpenSEO brief ready. Waiting for Gemini.']);
            OptimiseArticleSeo::dispatch($item->id);
        } catch (OpenSeoConnectionException $exception) {
            $item->update(['status' => 'failed', 'message' => $exception->getMessage().' Gemini was not called.', 'finished_at' => now()]);
        } catch (Throwable) {
            $this->failed(null);
        }
    }

    public function failed(?Throwable $exception): void
    {
        ArticleSeoBatchItem::whereKey($this->itemId)->whereIn('status', ['queued', 'researching'])->update(['status' => 'failed', 'message' => 'OpenSEO research did not complete. Gemini was not called. Check project settings, provider access and credits.', 'finished_at' => now()]);
    }
}
