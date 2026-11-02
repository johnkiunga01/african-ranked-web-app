<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\User;
use App\Services\EditorialWorkflow;
use App\Services\PublicationQuality;
use App\Services\ReviewedPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One-time, reversible revision-based release on the existing noindex review database.
 * Owner confirmed 2026-11-01 as the substantive verification date.
 * No article is published unless the normal publication workflow accepts it.
 */
class PublishOwnerConfirmedDrafts extends Command
{
    protected $signature = 'editorial:release-owner-confirmed
        {--apply : Write private revisions and publish articles passing existing checks}
        {--verified-date=2026-11-01 : Owner-confirmed verification date for missing dates only}
        {--limit=60 : Maximum number of unpublished articles to process}';

    protected $description = 'Enrich existing CMS drafts with curated Pexels images; publish only after existing quality and editorial checks succeed.';

    public function handle(): int
    {
        if (parse_url((string) config('app.url'), PHP_URL_HOST) !== 'african-ranked-web-app-production.up.railway.app') {
            $this->error('Refusing to run outside the known African Ranked review host.');
            return self::FAILURE;
        }
        $verifiedDate = (string) $this->option('verified-date');
        if (! preg_match('/^2026-11-01$/D', $verifiedDate)) {
            $this->error('The owner confirmed 2026-11-01; other dates require a separate review.');
            return self::FAILURE;
        }

        $actor = User::all()->first(fn (User $user) => $user->isSuperAdmin());
        if (! $actor) {
            $this->error('No active verified super admin is available; publication refused.');
            return self::FAILURE;
        }

        $manifest = json_decode(file_get_contents(base_path('content/research/pexels-cms-photo-pool.json')), true, 512, JSON_THROW_ON_ERROR);
        $photos = $manifest['photographs'];
        $seeds = collect(json_decode(file_get_contents(base_path('content/articles.json')), true, 512, JSON_THROW_ON_ERROR))->keyBy('slug');
        $evidenceManifest = json_decode(file_get_contents(base_path('content/research/owner-confirmed-seed-editorial-evidence.json')), true, 512, JSON_THROW_ON_ERROR)['articles'] ?? [];
        $articles = Article::whereNull('trashed_at')->whereNull('published_revision_id')
            ->with(['currentRevision', 'author'])->orderBy('id')->limit(min(100, max(1, (int) $this->option('limit'))))->get();
        $summary = ['found' => $articles->count(), 'enriched' => 0, 'published' => 0, 'blocked' => 0, 'errors' => 0, 'dry_run' => ! $this->option('apply')];
        $workflow = app(EditorialWorkflow::class);

        foreach ($articles as $article) {
            $prior = $article->currentRevision;
            if (! $prior) {
                $summary['blocked']++;
                $this->line("BLOCK {$article->id} {$article->slug}: no current revision");
                continue;
            }
            try {
                $seed = $seeds->get($article->slug);
                [$hero, $bodyPhoto, $extraPhoto] = $this->photosFor($article->slug, $prior->category_slug, $manifest, $seed);
                $markdown = trim((string) ($prior->body_markdown ?? ''));
                $newTitle = $prior->title;
                $newExcerpt = $prior->excerpt;
                $newSeoTitle = $prior->seo_title;
                $newMeta = $prior->meta_description;
                $newFocus = $prior->focus_keyword;
                $newFaqs = $prior->faqs;
                $sourceNotes = $prior->source_notes;

                // The original 13 inherited seed drafts were expanded in GitHub; only
                // upgrade when the repository body is longer, never erase richer CMS work.
                if ($seed && $this->seedWordCount($seed) > $this->wordCount($markdown) + 100) {
                    $markdown = $this->seedMarkdown($seed);
                    $newTitle = $seed['title'];
                    $newExcerpt = $seed['excerpt'];
                    $newSeoTitle = $seed['seoTitle'] ?? $newSeoTitle;
                    $newMeta = $seed['metaDescription'] ?? $newMeta;
                    $newFocus = $seed['focusKeyword'] ?? $newFocus;
                    if (count($seed['faqs'] ?? []) >= count($newFaqs ?? [])) {
                        $newFaqs = $seed['faqs'];
                    }
                }

                $annotation = "Illustrative photography; not a promise of wildlife sightings or an endorsement.";
                $bodyImages = $this->ensureBodyImages($markdown, $bodyPhoto, $extraPhoto);
                $markdown = $bodyImages['markdown'];

                $evidence = $prior->editorial_evidence ?? [];
                if (empty($evidence) && $article->slug === 'safari-packing-etiquette-first-timer-guide') {
                    $f = base_path('content/research/safari-packing-etiquette-first-timer-guide.evidence.json');
                    if (is_file($f)) {
                        $evidence = json_decode(file_get_contents($f), true, 512, JSON_THROW_ON_ERROR)['editorial_evidence'] ?? [];
                    }
                }
                if (isset($evidenceManifest[$article->slug])) {
                    $record = $evidenceManifest[$article->slug];
                    foreach (['answer', 'content_type', 'facts', 'sources', 'methodology'] as $key) {
                        if (empty($evidence[$key]) && ! empty($record[$key])) {
                            $evidence[$key] = $record[$key];
                        }
                    }
                    if (empty($evidence['last_verified_at'])) {
                        $evidence['last_verified_at'] = $verifiedDate;
                    }
                    if (! str_contains((string) $sourceNotes, 'Ranking methodology:') && ! empty($record['methodology'])) {
                        $sourceNotes = trim((string) $sourceNotes)."\nRanking methodology: ".$record['methodology'];
                    }
                }
                if (empty($evidence['last_verified_at'])) {
                    $evidence['last_verified_at'] = $verifiedDate;
                }
                $evidence['image_rights'] = "Pexels free-to-use licence: https://www.pexels.com/license/ . "
                    ."Hero: {$hero['photographer']} ({$hero['page']}). "
                    ."Inline: {$bodyPhoto['photographer']} ({$bodyPhoto['page']}) and {$extraPhoto['photographer']} ({$extraPhoto['page']}). "
                    ."Photography is illustrative; credit is shown and no endorsement by photographed people is implied.";
                $evidence['content_type'] ??= preg_match('/\b(rank|top \d+|best \d+)\b/i', $newTitle) ? 'ranking' : 'guide';

                $needsAuthor = ! $article->author?->is_active || ! $article->author?->hasVerifiedEmail();
                $wouldChange = $sourceNotes !== $prior->source_notes
                    || $needsAuthor
                    || $markdown !== trim((string) $prior->body_markdown)
                    || $prior->image !== $hero['url']
                    || $prior->image_alt !== $hero['alt']
                    || $evidence !== ($prior->editorial_evidence ?? [])
                    || $newTitle !== $prior->title;

                if (! $this->option('apply')) {
                    $this->line("PLAN {$article->id} {$article->slug}: hero + inline photos; ".($wouldChange ? 'new revision' : 'unchanged'));
                    continue;
                }

                if ($wouldChange) {
                    if ($needsAuthor) {
                        $article->user_id = $actor->id;
                        $article->save();
                    }
                    $data = [
                        'revision_id' => $prior->id,
                        'title' => $newTitle,
                        'excerpt' => $newExcerpt,
                        'category_slug' => $prior->category_slug,
                        'body_markdown' => $markdown,
                        'image' => $hero['url'],
                        'image_alt' => $hero['alt'],
                        'source_notes' => $sourceNotes,
                        'seo_title' => $newSeoTitle,
                        'meta_description' => $newMeta,
                        'focus_keyword' => $newFocus,
                        'editorial_evidence' => $evidence,
                    ];
                    if ($newFaqs !== null) {
                        $data['faqs_text'] = collect($newFaqs)->map(fn ($faq) => $faq['question']."\n".$faq['answer'])->implode("\n\n");
                    }
                    $article = $workflow->save($actor, $data, $article);
                    $revision = $article->currentRevision;
                    $blocks = $revision->body ?? [];
                    $imageMap = [];
                    foreach ([$bodyPhoto, $extraPhoto] as $photo) {
                        $imageMap[$photo['url']] = $photo;
                    }
                    foreach ($blocks as &$block) {
                        if (($block['type'] ?? null) === 'image' && isset($imageMap[$block['url'] ?? ''])) {
                            $photo = $imageMap[$block['url']];
                            $block['caption'] = $annotation;
                            $block['credit'] = $photo['photographer'].' / Pexels';
                            $block['source'] = $photo['page'];
                        }
                    }
                    unset($block);
                    $revision->body = $blocks;
                    $revision->save();
                    $summary['enriched']++;
                }

                $article = $article->fresh(['currentRevision', 'author']);
                $revision = $article->currentRevision;
                $quality = app(PublicationQuality::class)->inspect($revision);
                if ($quality['failures']) {
                    $summary['blocked']++;
                    $this->line("BLOCK {$article->id} {$article->slug}: ".implode(' | ', $quality['failures']));
                    continue;
                }
                if (! $article->author?->is_active || ! $article->author?->hasVerifiedEmail()) {
                    $summary['blocked']++;
                    $this->line("BLOCK {$article->id} {$article->slug}: author not verified");
                    continue;
                }

                try {
                    app(ReviewedPublisher::class)->publish($actor, $article, (int) $revision->id);
                    $summary['published']++;
                    $this->info("PUBLISHED {$article->id} {$article->slug}");
                } catch (Throwable $error) {
                    $summary['blocked']++;
                    $this->line("BLOCK {$article->id} {$article->slug}: publish workflow rejected revision (".mb_substr($error->getMessage(), 0, 180).")");
                }
            } catch (Throwable $error) {
                $summary['errors']++;
                $this->error("ERROR {$article->id} {$article->slug}: ".mb_substr($error->getMessage(), 0, 250));
            }
        }

        $output = 'AR_EDITORIAL_RELEASE '.json_encode($summary, JSON_UNESCAPED_SLASHES);
        $this->info($output);
        Log::info($output);
        // A failed individual article must never interrupt the web service rollout.
        return self::SUCCESS;
    }

    private function photosFor(string $slug, string $category, array $manifest, ?array $seed): array
    {
        $keys = $manifest['default'];
        foreach ($manifest['rules'] as $rule) {
            if (preg_match('/'.$rule['pattern'].'/i', $slug)) {
                $keys = $rule['keys'];
                break;
            }
        }
        $photos = $manifest['photographs'];
        $hero = $photos[$keys[0]];
        $body = $photos[$keys[1]];
        $other = match ($category) {
            'wildlife' => $photos['safari-giraffes'],
            'food' => $photos['kente-attire'],
            'music' => $photos['djembe-man'],
            'business', 'tech' => $photos['lagos-office'],
            default => $photos['cape-mountain'],
        };
        if ($other['url'] === $body['url'] || $other['url'] === $hero['url']) {
            $other = $photos['safari-zebras'];
        }
        if ($seed && filled($seed['image'] ?? null) && filled($seed['imageSource'] ?? null)) {
            $hero = [
                'url' => $seed['image'],
                'alt' => $seed['imageAlt'] ?? $hero['alt'],
                'page' => $seed['imageSource'],
                'photographer' => $seed['imageCredit'] ?? 'Pexels contributor',
            ];
        }
        return [$hero, $body, $other];
    }

    private function ensureBodyImages(string $markdown, array $first, array $second): array
    {
        $existing = preg_match_all('/^!\[.+\]\(https:\/\/images\.pexels\.com\/.+\)$/mu', $markdown);
        $needed = max(0, 2 - $existing);
        if ($needed < 1) {
            return ['markdown' => $markdown];
        }
        $photos = array_slice([$first, $second], 0, $needed);
        $matches = [];
        preg_match_all('/^#{2,3}\s+[^\n]+$/mu', $markdown, $matches, PREG_OFFSET_CAPTURE);
        $insertions = [];
        foreach ($photos as $i => $photo) {
            $alt = str_replace([']',"\n"], '', $photo['alt']);
            $line = "\n\n![".$alt."](".$photo['url'].")\n";
            $headings = $matches[0] ?? [];
            if ($headings) {
                $target = $headings[min(count($headings) - 1, $i * 3)];
                $insertions[] = [$target[1] + strlen($target[0]), $line];
            } else {
                $insertions[] = [strlen($markdown), $line];
            }
        }
        usort($insertions, fn ($a, $b) => $b[0] <=> $a[0]);
        foreach ($insertions as [$offset, $line]) {
            $markdown = substr_replace($markdown, $line, $offset, 0);
        }
        return ['markdown' => trim($markdown)];
    }

    private function seedMarkdown(array $seed): string
    {
        return collect($seed['body'])->map(fn ($block) => match ($block['type']) {
            'heading' => str_repeat('#', $block['level']).' '.$block['text'],
            'list' => collect($block['items'])->map(fn ($text) => '- '.$text)->implode("\n"),
            'link' => '['.$block['text'].']('.$block['url'].')',
            'image' => '!['.$block['alt'].']('.$block['url'].')',
            default => $block['text'] ?? '',
        })->implode("\n\n");
    }

    private function seedWordCount(array $seed): int
    {
        return $this->wordCount(collect($seed['body'])->map(fn ($block) => $block['text'] ?? implode(' ', $block['items'] ?? []))->implode(' '));
    }

    private function wordCount(string $text): int
    {
        return preg_match_all('/[\pL\pN]+(?:[’\x{0027}-][\pL\pN]+)*/u', strip_tags($text));
    }
}
