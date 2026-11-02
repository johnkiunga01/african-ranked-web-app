<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\SitePage;
use App\Services\MarkdownBlocks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SitePageController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:180']]);
        $pages = SitePage::whereNull('trashed_at')->with(['currentRevision', 'publishedRevision'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->whereHas('currentRevision', fn ($r) => $r->where('title', 'like', '%'.$term.'%')))->latest('updated_at')->paginate(20)->withQueryString();

        return view('staff.website.pages', compact('pages', 'filters'));
    }

    public function edit(?SitePage $page = null)
    {
        return view('staff.website.page-edit', ['page' => $page, 'revision' => $page?->currentRevision]);
    }

    public function save(Request $request, ?SitePage $page = null)
    {
        $data = $request->validate(['slug' => $page ? ['exclude'] : ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('site_pages', 'slug')], 'title' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:500'], 'body_markdown' => ['required', 'string', 'max:150000'], 'in_footer' => ['required', 'boolean'], 'revision_id' => $page ? ['required', 'integer'] : ['nullable']]);
        $saved = DB::transaction(function () use ($request, $page, $data) {
            $model = $page ? SitePage::lockForUpdate()->findOrFail($page->id) : SitePage::create(['slug' => $data['slug']]);
            if ($page && (int) $model->current_revision_id !== (int) $data['revision_id']) {
                throw ValidationException::withMessages(['page' => 'This page changed. Reload before saving.']);
            }
            $revision = $model->revisions()->create(['number' => ($model->revisions()->max('number') ?? 0) + 1, 'title' => $data['title'], 'description' => $data['description'] ?? '', 'body_markdown' => $data['body_markdown'], 'body' => app(MarkdownBlocks::class)->parse($data['body_markdown']), 'in_footer' => (bool) $data['in_footer'], 'created_by' => $request->user()->id]);
            $model->current_revision_id = $revision->id;
            $model->save();
            AuditEvent::record($request->user(), 'site.page_saved', $model, ['revision' => $revision->number]);

            return $model;
        });

        return redirect()->route('staff.pages.edit', $saved)->with('status', 'Page draft saved. The published page stays unchanged until you publish this revision.');
    }

    public function preview(SitePage $page)
    {
        return view('staff.website.page-preview', ['page' => $page, 'revision' => $page->currentRevision]);
    }

    public function action(Request $request, SitePage $page, string $action)
    {
        abort_unless(in_array($action, ['publish', 'unpublish'], true), 404);
        $data = $request->validate(['revision_id' => ['required', 'integer'], 'confirm' => ['accepted']]);
        DB::transaction(function () use ($request, $page, $data, $action) {
            $page = SitePage::lockForUpdate()->findOrFail($page->id);
            if ((int) $page->current_revision_id !== (int) $data['revision_id']) {
                throw ValidationException::withMessages(['page' => 'This page changed. Reload before publishing.']);
            }
            $revision = $page->currentRevision;
            if ($action === 'publish') {
                if (! $revision?->body) {
                    throw ValidationException::withMessages(['page' => 'Add page content before publishing.']);
                }
                $revision->published_at = now();
                $revision->save();
                $page->published_revision_id = $revision->id;
                $page->first_published_at ??= now();
            } else {
                $page->published_revision_id = null;
            }
            $page->save();
            AuditEvent::record($request->user(), 'site.page_'.$action, $page);
        });

        return back()->with('status', $action === 'publish' ? 'Saved page revision published. Navigation and sitemap now use that version.' : 'Page withdrawn from the website and discovery. Its revisions are preserved.');
    }
}
