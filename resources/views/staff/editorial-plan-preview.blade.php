@extends('staff.layout')
@section('title', 'Preview editorial import')
@section('content')
<h1 class="text-3xl font-bold">Preview editorial import</h1>
<p class="mt-3 text-stone-600">{{ count($intake['records']) }} briefs · {{ $intake['drafts'] }} populated team drafts. No article has been changed.</p>

<div class="staff-panel mt-5">
    <h2 class="font-bold">Existing content and duplicate check</h2>
    <p class="mt-2 text-sm text-stone-600">The CMS reconciles plan IDs and article slugs before making changes. A matching slug reuses the existing brief and keeps its original ID; it does not create a duplicate article.</p>
    <dl class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-5">
        <div><dt class="text-stone-500">New briefs</dt><dd class="font-bold">{{ $reconciliation['counts']['new'] }}</dd></div>
        <div><dt class="text-stone-500">Existing ID</dt><dd class="font-bold">{{ $reconciliation['counts']['existing'] }}</dd></div>
        <div><dt class="text-stone-500">Existing slug</dt><dd class="font-bold">{{ $reconciliation['counts']['reused_slug'] }}</dd></div>
        <div><dt class="text-stone-500">Existing article</dt><dd class="font-bold">{{ $reconciliation['counts']['linked_article'] }}</dd></div>
        <div><dt class="text-stone-500">Needs resolution</dt><dd class="font-bold">{{ $reconciliation['counts']['conflict'] }}</dd></div>
    </dl>
    @if($reconciliation['counts']['conflict'] > 0)
        <p class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-900" role="alert">One or more workbook plan IDs already belong to a different slug. Review the marked rows and correct their IDs before importing. Nothing has changed.</p>
    @endif
</div>

<div class="staff-panel mt-5">
    <ul class="list-disc space-y-2 pl-5 text-sm">
        <li>Matching slugs reuse existing briefs and preserve original plan IDs, team manuscripts and linked CMS articles.</li>
        <li>Starting a new article uses its private team draft; importing the workbook alone does not create, publish or approve CMS articles.</li>
        <li>Re-importing updates planning fields only. No author, article body, revision or publication pointer changes.</li>
        <li>Workbook estimates and source suggestions are not independently verified research.</li>
    </ul>
</div>

<div class="staff-panel mt-5 max-h-96 overflow-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr>
                <th class="p-2">Workbook ID</th>
                <th class="p-2">Title</th>
                <th class="p-2">Wave</th>
                <th class="p-2">Team draft</th>
                <th class="p-2">CMS action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($intake['records'] as $i => $record)
                <tr class="border-t border-stone-200">
                    <td class="p-2">{{ $record['plan_id'] }}</td>
                    <td class="p-2">{{ $record['title'] }}</td>
                    <td class="p-2">{{ $record['wave'] }}</td>
                    <td class="p-2">{{ filled($record['plan']['team_draft']) ? 'Available' : 'None' }}</td>
                    <td class="p-2">
                        @if($reconciliation['items'][$i]['status'] === 'new')
                            Create private brief
                        @elseif($reconciliation['items'][$i]['status'] === 'existing')
                            Update {{ $reconciliation['items'][$i]['matched_id'] }}
                        @elseif($reconciliation['items'][$i]['status'] === 'reused_slug')
                            Reuse {{ $reconciliation['items'][$i]['matched_id'] }} (same slug)
                        @elseif($reconciliation['items'][$i]['status'] === 'linked_article')
                            Link existing CMS article
                        @else
                            <span class="font-bold text-red-700">Plan ID conflict</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($reconciliation['counts']['conflict'] === 0)
    <form method="POST" action="{{ route('staff.editorial-plan.commit') }}" class="mt-6">
        @csrf
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="confirm_private" value="1" required>
            <span>I reviewed the mapping and want to import these private briefs, preserving existing CMS articles.</span>
        </label>
        <div class="mt-4 flex gap-3">
            <button class="staff-button">Import private briefs</button>
            <a class="staff-secondary" href="{{ route('staff.editorial-plan') }}">Cancel</a>
        </div>
    </form>
@else
    <a class="staff-secondary mt-6 inline-flex" href="{{ route('staff.editorial-plan') }}">Back to editorial plan</a>
@endif
@endsection
