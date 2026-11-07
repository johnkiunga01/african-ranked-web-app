<?php

namespace App\Jobs;

use App\Models\ArticleRecommendation;
use App\Models\AuditEvent;
use App\Services\ArticleResearcher;
use App\Services\GeminiArticleCurator;
use App\Services\GptArticleCurator;
use App\Services\RecommendationGuidelines;
use App\Services\RecommendationReadiness;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Gate;
use Throwable;

class PrepareArticleRecommendation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 150;

    public bool $failOnTimeout = true;

    public function __construct(public int $recommendationId)
    {
        $this->onConnection('database');
    }

    private function current(ArticleRecommendation $request): bool
    {
        $article = $request->article()->first();
        $user = $request->user()->first();

        $model = config($request->provider === 'gemini' ? 'services.gemini.model' : 'services.openai.article_model');

        return $article && $article->author?->is_active && $article->author?->hasVerifiedEmail() && $user && $model === ($request->payload['model'] ?? null) && (int) $article->current_revision_id === (int) $request->source_revision_id
            && (int) $article->user_id === (int) $request->payload['owner_id']
            && (int) $user->session_version === (int) $request->payload['session_version']
            && Gate::forUser($user)->allows('recommend', $article)
            && $request->payload['guidelines_fingerprint'] === app(RecommendationGuidelines::class)->fingerprint($article);
    }

    public function handle(ArticleResearcher $researcher): void
    {
        if (! ArticleRecommendation::whereKey($this->recommendationId)->where('status', 'queued')->update(['status' => 'researching'])) {
            return;
        }
        $request = ArticleRecommendation::with(['article.currentRevision', 'user'])->findOrFail($this->recommendationId);
        try {
            if (! $this->current($request)) {
                $this->stale();

                return;
            }
            $research = $researcher->research($request->user, $request->article, true);
            $request->refresh();
            if ($request->status !== 'researching') {
                return;
            }
            if (! $this->current($request)) {
                $this->stale();

                return;
            }
            $request->update(['status' => 'writing']);
            $brief = app(RecommendationGuidelines::class)->brief($request->payload['guidelines']);
            $curator = app($request->provider === 'gemini' ? GeminiArticleCurator::class : GptArticleCurator::class);
            $draft = $curator->generate($request->article, $brief);
            // The worker proposes only; it never creates a revision or publishes.
            $request->refresh();
            if ($request->status !== 'writing') {
                return;
            }
            if (! $this->current($request)) {
                $this->stale();

                return;
            }
            $readiness = app(RecommendationReadiness::class);
            $payload = $request->payload + ['draft' => $draft, 'research_id' => $research->id,
                'research_date' => $research->researched_at?->toIso8601String(),
                'changes' => $readiness->changes($request->article->currentRevision, $draft),
                'blockers' => $readiness->blockers($request->article, $draft)];
            $written = ArticleRecommendation::whereKey($request->id)->where('status', 'writing')->update(['status' => 'ready', 'payload' => json_encode($payload, JSON_THROW_ON_ERROR)]);
            if ($written) {
                AuditEvent::record($request->user, 'article.recommendation_ready', $request->article, ['recommendation_id' => $request->id]);
            }
        } catch (Throwable) {
            $this->failed(null);
        }
    }

    private function stale(): void
    {
        ArticleRecommendation::whereKey($this->recommendationId)->whereIn('status', ['researching', 'writing'])->update(['status' => 'stale']);
    }

    public function failed(?Throwable $exception): void
    {
        // Never persist an upstream exception/body: it can contain private content or credentials.
        ArticleRecommendation::whereKey($this->recommendationId)->whereIn('status', ['queued', 'researching', 'writing'])->update(['status' => 'failed']);
    }
}
