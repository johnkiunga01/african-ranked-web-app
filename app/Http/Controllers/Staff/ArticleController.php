<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RevisionStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ArticleSeoBatch;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\ArticleSeoAnalysis;
use App\Services\EditorialWorkflow;
use App\Services\SeedArticleImporter;
use App\Services\SiteSettings;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Article::class);
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['all', 'drafts', 'review', 'published', 'trash'])],
            'q' => ['nullable', 'string', 'max:180'],
            'status' => ['nullable', Rule::in(['draft', 'in_review', 'changes_requested', 'approved', 'published'])],
            'category' => ['nullable', Rule::in(app(Catalog::class)->categories()->pluck('slug')->all())],
            'source' => ['nullable', Rule::in(['all', 'cms', 'examples'])],
            'sort' => ['nullable', Rule::in(['updated', 'oldest', 'title', 'title_desc', 'created', 'created_oldest'])],
            'per_page' => ['nullable', Rule::in([10, 25, 50])],
            'author' => ['nullable', 'integer', 'min:1'],
            'publication' => ['nullable', Rule::in(['live', 'private', 'modified'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'examples_page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);
        $base = Article::query()->when(! $request->user()->isSuperAdmin(), fn ($q) => $q->where('user_id', $request->user()->id));
        $counts = [
            'all' => (clone $base)->whereNull('trashed_at')->count(),
            'drafts' => (clone $base)->whereNull('trashed_at')->whereHas('currentRevision', fn ($q) => $q->whereIn('status', ['draft', 'changes_requested']))->count(),
            'review' => (clone $base)->whereNull('trashed_at')->whereHas('currentRevision', fn ($q) => $q->where('status', 'in_review'))->count(),
            'published' => (clone $base)->whereNull('trashed_at')->whereNotNull('published_revision_id')->count(),
            'trash' => (clone $base)->whereNotNull('trashed_at')->count(),
        ];
        $tab = $filters['tab'] ?? 'all';
        if ($tab === 'trash') {
            $base->whereNotNull('trashed_at');
        } else {
            $base->whereNull('trashed_at');
            match ($tab) {
                'drafts' => $base->whereHas('currentRevision', fn ($q) => $q->whereIn('status', ['draft', 'changes_requested'])),
                'review' => $base->whereHas('currentRevision', fn ($q) => $q->where('status', 'in_review')),
                'published' => $base->whereNotNull('published_revision_id'),
                default => null,
            };
        }
        if (! $request->user()->isSuperAdmin()) {
            unset($filters['author']);
        }
        $query = (clone $base)->when($filters['author'] ?? null, fn ($q, $author) => $q->where('user_id', $author))
            ->when($filters['publication'] ?? null, function ($q, $publication) {
                match ($publication) {
                    'live' => $q->whereNotNull('published_revision_id'),
                    'private' => $q->whereNull('published_revision_id'),
                    'modified' => $q->whereNotNull('published_revision_id')->whereColumn('current_revision_id', '!=', 'published_revision_id'),
                };
            })->with(['currentRevision', 'publishedRevision', 'author'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(function ($q) use ($term) {
                $q->where('slug', 'like', '%'.$term.'%')->orWhereHas('currentRevision', fn ($r) => $r->where('title', 'like', '%'.$term.'%'));
            }))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->whereHas('currentRevision', fn ($r) => $r->where('status', $status)))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->whereHas('currentRevision', fn ($r) => $r->where('category_slug', $category)));
        if (($filters['source'] ?? '') === 'examples') {
            $query->whereRaw('1 = 0');
        }
        match ($filters['sort'] ?? 'updated') {
            'created' => $query->latest('created_at')->orderByDesc('id'),
            'created_oldest' => $query->oldest('created_at')->orderBy('id'),
            'title_desc' => $query->orderByDesc(ArticleRevision::select('title')->whereColumn('id', 'articles.current_revision_id')->limit(1))->orderByDesc('id'),
            'oldest' => $query->oldest('updated_at')->orderBy('id'),
            'title' => $query->orderBy(ArticleRevision::select('title')->whereColumn('id', 'articles.current_revision_id')->limit(1))->orderBy('id'),
            default => $query->latest('updated_at')->orderByDesc('id'),
        };
        $articles = $query->paginate((int) ($filters['per_page'] ?? 10))->withQueryString();
        $examples = collect();
        if ($request->user()->isSuperAdmin()) {
            $known = Article::pluck('slug')->all();
            $examples = collect(app(Catalog::class)->data('articles'))->reject(fn ($a) => in_array($a['slug'], $known, true));
        }
        $exampleTotal = $examples->count();
        $examples = $examples->filter(fn ($a) => (! filled($filters['q'] ?? null) || str_contains(mb_strtolower($a['title'].' '.$a['slug']), mb_strtolower($filters['q']))) &&
            (! filled($filters['category'] ?? null) || $a['category'] === $filters['category']) &&
            ! filled($filters['status'] ?? null) && ($filters['source'] ?? 'all') !== 'cms'
        );
        if (in_array($filters['sort'] ?? '', ['title', 'title_desc'], true)) {
            $examples = ($filters['sort'] === 'title_desc' ? $examples->sortByDesc('title') : $examples->sortBy('title'));
        }
        if (filled($filters['author'] ?? null) || filled($filters['publication'] ?? null)) {
            $examples = collect();
        }
        $examplePage = (int) ($filters['examples_page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $examples = new LengthAwarePaginator($examples->values()->forPage($examplePage, $perPage), $examples->count(), $perPage, $examplePage, ['path' => $request->url(), 'pageName' => 'examples_page', 'query' => $request->query()]);
        $owners = $request->user()->isSuperAdmin() ? User::where('is_active', true)->whereNotNull('email_verified_at')->orderBy('name')->get() : collect();
        $categories = app(Catalog::class)->categories();

        $seoBatches = ArticleSeoBatch::when(! $request->user()->isSuperAdmin(), fn ($q) => $q->where('user_id', $request->user()->id))->withCount('items')->latest('id')->limit(3)->get();

        return view('staff.articles.index', compact('articles', 'examples', 'exampleTotal', 'categories', 'filters', 'counts', 'owners', 'tab', 'seoBatches') + ['homepageSelection' => app(SiteSettings::class)->snapshot('homepage'), 'authorAi' => app(SiteSettings::class)->snapshot('general')['value']['author_ai_enabled']]);
    }

    public function import(Request $request, string $slug, SeedArticleImporter $importer)
    {
        Gate::authorize('manage-staff');
        $request->validate(['understand_private' => ['accepted']]);
        $record = collect(app(Catalog::class)->data('articles'))->firstWhere('slug', $slug);
        abort_unless($record, 404);
        $article = $importer->import($record, $request->user());

        return redirect()->route('staff.articles.edit', $article)->with('status', 'Example imported as your private draft. It is excluded from the public preview until reviewed publication.');
    }

    public function assign(Request $request, Article $article)
    {
        Gate::authorize('manage-staff');
        $data = $request->validate(['user_id' => ['required', 'integer'], 'revision_id' => ['required', 'integer']]);
        DB::transaction(function () use ($data, $article, $request) {
            $article = Article::lockForUpdate()->findOrFail($article->id);
            if ((int) $article->current_revision_id !== (int) $data['revision_id']) {
                throw ValidationException::withMessages(['assignment' => 'This article changed. Reload before assigning.']);
            }
            $owner = User::whereKey($data['user_id'])->where('is_active', true)->whereNotNull('email_verified_at')->first();
            if (! $owner) {
                throw ValidationException::withMessages(['user_id' => 'Choose an active, verified staff member.']);
            }
            $revision = $article->currentRevision->replicate(['number', 'status', 'reviewed_by', 'reviewed_at', 'published_at', 'review_checklist', 'review_notes']);
            $revision->number = $article->revisions()->max('number') + 1;
            $revision->status = RevisionStatus::Draft;
            $revision->author_name = $owner->name;
            $revision->author_title = $owner->job_title;
            $revision->created_by = $request->user()->id;
            $revision->save();
            $article->user_id = $owner->id;
            $article->current_revision_id = $revision->id;
            $article->save();
            AuditEvent::record($request->user(), 'article.assigned', $article, ['owner_id' => $owner->id]);
        });

        return back()->with('status', 'Article assigned in a new draft revision. Any published version is unchanged.');
    }

    public function create(Catalog $catalog)
    {
        Gate::authorize('create', Article::class);

        return view('staff.articles.edit', ['article' => null, 'revision' => null, 'categories' => $catalog->categories(), 'editions' => $catalog->regions()]);
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'slug' => $article ? ['exclude'] : ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles', 'slug')],
            'title' => ['required', 'string', 'max:180'], 'excerpt' => ['required', 'string', 'max:500'],
            'category_slug' => ['required', Rule::in(app(Catalog::class)->categories()->pluck('slug')->all())],
            'body_markdown' => ['required', 'string', 'max:150000'], 'image' => ['nullable', 'url:https', 'max:2048'],
            'source_notes' => ['nullable', 'string', 'max:30000'],
            'seo_title' => ['nullable', 'string', 'max:180'], 'meta_description' => ['nullable', 'string', 'max:500'],
            'focus_keyword' => ['nullable', 'string', 'max:180'], 'image_alt' => ['nullable', 'string', 'max:300'],
            'faqs_text' => ['sometimes', 'nullable', 'string', 'max:30000'],
            'all_editions' => ['sometimes', 'boolean'], 'regions' => ['sometimes', 'array', 'max:50'],
            'regions.*' => ['required', Rule::in(array_keys(app(Catalog::class)->regions()))],
            'revision_id' => $article ? ['required', 'integer'] : ['nullable'],
        ]);
    }

    public function store(Request $request, EditorialWorkflow $workflow)
    {
        Gate::authorize('create', Article::class);
        $article = $workflow->save($request->user(), $this->validated($request));

        return redirect()->route('staff.articles.edit', $article)->with('status', 'Draft created. It is private until reviewed and published.');
    }

    public function edit(Article $article, Catalog $catalog)
    {
        Gate::authorize('view', $article);
        if ($article->trashed_at) {
            return redirect()->route('staff.articles.index', ['tab' => 'trash'])->with('status', 'Restore this article before editing.');
        }

        return view('staff.articles.edit', ['article' => $article, 'revision' => $article->currentRevision, 'history' => $article->revisions()->select(['id', 'article_id', 'number', 'status', 'created_at'])->paginate(15, ['*'], 'history_page'), 'analysis' => app(ArticleSeoAnalysis::class)->analyze($article, $article->currentRevision), 'categories' => $catalog->categories(), 'editions' => $catalog->regions(), 'owners' => auth()->user()->isSuperAdmin() ? User::where('is_active', true)->whereNotNull('email_verified_at')->orderBy('name')->get() : collect()]);
    }

    public function update(Request $request, Article $article, EditorialWorkflow $workflow)
    {
        Gate::authorize('update', $article);
        $workflow->save($request->user(), $this->validated($request, $article), $article);

        return back()->with('status', 'New draft revision saved. Any published version stays unchanged.');
    }

    public function preview(Article $article)
    {
        Gate::authorize('view', $article);

        return view('staff.articles.preview', ['article' => $article, 'revision' => $article->currentRevision]);
    }

    public function action(Request $request, Article $article, string $action, EditorialWorkflow $workflow)
    {
        abort_unless(in_array($action, ['submit', 'request_changes', 'approve', 'publish', 'unpublish'], true), 404);
        Gate::authorize($action === 'submit' ? 'update' : (in_array($action, ['publish', 'unpublish'], true) ? 'publish' : 'review'), $article);
        $rules = ['revision_id' => ['required', 'integer']];
        if ($action === 'approve') {
            foreach (EditorialWorkflow::CHECKS as $check) {
                $rules[$check] = ['accepted'];
            }
        }
        $rules['review_notes'] = $action === 'request_changes' ? ['required', 'string', 'max:10000'] : ['nullable', 'string', 'max:10000'];
        $data = $request->validate($rules);
        $workflow->transition($request->user(), $article, $action, (int) $data['revision_id'], $data);

        return back()->with('status', match ($action) {
            'submit' => 'Article submitted for review.', 'approve' => 'This revision is approved. It is not published yet.',
            'publish' => 'Approved revision published.', 'unpublish' => 'Article withdrawn from public pages.',
            default => 'Changes requested. Feedback is visible to the author.',
        });
    }
}
