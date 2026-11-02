<?php

namespace App\Jobs;

use App\Models\ArticleSeoBatchItem;
use App\Services\SeoAutoFix;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class OptimiseArticleSeo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public int $deadline;

    public function __construct(public int $itemId)
    {
        $this->deadline = now()->addHours(6)->timestamp;
        $this->onConnection('database');
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->setTimestamp($this->deadline);
    }

    public function handle(SeoAutoFix $fixer): void
    {
        $item = ArticleSeoBatchItem::with(['batch.user', 'article'])->find($this->itemId);
        if (! $item || $item->status !== 'queued') {
            return;
        }
        $actor = $item->batch->user;
        $article = $item->article;
        if (! $actor || (int) $actor->session_version !== (int) $item->batch->session_version || ! Gate::forUser($actor)->allows('curate', $article)) {
            $this->finish('skipped', 'Access changed. No Gemini request was made.');

            return;
        }
        if ((int) $article->current_revision_id !== (int) $item->source_revision_id) {
            $this->finish('skipped', 'A newer revision exists. Select it again to optimise.');

            return;
        }
        // Release only before claiming an item. Provider failures are never retried.
        $rateKey = 'gemini-bulk-provider';
        if (! RateLimiter::attempt($rateKey, 5, fn () => true, 60)) {
            $this->release(max(5, RateLimiter::availableIn($rateKey) + 1));

            return;
        }
        if (! ArticleSeoBatchItem::whereKey($item->id)->where('status', 'queued')->update(['status' => 'running', 'started_at' => now(), 'updated_at' => now()])) {
            return;
        }
        try {
            $changes = $fixer->apply($actor, $article, (int) $item->source_revision_id, true);
            $this->finish('completed', count($changes) ? 'SEO improvements saved as a new draft.' : 'No additional metadata changes were needed.', (int) $article->fresh()->current_revision_id);
        } catch (ValidationException $exception) {
            $this->finish('failed', mb_substr(implode(' ', $exception->validator->errors()->all()), 0, 500));
        } catch (\RuntimeException $exception) {
            // GeminiAssistant exposes only curated, secret-free messages.
            $this->finish('failed', mb_substr($exception->getMessage(), 0, 500));
        } catch (Throwable) {
            $this->finish('failed', 'Optimisation stopped unexpectedly. No automatic retry was made. Check the article before trying a new revision.');
        }
    }

    private function finish(string $status, string $message, ?int $revision = null): void
    {
        ArticleSeoBatchItem::whereKey($this->itemId)->whereIn('status', ['queued', 'running'])->update(['status' => $status, 'message' => $message, 'result_revision_id' => $revision, 'finished_at' => now(), 'updated_at' => now()]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->finish('failed', 'The worker stopped or the queue deadline expired. No automatic Gemini retry was made. Check the saved revision before creating another request.');
    }
}
