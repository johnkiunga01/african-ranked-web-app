<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Support\Catalog;
use Illuminate\Support\Str;

class ArticleSeoAnalysis
{
    public function analyze(Article $article, ArticleRevision $revision): array
    {
        $title = $revision->seo_title ?: $revision->title.' | African Ranked';
        $description = $revision->meta_description ?: $revision->excerpt;
        $body = collect($revision->body ?? []);
        $text = $body->map(fn ($b) => $b['text'] ?? implode(' ', $b['items'] ?? []))->implode(' ');
        $headings = $body->where('type', 'heading')->values();
        $links = $body->where('type', 'link')->values();
        $findings = [];
        $add = function ($severity, $label, $detail) use (&$findings) {
            $findings[] = compact('severity', 'label', 'detail');
        };
        if (! $body->count()) {
            $add('error', 'Article body missing', 'Write content that answers the reader’s intent before review.');
        }
        if (mb_strlen($title) > 65) {
            $add('review', 'Search title may truncate', 'The effective title is '.mb_strlen($title).' characters. Inspect the preview; width and query affect truncation. This is a heuristic, not a ranking rule.');
        }
        if (! filled($description)) {
            $add('error', 'Search description missing', 'Add a useful summary of what this article delivers.');
        } elseif (mb_strlen($description) > 170) {
            $add('review', 'Search description is long', 'The effective description is '.mb_strlen($description).' characters. Search engines may truncate or rewrite it.');
        }
        if ($headings->isEmpty()) {
            $add('review', 'No section headings', 'Use descriptive H2 headings so readers can scan the article.');
        }
        if ($headings->isNotEmpty() && ($headings->first()['level'] ?? 2) === 3) {
            $add('review', 'Heading hierarchy starts at H3', 'Start the body with H2 sections below the page H1.');
        }
        if (! $revision->image) {
            $add('error', 'Hero image missing', 'Add an image with documented permission.');
        } elseif (! filled($revision->image_alt)) {
            $add('review', 'Image description needs review', 'Add descriptive alternative text. The current fallback repeats the title; confirm it describes the actual image.');
        }
        if (! filled($revision->source_notes) || str_contains($revision->source_notes ?? '', 'Claims, ranking completeness')) {
            $add('review', 'Research evidence needs review', 'Document sources, verification dates, ranking methodology and image rights; inherited import notes do not verify reporting.');
        }
        if (! $article->user_id) {
            $add('error', 'No assigned author', 'Assign an active, verified staff member before publication.');
        }
        $keyword = trim($revision->focus_keyword ?? '');
        if (! $keyword) {
            $add('review', 'Search intent not recorded', 'Record the intended query after research. This field has no search-volume or ranking guarantee.');
        } elseif (! Str::contains(mb_strtolower($revision->title.' '.$text), mb_strtolower($keyword))) {
            $add('review', 'Target phrase is absent', 'Check topic alignment and natural wording. Exact-match repetition is not required and should not be forced.');
        }
        $ranked = $headings->filter(fn ($h) => preg_match('/^\d+[.)]\s/u', $h['text']))->count();
        if (preg_match('/\b(?:top|best)\s+(\d+)\b|\b(\d+)\s+(?:best|most|top)\b/iu', $revision->title, $m)) {
            $promised = (int) (($m[1] ?? '') ?: ($m[2] ?? 0));
            if ($promised > $ranked) {
                $add('review', 'Ranking completeness needs checking', 'Title promises '.$promised.' entries; '.$ranked.' numbered headings detected. This detector cannot verify whether the entries are distinct or correct.');
            }
        }
        if ($links->isEmpty()) {
            $add('review', 'No structured article links', 'Add relevant internal reading links and primary sources using the link block format. Link checks cover supported link blocks only.');
        }
        $publicPaths = app(Catalog::class)->articles()->map(fn ($a) => '/articles/'.$a['slug'])->all();
        foreach ($links as $link) {
            $url = $link['url'];
            $path = parse_url($url, PHP_URL_PATH);
            $local = str_starts_with($url, '/') || parse_url($url, PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST);
            if ($local && str_starts_with($path ?? '', '/articles/') && ! in_array(rtrim($path, '/'), $publicPaths, true)) {
                $add('review', 'Internal article target is not public', $url.' is not in the current public catalog. No external URL was fetched.');
            }
        }
        if (! $revision->reviewed_at) {
            $add('review', 'Editorial approval outstanding', 'Field checks do not validate facts, originality, bylines, rights or usefulness. Complete human review.');
        }

        return [
            'title' => $title, 'description' => $description,
            'canonical' => app(Catalog::class)->url('/articles/'.$article->slug),
            'wordCount' => preg_match_all('/[\pL\pN]+(?:[’\x{0027}-][\pL\pN]+)*/u', $text),
            'headingCount' => $headings->count(), 'linkCount' => $links->count(), 'faqCount' => count($revision->faqs ?? []),
            'findings' => $findings, 'errors' => count(array_filter($findings, fn ($f) => $f['severity'] === 'error')),
        ];
    }
}
