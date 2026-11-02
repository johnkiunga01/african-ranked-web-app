<?php

namespace App\Services;

use App\Models\Article;

/**
 * Cheap near-duplicate guard using word-shingle Jaccard similarity.
 * Good enough up to tens of thousands of articles; at larger scale replace
 * with MinHash/LSH or a vector index behind the same method signature.
 */
class SimilarityChecker
{
    public function highestSimilarity(Article $article): float
    {
        $mine = $this->shingles((string) $article->body);
        if (! $mine) {
            return 0.0;
        }

        $query = Article::query()
            ->published()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id ?? 0)
            ->latest('published_at')
            ->limit(200);

        if ($article->canonical_article_id) {
            $query->where('id', '!=', $article->canonical_article_id);
        }

        $max = 0.0;
        foreach ($query->get(['id', 'body', 'canonical_article_id']) as $other) {
            // Regional copies are allowed to match their canonical group.
            if ($article->id && $other->canonical_article_id === $article->id) {
                continue;
            }

            $theirs = $this->shingles((string) $other->body);
            if (! $theirs) {
                continue;
            }

            $intersection = count(array_intersect_key($mine, $theirs));
            $union = count($mine) + count($theirs) - $intersection;
            $max = max($max, $union > 0 ? $intersection / $union : 0.0);
        }

        return round($max, 3);
    }

    /** @return array<int,bool> hashed 5-word shingles */
    private function shingles(string $text, int $size = 5): array
    {
        $words = preg_split('/\W+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        for ($i = 0, $n = count($words) - $size + 1; $i < $n; $i++) {
            $out[crc32(implode(' ', array_slice($words, $i, $size)))] = true;
        }

        return $out;
    }
}
