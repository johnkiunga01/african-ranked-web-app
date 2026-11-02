<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleLifecycle;
use App\Services\EditorialWorkflow;
use App\Services\ReviewedPublisher;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ArticleOperationsController extends Controller
{
    public function quickEdit(Request $request, Article $article, EditorialWorkflow $workflow)
    {
        Gate::authorize('update', $article);
        $data = $request->validate(['revision_id' => ['required', 'integer'], 'title' => ['required', 'string', 'max:180'], 'excerpt' => ['required', 'string', 'max:500'], 'category_slug' => ['required', Rule::in(app(Catalog::class)->categories()->pluck('slug')->all())]]);
        $prior = $article->currentRevision;
        $workflow->save($request->user(), $data + ['preserve_body' => true, 'body_markdown' => $prior->body_markdown, 'image' => $prior->image, 'source_notes' => $prior->source_notes], $article);

        return back()->with('status', 'Quick edit saved as a draft. The live version is unchanged.');
    }

    public function lifecycle(Request $request, Article $article, string $action, ArticleLifecycle $lifecycle)
    {
        abort_unless(in_array($action, ['trash', 'restore'], true), 404);
        $data = $request->validate(['revision_id' => ['required', 'integer']]);
        $lifecycle->change($request->user(), $article, $action, (int) $data['revision_id']);

        return redirect()->route('staff.articles.index', $action === 'trash' ? [] : ['tab' => 'drafts'])->with('status', $action === 'trash' ? 'Article moved to Trash. Its history is preserved.' : 'Article restored as a private draft.');
    }

    public function publishForm(Article $article)
    {
        Gate::authorize('publish', $article);
        abort_if($article->trashed_at, 422);

        return view('staff.articles.publish', ['article' => $article, 'revision' => $article->currentRevision]);
    }

    public function publish(Request $request, Article $article, EditorialWorkflow $workflow)
    {
        Gate::authorize('publish', $article);
        $data = $request->validate(['revision_id' => ['required', 'integer'], 'editorial_approval' => ['accepted']]);
        app(ReviewedPublisher::class)->publish($request->user(), $article, (int) $data['revision_id']);

        return redirect()->route('staff.articles.edit', $article)->with('status', 'Article published. The website, feed and sitemap now use this approved revision.');
    }
}
