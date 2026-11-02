<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Validation\ValidationException;

class ArticleImageReuseGuard
{
    public function key(?string $url): ?string
    {
        if (! $url || ! str_starts_with($url, 'https://')) return null;
        $parsed = parse_url($url);
        if (! is_array($parsed) || empty($parsed['host']) || empty($parsed['path'])) return null;
        $host = strtolower($parsed['host']);
        $path = strtolower(rawurldecode($parsed['path']));
        if (str_contains($path, '/editorial-art/')) return $host.$path;
        if (str_contains($host, 'pexels.com') && preg_match('~/photos/(\d+)/~', $path, $matches)) return 'pexels:'.$matches[1];

        return $host.$path;
    }

    private function images(string $body): array
    {
        preg_match_all('~!\[[^\]\r\n]+\]\((https://[^\s)]+)\)~u', $body, $matches);
        return $matches[1] ?? [];
    }

    /** Prevent NEW cross-article stock or author photo reuse; retain older edits. */
    public function assertNoNewCrossArticleDuplicates(array $data, ?Article $article): void
    {
        $urls = array_filter(array_merge([$data['image'] ?? null], $this->images((string) ($data['body_markdown'] ?? ''))));
        if (! $urls) return;
        $existing = [];
        if ($article?->currentRevision) {
            $own = $article->currentRevision;
            foreach (array_merge([$own->image], $this->images((string) $own->body_markdown)) as $url) {
                if ($key = $this->key($url)) $existing[$key] = true;
            }
        }
        $candidates = [];
        foreach ($urls as $url) {
            if (! ($key = $this->key($url)) || isset($existing[$key])) continue;
            $candidates[$key] = true;
        }
        if (! $candidates) return;

        $others = Article::whereNull('trashed_at')
            ->when($article, fn ($query) => $query->where('id', '!=', $article->id))
            ->with(['currentRevision', 'publishedRevision'])->get();
        foreach ($others as $other) {
            foreach ([$other->currentRevision, $other->publishedRevision] as $revision) {
                if (! $revision) continue;
                foreach (array_merge([$revision->image], $this->images((string) $revision->body_markdown)) as $url) {
                    if (($key = $this->key($url)) && isset($candidates[$key])) {
                        throw ValidationException::withMessages([
                            'image' => 'This image is already used by a different article. Choose a unique photo or generate original artwork for this story.',
                        ]);
                    }
                }
            }
        }
    }
}
