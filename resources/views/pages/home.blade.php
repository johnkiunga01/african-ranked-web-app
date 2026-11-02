@extends('layouts.app')
@section('content')
<div class="space-y-16 pb-16">
    <section class="relative overflow-hidden bg-gradient-to-br from-stone-900 via-stone-800 to-amber-950">
        <div class="pointer-events-none absolute -right-24 top-0 h-96 w-96 rounded-full bg-amber-600/15 blur-3xl"></div><div class="pointer-events-none absolute -left-24 bottom-0 h-96 w-96 rounded-full bg-orange-700/10 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28"><div class="max-w-3xl">
            <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-500/10 px-4 py-1.5 text-sm font-medium text-amber-300"><x-icon name="compass" class="h-4 w-4" />{{ $region['flag'] }} {{ $region['name'] }} edition</div>
            <h1 class="text-4xl font-bold leading-tight text-white sm:text-5xl lg:text-6xl">{{ $homepage['hero_title'] }}<span class="block bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">{{ $homepage['hero_highlight'] }}</span></h1>
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-stone-300">{{ $homepage['hero_description'] ?: $region['tagline'].'. Explore destinations, cuisines, startups, music, and wildlife from across the continent, curated for readers in '.$region['name'].'.' }}</p>
            <div class="mt-8 flex flex-wrap gap-3"><a href="{{ $articles->isNotEmpty() && $homepage['show_featured'] ? '#featured' : '#categories' }}" class="rounded-xl bg-amber-500 px-6 py-3 font-semibold text-stone-950 transition hover:bg-amber-400">{{ $region['localizedTerms']['explore'] }}</a><a href="#categories" class="rounded-xl border border-white/20 bg-white/5 px-6 py-3 font-semibold text-white backdrop-blur transition hover:bg-white/10">Browse categories</a></div>
        </div></div>
    </section>
    <section id="categories" class="mx-auto max-w-7xl px-4 sm:px-6" aria-labelledby="categories-heading"><div class="mb-8"><h2 id="categories-heading" class="text-2xl font-bold sm:text-3xl">Explore by category</h2><p class="mt-1 text-stone-600">Explore destinations, cuisine, music, business, technology and wildlife through focused African guides</p></div><div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach($categories as $tile)<a data-reveal href="{{ $basePath }}/categories/{{ $tile['slug'] }}" class="group flex flex-col items-center gap-3 rounded-xl border border-stone-200 bg-white p-5 text-center transition hover:border-amber-300 hover:shadow-md"><span class="flex h-12 w-12 items-center justify-center rounded-xl text-white transition group-hover:scale-110" style="background-color: {{ $tile['color'] }}"><x-icon :name="$tile['icon']" class="h-6 w-6" /></span><span class="text-sm font-semibold text-stone-800">{{ explode(' & ', $tile['name'])[0] }}</span><x-icon class="h-4 w-4 text-stone-400 transition group-hover:translate-x-1 group-hover:text-amber-600" /></a>@endforeach
    </div></section>
    @if($articles->isNotEmpty() && $homepage['show_featured'])
    <section id="featured" class="mx-auto max-w-7xl px-4 sm:px-6" aria-labelledby="featured-heading"><div class="mb-8 flex items-center gap-2"><x-icon name="TrendingUp" class="h-5 w-5 text-amber-600" /><h2 id="featured-heading" class="text-2xl font-bold sm:text-3xl">{{ $region['localizedTerms']['featured'] }}</h2></div>@include('partials.card', ['item' => $articles->first(), 'featured' => true])</section>
    @endif
    @if($articles->count() > ($homepage['show_featured'] ? 1 : 0))
    <section class="mx-auto max-w-7xl px-4 sm:px-6" aria-labelledby="trending-heading"><div class="mb-8"><h2 id="trending-heading" class="text-2xl font-bold sm:text-3xl">{{ $region['localizedTerms']['trending'] }}</h2><p class="mt-1 text-stone-600">More rankings from across the continent</p></div><div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach($articles->skip($homepage['show_featured'] ? 1 : 0) as $item)@include('partials.card', ['featured' => false])@endforeach</div></section>
    @endif
    <section class="mx-auto max-w-7xl px-4 sm:px-6" aria-labelledby="editorial-heading">
        <div class="grid gap-8 rounded-2xl border border-stone-200 bg-stone-50 p-8 md:grid-cols-2 md:p-12">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-amber-700">Discover Africa</p>
                <h2 id="editorial-heading" class="mt-3 text-2xl font-bold text-stone-900 sm:text-3xl">A closer look at the people, places and ideas shaping Africa</h2>
            </div>
            <div>
                <p class="leading-relaxed text-stone-700">Explore travel destinations, food traditions, music, businesses, technology and wildlife through rankings and guides designed to help you discover more of the continent.</p>
                <p class="mt-4 leading-relaxed text-stone-700">Our editorial approach puts clear criteria, useful context and responsible sourcing at the centre of each ranking.</p>
                <a href="{{ $basePath }}/strategy" class="mt-5 inline-flex font-semibold text-amber-800 underline underline-offset-4 hover:text-amber-950">Read our editorial approach</a>
            </div>
        </div>
    </section>
    <div class="mx-auto max-w-7xl px-4 sm:px-6">@include('partials.newsletter')</div>
</div>
@endsection
