@extends('layouts.app')
@section('content')
@php
    $readingSections = collect($article['body'] ?? [])->filter(fn ($block) => ($block['type'] ?? null) === 'heading');
    $moreTopics = $categories->filter(fn ($topic) => $topic['slug'] !== $category['slug'])->take(5);
@endphp
<div class="bg-[#f7f8f9] pb-16 sm:pb-24">
    <div class="mx-auto max-w-[1310px] px-4 pt-3 sm:px-6 lg:px-8">
        @include('partials.breadcrumbs')
    </div>
    <div class="mx-auto grid max-w-[1310px] items-start gap-7 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_296px] lg:gap-8 lg:px-8 xl:grid-cols-[minmax(0,1fr)_320px]">
        <article class="min-w-0 rounded-3xl border border-stone-200/70 bg-white px-5 pb-9 pt-7 shadow-[0_12px_40px_-30px_rgba(20,25,30,.3)] sm:px-8 sm:pb-12 sm:pt-10 xl:px-12">
            <header>
                <a href="{{ $basePath }}/categories/{{ $category['slug'] }}" class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-extrabold text-amber-900 transition hover:border-amber-400 hover:bg-amber-100">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $category['name'] }}
                </a>
                <h1 class="mt-5 text-[clamp(2rem,3.5vw,3.2rem)] font-extrabold leading-[1.14] tracking-[-.04em] text-[#171c20] [text-wrap:balance]">{{ $article['title'] }}</h1>
                <p class="mt-5 max-w-3xl text-base leading-[1.85] text-stone-600 sm:text-lg">{{ $article['excerpt'] }}</p>
                <div class="mt-7 flex flex-wrap items-center justify-between gap-4 border-y border-stone-200 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#171c20] text-xs font-extrabold text-amber-300" aria-hidden="true">{{ collect(explode(' ', $article['author']))->map(fn ($name) => mb_substr($name, 0, 1))->implode('') }}</span>
                        <div>
                            <p class="text-sm font-extrabold text-stone-900">{{ $article['author'] }}</p>
                            <p class="mt-0.5 text-xs text-stone-500">{{ $article['authorTitle'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-xs font-semibold text-stone-500">
                        <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="h-4 w-4" />{{ $article['readTime'] }} min read</span>
                        <button type="button" data-share class="inline-flex min-h-10 items-center gap-1.5 rounded-lg border border-stone-200 px-3 text-amber-900 transition hover:border-amber-300 hover:bg-amber-50">
                            <x-icon name="share" class="h-4 w-4" />Share
                        </button>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-stone-500">
                    <span>Published <time datetime="{{ $article['datePublished'] }}">{{ \Carbon\Carbon::parse($article['datePublished'])->format('M j, Y') }}</time></span>
                    <span aria-hidden="true">·</span>
                    <span>Updated <time datetime="{{ $article['dateModified'] }}">{{ \Carbon\Carbon::parse($article['dateModified'])->format('M j, Y') }}</time></span>
                </div>
                <p data-share-status role="status" aria-live="polite" class="mt-2 text-xs font-semibold text-amber-800"></p>
            </header>

            <figure class="mt-7">
                <img src="{{ $article['image'] }}" alt="{{ ($article['imageAlt'] ?? null) ?: $article['title'] }}" width="1200" height="675" fetchpriority="high" class="aspect-[16/9] w-full rounded-2xl bg-stone-100 object-cover" data-image-fallback>
                @if(!empty($article['imageCredit']))
                    <figcaption class="mt-2 text-xs leading-relaxed text-stone-500">{{ str_contains((string) $article['image'], '/editorial-art/') ? 'Original artwork' : 'Featured image' }}: {{ $article['imageCredit'] }}</figcaption>
                @endif
            </figure>

            @include('editorial.evidence', ['evidence' => $article['editorialEvidence'] ?? []])

            @if($readingSections->isNotEmpty() || !empty($article['faqs']))
            <details class="mt-8 rounded-2xl border border-stone-200 bg-stone-50 p-4 lg:hidden" data-mobile-toc>
                <summary class="flex min-h-7 cursor-pointer items-center justify-between gap-3 text-sm font-extrabold text-stone-900">
                    On this page <x-icon name="chevron-down" class="h-4 w-4" />
                </summary>
                <nav aria-label="Article sections" class="mt-3 border-t border-stone-200 pt-2">
                    <ol class="space-y-1">
                        @foreach($article['body'] as $index => $block)
                            @if($block['type'] === 'heading')
                            <li><a href="#{{ $catalog->headingId($index) }}" class="ar-reading-link {{ ($block['level'] ?? 2) === 3 ? 'pl-5' : '' }}">{{ $block['text'] }}</a></li>
                            @endif
                        @endforeach
                        @if(!empty($article['faqs']))<li><a href="#frequently-asked-questions" class="ar-reading-link">Frequently asked questions</a></li>@endif
                    </ol>
                </nav>
            </details>
            @endif

            <div class="ar-article-prose mt-8 space-y-6">
                @foreach($article['body'] as $index => $block)
                    @switch($block['type'])
                    @case('heading')
                        @if(($block['level'] ?? 2) === 3)
                            <h3 id="{{ $catalog->headingId($index) }}">{{ $block['text'] }}</h3>
                        @else
                            <h2 id="{{ $catalog->headingId($index) }}">{{ $block['text'] }}</h2>
                        @endif
                        @break
                    @case('image')
                        @if(filter_var($block['url'] ?? '', FILTER_VALIDATE_URL) && parse_url($block['url'], PHP_URL_SCHEME) === 'https' && filled($block['alt'] ?? null))
                            <figure class="my-8 overflow-hidden rounded-2xl border border-stone-200 bg-stone-50">
                                <img src="{{ $block['url'] }}" alt="{{ $block['alt'] }}" loading="lazy" decoding="async" class="aspect-[16/10] w-full object-cover">
                                @if(filled($block['caption'] ?? null) || filled($block['credit'] ?? null))
                                    <figcaption class="px-4 py-3 text-xs leading-relaxed text-stone-600 sm:text-sm">
                                        @if(filled($block['caption'] ?? null))<span>{{ $block['caption'] }}</span>@endif
                                        @if(filled($block['credit'] ?? null))
                                            <span>{{ filled($block['caption'] ?? null) ? ' · ' : '' }}{{ filled($block['source'] ?? null) ? 'Image by ' : 'Credit: ' }}</span>
                                            @if(filter_var($block['source'] ?? '', FILTER_VALIDATE_URL) && parse_url($block['source'], PHP_URL_SCHEME) === 'https')
                                                <a href="{{ $block['source'] }}" rel="noopener noreferrer" target="_blank" class="font-semibold text-amber-800 underline underline-offset-2">{{ $block['credit'] }}</a>
                                            @else
                                                {{ $block['credit'] }}
                                            @endif
                                        @endif
                                    </figcaption>
                                @endif
                            </figure>
                        @endif
                        @break
                    @case('link')
                        <p><a href="{{ $block['url'] }}" class="font-semibold text-amber-800 underline underline-offset-4 hover:text-amber-600">{{ $block['text'] }}</a></p>
                        @break
                    @case('paragraph')
                        <p>{{ $block['text'] }}</p>
                        @break
                    @case('list')
                        <ul class="list-disc space-y-2 pl-5 text-base leading-[1.8] text-stone-700 marker:text-amber-600">
                            @foreach($block['items'] as $listItem)<li>{{ $listItem }}</li>@endforeach
                        </ul>
                        @break
                    @case('quote')
                        <blockquote class="rounded-r-xl border-l-4 border-amber-400 bg-amber-50/70 py-4 pl-6 pr-4">
                            <p class="text-lg italic leading-relaxed text-stone-800">“{{ $block['text'] }}”</p>
                            <cite class="mt-2 block text-sm font-semibold not-italic text-stone-600">— {{ $block['attribution'] }}</cite>
                        </blockquote>
                        @break
                    @endswitch
                @endforeach
            </div>

            @include('editorial.sources', ['evidence' => $article['editorialEvidence'] ?? [], 'reviewer' => $article['reviewer'] ?? null])

            @if(!empty($article['faqs']))
            <section id="frequently-asked-questions" class="mt-12 rounded-2xl border border-stone-200 bg-stone-50 p-5 sm:p-7" aria-labelledby="faq-heading">
                <h2 id="faq-heading" class="text-xl font-extrabold tracking-tight text-stone-950">Frequently asked questions</h2>
                <div class="mt-3 divide-y divide-stone-200">
                    @foreach($article['faqs'] as $faq)
                        <div class="py-4"><h3 class="font-bold text-stone-900">{{ $faq['question'] }}</h3><p class="mt-2 text-sm leading-[1.85] text-stone-600">{{ $faq['answer'] }}</p></div>
                    @endforeach
                </div>
            </section>
            @endif
            <a href="{{ $basePath }}/categories/{{ $category['slug'] }}" class="mt-10 inline-flex min-h-11 items-center gap-2 rounded-xl border border-stone-200 px-4 text-sm font-bold text-amber-900 transition hover:border-amber-300 hover:bg-amber-50">
                <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Explore more {{ $category['name'] }}
            </a>
        </article>

        <aside aria-label="Explore more African Ranked content" class="min-w-0 space-y-5 lg:sticky lg:top-28">
            @if($readingSections->isNotEmpty() || !empty($article['faqs']))
            <section class="ar-article-sidebar hidden lg:block" aria-labelledby="sidebar-contents-heading">
                <h2 id="sidebar-contents-heading" class="border-b border-stone-100 px-5 py-4 text-sm font-extrabold text-stone-950">On this page</h2>
                <nav aria-label="Article section navigation" class="max-h-[36vh] overflow-y-auto px-3 py-3">
                    <ol class="space-y-0.5">
                        @foreach($article['body'] as $index => $block)
                            @if($block['type'] === 'heading')
                            <li><a href="#{{ $catalog->headingId($index) }}" class="ar-reading-link {{ ($block['level'] ?? 2) === 3 ? 'pl-5' : '' }}">{{ $block['text'] }}</a></li>
                            @endif
                        @endforeach
                        @if(!empty($article['faqs']))<li><a href="#frequently-asked-questions" class="ar-reading-link">Frequently asked questions</a></li>@endif
                    </ol>
                </nav>
            </section>
            @endif

            @if($related->isNotEmpty())
            <section class="ar-article-sidebar" aria-labelledby="sidebar-related-heading">
                <div class="border-b border-stone-100 px-5 py-4">
                    <p class="text-[11px] font-extrabold uppercase tracking-[.15em] text-amber-700">Continue exploring</p>
                    <h2 id="sidebar-related-heading" class="mt-1 text-base font-extrabold text-stone-950">More in {{ $category['name'] }}</h2>
                </div>
                <ul class="divide-y divide-stone-100 px-4">
                    @foreach($related->take(3) as $item)
                    <li>
                        <a href="{{ $basePath }}/articles/{{ $item['slug'] }}" class="group flex min-h-24 items-start gap-3 rounded-xl px-1 py-4 transition hover:bg-amber-50/70">
                            <img src="{{ $item['image'] }}" alt="" width="88" height="78" loading="lazy" decoding="async" data-image-fallback class="h-[72px] w-20 shrink-0 rounded-lg bg-stone-100 object-cover">
                            <span class="min-w-0 flex-1">
                                <span class="line-clamp-2 text-[13px] font-extrabold leading-snug text-stone-900 group-hover:text-amber-800">{{ $item['title'] }}</span>
                                <span class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-stone-500"><x-icon name="clock" class="h-3 w-3" />{{ $item['readTime'] }} min read</span>
                            </span>
                        </a>
                    </li>
                    @endforeach
                </ul>
                <a href="{{ $basePath }}/categories/{{ $category['slug'] }}" class="inline-flex min-h-11 items-center gap-2 px-5 pb-4 text-sm font-extrabold text-amber-800 hover:underline">View all in {{ $category['name'] }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </section>
            @endif

            <section class="ar-article-sidebar" aria-labelledby="sidebar-topics-heading">
                <h2 id="sidebar-topics-heading" class="border-b border-stone-100 px-5 py-4 text-sm font-extrabold text-stone-950">Explore topics</h2>
                <nav aria-label="Browse article categories" class="flex flex-wrap gap-2 p-4">
                    <a href="{{ $basePath }}/categories/{{ $category['slug'] }}" class="rounded-full border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-extrabold text-amber-900">{{ $category['name'] }}</a>
                    @foreach($moreTopics as $topic)
                        <a href="{{ $basePath }}/categories/{{ $topic['slug'] }}" class="rounded-full border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-stone-950">{{ $topic['name'] }}</a>
                    @endforeach
                </nav>
                <a href="{{ $basePath }}/rankings" class="mb-4 ml-5 inline-flex min-h-10 items-center gap-2 text-sm font-extrabold text-amber-800 hover:underline">All rankings &amp; guides <x-icon name="arrow-up-right" class="h-4 w-4" /></a>
            </section>
        </aside>
    </div>
</div>
@endsection
