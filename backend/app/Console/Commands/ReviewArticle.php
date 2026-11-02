<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Services\PublishingGate;
use Illuminate\Console\Command;

class ReviewArticle extends Command
{
    protected $signature = 'cms:article {id} {action : check|review|approve|publish|index}';

    protected $description = 'Human-operated editorial workflow; agents have no access to these actions';

    public function handle(): int
    {
        $article = Article::findOrFail($this->argument('id'));
        $action = $this->argument('action');
        if (! in_array($action, ['check', 'review', 'approve', 'publish', 'index'], true)) {
            $this->error('Use check, review, approve, publish or index.');

            return self::FAILURE;
        }
        if ($action === 'check') {
            $failures = app(PublishingGate::class)->evaluate($article);
            foreach ($failures as $failure) {
                $this->line($failure);
            }
            if (! $failures) {
                $this->info('All publishing gates passed. Human source review is still required.');
            }

            return $failures ? self::FAILURE : self::SUCCESS;
        }
        if ($action === 'review') {
            if (! in_array($article->status, [ArticleStatus::Draft, ArticleStatus::NeedsUpdate], true)) {
                $this->error('Only a draft or stale article can enter review.');

                return self::FAILURE;
            }
            $article->update(['status' => ArticleStatus::InReview, 'indexable' => false]);
        }
        if ($action === 'approve') {
            if ($article->status !== ArticleStatus::InReview) {
                $this->error('The article must be in review before approval.');

                return self::FAILURE;
            }
            if (! $article->reviewer?->is_reviewer) {
                $this->error('Assign a named reviewer first.');

                return self::FAILURE;
            }
            if (! $this->confirm('Have you opened every cited source and resolved every verification flag?', false)) {
                return self::FAILURE;
            }
            $article->update(['status' => ArticleStatus::Approved]);
        }
        try {
            if ($action === 'publish') {
                if ($article->status !== ArticleStatus::Approved) {
                    $this->error('The article must be approved before publishing.');

                    return self::FAILURE;
                }
                $article->publish();
            }
            if ($action === 'index') {
                if (! $this->confirm('Has this published page passed the separate indexing audit?', false)) {
                    return self::FAILURE;
                }
                $article->markIndexable();
            }
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info('Article '.$article->id.': '.$article->status->value.'; indexable='.($article->indexable ? 'true' : 'false'));

        return self::SUCCESS;
    }
}
