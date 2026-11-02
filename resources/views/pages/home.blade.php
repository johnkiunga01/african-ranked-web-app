@extends('layouts.app')
@section('content')
<div class="home-page space-y-16 pb-16">
    @php
        $categoryImages = [
            'wildlife' => ['photo' => '29555791', 'source' => 'https://www.pexels.com/photo/majestic-male-lion-resting-in-the-wild-29555791/', 'credit' => 'Omar Ramadan'],
            'travel' => ['photo' => '30125143', 'source' => 'https://www.pexels.com/photo/aerial-view-of-zanzibar-s-pristine-beaches-30125143/', 'credit' => 'Dajana Reçi'],
            'food' => ['photo' => '17952746', 'source' => 'https://www.pexels.com/photo/diner-with-rice-and-fried-vegetables-17952746/', 'credit' => 'Studio Saiz'],
            'business' => ['photo' => '38133494', 'source' => 'https://www.pexels.com/photo/nairobi-skyline-at-sunset-with-urban-landmarks-38133494/', 'credit' => 'Youngafrikanna'],
            'tech' => ['photo' => '4913749', 'source' => 'https://www.pexels.com/photo/a-woman-using-a-laptop-4913749/', 'credit' => 'Brian Ngali'],
            'music' => ['photo' => '31177554', 'source' => 'https://www.pexels.com/photo/close-up-of-african-and-latin-percussion-drums-31177554/', 'credit' => 'wr heustis'],
        ];
        $heroPhoto = 'https://www.pexels.com/photo/group-of-zebras-grazing-grass-in-savanna-amboseli-national-park-in-kenya-19171291/';
        $orderedCategories = $categories->sortBy(fn ($c) => array_search($c['slug'], ['wildlife','travel','food','business','tech','music'], true))->values();
    @endphp
    <section class="ar-hero relative isolate overflow-hidden text-white" aria-labelledby="home-hero-heading">
        <img
            class="ar-hero-photo absolute inset-0 -z-20 h-full w-full object-cover"
            src="https://images.pexels.com/photos/19171291/pexels-photo-19171291.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=2200"
            srcset="https://images.pexels.com/photos/19171291/pexels-photo-19171291.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1000 1000w, https://images.pexels.com/photos/19171291/pexels-photo-19171291.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1600 1600w, https://images.pexels.com/photos/19171291/pexels-photo-19171291.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=2200 2200w"
            sizes="100vw" alt="Zebras grazing near acacia trees beneath Mount Kilimanjaro" width="2200" height="1467" loading="eager" fetchpriority="high" decoding="async" data-image-fallback>
        <div class="ar-hero-shade absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="relative mx-auto flex min-h-[690px] max-w-[1536px] flex-col justify-between gap-12 px-4 pb-7 pt-[148px] sm:min-h-[760px] sm:px-8 sm:pt-[165px] lg:min-h-[790px] lg:px-12 lg:pb-10 xl:px-16">
            <div class="max-w-[760px]">
                <p data-hero-intro class="inline-flex items-center gap-2.5 rounded-full border border-amber-400/70 bg-stone-950/50 px-4 py-2.5 text-sm font-bold tracking-tight text-amber-300 backdrop-blur-md">
                    <x-icon name="compass" class="h-4 w-4" />
                    <span>{{ $region['flag'] }} {{ $region['name'] }} edition</span>
                </p>
                <h1 id="home-hero-heading" data-hero-heading class="mt-7 max-w-[760px] text-[clamp(2.7rem,5.1vw,5.4rem)] font-extrabold leading-[1.08] tracking-[-0.052em] text-white [text-wrap:balance]">
                    {{ $homepage['hero_title'] }}
                    @if(filled($homepage['hero_highlight']))
                        <span class="mt-1 block text-amber-400">{{ $homepage['hero_highlight'] }}</span>
                    @endif
                </h1>
                <p data-hero-description class="mt-5 max-w-[660px] text-base leading-relaxed text-stone-100/95 sm:mt-6 sm:text-lg sm:leading-[1.7]">{{ $homepage['hero_description'] }}</p>
                <div data-hero-actions class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="{{ $basePath }}/rankings" class="ar-btn-primary group inline-flex min-h-14 items-center justify-center gap-3 rounded-2xl bg-amber-400 px-7 py-3.5 text-base font-extrabold text-stone-950 shadow-xl shadow-black/15 transition hover:bg-amber-300 focus-visible:outline-offset-4">
                        <x-icon name="chart-no-axes-column-increasing" class="h-5 w-5" />
                        Explore rankings
                        <x-icon name="arrow-right" class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                    </a>
                    <a href="#categories" class="ar-btn-outline inline-flex min-h-14 items-center justify-center gap-3 rounded-2xl border border-white/75 bg-black/25 px-7 py-3.5 text-base font-bold text-white backdrop-blur-sm transition hover:border-amber-300 hover:bg-white/15 focus-visible:outline-offset-4">
                        <x-icon name="layout-grid" class="h-5 w-5" />
                        Browse categories
                    </a>
                </div>
            </div>
            <div class="relative">
                <h2 id="categories-heading" class="sr-only">Explore by category</h2>
                <div id="categories" data-hero-category-grid class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 lg:gap-4" aria-labelledby="categories-heading">
                    @foreach($orderedCategories as $tile)
                        @php($tileMedia = $categoryImages[$tile['slug']] ?? null)
                        <a href="{{ $basePath }}/categories/{{ $tile['slug'] }}" data-hero-category class="ar-category-tile group relative isolate flex min-h-[130px] items-end overflow-hidden rounded-2xl border border-white/35 bg-stone-900 px-4 pb-4 pt-10 text-left text-white shadow-lg shadow-black/20 transition sm:min-h-[148px] lg:min-h-[145px]">
                            @if($tileMedia)
                                <img src="https://images.pexels.com/photos/{{ $tileMedia['photo'] }}/pexels-photo-{{ $tileMedia['photo'] }}.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=450"
                                    alt="" aria-hidden="true" width="450" height="330" loading="lazy" decoding="async" data-image-fallback
                                    class="absolute inset-0 -z-20 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                            @endif
                            <span class="absolute inset-0 -z-10 bg-gradient-to-t from-black/90 via-black/40 to-black/10"></span>
                            <span class="flex w-full items-end justify-between gap-2">
                                <span class="flex flex-col gap-2">
                                    <x-icon :name="$tile['icon']" class="h-6 w-6 text-amber-300" />
                                    <span class="text-sm font-bold leading-snug sm:text-[15px]">{{ $tile['name'] }}</span>
                                </span>
                                <x-icon name="arrow-up-right" class="h-4 w-4 shrink-0 text-white/80 opacity-80 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                            </span>
                        </a>
                    @endforeach
                </div>
                <div class="mt-3 text-right text-[11px] text-white/80">
                    Hero photography by <a class="underline underline-offset-2 hover:text-amber-300" href="{{ $heroPhoto }}" target="_blank" rel="noopener noreferrer">Wladimir Kühne / Pexels</a>.
                    <details class="relative ml-2 inline-block text-left">
                        <summary class="cursor-pointer underline underline-offset-2 hover:text-amber-300">Other photo credits</summary>
                        <ul class="absolute bottom-full right-0 z-20 mb-2 w-64 rounded-xl border border-white/20 bg-stone-950 p-3 text-xs shadow-2xl">
                            @foreach($categoryImages as $photo)
                                <li><a class="block py-1 hover:text-amber-300" href="{{ $photo['source'] }}" target="_blank" rel="noopener noreferrer">{{ $photo['credit'] }} / Pexels</a></li>
                            @endforeach
                        </ul>
                    </details>
                </div>
            </div>
        </div>
    </section>
    @if($articles->isNotEmpty() && $homepage['show_featured'])
    <section id="featured" class="mx-auto max-w-7xl px-4 sm:px-6" aria-labelledby="featured-heading"><div class="mb-8 flex items-center gap-2"><x-icon name="TrendingUp" class="h-5 w-5 text-amber-600" /><h2 id="featured-heading" class="text-2xl font-bold sm:text-3xl">{{ $region['localizedTerms']['featured'] }}</h2></div>@include('partials.card', ['item' => $articles->first(), 'featured' => true])</section>
    @endif
    @if($articles->count() > ($homepage['show_featured'] ? 1 : 0))
    <section class="mx-auto max-w-7xl px-4 sm:px-6" aria-labelledby="trending-heading"><div class="mb-8"><h2 id="trending-heading" class="text-2xl font-bold sm:text-3xl">{{ $region['localizedTerms']['trending'] }}</h2><p class="mt-1 text-stone-600">More rankings from across the continent</p></div><div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach($articles->skip($homepage['show_featured'] ? 1 : 0)->take(6) as $item)@include('partials.card', ['featured' => false])@endforeach</div><div class="mt-7 text-center"><a href="{{ $basePath }}/rankings" class="inline-flex items-center gap-2 rounded-full border border-amber-300 bg-white px-5 py-3 text-sm font-bold text-amber-900 transition hover:bg-amber-50">Browse every ranking and guide <x-icon name="arrow-right" class="h-4 w-4" /></a></div></section>
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
