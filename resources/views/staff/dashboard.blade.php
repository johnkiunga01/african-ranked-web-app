@extends('staff.layout')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-7">
    <div class="flex flex-wrap items-start justify-between gap-5">
        <div class="max-w-2xl">
            <p class="text-xs font-extrabold uppercase tracking-[.18em] text-amber-700">Editorial workspace</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-stone-950 sm:text-4xl">Your publication, at a glance.</h1>
            <p class="mt-3 text-sm leading-6 text-stone-600 sm:text-base">Manage stories, clear the review queue and keep African Ranked moving.</p>
        </div>
        <a href="{{ route('staff.articles.create') }}" class="staff-button min-h-12 gap-2 px-5">
            <x-icon name="plus" class="h-4 w-4" /> New article
        </a>
    </div>

    @if($isAdmin && !auth()->user()->two_factor_confirmed_at)
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-stone-800">
        <p><strong>Protect your publishing account.</strong> Two-factor authentication is not enabled.</p>
        <a href="{{ route('staff.security.index') }}" class="font-bold text-amber-900 underline underline-offset-4">Set up 2FA</a>
    </div>
    @endif

    <section aria-labelledby="performance-heading">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 id="performance-heading" class="text-lg font-extrabold tracking-tight text-stone-900">Editorial overview</h2>
            <span class="text-xs text-stone-500">Current CMS records</span>
        </div>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
            <a href="{{ route('staff.articles.index', ['tab' => 'all']) }}" class="staff-metric group">
                <span class="staff-metric-label">All articles</span>
                <strong class="staff-metric-value">{{ number_format($total) }}</strong>
                <span class="staff-metric-action">View library <x-icon name="arrow-right" class="h-3.5 w-3.5" /></span>
            </a>
            <a href="{{ route('staff.articles.index', ['tab' => 'published']) }}" class="staff-metric group">
                <span class="staff-metric-label">Published</span>
                <strong class="staff-metric-value">{{ number_format($published) }}</strong>
                <span class="staff-metric-action">See live stories <x-icon name="arrow-right" class="h-3.5 w-3.5" /></span>
            </a>
            <a href="{{ route('staff.articles.index', ['tab' => 'drafts']) }}" class="staff-metric group">
                <span class="staff-metric-label">Drafts to complete</span>
                <strong class="staff-metric-value">{{ number_format($drafts) }}</strong>
                <span class="staff-metric-action">Continue writing <x-icon name="arrow-right" class="h-3.5 w-3.5" /></span>
            </a>
            <a href="{{ route('staff.articles.index', ['tab' => 'review']) }}" class="staff-metric group">
                <span class="staff-metric-label">Awaiting review</span>
                <strong class="staff-metric-value">{{ number_format($inReview) }}</strong>
                <span class="staff-metric-action">Open review queue <x-icon name="arrow-right" class="h-3.5 w-3.5" /></span>
            </a>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.85fr)_minmax(290px,1fr)]">
        <section class="staff-panel !p-0" aria-labelledby="recent-articles-heading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-5 sm:px-6">
                <div>
                    <h2 id="recent-articles-heading" class="text-lg font-extrabold tracking-tight text-stone-950">Recently updated</h2>
                    <p class="mt-1 text-xs text-stone-500">Pick up where you left off</p>
                </div>
                <a href="{{ route('staff.articles.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-800 hover:underline">All articles <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
            <ul class="divide-y divide-stone-100">
                @forelse($recentArticles as $entry)
                    @php
                        $revision = $entry->currentRevision;
                        $status = $revision?->status?->value ?? 'draft';
                        $hasNewDraft = $entry->published_revision_id && $entry->current_revision_id !== $entry->published_revision_id;
                    @endphp
                    <li class="px-5 py-4 transition hover:bg-amber-50/40 sm:px-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <a href="{{ route('staff.articles.edit', $entry) }}" class="block text-sm font-bold leading-6 text-stone-900 hover:text-amber-800">{{ $revision?->title ?? $entry->slug }}</a>
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone-500">
                                    <span class="staff-status-chip {{ $entry->published_revision_id && ! $hasNewDraft ? 'staff-status-live' : ($status === 'in_review' ? 'staff-status-review' : '') }}">{{ $hasNewDraft ? 'Live + draft' : ($entry->published_revision_id ? 'Published' : ($status === 'in_review' ? 'In review' : 'Draft')) }}</span>
                                    @if($isAdmin && $entry->author)<span>{{ $entry->author->name }}</span>@endif
                                    <time datetime="{{ $entry->updated_at->toIso8601String() }}">{{ $entry->updated_at->timezone(config('editorial.display_timezone'))->diffForHumans() }}</time>
                                </div>
                            </div>
                            <a href="{{ route('staff.articles.edit', $entry) }}" class="staff-row-action shrink-0" aria-label="Edit {{ $revision?->title ?? $entry->slug }}"><x-icon name="arrow-up-right" class="h-4 w-4" /></a>
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-10 text-sm text-stone-600">No articles yet. <a href="{{ route('staff.articles.create') }}" class="font-bold text-amber-800 underline">Write your first story</a>.</li>
                @endforelse
            </ul>
        </section>

        <div class="space-y-5">
            <section class="rounded-3xl bg-[#171c20] p-6 text-white shadow-xl shadow-stone-900/10 sm:p-7" aria-labelledby="actions-heading">
                <p class="text-xs font-extrabold uppercase tracking-[.17em] text-amber-300">Quick actions</p>
                <h2 id="actions-heading" class="mt-2 text-xl font-extrabold tracking-tight">Get work done.</h2>
                <div class="mt-5 space-y-2">
                    
                    <a href="{{ route('staff.articles.index', ['tab' => 'review']) }}" class="staff-action-link"><span>{{ $isAdmin ? 'Review submissions' : 'Track my submissions' }}</span><x-icon name="arrow-up-right" class="h-4 w-4 text-amber-300" /></a>
                    <a href="{{ route('staff.content-readiness') }}" class="staff-action-link"><span>Check content quality</span><x-icon name="arrow-up-right" class="h-4 w-4 text-amber-300" /></a>
                    @if($isAdmin)
                    <a href="{{ route('staff.website.homepage') }}" class="staff-action-link"><span>Edit homepage</span><x-icon name="arrow-up-right" class="h-4 w-4 text-amber-300" /></a>
                    @endif
                </div>
            </section>
            @if($isAdmin)
            <section class="staff-panel" aria-labelledby="publication-heading">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="publication-heading" class="text-base font-extrabold text-stone-900">Needs your attention</h2>
                    <x-icon name="check-circle" class="h-5 w-5 text-amber-700" />
                </div>
                <a href="{{ route('staff.articles.index', ['tab' => 'review']) }}" class="mt-4 flex min-h-11 items-center justify-between gap-3 rounded-xl bg-amber-50 px-4 py-3 transition hover:bg-amber-100">
                    <span class="text-sm font-bold text-stone-900">Articles awaiting review</span>
                    <span class="rounded-full bg-amber-400 px-2.5 py-1 text-xs font-extrabold tabular-nums text-stone-950">{{ number_format($inReview) }}</span>
                </a>
                @if($reviewQueue->isNotEmpty())
                <ul class="mt-3 divide-y divide-stone-100" aria-label="Pending article reviews">
                    @foreach($reviewQueue as $pending)
                    <li><a href="{{ route('staff.articles.edit', $pending) }}" class="block py-2 text-xs font-semibold leading-relaxed text-amber-900 hover:underline">{{ $pending->currentRevision?->title ?? $pending->slug }}</a></li>
                    @endforeach
                </ul>
                @endif
                <a href="{{ route('staff.articles.index', ['publication' => 'modified']) }}" class="mt-4 flex min-h-11 items-center justify-between gap-3 rounded-xl border border-stone-200 px-4 py-3 transition hover:border-amber-300 hover:bg-amber-50">
                    <span class="text-sm font-bold text-stone-800">Live articles with newer drafts</span>
                    <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-extrabold tabular-nums text-stone-800">{{ number_format($updatedDrafts) }}</span>
                </a>
                <a href="{{ route('staff.launch-readiness') }}" class="mt-4 inline-flex items-center gap-2 text-xs font-bold text-amber-800 hover:underline">Review release checks <x-icon name="arrow-right" class="h-3.5 w-3.5" /></a>
            </section>
            @endif
        </div>
    </div>

    @if($isAdmin && $events->isNotEmpty())
    <section class="staff-panel" aria-labelledby="activity-heading">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 id="activity-heading" class="text-base font-extrabold text-stone-950">Recent activity</h2>
            <span class="text-xs text-stone-500">Latest editorial actions</span>
        </div>
        <ul class="mt-3 divide-y divide-stone-100">
            @foreach($events as $event)
            <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                <span class="font-medium text-stone-700">{{ $event->label() }}</span>
                <time datetime="{{ $event->created_at->toIso8601String() }}" class="text-xs text-stone-500">{{ $event->created_at->timezone(config('editorial.display_timezone'))->format('M j · H:i') }}</time>
            </li>
            @endforeach
        </ul>
    </section>
    @endif
</div>
@endsection
