@extends('staff.layout')
@section('title', 'Editorial plan')
@section('content')
<h1 class="text-3xl font-bold">Editorial plan</h1>
<p class="mt-3 max-w-3xl text-sm text-stone-600">Your master guide, team drafts and CMS progress in one queue. Workbooks create private briefs. Existing articles stay intact, and estimated difficulty is kept separate from measured OpenSEO research.</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('staff.editorial-plan.preview') }}" class="staff-panel mt-6 flex flex-wrap items-end gap-4">@csrf<div><label for="workbook" class="block text-sm font-semibold">Import the master guide or team workbook</label><input id="workbook" name="workbook" type="file" accept=".xlsx" required class="mt-2 block text-sm"><p class="mt-2 text-xs text-stone-500">.xlsx, up to 4 MB. Preview the plan and draft counts before importing.</p></div><button class="staff-button">Preview workbook</button></form>

@if($sources->isNotEmpty())
<section class="staff-panel mt-5" aria-labelledby="bulk-private-drafts-heading">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="bulk-private-drafts-heading" class="text-lg font-bold">Create private article drafts in bulk</h2>
            <p class="mt-2 max-w-2xl text-sm text-stone-600">Use an imported workbook to create only eligible draft articles. Matching URLs, existing articles, research-only references and editorial holds are skipped. No review or publication takes place.</p>
        </div>
    </div>
    <form method="GET" action="{{ route('staff.editorial-plan.draft-batches.preview') }}" class="mt-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="draft-source" class="block text-sm font-semibold">Imported research workbook</label>
            <select id="draft-source" name="source_hash" class="staff-input mt-2 max-w-full" required>
                @foreach($sources as $source)
                    <option value="{{ $source->source_hash }}">{{ $source->filename }} · {{ $source->brief_count }} briefs</option>
                @endforeach
            </select>
        </div>
        <button class="staff-button">Review eligible private drafts</button>
    </form>
    @if($recentDraftBatches->isNotEmpty())
        <div class="mt-4 border-t border-stone-200 pt-3 text-sm">
            <h3 class="font-semibold">Recent batch progress</h3>
            <div class="mt-2 flex flex-wrap gap-3">
                @foreach($recentDraftBatches as $recent)
                    <a class="staff-secondary" href="{{ route('staff.editorial-plan.draft-batches.show', $recent) }}">Batch #{{ $recent->id }} · {{ $recent->total }} queued</a>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endif
<form method="GET" class="mt-6 flex flex-wrap items-end gap-3"><div><label for="plan-wave" class="block text-xs">Wave</label><select id="plan-wave" name="wave" class="staff-input"><option value="">All waves</option>@foreach([1,2,3] as $wave)<option value="{{ $wave }}" @selected(($filters['wave'] ?? '') == $wave)>Wave {{ $wave }}</option>@endforeach</select></div><div><label for="plan-search" class="block text-xs">Search plan</label><input id="plan-search" name="q" value="{{ $filters['q'] ?? '' }}" class="staff-input" maxlength="180"></div><label class="text-sm"><input type="checkbox" name="drafts" value="1" @checked($filters['drafts'] ?? false)> Team drafts first</label><button class="staff-secondary">Filter</button></form>
<div class="staff-panel mt-5 overflow-x-auto"><table class="w-full text-left text-sm"><caption class="sr-only">Editorial briefs and current CMS progress</caption><thead><tr><th class="p-3">Brief</th><th class="p-3">Research and structure</th><th class="p-3">Team / CMS progress</th><th class="p-3">Next step</th></tr></thead><tbody>@forelse($briefs as $brief)<tr class="border-t border-stone-200"><th class="p-3 align-top"><span class="block text-xs text-stone-500">{{ $brief->plan_id }} · Wave {{ $brief->wave }}</span><span class="mt-1 block">{{ $brief->title }}</span><span class="mt-2 block font-normal text-xs">{{ $brief->plan['fields']['Pillar'] ?? '' }} · {{ $brief->plan['fields']['Pillar role'] ?? '' }}</span></th><td class="p-3 align-top"><p>{{ $brief->keyword }}</p><p class="mt-2 text-xs">{{ $brief->plan['fields']['Template'] }} · {{ $brief->plan['fields']['Target body words'] }} target body words</p><details class="mt-2 text-xs"><summary>View brief and source requirements</summary><pre class="mt-2 max-w-lg whitespace-pre-wrap font-sans">{{ $brief->instructions() }}</pre></details></td><td class="p-3 align-top"><p>{{ filled($brief->plan['team_draft'] ?? '') ? 'Team draft available' : 'Research brief' }}</p><p class="mt-2 text-xs">CMS: {{ $brief->article?->currentRevision?->status?->value ?? 'Not started' }}</p>@if($brief->article?->author)<p class="mt-1 text-xs">{{ $brief->article->author->name }}</p>@endif</td><td class="p-3 align-top">@if($brief->article)<a class="staff-secondary" href="{{ route('staff.articles.edit', $brief->article) }}">Open article</a>@elseif(filled($brief->plan['related_existing_article_id'] ?? null))<a class="staff-secondary" href="{{ route('staff.articles.edit', $brief->plan['related_existing_article_id']) }}">Open existing article</a><p class="mt-2 text-xs text-stone-600">Research brief only; the existing CMS article belongs to plan {{ $brief->plan['related_existing_plan_id'] ?? 'another plan' }}. No duplicate article link was created.</p>@else<form method="POST" action="{{ route('staff.editorial-plan.start', $brief) }}">@csrf<button class="staff-secondary">Start private draft</button></form><details class="mt-3 text-xs"><summary>Use an existing article</summary><form class="mt-2" method="POST" action="{{ route('staff.editorial-plan.link',$brief) }}">@csrf<label class="sr-only" for="link-{{ $brief->id }}">Existing article for {{ $brief->plan_id }}</label><select id="link-{{ $brief->id }}" name="article_id" required class="staff-input"><option value="">Choose existing article</option>@foreach($linkable as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->currentRevision?->title }}</option>@endforeach</select><button class="staff-secondary mt-2">Link and preserve URL</button></form></details>@endif</td></tr>@empty<tr><td colspan="4" class="p-6">Import your workbook to start the editorial queue.</td></tr>@endforelse</tbody></table></div>
{{ $briefs->links('staff.pagination') }}
@endsection
