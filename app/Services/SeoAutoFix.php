<?php

namespace App\Services;

use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\ArticleResearch;
use App\Models\AuditEvent;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SeoAutoFix
{
    public function apply(User $actor, Article $article, int $expected, bool $useAi): array
    {
        Gate::forUser($actor)->authorize('update', $article);
        if ($useAi) {
            Gate::forUser($actor)->authorize('curate', $article);
        }
        $prior = $article->currentRevision;
        if ((int) $prior->id !== $expected) {
            throw ValidationException::withMessages(['article' => 'This article changed. Reload before applying fixes.']);
        }
        $title = $this->shorten($prior->seo_title ?: $prior->title, 65);
        // Drop an optional brand suffix before shortening the factual headline.
        if (mb_strlen($prior->seo_title ?? '') > 65 && mb_strlen($prior->title) <= 65) {
            $title = $prior->title;
        }
        $description = $this->shorten($prior->meta_description ?: $prior->excerpt, 160);
        $query = $prior->focus_keyword ?: mb_strtolower(preg_replace('/\s+(?:ranked\s*)?(?:for\s*)?20\d{2}$/iu', '', preg_replace('/^(?:the\s+)?(?:top\s+)?\d+\s+/iu', '', $prior->title)));
        $values = ['seo_title' => $title, 'meta_description' => $description, 'focus_keyword' => mb_substr($query, 0, 180)];
        $requestId = null;
        if ($useAi) {
            $research = ArticleResearch::current($article);
            if (! $research) {
                throw new RuntimeException('Run research-led optimisation first so Gemini can use an OpenSEO brief.');
            }
            if (! app(ProviderCredentials::class)->key('gemini')) {
                throw new RuntimeException('Add a Gemini free-tier key in Integrations, or use no-API fixes.');
            }
            try {
                $requestId = app(AiUsage::class)->request($actor, $article, $expected, 'seo_fix', 'gemini');
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['ai' => 'AI fixes were already requested for this saved revision. No new request was made.']);
            }
            try {
                $schema = ['type' => 'OBJECT', 'properties' => ['seo_title' => ['type' => 'STRING'], 'meta_description' => ['type' => 'STRING'], 'focus_keyword' => ['type' => 'STRING']], 'required' => ['seo_title', 'meta_description', 'focus_keyword']];
                $values = app(GeminiAssistant::class)->generate(['title' => $prior->title, 'excerpt' => $prior->excerpt, 'body' => mb_substr($prior->body_markdown ?? '', 0, 20000), 'openseo_research' => $research->brief], $schema, 'Improve only the search title (at most 65 characters) and search description (at most 160 characters). Use the supplied OpenSEO query and SERP observations for intent; preserve the researched keyword as focus_keyword. Search snippets are research leads, not factual evidence. Do not invent competitor page contents or metrics. Do not change the article topic to chase volume. Return no HTML.');
                $values = Validator::make($values, ['seo_title' => ['required', 'string', 'max:65'], 'meta_description' => ['required', 'string', 'max:160'], 'focus_keyword' => ['required', 'string', 'max:180']])->validate();
                $values['focus_keyword'] = $research->keyword;
            } catch (Throwable $exception) {
                DB::table('article_ai_requests')->where('id', $requestId)->update(['status' => 'failed', 'updated_at' => now()]);
                throw $exception;
            }
        }

        try {
            return DB::transaction(function () use ($actor, $article, $expected, $values, $useAi, $requestId) {
                $article = Article::lockForUpdate()->findOrFail($article->id);
                Gate::forUser($actor)->authorize('update', $article);
                if ((int) $article->current_revision_id !== $expected) {
                    throw ValidationException::withMessages(['article' => 'This article changed while fixes were prepared. Reload before retrying.']);
                }
                $prior = $article->currentRevision;
                $changes = array_keys(array_filter($values, fn ($value, $field) => $value !== $prior->$field, ARRAY_FILTER_USE_BOTH));
                $related = null;
                if (! collect($prior->body)->contains('type', 'link')) {
                    $related = app(Catalog::class)->articles()->first(fn ($a) => $a['category'] === $prior->category_slug && $a['slug'] !== $article->slug);
                    if ($related) {
                        $changes[] = 'related_reading_link';
                    }
                }
                if (! count($changes)) {
                    if ($requestId) {
                        DB::table('article_ai_requests')->where('id', $requestId)->update(['status' => 'completed', 'result_revision_id' => $expected, 'updated_at' => now()]);
                    }

                    return [];
                }
                $revision = $prior->replicate(['number', 'status', 'reviewed_by', 'reviewed_at', 'published_at', 'review_checklist', 'review_notes']);
                foreach ($values as $field => $value) {
                    $revision->$field = $value;
                }
                if ($related) {
                    $label = str_replace(['[', ']'], '', $related['title']);
                    $revision->body = array_merge($prior->body, [['type' => 'heading', 'level' => 2, 'text' => 'Related reading'], ['type' => 'link', 'text' => $label, 'url' => '/articles/'.$related['slug']]]);
                    $revision->body_markdown = $prior->body_markdown."\n\n## Related reading\n\n[".$label.'](/articles/'.$related['slug'].')';
                }
                if (! $prior->focus_keyword && ! $useAi) {
                    $revision->source_notes = ($prior->source_notes ?? '')."\n\nTarget query inferred from existing content during SEO auto-fix; search demand has not been verified.";
                }
                $revision->number = $article->revisions()->max('number') + 1;
                $revision->status = RevisionStatus::Draft;
                $revision->created_by = $actor->id;
                $revision->save();
                $article->current_revision_id = $revision->id;
                $article->save();
                AuditEvent::record($actor, 'article.seo_autofixed', $article, ['fields' => $changes, 'provider' => $useAi ? 'gemini' : 'local']);

                if ($requestId) {
                    DB::table('article_ai_requests')->where('id', $requestId)->update(['status' => 'completed', 'result_revision_id' => $revision->id, 'updated_at' => now()]);
                }

                return $changes;
            });
        } catch (Throwable $exception) {
            if ($requestId) {
                DB::table('article_ai_requests')->where('id', $requestId)->update(['status' => 'failed', 'updated_at' => now()]);
            } throw $exception;
        }
    }

    private function shorten(string $value, int $max): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        if (mb_strlen($value) <= $max) {
            return $value;
        }
        $cut = mb_substr($value, 0, $max + 1);
        $cut = preg_replace('/\s+\S*$/u', '', $cut);

        return mb_substr(rtrim($cut, ' ,;:-'), 0, $max);
    }
}
