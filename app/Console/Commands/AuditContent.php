<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Services\PublicationQuality;
use Illuminate\Console\Command;

class AuditContent extends Command
{
    protected $signature = 'content:audit {--repository : Inspect inherited JSON without a database} {--json}';

    protected $description = 'Read-only structural article audit; no import, provider calls or publication';

    public function handle(PublicationQuality $quality): int
    {
        $rows = [];
        if ($this->option('repository')) {
            foreach (json_decode(file_get_contents(base_path('content/articles.json')), true, 512, JSON_THROW_ON_ERROR) as $record) {
                $revision = (new ArticleRevision)->forceFill(['title' => $record['title'], 'excerpt' => $record['excerpt'], 'body' => $record['body'], 'faqs' => $record['faqs'] ?? [], 'source_notes' => null]);
                $rows[] = ['slug' => $record['slug'], 'title' => $record['title'], 'source' => 'repository_unverified', ...$quality->inspect($revision)];
            }
        } else {
            foreach (Article::whereNull('trashed_at')->with('currentRevision')->orderBy('id')->cursor() as $article) {
                if ($article->currentRevision) {
                    $rows[] = ['slug' => $article->slug, 'title' => $article->currentRevision->title, 'revision' => $article->currentRevision->number, 'source' => 'cms_current_revision', ...$quality->inspect($article->currentRevision)];
                }
            }
        }
        $report = ['read_only' => true, 'independent_fact_verification' => false, 'articles' => $rows, 'total' => count($rows),
            'structurally_ready' => count(array_filter($rows, fn ($row) => ! $row['failures']))];
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Slug', 'Words', 'Sources', 'FAQs', 'Blockers'], array_map(fn ($row) => [$row['slug'], $row['body_words'], $row['source_links'], $row['complete_faqs'], implode("\n", $row['failures'])], $rows));
            $this->line('Read-only checks; no facts, sources or permissions independently verified.');
        }

        return $report['structurally_ready'] === $report['total'] ? self::SUCCESS : self::FAILURE;
    }
}
