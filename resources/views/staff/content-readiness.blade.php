@extends('staff.layout')
@section('title', 'Content readiness')
@section('content')
<h1 class="text-3xl font-bold">Content readiness</h1>
<p class="mt-3 text-sm text-stone-600">These checks identify structural blockers before publication. Passing them does not verify a claim, source, byline or image licence. AI output stays a private draft until human review.</p>
<div class="staff-panel mt-6 overflow-x-auto"><table class="w-full text-left text-sm"><caption class="sr-only">Readiness of current saved revisions</caption><thead><tr><th scope="col">Article</th><th scope="col">Saved revision</th><th scope="col">Publication blockers</th></tr></thead><tbody>
@forelse($articles as $article)
<tr class="border-b border-stone-200"><th scope="row" class="py-4 pr-4"><a href="{{ route('staff.articles.edit', $article) }}">{{ $article->currentRevision?->title }}</a></th><td class="py-4 pr-4">{{ $article->currentRevision?->number }}</td><td class="py-4">@if(isset($reports[$article->id]))<p>{{ $reports[$article->id]['body_words'] }} words · {{ $reports[$article->id]['source_links'] }} source links · {{ $reports[$article->id]['complete_faqs'] }} complete FAQs</p><ul class="mt-2 list-disc pl-5">@forelse($reports[$article->id]['failures'] as $failure)<li>{{ $failure }}</li>@empty<li>Structural checks pass. Complete human review before publishing.</li>@endforelse</ul>@endif</td></tr>
@empty<tr><td colspan="3" class="py-4">No assigned articles to assess.</td></tr>@endforelse
</tbody></table></div>
{{ $articles->links('staff.pagination') }}
@endsection
