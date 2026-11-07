<?php

namespace App\Services;

use App\Enums\RevisionStatus;
use App\Jobs\PrepareArticleRecommendation;
use App\Models\Article;
use App\Models\ArticleRecommendation;
use App\Models\ArticleResearch;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ArticleRecommendations
{
    public function request(User $actor, Article $article, int $expected): ArticleRecommendation
    {
        Gate::forUser($actor)->authorize('recommend', $article);

        return DB::transaction(function () use ($actor, $article, $expected) {
            $actor = User::lockForUpdate()->findOrFail($actor->id);
            $article = Article::lockForUpdate()->findOrFail($article->id);
            Gate::forUser($actor)->authorize('recommend', $article);
            if (! $article->author?->is_active || ! $article->author?->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['suggestion' => 'Assign an active author before requesting improvements.']);
            }
            if ((int) $article->current_revision_id !== $expected) {
                $this->changed();
            }
            if ($saved = ArticleRecommendation::where('article_id', $article->id)->where('source_revision_id', $expected)->first()) {
                return $saved;
            }
            $credentials = app(ProviderCredentials::class);
            // Choose once from configured providers. Never switch providers after a failed request.
            $provider = filled($credentials->key('gemini')) ? 'gemini' : (filled($credentials->key('openai')) ? 'openai' : null);
            if (! $provider) {
                throw ValidationException::withMessages(['suggestion' => 'Suggestions are not available yet. Ask the administrator to connect a writing service.']);
            }
            if (! ArticleResearch::current($article) && ! filled($credentials->key('openseo'))) {
                throw ValidationException::withMessages(['suggestion' => 'Search research is not available yet. Ask the administrator to connect it.']);
            }
            $rateKey = 'article-recommendations:'.$actor->id;
            if (RateLimiter::tooManyAttempts($rateKey, 2)) {
                throw ValidationException::withMessages(['suggestion' => 'Please wait a minute before requesting another suggestion.']);
            }
            RateLimiter::hit($rateKey, 60);
            if (app(AiUsage::class)->remaining($actor) < 1) {
                throw ValidationException::withMessages(['suggestion' => 'Your suggestion allowance is used up for today. Try again tomorrow.']);
            }
            $id = app(AiUsage::class)->request($actor, $article, $expected, 'editorial_recommendation', $provider);
            $request = ArticleRecommendation::findOrFail($id);
            $guidelines = app(RecommendationGuidelines::class);
            $request->update(['status' => 'queued', 'payload' => [
                'session_version' => $actor->session_version,
                'owner_id' => $article->user_id,
                'guidelines' => $guidelines->snapshot($article),
                'guidelines_fingerprint' => $guidelines->fingerprint($article),
                'model' => config($provider === 'gemini' ? 'services.gemini.model' : 'services.openai.article_model'),
            ]]);
            AuditEvent::record($actor, 'article.recommendation_requested', $article, ['recommendation_id' => $id]);
            PrepareArticleRecommendation::dispatch($id)->afterCommit();

            return $request;
        });
    }

    public function accept(User $actor, ArticleRecommendation $request, int $expected): Article
    {
        return $this->apply($actor, $request, $expected, true);
    }

    public function saveDraft(User $actor, ArticleRecommendation $request, int $expected): Article
    {
        return $this->apply($actor, $request, $expected, false);
    }

    private function apply(User $actor, ArticleRecommendation $request, int $expected, bool $publish): Article
    {
        return DB::transaction(function () use ($actor, $request, $expected, $publish) {
            $actor = User::lockForUpdate()->findOrFail($actor->id);
            $article = Article::lockForUpdate()->findOrFail($request->article_id);
            $request = ArticleRecommendation::lockForUpdate()->findOrFail($request->id);
            Gate::forUser($actor)->authorize('acceptRecommendation', $article);
            $payload = $request->payload;
            if (! $publish && $request->status === 'draft_saved' && (int) $article->current_revision_id === (int) $request->result_revision_id) {
                return $article;
            }
            if ($request->status === 'accepted' && $request->result_revision_id && (int) $article->published_revision_id === (int) $request->result_revision_id) {
                return $article;
            }
            if ($request->status !== 'ready' || (int) $request->source_revision_id !== $expected || (int) $article->current_revision_id !== $expected
                || (int) $article->user_id !== (int) $payload['owner_id']
                || (int) $request->user->session_version !== (int) $payload['session_version']
                || ! Gate::forUser($request->user)->allows('recommend', $article)
                || $payload['guidelines_fingerprint'] !== app(RecommendationGuidelines::class)->fingerprint($article)) {
                $this->changed();
            }
            if (! $article->author?->is_active || ! $article->author?->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['suggestion' => 'The assigned author is no longer active. Nothing was published.']);
            }
            $draft = $payload['draft'];
            $blockers = app(RecommendationReadiness::class)->blockers($article, $draft);
            if ($publish && $blockers) {
                throw ValidationException::withMessages(['suggestion' => 'Finish the listed editorial checks before publishing. Your article has not changed.']);
            }
            $prior = $article->currentRevision;
            app(ArticleImageReuseGuard::class)->assertNoNewCrossArticleDuplicates($draft + ['image' => $prior->image], $article);
            $saved = app(EditorialWorkflow::class)->save($actor, $draft + [
                'revision_id' => $expected, 'category_slug' => $prior->category_slug, 'image' => $prior->image,
                'image_alt' => $prior->image_alt, 'focus_keyword' => $prior->focus_keyword, 'source_notes' => $prior->source_notes,
            ], $article);
            $revision = $saved->currentRevision;
            $revision->body = app(RecommendationReadiness::class)->candidate($article, $draft)->body;
            if (! $publish) {
                $revision->save();
                $request->update(['status' => 'draft_saved', 'result_revision_id' => $revision->id]);
                AuditEvent::record($actor, 'article.recommendation_saved_as_draft', $saved, ['recommendation_id' => $request->id]);

                return $saved->fresh();
            }
            app(PublicationQuality::class)->assertPublishable($revision);
            // Human acceptance is the approval action for this specific generated version only.
            $revision->forceFill(['status' => RevisionStatus::Published, 'review_checklist' => array_fill_keys(EditorialWorkflow::CHECKS, true),
                'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'published_at' => now()])->save();
            $saved->forceFill(['published_revision_id' => $revision->id, 'first_published_at' => $article->first_published_at ?? now()])->save();
            $request->update(['status' => 'accepted', 'result_revision_id' => $revision->id,
                'payload' => $payload + ['accepted_by' => $actor->id, 'accepted_at' => now()->toIso8601String()]]);
            AuditEvent::record($actor, 'article.recommendation_accepted', $saved, ['recommendation_id' => $request->id, 'revision' => $revision->number]);
            AuditEvent::record($actor, 'article.publish', $saved, ['recommendation_id' => $request->id, 'revision' => $revision->number]);

            return $saved->fresh();
        });
    }

    public function discard(User $actor, ArticleRecommendation $request): void
    {
        DB::transaction(function () use ($actor, $request) {
            $article = Article::lockForUpdate()->findOrFail($request->article_id);
            Gate::forUser($actor)->authorize('recommend', $article);
            $request = ArticleRecommendation::lockForUpdate()->findOrFail($request->id);
            if ($request->status === 'accepted') {
                throw ValidationException::withMessages(['suggestion' => 'This version has already been published.']);
            }
            $request->update(['status' => 'discarded']);
            AuditEvent::record($actor, 'article.recommendation_discarded', $article, ['recommendation_id' => $request->id]);
        });
    }

    private function changed(): never
    {
        throw ValidationException::withMessages(['suggestion' => 'This article or its access changed. Return to the editor for a fresh suggestion. Nothing was published.']);
    }
}
