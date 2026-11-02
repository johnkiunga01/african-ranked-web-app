<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Console\Command;

class FlagStaleArticles extends Command
{
    protected $signature = 'articles:flag-stale';

    protected $description = 'Mark published articles past their re-verification date as needs_update and noindex them';

    public function handle(): int
    {
        $count = Article::where('status', ArticleStatus::Published->value)
            ->where('next_verification_at', '<', now())
            ->update(['status' => ArticleStatus::NeedsUpdate->value, 'indexable' => false]);

        $this->info("Flagged {$count} article(s) for re-verification.");

        return self::SUCCESS;
    }
}
