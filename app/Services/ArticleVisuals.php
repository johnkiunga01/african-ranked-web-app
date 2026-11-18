<?php

namespace App\Services;

class ArticleVisuals
{
    public function showInline(?string $cover, ?string $candidate, array $alreadyShown = []): bool
    {
        if (! $candidate || ! filter_var($candidate, FILTER_VALIDATE_URL)
            || parse_url($candidate, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        $identity = $this->identity($candidate);
        if ($identity === $this->identity($cover)) {
            return false;
        }

        if (in_array($identity, array_map(fn (string $url) => $this->identity($url), $alreadyShown), true)) {
            return false;
        }

        $coverArt = $this->artSlug($cover);
        $detailArt = $this->artSlug($candidate);
        if ($detailArt !== null && in_array($detailArt, array_map(fn (string $url) => $this->artSlug($url), $alreadyShown), true)) {
            return false;
        }

        // In-house cover/detail SVG variants currently repeat the same visual
        // motif and headline. Show the cover once, not multiple near copies.
        return $coverArt === null || $coverArt !== $detailArt;
    }

    /** Read-time filtering does not delete an editor's original revision data. */
    public function filterBody(?string $cover, array $blocks): array
    {
        $seen = [];
        $filtered = [];
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) === 'image') {
                if (! $this->showInline($cover, $block['url'] ?? null, $seen)) {
                    continue;
                }
                $seen[] = $block['url'];
            }
            $filtered[] = $block;
        }

        return $filtered;
    }

    private function identity(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return null;
        }

        return mb_strtolower(($parts['host'] ?? '').($parts['path'] ?? ''));
    }

    private function artSlug(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }

        return preg_match('~/editorial-art/([a-z0-9-]+)/(?:cover|detail-[1-9][0-9]?)\.svg$~', $path, $m)
            ? $m[1]
            : null;
    }
}
