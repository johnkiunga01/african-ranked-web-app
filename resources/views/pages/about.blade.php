@extends('layouts.app')
@section('content')
<article>
    <section class="relative isolate overflow-hidden bg-stone-950 text-white">
        <div class="absolute -right-24 top-0 h-96 w-96 rounded-full bg-amber-400/10 blur-3xl" aria-hidden="true"></div>
        <div class="relative mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[1.4fr_1fr] lg:items-center lg:px-10">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-400">About African Ranked</p>
                <h1 class="mt-6 max-w-3xl text-4xl font-extrabold leading-tight tracking-tight sm:text-6xl">A continent of stories.<br><span class="text-amber-400">A clearer way to explore.</span></h1>
                <p class="mt-7 max-w-xl text-lg leading-relaxed text-stone-300">Africa rewards curiosity. We help you turn yours into a place to visit, a sound to discover or a subject to understand.</p>
                <a href="/rankings" class="mt-8 inline-flex min-h-12 items-center gap-3 rounded-full bg-amber-400 px-6 py-3 font-bold text-stone-950 transition hover:bg-amber-300">Explore our stories <span aria-hidden="true">→</span></a>
            </div>
            <div class="rounded-3xl border border-white/15 bg-white/5 p-8 sm:p-10">
                <img src="/africa-mark.svg?v=dotted-20261103" width="353" height="372" alt="" class="h-32 w-auto" />
                <p class="mt-8 text-2xl font-bold leading-snug">The best of Africa, ranked.<br><span class="text-stone-400">With room for the details.</span></p>
                <p class="mt-4 leading-relaxed text-stone-300">Rankings give you a starting point. Our guides help you understand the choices behind them.</p>
            </div>
        </div>
    </section>
    <section class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20 lg:px-10" aria-labelledby="about-purpose">
        <div class="grid gap-6 lg:grid-cols-2 lg:gap-20">
            <h2 id="about-purpose" class="max-w-md text-3xl font-bold tracking-tight sm:text-4xl">More context.<br>Better discoveries.</h2>
            <div class="space-y-5 text-lg leading-relaxed text-stone-600"><p>African Ranked is an English-language editorial platform covering African travel, wildlife, food, music, technology and business.</p><p>Our purpose is to make those subjects easier to explore through practical guides, comparisons and rankings. A useful list should explain what matters, who a choice suits and where its limits lie.</p></div>
        </div>
        <div class="mt-12 grid gap-5 md:grid-cols-3">
            @foreach([['01','Discover','Find a starting point across destinations, cultures and industries. Follow your interests beyond the familiar headlines.'],['02','Understand','Look beyond a position on a list. Consider the criteria, source dates and trade-offs that make a comparison useful.'],['03','Decide','Use practical context to plan your next step. Check time-sensitive details with the linked provider or authority before acting.']] as [$number,$heading,$description])
                <div class="rounded-2xl border border-stone-200 bg-white p-7"><span class="text-sm font-bold text-amber-700">{{ $number }}</span><h3 class="mt-5 text-xl font-bold">{{ $heading }}</h3><p class="mt-3 leading-relaxed text-stone-600">{{ $description }}</p></div>
            @endforeach
        </div>
    </section>
    <section class="border-y border-stone-200 bg-stone-100" aria-labelledby="about-coverage">
        <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:px-10">
            <p class="text-xs font-bold uppercase tracking-widest text-amber-800">Follow your curiosity</p><h2 id="about-coverage" class="mt-3 text-3xl font-bold">One continent. Many perspectives.</h2>
            <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach($categories as $category)<a href="/categories/{{ $category['slug'] }}" class="group flex min-h-16 items-center justify-between gap-4 rounded-xl border border-stone-200 bg-white px-5 py-4 font-semibold transition hover:border-amber-500 hover:bg-amber-50"><span>{{ $category['name'] }}</span><span aria-hidden="true" class="text-amber-700 transition group-hover:translate-x-1">→</span></a>@endforeach</div>
        </div>
    </section>
    <section class="mx-auto grid max-w-7xl gap-8 px-5 py-16 sm:px-8 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-10">
        <div><p class="text-xs font-bold uppercase tracking-widest text-amber-800">Behind the stories</p><h2 class="mt-3 text-3xl font-bold">Curiosity needs context.</h2><p class="mt-5 leading-relaxed text-stone-600">Our editorial approach explains how we work with research, rankings and changing information. Read it alongside an article's sources and methodology to understand the basis for its conclusions.</p><a href="/strategy" class="mt-6 inline-flex min-h-11 items-center gap-2 font-bold text-amber-900 underline underline-offset-4">Read our editorial approach <span aria-hidden="true">→</span></a></div>
        <div class="rounded-3xl bg-amber-50 p-8 sm:p-10"><h2 class="text-2xl font-bold">Help us get the details right.</h2><p class="mt-4 leading-relaxed text-stone-700">Have a correction, a question or a story suggestion? Include the article link and any supporting sources so we can understand what needs attention.</p><a href="mailto:info@africanranked.com" class="mt-6 inline-flex min-h-11 break-all font-bold text-amber-900 underline underline-offset-4">info@africanranked.com</a></div>
    </section>
</article>
@endsection
