<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Jobs\ResearchArticleSeo;
use App\Models\Article;
use App\Models\ArticleSeoBatch;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\AiUsage;
use App\Services\ProviderCredentials;
use App\Services\ReviewedPublisher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BulkArticleController extends Controller
{
    private function selection(Request $request, bool $lock = false): array
    {
        $data = $request->validate([
            'operation' => ['required', Rule::in(['publish', 'gemini'])],
            'selected' => ['required', 'array', 'min:1', 'max:50'],
            'selected.*' => ['required', 'integer', 'min:1'],
        ]);
        ksort($data['selected'], SORT_NUMERIC);
        $articles = collect();
        foreach ($data['selected'] as $id => $revision) {
            abort_unless(ctype_digit((string) $id) && (int) $id > 0, 422);
            $article = Article::with(['currentRevision', 'author'])->when($lock, fn ($q) => $q->lockForUpdate())->findOrFail($id);
            Gate::authorize($data['operation'] === 'publish' ? 'publish' : 'curate', $article);
            if ($article->trashed_at || (int) $article->current_revision_id !== (int) $revision) {
                throw ValidationException::withMessages(['selected' => 'An article changed or moved to Trash. Reload the list and select the current revisions.']);
            }
            $articles->push($article);
        }

        return [$data, $articles];
    }

    public function prepare(Request $request)
    {
        [$data, $articles] = $this->selection($request);

        return view('staff.articles.bulk-confirm', [
            'operation' => $data['operation'], 'articles' => $articles,
            'remaining' => app(AiUsage::class)->remaining($request->user()),
            'configured' => (bool) app(ProviderCredentials::class)->key('gemini'),
        ]);
    }

    public function execute(Request $request)
    {
        try {
            return $this->executeSelection($request);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            return redirect()->route('staff.articles.index')->withErrors($exception->errors());
        }
    }

    private function executeSelection(Request $request)
    {
        $operation = $request->input('operation');
        $request->validate($operation === 'publish' ? ['editorial_approval' => ['accepted']] : ['ai_consent' => ['accepted']]);
        try {
            $batch = DB::transaction(function () use ($request) {
                User::lockForUpdate()->findOrFail($request->user()->id);
                [$data, $articles] = $this->selection($request, true);
                if ($data['operation'] === 'publish') {
                    foreach ($articles as $article) {
                        app(ReviewedPublisher::class)->publish($request->user(), $article, (int) $data['selected'][$article->id]);
                    }

                    return null;
                }
                if (! app(ProviderCredentials::class)->key('gemini')) {
                    throw ValidationException::withMessages(['gemini' => 'Save a Gemini credential in Integrations before queueing optimisation.']);
                }
                if ($articles->count() > app(AiUsage::class)->remaining($request->user())) {
                    throw ValidationException::withMessages(['selected' => 'This selection exceeds your remaining daily AI allowance. Choose fewer articles or wait until the next UTC day.']);
                }
                $batch = ArticleSeoBatch::create(['user_id' => $request->user()->id, 'session_version' => $request->user()->session_version]);
                foreach ($articles as $article) {
                    if (DB::table('article_ai_requests')->where('article_id', $article->id)->where('source_revision_id', $article->current_revision_id)->where('provider', 'gemini')->where('purpose', 'seo_fix')->exists()) {
                        throw ValidationException::withMessages(['selected' => 'Gemini was already requested for a selected revision. Open its SEO page and save a new draft before requesting another generation.']);
                    }
                    $item = $batch->items()->create(['article_id' => $article->id, 'source_revision_id' => $article->current_revision_id]);
                    // Insert into the durable database queue in the same transaction.
                    ResearchArticleSeo::dispatch($item->id)->beforeCommit();
                }
                AuditEvent::record($request->user(), 'article.seo_batch_queued', $batch, ['articles' => $articles->count()]);

                return $batch;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['selected' => 'A selected revision is already in a Gemini batch. Open its batch results or save a new draft. No duplicate requests were queued.']);
        }
        if ($batch) {
            return redirect()->route('staff.articles.seo-batches.show', $batch)->with('status', 'Research-led optimisation queued. OpenSEO briefs are reused for 30 days; Gemini results become private drafts.');
        }

        return redirect()->route('staff.articles.index', ['tab' => 'published'])->with('status', 'Selected articles published. Already-live revisions were left unchanged.');
    }

    public function show(Request $request, ArticleSeoBatch $batch)
    {
        abort_unless($request->user()->isSuperAdmin() || (int) $batch->user_id === $request->user()->id, 403);
        $batch->load(['items.article', 'items.sourceRevision']);
        $counts = $batch->items->countBy('status');
        if ($request->expectsJson()) {
            return response()->json(['counts' => $counts, 'items' => $batch->items->map(fn ($item) => ['id' => $item->id, 'status' => $item->status, 'message' => $item->message])]);
        }

        return view('staff.articles.seo-batch', compact('batch', 'counts'));
    }
}
