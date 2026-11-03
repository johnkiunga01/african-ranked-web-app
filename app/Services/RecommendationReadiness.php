<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleRevision;

class RecommendationReadiness
{
    public function candidate(Article $article, array $draft): ArticleRevision
    {
        $prior = $article->currentRevision;

        $body = app(MarkdownBlocks::class)->parse($draft['body_markdown']);
        $images = collect($prior->body ?? [])->where('type', 'image')->keyBy('url');
        foreach ($body as &$block) {
            if (($block['type'] ?? '') === 'image' && $images->has($block['url'])) {
                $block = array_replace($images[$block['url']], $block);
            }
        }
        unset($block);

        return (new ArticleRevision)->forceFill([
            'article_id' => $article->id, 'title' => $draft['title'], 'excerpt' => $draft['excerpt'],
            'body' => $body,
            'body_markdown' => $draft['body_markdown'], 'image' => $prior->image,
            'faqs' => app(FaqEditor::class)->parse($draft['faqs_text'] ?? ''),
            'editorial_evidence' => $draft['editorial_evidence'] ?? $prior->editorial_evidence,
            'source_notes' => $prior->source_notes,
        ])->setRelation('article', $article);
    }

    public function blockers(Article $article, array $draft): array
    {
        $candidate = $this->candidate($article, $draft);
        $failures = app(PublicationQuality::class)->inspect($candidate)['failures'];
        // Author acceptance is not a substitute for recorded source and rights evidence.
        $failures = array_merge($failures, app(EditorialEvidence::class)->inspect($candidate->editorial_evidence));
        if (! $candidate->image) {
            $failures[] = 'Add a cover image with documented permission.';
        }
        $original = $article->currentRevision;
        $beforeImages = collect($original->body ?? [])->where('type', 'image')->pluck('url')->all();
        $afterImages = collect($candidate->body ?? [])->where('type', 'image')->pluck('url')->all();
        if ($beforeImages !== $afterImages) {
            $failures[] = 'Keep the original article photos before accepting this suggestion.';
        }
        $known = collect($original->body ?? [])->where('type', 'link')->pluck('url')->merge(collect($original->editorial_evidence['sources'] ?? [])->pluck('url'))->all();
        foreach (collect($candidate->body ?? [])->where('type', 'link') as $link) {
            if (str_starts_with($link['url'], 'https://') && ! in_array($link['url'], $known, true)) {
                $failures[] = 'A new source link needs your verification before publication.';
            }
        }

        return array_values(array_unique($failures));
    }

    public function tasks(array $failures): array
    {
        return collect($failures)->map(fn ($detail) => ['label' => match (true) {
            str_contains($detail, 'body has') => 'Add more evidence-backed detail',
            str_contains($detail, 'direct answer') => 'Check the opening answer',
            str_contains($detail, 'key facts') => 'Check the key facts',
            str_contains($detail, 'last-verified') => 'Record when the facts were checked',
            str_contains($detail, 'image') || str_contains($detail, 'photos') => 'Check image permission and photos',
            str_contains($detail, 'source') || str_contains($detail, 'cited') => 'Check and record the sources',
            str_contains($detail, 'FAQ') => 'Complete the reader questions',
            str_contains($detail, 'position') || str_contains($detail, 'entries') || str_contains($detail, 'Ranking') => 'Finish the promised ranking',
            str_contains($detail, 'methodology') || str_contains($detail, 'criteria') => 'Explain how the ranking was decided',
            default => 'Finish the remaining editorial check',
        }, 'detail' => $detail])->unique('label')->values()->all();
    }

    public function changes(ArticleRevision $original, array $draft): array
    {
        $changes = [];
        foreach (['title' => 'Article title', 'excerpt' => 'Opening summary', 'body_markdown' => 'Article structure and wording', 'seo_title' => 'Search title', 'meta_description' => 'Search description'] as $field => $label) {
            if (($draft[$field] ?? '') !== ($original->$field ?? '')) {
                $changes[] = $label;
            }
        }
        if (app(FaqEditor::class)->parse($draft['faqs_text'] ?? '') !== ($original->faqs ?? [])) {
            $changes[] = 'Reader questions and answers';
        }
        if (($draft['editorial_evidence']['answer'] ?? null) !== ($original->editorial_evidence['answer'] ?? null)) {
            $changes[] = 'Direct answer';
        }

        return $changes;
    }
}
