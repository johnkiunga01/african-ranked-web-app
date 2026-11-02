<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\EditorialBrief;
use App\Services\EditorialPlanImport;
use App\Services\EditorialWorkbook;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EditorialPlanController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['wave' => ['nullable', 'in:1,2,3'], 'drafts' => ['nullable', 'in:1'], 'q' => ['nullable', 'string', 'max:180']]);
        $briefs = EditorialBrief::with('article.currentRevision', 'article.author')
            ->when($filters['wave'] ?? null, fn ($q, $wave) => $q->where('wave', $wave))
            ->when($filters['drafts'] ?? null, fn ($q) => $q->where('plan->team_draft', '!=', ''))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('plan_id', 'like', '%'.$term.'%')->orWhere('keyword', 'like', '%'.$term.'%')))
            ->orderBy('wave')->orderBy('plan_id')->paginate(25)->withQueryString();

        return view('staff.editorial-plan', compact('briefs', 'filters'));
    }

    public function preview(Request $request, EditorialWorkbook $reader)
    {
        $request->validate(['workbook' => ['required', 'file', 'extensions:xlsx', 'max:4096']]);
        $intake = $reader->read($request->file('workbook')->getRealPath(), $request->file('workbook')->getClientOriginalName());
        // Server-side session; no workbook path, body or capability is trusted from a hidden form.
        $request->session()->put('editorial_intake', $intake + ['expires' => now()->addMinutes(30)->timestamp]);

        return view('staff.editorial-plan-preview', ['intake' => $intake]);
    }

    public function commit(Request $request, EditorialPlanImport $importer)
    {
        $request->validate(['confirm_private' => ['accepted']]);
        $intake = $request->session()->get('editorial_intake');
        if (! $intake || $intake['expires'] < now()->timestamp) {
            throw ValidationException::withMessages(['workbook' => 'The preview expired or was already imported. Upload and preview again.']);
        }
        $count = $importer->commit($request->user(), $intake);
        $request->session()->forget('editorial_intake');

        return redirect()->route('staff.editorial-plan')->with('status', $count.' private briefs imported. Existing articles and publication decisions were preserved.');
    }

    public function start(Request $request, EditorialBrief $brief, EditorialPlanImport $importer)
    {
        $article = $importer->start($request->user(), $brief);

        return redirect()->route('staff.articles.edit', $article)->with('status', 'Opened the private draft. Research and review are required before publication.');
    }
}
