@extends('staff.layout')
@section('title', 'SEO optimisation progress')
@section('content')
<a class="text-sm font-semibold text-amber-800" href="{{ route('staff.articles.index') }}">← Articles</a>
<div class="mt-5 flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs uppercase tracking-widest text-stone-500">Batch #{{ $batch->id }}</p><h1 class="mt-2 text-2xl font-bold">Research & optimisation</h1><p class="mt-3 text-sm text-stone-600">OpenSEO research → Gemini metadata → private draft. Articles are not published by this job.</p></div><a class="staff-secondary" href="{{ route('staff.articles.seo-batches.show', $batch) }}">Refresh status</a></div>
<section class="staff-panel mt-6" data-seo-batch data-status-url="{{ route('staff.articles.seo-batches.show', $batch) }}">
    <p class="text-sm font-semibold" role="status" aria-live="polite" data-batch-summary>{{ $counts->get('completed', 0) }} completed · {{ $counts->get('failed', 0) }} failed · {{ $counts->get('skipped', 0) }} skipped · {{ $batch->items->count() - $counts->get('completed', 0) - $counts->get('failed', 0) - $counts->get('skipped', 0) }} pending</p>
    <p class="mt-2 text-xs leading-relaxed text-stone-500">You can leave this page. The background worker processes requests with rate and daily limits. Failed calls are not automatically retried. If queued items do not start, check the queue worker.</p>
    <ul class="mt-6 divide-y divide-stone-200">
    @foreach($batch->items as $item)
        <li class="flex flex-wrap items-start justify-between gap-4 py-5" data-batch-item="{{ $item->id }}"><div class="min-w-0 flex-1">
            @can('view', $item->article)
                <h2 class="text-sm font-semibold">{{ $item->sourceRevision->title }}</h2><p class="mt-1 text-xs text-stone-500">Source revision {{ $item->sourceRevision->number }}</p><div class="mt-3 flex gap-4 text-xs"><a class="font-semibold text-amber-800" href="{{ route('staff.articles.seo', $item->article) }}">Review SEO results</a><a class="text-amber-800" href="{{ route('staff.articles.edit', $item->article) }}">Edit article</a></div>
            @else
                <h2 class="text-sm font-semibold">Article access changed</h2>
            @endcan
            <p class="mt-3 text-xs leading-relaxed text-stone-600" data-item-message>{{ $item->message ?: 'Waiting for the background worker.' }}</p></div><span class="staff-badge" data-item-status>{{ ucfirst($item->status) }}</span></li>
    @endforeach
    </ul>
</section>
@endsection
