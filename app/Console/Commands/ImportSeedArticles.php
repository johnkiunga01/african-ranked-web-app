<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSeedArticles extends Command
{
    protected $signature = 'content:import-seeds';

    protected $description = 'Import the inherited catalog as unapproved drafts, preserving existing CMS records.';

    public function handle(Catalog $catalog): int
    {
        $count = DB::transaction(function () use ($catalog) {
            foreach ($catalog->data('categories') as $category) {
                Category::firstOrCreate(['slug' => $category['slug']], $category);
            }
            $count = 0;
            foreach ($catalog->data('articles') as $record) {
                if (Article::where('slug', $record['slug'])->exists()) {
                    continue;
                }
                app(SeedArticleImporter::class)->import($record);
                $count++;
            }

            return $count;
        });
        $this->info($count.' inherited articles imported as private, unassigned drafts. No existing CMS record changed.');

        return self::SUCCESS;
    }
}
