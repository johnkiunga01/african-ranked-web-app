<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Staff\SitePageController;
use App\Models\SitePage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;

/** Separately enabled operator intake for three policy drafts. Cannot publish or edit articles. */
class PolicyDraftIntegrationController extends Controller
{
    public const SLUGS = ['privacy-policy', 'terms-of-use', 'cookies-and-analytics'];

    private function authorizeActor(Request $request): void
    {
        abort_unless(config('editorial.policy_draft_integration_enabled'), 404);
        $actor = $request->attributes->get('editorial_actor');
        Gate::forUser($actor)->authorize('manage-staff');
        $request->setUserResolver(fn () => $actor);
    }

    public function index(Request $request)
    {
        $this->authorizeActor($request);

        return response()->json(['items' => SitePage::whereIn('slug', self::SLUGS)->with('currentRevision')->get()->map(fn ($page) => $this->record($page))]);
    }

    public function show(Request $request, SitePage $page)
    {
        $this->authorizeActor($request);
        abort_unless(in_array($page->slug, self::SLUGS, true) && ! $page->trashed_at, 404);

        return response()->json($this->record($page));
    }

    public function save(Request $request, string $slug, SitePageController $cms)
    {
        $this->authorizeActor($request);
        abort_unless(in_array($slug, self::SLUGS, true), 404);
        $data = $request->validate(['expected_revision_id' => ['present', 'nullable', 'integer', 'min:1']]);
        try {
            $page = DB::transaction(function () use ($request, $slug, $data, $cms) {
                $page = SitePage::where('slug', $slug)->lockForUpdate()->first();
                if ($page?->trashed_at || ($page ? (int) $page->current_revision_id !== (int) $data['expected_revision_id'] : $data['expected_revision_id'] !== null)) {
                    throw ValidationException::withMessages(['expected_revision_id' => 'This policy changed. Reload before saving a revision.']);
                }
                $request->merge(['slug' => $slug, 'revision_id' => $data['expected_revision_id']]);
                // Use the same validation, immutable revision save, author and audit as the staff CMS.
                $cms->save($request, $page);

                return SitePage::where('slug', $slug)->with('currentRevision')->firstOrFail();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['expected_revision_id' => 'Another editor created this policy. Reload before saving.']);
        }

        return response()->json($this->record($page), 201);
    }

    public function preview(Request $request, SitePage $page, SitePageController $cms)
    {
        $this->authorizeActor($request);
        abort_unless(in_array($page->slug, self::SLUGS, true) && ! $page->trashed_at, 404);

        return response()->view('staff.website.page-preview', ['page' => $page, 'revision' => $page->currentRevision, 'errors' => new ViewErrorBag]);
    }

    private function record(SitePage $page): array
    {
        $r = $page->currentRevision;

        return ['id' => $page->id, 'slug' => $page->slug, 'revision_id' => $r?->id,
            'revision_number' => $r?->number, 'published_revision_id' => $page->published_revision_id,
            'status' => $page->published_revision_id === $page->current_revision_id && $page->published_revision_id ? 'published' : 'draft',
            'title' => $r?->title, 'description' => $r?->description,
            'body_markdown' => $r?->body_markdown, 'in_footer' => $r?->in_footer,
            'content_sha256' => hash('sha256', $r?->body_markdown ?? ''),
            'cms_url' => route('staff.pages.edit', $page), 'preview_url' => route('staff.pages.preview', $page),
            'public_url' => url($page->publicPath()),
        ];
    }
}
