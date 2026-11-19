@extends('staff.layout')
@section('title', 'Private draft batch')
@section('content')
@php
    $queued = (int) ($counts['queued'] ?? 0);
    $running = (int) ($counts['running'] ?? 0);
    $created = (int) ($counts['created'] ?? 0);
    $skipped = (int) ($counts['skipped'] ?? 0);
    $failed = (int) ($counts['failed'] ?? 0);
    $finished = $created + $skipped + $failed;
    $percentage = $batch->total ? min(100, round(100 * $finished / $batch->total)) : 100;
@endphp
<a href="{{ route('staff.editorial-plan') }}" class="text-sm text-stone-600 underline">Back to editorial plan</a>
<h1 class="mt-4 text-3xl font-bold">Bulk private draft creation</h1>
<p class="mt-2 text-sm text-stone-600">Batch #{{ $batch->id }} · {{ $batch->total }} selected briefs · Started {{ $batch->created_at->timezone('Africa/Nairobi')->format('j M Y, g:i A') }} EAT</p>
<div class="staff-panel mt-6" aria-live="polite">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-bold">{{ $remaining ? 'Creating private drafts' : 'Batch completed' }}</h2>
        <strong class="text-lg">{{ $percentage }}%</strong>
    </div>
    <div class="mt-4 h-3 overflow-hidden rounded-full bg-stone-200" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percentage }}" aria-label="Private draft creation progress">
        <div class="h-full rounded-full bg-amber-500" style="width: {{ $percentage }}%"></div>
    </div>
    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm sm:grid-cols-5">
        <div><dt class="text-stone-500">Drafts created</dt><dd class="text-2xl font-bold">{{ $created }}</dd></div>
        <div><dt class="text-stone-500">Queued</dt><dd class="text-2xl font-bold">{{ $queued }}</dd></div>
        <div><dt class="text-stone-500">Running</dt><dd class="text-2xl font-bold">{{ $running }}</dd></div>
        <div><dt class="text-stone-500">Safely skipped</dt><dd class="text-2xl font-bold">{{ $skipped }}</dd></div>
        <div><dt class="text-stone-500">Needs attention</dt><dd class="text-2xl font-bold">{{ $failed }}</dd></div>
    </dl>
    <p class="mt-4 text-sm text-stone-600">Only draft revisions are created. Existing articles, editorial review decisions and live publications are never modified.</p>
    <a class="staff-secondary mt-4 inline-flex" href="{{ route('staff.editorial-plan.draft-batches.show', $batch) }}">Refresh status</a>
</div>
<div class="staff-panel mt-5 overflow-x-auto">
    <table class="w-full text-left text-sm">
        <caption class="sr-only">Private draft creation results</caption>
        <thead><tr><th class="p-2">Brief</th><th class="p-2">Status</th><th class="p-2">Details</th><th class="p-2">Article</th></tr></thead>
        <tbody>
            @foreach($items as $item)
                <tr class="border-t border-stone-200">
                    <td class="p-2">{{ $item->brief?->plan_id }} · {{ $item->brief?->title }}</td>
                    <td class="p-2 font-semibold">{{ ucfirst($item->status) }}</td>
                    <td class="p-2">{{ $item->message ?? 'Queued for the CMS worker.' }}</td>
                    <td class="p-2">
                        @if($item->article)
                            <a class="staff-secondary" href="{{ route('staff.articles.edit', $item->article) }}">Open draft</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
{{ $items->links('staff.pagination') }}
@if($remaining > 0)
<script>
    window.setTimeout(() => {
        if (document.visibilityState === 'visible') window.location.reload();
    }, 5000);
</script>
@endif
@endsection
