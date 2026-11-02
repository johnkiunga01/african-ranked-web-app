<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RepositoryArticleSync
{
    public function run(?string $ownerEmail = null): int
    {
        $owner = null;
        if ($ownerEmail) {
            $owner = User::where('email', mb_strtolower(trim($ownerEmail)))->first();
            if (! $owner?->isSuperAdmin()) {
                throw new RuntimeException('The configured repository owner must be an active, verified super admin.');
            }
        }

        return DB::transaction(function () use ($owner) {
            $catalog = app(Catalog::class);
            foreach ($catalog->data('categories') as $category) {
                Category::firstOrCreate(['slug' => $category['slug']], $category);
            }
            $count = 0;
            foreach ($catalog->data('articles') as $record) {
                if (Article::where('slug', $record['slug'])->exists()) {
                    continue;
                }
                app(SeedArticleImporter::class)->import($record, $owner);
                $count++;
            }

            return $count;
        });
    }
}
