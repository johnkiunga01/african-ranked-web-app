<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLegacyContent extends Command
{
    protected $signature = 'cms:import-legacy {--file= : JSON exported from the existing React content}';

    protected $description = 'Import existing site content once, preserving its public rendering and original dates';

    public function handle(): int
    {
        $file = $this->option('file') ?: database_path('seeders/legacy-content.json');
        $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $count = DB::transaction(function () use ($data) {
            foreach ($data['categories'] as $c) {
                Category::firstOrCreate(['slug' => $c['slug']], array_intersect_key($c, array_flip(['name', 'description'])));
            }
            $count = 0;
            foreach ($data['articles'] as $legacy) {
                if (Article::where('slug', $legacy['slug'])->where('edition', 'global')->exists()) {
                    continue; // Never overwrite edited content or dates on rerun.
                }
                $author = Author::firstOrCreate(['slug' => Str::slug($legacy['author'])], [
                    'name' => $legacy['author'], 'credentials' => $legacy['authorTitle'],
                ]); // No invented biography or reviewer.
                $body = collect($legacy['body'])->map(fn ($b) => match ($b['type']) {
                    'heading' => str_repeat('#', $b['level']).' '.$b['text'],
                    'list' => implode("\n", array_map(fn ($item) => '- '.$item, $b['items'])),
                    'quote' => '> '.$b['text']."\n> ".$b['attribution'],
                    default => $b['text'],
                })->implode("\n\n");
                (new Article)->forceFill([
                    'slug' => $legacy['slug'], 'edition' => 'global',
                    // Conservative initial classification; editorial review can change it.
                    'type' => preg_match('/\b(top|ranked|best|largest)\b/i', $legacy['title']) ? 'ranking' : 'guide',
                    'status' => 'draft', 'indexable' => false, 'legacy_public' => true,
                    'title' => $legacy['title'], 'dek' => $legacy['excerpt'], 'body' => $body,
                    'faq' => $legacy['faqs'] ?? [], 'word_count' => Article::countWords($body),
                    'category_id' => Category::where('slug', $legacy['category'])->firstOrFail()->id,
                    'author_id' => $author->id, 'published_at' => $legacy['datePublished'],
                    'created_at' => $legacy['datePublished'], 'updated_at' => $legacy['dateModified'],
                    'frontend_payload' => $legacy,
                ])->save();
                $count++;
            }

            return $count;
        });
        $this->info("Imported {$count} articles as draft/non-indexable; legacy visibility preserved.");

        return self::SUCCESS;
    }
}
