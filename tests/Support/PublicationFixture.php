<?php

namespace Tests\Support;

use App\Models\Article;
use App\Services\MarkdownBlocks;

/** Synthetic text for disposable tests only. Never called by application seed/import code. */
class PublicationFixture
{
    public static function body(string $prefix = ''): string
    {
        $text = $prefix."\n\n".str_repeat('Synthetic fixture explanation exercises editorial controls in the isolated test database. ', 120);
        for ($i = 1; $i <= 10; $i++) {
            $text .= "\n\n## {$i}. Synthetic entry {$i}\n\n".str_repeat('Synthetic entry details support workflow checks without claiming factual reporting. ', 7);
        }
        $text .= "\n\n## Methodology\n\nSynthetic test-only methodology, not reporting.\n\n";
        for ($i = 1; $i <= 3; $i++) {
            $text .= "\n\n[Test source {$i}](https://source.example.test/{$i})";
        }

        return $text;
    }

    public static function faqs(array $existing = []): array
    {
        while (count($existing) < 3) {
            $existing[] = ['question' => 'Synthetic test question '.(count($existing) + 1).'?', 'answer' => 'This is isolated test-fixture text.'];
        }

        return $existing;
    }

    public static function prepare(Article $article): void
    {
        $revision = $article->currentRevision;
        $markdown = self::body();
        $revision->update(['body_markdown' => $markdown, 'body' => app(MarkdownBlocks::class)->parse($markdown), 'faqs' => self::faqs($revision->faqs ?? [])]);
        $article->unsetRelation('currentRevision');
    }
}
