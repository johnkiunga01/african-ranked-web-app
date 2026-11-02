<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleResearch;
use App\Models\AuditEvent;
use App\Services\AiUsage;
use App\Services\EditorialWorkflow;
use App\Services\GptArticleCurator;
use App\Services\ProviderCredentials;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ArticleCurationController extends Controller
{
    public function generate(Request $request, Article $article, GptArticleCurator $curator, EditorialWorkflow $workflow)
    {
        Gate::authorize('curate', $article);
        $data = $request->validate(['revision_id' => ['required', 'integer'], 'brief' => ['required', 'string', 'min:30', 'max:10000'], 'confirm_external' => ['accepted']]);
        if (! filled(app(ProviderCredentials::class)->key('openai'))) {
            throw ValidationException::withMessages(['generation' => 'Configure OPENAI_API_KEY privately on the server before generating.']);
        }
        $article->load('currentRevision');
        $prior = $article->currentRevision;
        if ((int) $prior->id !== (int) $data['revision_id']) {
            throw ValidationException::withMessages(['generation' => 'This article changed. Reload before generating.']);
        }
        if (! filled($prior->focus_keyword) || ! filled($prior->source_notes) || mb_strlen(($prior->body_markdown ?? '').$prior->source_notes.$data['brief']) > 100000) {
            throw ValidationException::withMessages(['generation' => 'Save a target query and source notes first. Combined draft, notes and brief must be under 100,000 characters.']);
        }
        if (! ArticleResearch::current($article)) {
            throw ValidationException::withMessages(['generation' => 'Run research-led optimisation in the SEO workspace first. A current OpenSEO brief is required for article drafting.']);
        }
        try {
            $id = app(AiUsage::class)->generation($request->user(), $article, $prior->id);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['generation' => 'A generation was already requested for this saved revision. Review its result, or save a new draft revision before another request.']);
        }
        try {
            $generated = $curator->generate($article, $data['brief']);
            $result = DB::transaction(function () use ($generated, $prior, $article, $request, $workflow, $id) {
                $saved = $workflow->save($request->user(), $generated + ['revision_id' => $prior->id, 'category_slug' => $prior->category_slug, 'image' => $prior->image, 'image_alt' => $prior->image_alt, 'focus_keyword' => $prior->focus_keyword, 'source_notes' => $prior->source_notes."\n\nAI-assisted draft generated ".now()->toIso8601String().'. Model: '.config('services.openai.article_model').'. Sources and claims require human verification; no new research was performed automatically.'], $article);
                DB::table('article_generations')->where('id', $id)->update(['status' => 'completed', 'result_revision_id' => $saved->current_revision_id, 'updated_at' => now()]);
                AuditEvent::record($request->user(), 'article.ai_drafted', $saved, ['generation_id' => $id, 'model' => config('services.openai.article_model')]);

                return $saved;
            });
        } catch (Throwable $exception) {
            DB::table('article_generations')->where('id', $id)->update(['status' => 'failed', 'updated_at' => now()]);
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            throw ValidationException::withMessages(['generation' => $exception instanceof RuntimeException ? 'GPT drafting failed. No generated article was saved. The provider request was not retried; a charge may still apply. Save a new revision before retrying.' : 'The draft could not be saved. Reload and review the current revision before trying again.']);
        }

        return redirect()->route('staff.articles.edit', $result)->with('status', 'GPT created a new private draft revision. Check every claim, citation and ranking before submitting for review. No content was published.');
    }
}
