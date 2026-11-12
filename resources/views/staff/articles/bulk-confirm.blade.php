@extends('staff.layout')
@section('title', $operation === 'publish' ? 'Publish selected articles' : 'Research & optimise selected articles')
@section('content')
<a class="text-sm font-semibold text-amber-800" href="{{ route('staff.articles.index') }}">← Articles</a>
<div class="mt-5 max-w-4xl">
    <p class="text-xs font-semibold uppercase tracking-widest text-stone-500">{{ $articles->count() }} selected articles</p>
    <h1 class="mt-2 text-2xl font-bold">{{ $operation === 'publish' ? 'Publish selected articles' : 'Research & optimise with Gemini' }}</h1>
    <p class="mt-3 text-sm leading-relaxed text-stone-600">{{ $operation === 'publish' ? 'Publish these exact saved revisions together. If an article changes or fails its publishing checks, the entire selection stays unchanged.' : 'OpenSEO supplies the keyword and SERP brief. Gemini uses that brief to improve search titles and descriptions in new private drafts. Publication stays separate.' }}</p>
    @if($operation === 'gemini')
        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold">{{ $remaining }} AI requests available today</p>
            <p class="mt-2 leading-relaxed">One Gemini request per article. Existing OpenSEO research is reused for 30 days. Missing briefs require keyword metrics and a top-10 SERP lookup, which consume OpenSEO credits. Google usage follows your account's quota and billing. No credits are purchased automatically.</p>
            <p class="mt-2 text-xs">Research market: {{ config('services.openseo.location_code') === 2840 ? 'United States' : config('services.openseo.location_code') }} · English · Desktop. A national benchmark is not global demand.</p>
        </div>
    @endif
    <div class="my-6 overflow-hidden rounded-xl border border-stone-200 bg-white"><ul class="divide-y divide-stone-100">
    @foreach($articles as $article)
        <li class="flex flex-wrap items-center justify-between gap-3 p-4"><span class="text-sm font-semibold">{{ $article->currentRevision->title }}</span><span class="text-xs text-stone-500">Revision {{ $article->currentRevision->number }} · {{ ucfirst(str_replace('_', ' ', $article->currentRevision->status->value)) }}</span></li>
    @endforeach
    </ul></div>
    <form method="POST" action="{{ route('staff.articles.bulk.execute', [], false) }}" class="space-y-5" data-bulk-confirm>
        @csrf
        <input type="hidden" name="operation" value="{{ $operation }}">
        @foreach($articles as $article)
            <input type="hidden" name="selected[{{ $article->id }}]" value="{{ $article->current_revision_id }}">
        @endforeach
        @if($operation === 'publish')
            <label class="flex items-start gap-3 text-sm leading-relaxed"><input class="mt-1" type="checkbox" name="editorial_approval" value="1" required><span>I have reviewed every selected article for factual accuracy, ranking completeness, attribution and image rights, and approve these revisions for publication.</span></label>
        @else
            <label class="flex items-start gap-3 text-sm leading-relaxed"><input class="mt-1" type="checkbox" name="ai_consent" value="1" required><span>Use my OpenSEO credits for missing research and send the saved article text and research brief to Gemini. Save results as drafts for review.</span></label>
            @if(!$configured)
                <p class="text-sm text-red-800">Add your Gemini key in Integrations before starting.</p>
            @endif
        @endif
        <div class="flex flex-wrap gap-3"><button class="staff-button" type="submit" @disabled($operation === 'gemini' && (!$configured || $articles->count() > $remaining))>{{ $operation === 'publish' ? 'Publish '.$articles->count().' articles' : 'Queue research & optimisation' }}</button><a class="staff-secondary" href="{{ route('staff.articles.index') }}">Cancel</a></div>
    </form>
</div>
@endsection
