<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleRecommendation;
use App\Services\ArticleRecommendations;
use App\Services\RecommendationGuidelines;
use App\Services\RecommendationReadiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleRecommendationController extends Controller
{
    public function store(Request $request, Article $article, ArticleRecommendations $suggestions)
    {
        Gate::authorize('recommend', $article);
        $data = $request->validate(['revision_id' => ['required', 'integer'], 'consent' => ['accepted']]);
        $suggestion = $suggestions->request($request->user(), $article, (int) $data['revision_id']);

        return redirect()->route('staff.recommendations.show', [$article, $suggestion]);
    }

    public function show(Request $request, Article $article, ArticleRecommendation $recommendation)
    {
        abort_unless((int) $recommendation->article_id === (int) $article->id, 404);
        Gate::authorize('view', $article);
        $stale = (int) $article->current_revision_id !== (int) $recommendation->source_revision_id && $recommendation->status !== 'accepted';
        if (in_array($recommendation->status, ['queued', 'researching', 'writing', 'ready'], true)) {
            $stale = $stale || (int) ($recommendation->payload['owner_id'] ?? 0) !== (int) $article->user_id
                || ($recommendation->payload['guidelines_fingerprint'] ?? '') !== app(RecommendationGuidelines::class)->fingerprint($article);
        }
        $status = $stale ? 'stale' : $recommendation->status;
        if ($request->expectsJson()) {
            return response()->json(['status' => $status,
                'review_url' => route('staff.recommendations.show', [$article, $recommendation])]);
        }
        $draft = $recommendation->payload['draft'] ?? null;
        $readiness = app(RecommendationReadiness::class);
        $blockers = $draft && $status === 'ready' ? $readiness->blockers($article, $draft) : [];

        return view('staff.articles.recommendation', compact('article', 'recommendation', 'draft', 'status', 'blockers') + [
            'source' => $recommendation->sourceRevision, 'tasks' => $readiness->tasks($blockers),
            'proposed' => $draft ? $readiness->candidate($article, $draft) : null,
        ]);
    }

    public function accept(Request $request, Article $article, ArticleRecommendation $recommendation, ArticleRecommendations $suggestions)
    {
        abort_unless((int) $recommendation->article_id === (int) $article->id, 404);
        Gate::authorize('acceptRecommendation', $article);
        $data = $request->validate(['revision_id' => ['required', 'integer'], 'human_review' => ['accepted']]);
        $published = $suggestions->accept($request->user(), $recommendation, (int) $data['revision_id']);

        return redirect()->route('staff.articles.edit', $published)->with('status', 'Your accepted version is published. The previous version is kept in history.');
    }

    public function saveDraft(Request $request, Article $article, ArticleRecommendation $recommendation, ArticleRecommendations $suggestions)
    {
        abort_unless((int) $recommendation->article_id === (int) $article->id, 404);
        Gate::authorize('recommend', $article);
        $data = $request->validate(['revision_id' => ['required', 'integer']]);
        $saved = $suggestions->saveDraft($request->user(), $recommendation, (int) $data['revision_id']);

        return redirect()->route('staff.articles.edit', $saved)->with('status', 'Suggested version saved as a draft. Finish the remaining checks before publishing.');
    }

    public function discard(Request $request, Article $article, ArticleRecommendation $recommendation, ArticleRecommendations $suggestions)
    {
        abort_unless((int) $recommendation->article_id === (int) $article->id, 404);
        $suggestions->discard($request->user(), $recommendation);

        return redirect()->route('staff.articles.edit', $article)->with('status', 'Kept your original article. Nothing was published.');
    }
}
