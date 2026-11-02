<?php

namespace App\Console\Commands;

use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\AuditEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeOnsiteArticles extends Command
{
    protected $signature = 'content:optimize-onsite';

    protected $description = 'Fill missing search metadata in new draft revisions without modifying published content or factual claims.';

    public function handle(): int
    {
        $count = 0;
        Article::whereHas('currentRevision', fn ($q) => $q->whereNull('seo_title')->orWhereNull('meta_description'))->orderBy('id')->chunkById(50, function ($articles) use (&$count) {
            foreach ($articles as $candidate) {
                DB::transaction(function () use ($candidate, &$count) {
                    $article = Article::lockForUpdate()->findOrFail($candidate->id);
                    $prior = $article->currentRevision;
                    if ($prior->seo_title !== null && $prior->meta_description !== null) {
                        return;
                    }
                    $revision = $prior->replicate(['number', 'status', 'reviewed_by', 'reviewed_at', 'published_at', 'review_checklist', 'review_notes']);
                    $revision->number = $article->revisions()->max('number') + 1;
                    $revision->status = RevisionStatus::Draft;
                    $revision->created_by = null;
                    $revision->save();
                    $article->current_revision_id = $revision->id;
                    $article->save();
                    AuditEvent::record(null, 'article.onsite_optimized', $article, ['revision' => $revision->number]);
                    $count++;
                });
            }
        });
        $this->info($count.' articles received metadata defaults in new private revisions. Published pointers, content and claims preserved.');

        return self::SUCCESS;
    }
}
