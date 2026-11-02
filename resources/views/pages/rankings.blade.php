@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-7xl px-4 pb-20 pt-10 sm:px-6">
    <nav aria-label="Breadcrumb" class="text-sm text-stone-600">
        <a href="{{ $basePath ?: '/' }}" class="font-medium hover:text-amber-700">Home</a>
        <span class="mx-2 text-stone-400" aria-hidden="true">/</span>
        <span aria-current="page" class="text-stone-900">Rankings &amp; guides</span>
    </nav>

    <section class="mt-10 max-w-3xl" aria-labelledby="rankings-heading">
        <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-amber-800">
            <x-icon name="compass" class="h-4 w-4" />
            {{ $region['flag'] }} {{ $region['name'] }} edition
        </div>
        <h1 id="rankings-heading" class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-stone-950 sm:text-5xl">
            African rankings &amp; guides
        </h1>
        <p class="mt-5 text-base leading-8 text-stone-600 sm:text-lg">
            Find your next read across Africa's destinations, wildlife, food, music, business and innovation. Explore researched rankings, useful comparisons and practical guides.
        </p>
    </section>

    <section class="mt-10 border-y border-stone-200 py-5" aria-label="Browse rankings by topic">
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="mr-2 text-sm font-semibold text-stone-700">Browse topics</span>
            @foreach($categories as $tile)
                @if($articles->contains('category', $tile['slug']))
                    <a href="{{ $basePath }}/categories/{{ $tile['slug'] }}" class="inline-flex items-center gap-2 rounded-full border border-stone-200 bg-white px-3.5 py-2 text-sm font-semibold text-stone-700 transition hover:border-amber-400 hover:bg-amber-50 hover:text-stone-950">
                        {{ $tile['name'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </section>

    <section class="mt-10" aria-labelledby="all-rankings-heading">
        <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="all-rankings-heading" class="text-2xl font-bold tracking-tight text-stone-950 sm:text-3xl">Latest articles</h2>
                <p class="mt-1 text-sm text-stone-600">Only published articles appear here.</p>
            </div>
            <span class="text-sm font-medium text-stone-500">{{ $articles->count() }} {{ $articles->count() === 1 ? 'article' : 'articles' }}</span>
        </div>
        @forelse($articles as $item)
            @if($loop->first)
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @endif
                    @include('partials.card', ['featured' => false])
            @if($loop->last)
                </div>
            @endif
        @empty
            <div class="rounded-2xl border border-stone-200 bg-white px-6 py-16 text-center">
                <h3 class="text-xl font-bold text-stone-900">No published articles for this edition yet</h3>
                <p class="mt-2 text-stone-600">Explore the available topics or return to the global edition.</p>
                <a href="/rankings" class="mt-5 inline-flex rounded-xl bg-amber-400 px-5 py-3 font-bold text-stone-950 hover:bg-amber-300">Browse global rankings</a>
            </div>
        @endforelse
    </section>
</div>
@endsection
