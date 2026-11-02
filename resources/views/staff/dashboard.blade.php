@extends('staff.layout')
@section('title', 'Workspace overview')
@section('content')
@if($growth)
@include('staff.growth-overview', $growth)
@else
<div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-sm text-amber-800">{{ auth()->user()->isSuperAdmin() ? 'Publishing overview' : 'Your writing desk' }}</p><h1 class="mt-1 text-3xl font-bold">Welcome, {{ auth()->user()->name }}</h1><p class="mt-2 text-sm text-stone-600">Turn a useful idea into a carefully reviewed article.</p></div><a href="{{ route('staff.articles.create') }}" class="staff-button">Write an article</a></div>
<div class="mt-8 grid gap-5 sm:grid-cols-3">@foreach(['Articles' => $total, 'Awaiting review' => $inReview, 'Published' => $published] as $label => $count)<div class="staff-panel"><p class="text-sm text-stone-500">{{ $label }}</p><p class="mt-3 text-4xl font-bold">{{ $count }}</p></div>@endforeach</div>
<section class="staff-panel mt-7"><h2 class="text-xl font-bold">The editorial process</h2><ol class="mt-4 grid gap-4 text-sm sm:grid-cols-4"><li><span class="font-semibold text-amber-800">1. Draft</span><p class="mt-1 text-stone-500">Write, add an image and record your sources.</p></li><li><span class="font-semibold text-amber-800">2. Submit</span><p class="mt-1 text-stone-500">Send your revision to the review queue.</p></li><li><span class="font-semibold text-amber-800">3. Review</span><p class="mt-1 text-stone-500">An administrator checks facts, attribution and completeness.</p></li><li><span class="font-semibold text-amber-800">4. Publish</span><p class="mt-1 text-stone-500">Only an approved revision goes live. Later edits remain drafts.</p></li></ol><a href="{{ route('staff.articles.index') }}" class="mt-6 inline-block text-sm font-semibold text-amber-800">Open article workspace →</a></section>
@endif
@if($events->isNotEmpty())<section class="staff-panel mt-7"><h2 class="text-lg font-bold">Recent activity</h2><ul class="mt-4 divide-y divide-stone-100 text-sm">@foreach($events as $event)<li class="flex flex-wrap justify-between gap-2 py-3"><span>{{ $event->label() }} <span class="text-stone-400">#{{ $event->subject_id }}</span></span><time class="text-xs text-stone-500">{{ $event->created_at->timezone(config('editorial.display_timezone'))->format('M j, H:i T') }}</time></li>@endforeach</ul></section>@endif
@endsection
