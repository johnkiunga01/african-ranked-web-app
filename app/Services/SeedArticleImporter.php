<?php

namespace App\Services;

use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SeedArticleImporter
{
    public function import(array $record, ?User $actor = null): Article
    {
        return DB::transaction(function () use ($record, $actor) {
            $article = Article::firstOrCreate(['slug' => $record['slug']]);
            if (! $article->wasRecentlyCreated) {
                return $article;
            }
            $markdown = collect($record['body'])->map(fn ($block) => match ($block['type']) {
                'heading' => str_repeat('#', $block['level']).' '.$block['text'],
                'list' => collect($block['items'])->map(fn ($item) => '- '.$item)->implode("\n"),
                'image' => '!['.($block['alt'] ?? '').']('.$block['url'].')',
                'link' => '['.$block['text'].']('.$block['url'].')',
                'quote' => $block['text'].' — '.($block['attribution'] ?? ''),
                default => $block['text'],
            })->implode("\n\n");
            $revision = $article->revisions()->create([
                'created_by' => $actor?->id, 'number' => 1, 'status' => RevisionStatus::Draft,
                'title' => $record['title'], 'excerpt' => $record['excerpt'], 'category_slug' => $record['category'],
                'body' => $record['body'], 'body_markdown' => $markdown, 'faqs' => $record['faqs'] ?? [],
                'regions' => $record['regions'] ?? null, 'image' => $record['image'], 'image_alt' => $record['imageAlt'] ?? null,
                'author_name' => $actor?->name ?? $record['author'], 'author_title' => $actor?->job_title ?? $record['authorTitle'],
                'read_time' => $record['readTime'],
                'source_notes' => 'Imported example. Original byline: '.$record['author'].'. Claims, ranking completeness, attribution, sources and image rights require verification. Assignment is not confirmation of authorship.'
                    .(filled($record['imageSource'] ?? null) ? "\nHero photo source: ".($record['imageCredit'] ?? 'Pexels contributor').' — '.$record['imageSource'].' (Pexels licence; image rights must be documented in editorial evidence).' : ''),
            ]);
            $article->user_id = $actor?->id;
            $article->current_revision_id = $revision->id;
            $article->save();
            AuditEvent::record($actor, 'article.imported', $article);

            return $article;
        });
    }
}
