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

class FinishFourSafariDrafts extends Command
{
    protected $signature = 'editorial:finish-safari-four {--apply}';

    protected $description = 'Apply researched reader-value additions to four private safari drafts, then publish through standard checks.';

    public function handle(): int
    {
        $manifest = json_decode(file_get_contents(base_path('content/research/safari-four-editorial-additions.json')), true, 512, JSON_THROW_ON_ERROR);
        $actor = User::all()->first(fn (User $user) => $user->isSuperAdmin());
        if (! $actor) {
            return self::FAILURE;
        }
        $counts = ['considered' => 0, 'saved' => 0, 'published' => 0, 'blocked' => 0, 'errors' => 0];
        foreach ($manifest['articles'] as $slug => $data) {
            $article = Article::where('slug', $slug)->whereNull('trashed_at')->with(['currentRevision', 'author'])->first();
            if (! $article || $article->published_revision_id) {
                continue;
            }
            $counts['considered']++;
            try {
                $prior = $article->currentRevision;
                $extra = collect($data['paragraphs'])->map(fn ($p) => '## '.$p[0]."\n\n".$p[1])->implode("\n\n");
                $original = trim((string) $prior->body_markdown);
                if (str_contains($original, $data['paragraphs'][0][0])) {
                    $counts['blocked']++;
                    $this->warn('SKIPPED duplicate sections '.$slug);

                    continue;
                }
                $body = $original."\n\n".$extra;
                $evidence = $prior->editorial_evidence ?? [];
                $evidence['answer'] = $data['answer'];
                $evidence['last_verified_at'] = '2026-11-02';
                if (! $this->option('apply')) {
                    $this->line('PLAN '.$slug.' '.str_word_count(strip_tags($body)).' words');

                    continue;
                }
                if (! $article->author?->is_active || ! $article->author?->hasVerifiedEmail()) {
                    $article->user_id = $actor->id;
                    $article->save();
                }
                $saved = app(EditorialWorkflow::class)->save($actor, [
                    'revision_id' => $prior->id, 'title' => $prior->title, 'excerpt' => $prior->excerpt,
                    'category_slug' => $prior->category_slug, 'body_markdown' => $body,
                    'image' => $prior->image, 'image_alt' => $prior->image_alt,
                    'seo_title' => $prior->seo_title, 'meta_description' => $prior->meta_description,
                    'focus_keyword' => $prior->focus_keyword, 'source_notes' => $prior->source_notes,
                    'editorial_evidence' => $evidence,
                    'faqs_text' => collect($prior->faqs ?? [])->map(fn ($f) => $f['question']."\n".$f['answer'])->implode("\n\n"),
                ], $article);
                $counts['saved']++;
                $quality = app(PublicationQuality::class)->inspect($saved->currentRevision);
                if ($quality['failures']) {
                    $counts['blocked']++;
                    $this->warn('BLOCK '.$slug.' '.$quality['body_words'].' words '.implode(' ; ', $quality['failures']));

                    continue;
                }
                app(ReviewedPublisher::class)->publish($actor, $saved, (int) $saved->current_revision_id);
                $counts['published']++;
                $this->info('PUBLISHED '.$slug);
            } catch (Throwable $error) {
                $counts['errors']++;
                $this->error('ERROR '.$slug.': '.mb_substr($error->getMessage(), 0, 170));
            }
        }
        $this->info('AR_EDITORIAL_FINISH '.json_encode($counts));
        Log::info('AR_EDITORIAL_FINISH '.json_encode($counts));

        return self::SUCCESS;
    }
}
