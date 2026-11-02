@extends('layouts.app')
@section('content')
<div class="space-y-16 pb-16">
    @php
        $heroArticle = $articles->isNotEmpty() && $homepage['show_featured'] ? $articles->first() : null;
        $heroCategory = $heroArticle ? $catalog->category($heroArticle['category']) : null;
    @endphp
    <section class="relative isolate overflow-hidden bg-[#171513] text-white" aria-labelledby="home-hero-heading">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[#171513] via-[#25201c] to-[#452710]"></div>
        <div class="pointer-events-none absolute -right-16 -top-28 h-[460px] w-[460px] rounded-full bg-amber-500/[0.09] blur-[95px]"></div>
        <div class="pointer-events-none absolute -bottom-48 left-0 h-[420px] w-[420px] rounded-full bg-orange-600/[0.08] blur-[90px]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-20" style="background-image:radial-gradient(rgba(255,255,255,.14) .65px,transparent .65px);background-size:24px 24px;"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:min-h-[560px] lg:grid-cols-[minmax(0,1.1fr)_minmax(350px,.9fr)] lg:gap-16 lg:py-24">
            <div class="max-w-[670px]">
                <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-amber-400/25 bg-amber-400/10 px-3.5 py-2 text-xs font-semibold tracking-wide text-amber-200 sm:text-sm">
                    <x-icon name="compass" class="h-4 w-4 shrink-0" />
                    <span>{{ $region['flag'] }} {{ $region['name'] }} edition</span>
                </div>

                <h1 id="home-hero-heading" class="max-w-[700px] text-[clamp(2.65rem,5vw,4.55rem)] font-extrabold leading-[1.1] tracking-[-0.045em] text-white">
                    {{ $homepage['hero_title'] }}
                    @if(filled($homepage['hero_highlight']))
                        <span class="mt-1 block bg-gradient-to-r from-amber-300 via-amber-400 to-orange-400 bg-clip-text text-transparent">{{ $homepage['hero_highlight'] }}</span>
                    @endif
                </h1>

                <p class="mt-7 max-w-[580px] text-base leading-8 text-stone-300 sm:text-lg">{{ $homepage['hero_description'] ?: $region['tagline'].'. Explore destinations, cuisines, startups, music and wildlife from across Africa.' }}</p>

                <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="{{ $basePath }}/rankings" class="group inline-flex min-h-[52px] items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-3 text-base font-bold text-stone-950 shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:bg-amber-300 focus-visible:outline-offset-4">
                        {{ $region['localizedTerms']['explore'] }}
                        <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-1" />
                    </a>
                    <a href="#categories" class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/[0.07] px-6 py-3 text-base font-semibold text-white transition hover:border-white/50 hover:bg-white/[0.13] focus-visible:outline-offset-4">
                        Browse categories
                        <x-icon name="chevron-right" class="h-4 w-4" />
                    </a>
                </div>

                <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-white/10 pt-6 text-sm text-stone-300">
                    <span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>Researched rankings</span>
                    <span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>Practical guides</span>
                    <span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>{{ $categories->count() }} topics to explore</span>
                </div>
            </div>

            @if($heroArticle)
                <a href="{{ $basePath }}/articles/{{ $heroArticle['slug'] }}" class="group relative block h-[350px] overflow-hidden rounded-[1.75rem] border border-white/15 bg-stone-800 shadow-2xl shadow-black/30 transition duration-300 hover:-translate-y-1 hover:border-amber-400/60 sm:h-[410px] lg:h-[445px]" aria-label="Read featured article: {{ $heroArticle['title'] }}">
                    <img src="{{ $heroArticle['image'] }}" alt="{{ $heroArticle['imageAlt'] ?? $heroArticle['title'] }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" width="720" height="800" loading="eager" fetchpriority="high" decoding="async" data-image-fallback>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/35 to-black/5"></div>
                    <div class="absolute left-5 top-5 inline-flex items-center gap-2 rounded-full border border-white/25 bg-stone-950/65 px-3.5 py-2 text-xs font-bold uppercase tracking-[0.14em] text-white backdrop-blur-sm sm:left-7 sm:top-7">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>Editor's pick
                    </div>
                    <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                        @if($heroCategory)
                            <span class="text-xs font-bold uppercase tracking-[0.16em] text-amber-300">{{ $heroCategory['name'] }}</span>
                        @endif
                        <h2 class="mt-3 line-clamp-2 text-2xl font-bold leading-tight tracking-tight text-white sm:text-[1.85rem]">{{ $heroArticle['title'] }}</h2>
                        <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-white">Read the story<x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-1" /></span>
                    </div>
                </a>
            @else
                <div class="rounded-[1.75rem] border border-white/15 bg-white/[0.06] p-6 shadow-xl backdrop-blur-sm sm:p-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-300">Find your starting point</p>
                    <h2 class="mt-3 text-2xl font-bold text-white">What would you like to explore?</h2>
                    <div class="mt-6 space-y-3">
                        @foreach($categories->take(3) as $suggestedCategory)
                            <a href="{{ $basePath }}/categories/{{ $suggestedCategory['slug'] }}" class="flex items-center justify-between rounded-xl border border-white/15 bg-white/[0.05] px-4 py-4 font-semibold text-white transition hover:border-amber-400/50 hover:bg-white/10">
                                <span>{{ $suggestedCategory['name'] }}</span><x-icon name="arrow-right" class="h-4 w-4 text-amber-300" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
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
