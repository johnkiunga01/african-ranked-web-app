<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\User;
use App\Services\EditorialWorkflow;
use App\Services\ReviewedPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RepairDuplicateEditorialMedia extends Command
{
    protected $signature = 'editorial:deduplicate-media {--apply : Save unique editorial illustrations for repeated cross-article images and publish verified revisions}';
    protected $description = 'Audit stock image reuse across articles; replace repeated uses with article-specific original illustrations without altering first attribution.';

    private function identity(?string $url): ?string
    {
        if (! $url || ! str_starts_with($url, 'https://')) return null;
        $parts = parse_url($url);
        if (empty($parts['host']) || empty($parts['path'])) return null;
        $path = strtolower(rawurldecode($parts['path']));
        if (str_contains($parts['host'], 'pexels.com') && preg_match('~/photos/(\d+)/~', $path, $m)) {
            return 'pexels:'.$m[1];
        }
        // Each unique in-house illustration has its own per-article slug.
        if (str_contains($path, '/editorial-art/')) return null;
        return strtolower($parts['host']).$path;
    }

    public function handle(): int
    {
        $actor = User::all()->first(fn (User $user) => $user->isSuperAdmin());
        if (! $actor && $this->option('apply')) {
            $this->error('No verified super admin for the standard publishing workflow.');
            return self::FAILURE;
        }
        $articles = Article::whereNull('trashed_at')
            ->with(['currentRevision', 'publishedRevision'])
            ->get()->sort(function ($a, $b) {
                return [! $a->published_revision_id, $a->id] <=> [! $b->published_revision_id, $b->id];
            });
        $owned = [];
        $summary = ['articles' => $articles->count(), 'affected_articles' => 0,
            'duplicate_hero' => 0, 'duplicate_inline' => 0, 'saved' => 0,
            'published' => 0, 'drafts_updated' => 0, 'conflicts' => 0, 'errors' => 0, 'apply' => (bool) $this->option('apply')];
        foreach ($articles as $article) {
            $published = $article->publishedRevision;
            $current = $article->currentRevision;
            $revision = $published ?? $current;
            if (! $revision) continue;
            $newImage = $revision->image;
            $newAlt = $revision->image_alt;
            $changed = [];
            $heroIdentity = $this->identity($revision->image);
            if ($heroIdentity) {
                if (isset($owned[$heroIdentity]) && $owned[$heroIdentity] !== $article->id) {
                    $newImage = url('/editorial-art/'.$article->slug.'/cover.svg');
                    $newAlt = 'Original African Ranked illustration for '.$revision->title;
                    $summary['duplicate_hero']++;
                    $changed[] = 'cover';
                } else {
                    $owned[$heroIdentity] = $article->id;
                }
            }
            $body = (string) $revision->body_markdown;
            $index = 0;
            $body = preg_replace_callback('~!\[([^\]\r\n]+)\]\((https://[^\s)]+)\)~u', function ($match) use (&$owned, &$summary, &$changed, $article, &$index) {
                $id = $this->identity($match[2]);
                if (! $id) return $match[0];
                if (! isset($owned[$id]) || $owned[$id] === $article->id) {
                    $owned[$id] = $article->id;
                    return $match[0];
                }
                $index++;
                if ($index > 99) return $match[0];
                $summary['duplicate_inline']++;
                $changed[] = 'inline';
                return '![Original African Ranked illustration for '.str_replace([']', '['], '', $article->slug).']('.url('/editorial-art/'.$article->slug.'/detail-'.$index.'.svg').')';
            }, $body);
            if (! $changed) continue;
            $summary['affected_articles']++;
            $this->line(($this->option('apply') ? 'REPAIR ' : 'DUPLICATE ').$article->id.' '.$article->slug.' '.implode(',', $changed));
            if (! $this->option('apply')) continue;
            try {
                // Never discard a private, more recent working draft behind a published article.
                if ($published && (int) $published->id !== (int) $current?->id) {
                    $summary['conflicts']++;
                    $this->warn('SKIPPED newer private draft: '.$article->slug);
                    continue;
                }
                $prior = $current;
                $evidence = $prior->editorial_evidence ?? [];
                $evidence['image_rights'] = trim(($evidence['image_rights'] ?? '')." Original unique SVG editorial illustrations created for this article by African Ranked; artwork is not a documentary photograph. Duplicate photographs were replaced.");
                $data = [
                    'revision_id' => $prior->id, 'title' => $prior->title, 'excerpt' => $prior->excerpt,
                    'category_slug' => $prior->category_slug, 'body_markdown' => $body,
                    'image' => $newImage, 'image_alt' => $newAlt,
                    'seo_title' => $prior->seo_title, 'meta_description' => $prior->meta_description,
                    'focus_keyword' => $prior->focus_keyword, 'source_notes' => $prior->source_notes,
                    'editorial_evidence' => $evidence,
                    'faqs_text' => collect($prior->faqs ?? [])->map(fn ($faq) => $faq['question']."\n".$faq['answer'])->implode("\n\n"),
                ];
                if ($prior->regions === null) $data['all_editions'] = true;
                else { $data['all_editions'] = false; $data['regions'] = $prior->regions; }
                $saved = app(EditorialWorkflow::class)->save($actor, $data, $article);
                // Retain original photographer credits on image blocks we did not replace.
                $before = collect($prior->body ?? [])->filter(fn ($block) => ($block['type'] ?? '') === 'image')->keyBy('url');
                $next = $saved->currentRevision;
                $blocks = $next->body ?? [];
                foreach ($blocks as &$block) {
                    if (($block['type'] ?? '') !== 'image' || ! $before->has($block['url'] ?? '')) continue;
                    foreach (['caption', 'credit', 'source'] as $key) {
                        $old = $before->get($block['url']);
                        if (isset($old[$key])) $block[$key] = $old[$key];
                    }
                }
                unset($block);
                $next->body = $blocks;
                $next->save();
                $summary['saved']++;
                if ($article->published_revision_id) {
                    app(ReviewedPublisher::class)->publish($actor, $saved, (int) $saved->current_revision_id);
                    $summary['published']++;
                } else {
                    $summary['drafts_updated']++;
                }
            } catch (Throwable $exception) {
                $summary['errors']++;
                $this->error('ERROR '.$article->slug.' '.mb_substr($exception->getMessage(), 0, 220));
            }
        }
        $message = 'AR_MEDIA_DEDUP '.json_encode($summary, JSON_UNESCAPED_SLASHES);
        $this->info($message);
        Log::info($message);
        return self::SUCCESS;
    }
}
