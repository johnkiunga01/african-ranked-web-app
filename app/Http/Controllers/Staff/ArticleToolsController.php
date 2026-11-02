<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RevisionStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleResearch;
use App\Models\AuditEvent;
use App\Services\ArticleLifecycle;
use App\Services\ArticleSeoAnalysis;
use App\Services\EditorialWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ArticleToolsController extends Controller
{
    public function seo(Article $article, ArticleSeoAnalysis $analyzer)
    {
        Gate::authorize('view', $article);
        $revision = $article->currentRevision;

        return view('staff.articles.seo', compact('article', 'revision') + ['analysis' => $analyzer->analyze($article, $revision), 'research' => ArticleResearch::current($article)]);
    }

    public function metadata(Request $request, Article $article, EditorialWorkflow $workflow)
    {
        Gate::authorize('update', $article);
        $data = $request->validate(['revision_id' => ['required', 'integer'], 'seo_title' => ['nullable', 'string', 'max:180'], 'meta_description' => ['nullable', 'string', 'max:500'], 'focus_keyword' => ['nullable', 'string', 'max:180'], 'image_alt' => ['nullable', 'string', 'max:300']]);
        $prior = $article->currentRevision;
        $workflow->save($request->user(), $data + ['title' => $prior->title, 'excerpt' => $prior->excerpt, 'category_slug' => $prior->category_slug, 'body_markdown' => $prior->body_markdown, 'preserve_body' => true, 'image' => $prior->image, 'source_notes' => $prior->source_notes], $article);

        return redirect()->route('staff.articles.seo', $article)->with('status', 'Search metadata saved as a new draft. The published article is unchanged.');
    }

    public function revision(Article $article, int $revision)
    {
        Gate::authorize('view', $article);
        $historical = $article->revisions()->findOrFail($revision);

        return view('staff.articles.revision', ['article' => $article, 'historical' => $historical, 'current' => $article->currentRevision]);
    }

    public function restore(Request $request, Article $article, int $revision)
    {
        Gate::authorize('update', $article);
        $data = $request->validate(['revision_id' => ['required', 'integer']]);
        DB::transaction(function () use ($request, $article, $revision, $data) {
            $article = Article::lockForUpdate()->findOrFail($article->id);
            if ((int) $article->current_revision_id !== (int) $data['revision_id']) {
                throw ValidationException::withMessages(['revision_id' => 'This article changed. Reload before restoring.']);
            }
            $source = $article->revisions()->findOrFail($revision);
            $copy = $source->replicate(['number', 'status', 'reviewed_by', 'reviewed_at', 'published_at', 'review_checklist', 'review_notes']);
            $copy->number = $article->revisions()->max('number') + 1;
            $copy->status = RevisionStatus::Draft;
            $copy->created_by = $request->user()->id;
            $copy->author_name = $article->author?->name ?? 'Unassigned author';
            $copy->author_title = $article->author?->job_title ?? 'Contributor';
            $copy->save();
            $article->current_revision_id = $copy->id;
            $article->save();
            AuditEvent::record($request->user(), 'article.restored', $article, ['source_revision' => $source->number, 'new_revision' => $copy->number]);
        });

        return redirect()->route('staff.articles.edit', $article)->with('status', 'Historical content restored as a new private draft. Editorial approval is required again; any published version is unchanged.');
    }

    public function bulk(Request $request, EditorialWorkflow $workflow)
    {
        if (in_array($request->input('operation'), ['publish', 'gemini'], true)) {
            return app(BulkArticleController::class)->prepare($request);
        }
        $data = $request->validate([
            'operation' => ['required', Rule::in(['submit', 'unpublish', 'trash', 'restore'])],
            'selected' => ['required', 'array', 'min:1', 'max:50'],
            'selected.*' => ['required', 'integer', 'min:1'],
            'confirm' => ['accepted'],
        ]);
        DB::transaction(function () use ($request, $data, $workflow) {
            $selected = $data['selected'];
            ksort($selected, SORT_NUMERIC);
            foreach ($selected as $id => $revision) {
                abort_unless(ctype_digit((string) $id), 422);
                $article = Article::findOrFail($id);
                if (in_array($data['operation'], ['trash', 'restore'], true)) {
                    app(ArticleLifecycle::class)->change($request->user(), $article, $data['operation'], (int) $revision);
                } else {
                    $workflow->transition($request->user(), $article, $data['operation'], (int) $revision);
                }
            }
        });

        return redirect()->route('staff.articles.index', $request->only(['tab', 'q', 'status', 'category', 'source', 'sort', 'per_page', 'author', 'publication', 'page']))
            ->with('status', count($data['selected']).' articles updated. The batch was applied as one transaction.');
    }
}
