<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditEvent;
use App\Models\EditorialBrief;
use App\Models\EditorialDraftBatch;
use App\Services\EditorialDraftBatchService;
use App\Services\EditorialPlanImport;
use App\Services\EditorialWorkbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditorialPlanController extends Controller
{
    public function index(Request $request, EditorialDraftBatchService $draftBatches)
    {
        $filters = $request->validate(['wave' => ['nullable', 'in:1,2,3'], 'drafts' => ['nullable', 'in:1'], 'q' => ['nullable', 'string', 'max:180']]);
        $briefs = EditorialBrief::with('article.currentRevision', 'article.author')
            ->when($filters['wave'] ?? null, fn ($q, $wave) => $q->where('wave', $wave))
            ->when($filters['drafts'] ?? null, fn ($q) => $q->where('plan->team_draft', '!=', ''))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('plan_id', 'like', '%'.$term.'%')->orWhere('keyword', 'like', '%'.$term.'%')))
            ->orderBy('wave')->orderBy('plan_id')->paginate(25)->withQueryString();

        $linkable = Article::whereNull('trashed_at')->with('currentRevision')->orderBy('slug')->get();

        $sources = $draftBatches->sources();
        $recentDraftBatches = EditorialDraftBatch::latest('id')->limit(6)->get();

        return view('staff.editorial-plan', compact('briefs', 'filters', 'linkable', 'sources', 'recentDraftBatches'));
    }

    public function preview(Request $request, EditorialWorkbook $reader)
    {
        $request->validate(['workbook' => ['required', 'file', 'extensions:xlsx', 'max:4096']]);
        $intake = $reader->read($request->file('workbook')->getRealPath(), $request->file('workbook')->getClientOriginalName());
        // Server-side session; no workbook path, body or capability is trusted from a hidden form.
        $request->session()->put('editorial_intake', $intake + ['expires' => now()->addMinutes(30)->timestamp]);

        // Post/Redirect/Get: validation errors from the later import POST must
        // redirect to a GET-capable preview, not back to a POST-only URL.
        return redirect()->route('staff.editorial-plan.preview.show', [], 303);
    }

    public function showPreview(Request $request, EditorialPlanImport $importer)
    {
        $intake = $request->session()->get('editorial_intake');
        if (! is_array($intake) || empty($intake['records']) || ($intake['expires'] ?? 0) < now()->timestamp) {
            $request->session()->forget('editorial_intake');

            return redirect()->route('staff.editorial-plan')->withErrors([
                'workbook' => 'The workbook preview expired or its upload was interrupted. Select the file and preview it again.',
            ]);
        }

        $reconciliation = $importer->inspect($intake['records']);

        return response()->view('staff.editorial-plan-preview', ['intake' => $intake, 'reconciliation' => $reconciliation])
            ->header('Cache-Control', 'private, no-store');
    }

    public function commit(Request $request, EditorialPlanImport $importer)
    {
        $request->validate(['confirm_private' => ['accepted']]);
        $intake = $request->session()->get('editorial_intake');
        if (! $intake || $intake['expires'] < now()->timestamp) {
            throw ValidationException::withMessages(['workbook' => 'The preview expired or was already imported. Upload and preview again.']);
        }
        $counts = $importer->commit($request->user(), $intake);
        $request->session()->forget('editorial_intake');

        $message = "{$counts['new']} new private briefs created; {$counts['linked_article']} linked to existing articles; "
            ."{$counts['linked_elsewhere']} research briefs referenced existing articles without duplicate links; "
            ."{$counts['existing']} existing briefs refreshed; {$counts['reused_slug']} existing slugs reconciled. "
            .'No article bodies, author assignments, approvals or publications were changed.';

        return redirect()->route('staff.editorial-plan')->with('status', $message);
    }

    public function previewPrivateDrafts(Request $request, EditorialDraftBatchService $draftBatches)
    {
        $data = $request->validate(['source_hash' => ['required', 'regex:/^[a-f0-9]{64}$/D']]);
        $inspection = $draftBatches->inspect($data['source_hash']);
        $source = EditorialBrief::where('source_hash', $data['source_hash'])->firstOrFail();

        return response()->view('staff.editorial-draft-batch-preview', [
            'inspection' => $inspection,
            'sourceHash' => $data['source_hash'],
            'filename' => $source->plan['file'] ?? 'Imported workbook',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function queuePrivateDrafts(Request $request, EditorialDraftBatchService $draftBatches)
    {
        $data = $request->validate([
            'source_hash' => ['required', 'regex:/^[a-f0-9]{64}$/D'],
            'confirm_private' => ['accepted'],
        ]);

        $batch = $draftBatches->queue($request->user(), $data['source_hash']);

        return redirect()->route('staff.editorial-plan.draft-batches.show', $batch, 303)
            ->with('status', 'The selected eligible briefs were queued for private draft creation. Existing and held topics are skipped.');
    }

    public function showDraftBatch(Request $request, EditorialDraftBatch $batch)
    {
        $counts = $batch->items()->select('status')
            ->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status')
            ->map(fn ($total) => (int) $total);
        $remaining = (int) ($counts['queued'] ?? 0) + (int) ($counts['running'] ?? 0);
        if ($request->expectsJson()) {
            return response()->json([
                'total' => $batch->total,
                'counts' => $counts,
                'remaining' => $remaining,
                'complete' => $remaining === 0,
            ])->header('Cache-Control', 'private, no-store');
        }

        $items = $batch->items()->with(['brief', 'article'])->orderBy('id')->paginate(50);

        return response()->view('staff.editorial-draft-batch', compact('batch', 'items', 'counts', 'remaining'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function start(Request $request, EditorialBrief $brief, EditorialPlanImport $importer)
    {
        $article = $importer->start($request->user(), $brief);

        return redirect()->route('staff.articles.edit', $article)->with('status', 'Opened the private draft. Research and review are required before publication.');
    }

    public function link(Request $request, EditorialBrief $brief)
    {
        $data = $request->validate(['article_id' => ['required', 'integer', 'exists:articles,id']]);
        DB::transaction(function () use ($request, $brief, $data) {
            $brief = EditorialBrief::lockForUpdate()->findOrFail($brief->id);
            if ($brief->article_id || EditorialBrief::where('article_id', $data['article_id'])->exists()) {
                throw ValidationException::withMessages(['article_id' => 'This brief or article is already linked. No mapping was changed.']);
            }
            abort_if(Article::findOrFail($data['article_id'])->trashed_at, 422);
            $brief->update(['article_id' => $data['article_id']]);
            AuditEvent::record($request->user(), 'editorial_plan.linked', $brief, ['article_id' => $data['article_id']]);
        });

        return back()->with('status', 'Brief linked to the existing article. Its URL, author, content and publication are preserved.');
    }
}
