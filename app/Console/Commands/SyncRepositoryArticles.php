<?php

namespace App\Console\Commands;

use App\Services\RepositoryArticleSync;
use Illuminate\Console\Command;
use RuntimeException;

class SyncRepositoryArticles extends Command
{
    protected $signature = 'content:sync {--owner= : Active verified super-admin email for newly imported records}';

    protected $description = 'Idempotently sync new repository articles into private CMS drafts; never overwrite editorial revisions.';

    public function handle(RepositoryArticleSync $sync): int
    {
        try {
            $count = $sync->run($this->option('owner') ?: config('editorial.repository_owner'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info($count.' new repository articles synced to private CMS drafts. Existing records and publication decisions preserved.');

        return self::SUCCESS;
    }
}
