@extends('layouts.app')
@section('content')
<div class="pb-16">
    <div class="mx-auto max-w-3xl px-4 sm:px-6">@include('partials.breadcrumbs')</div>
    <article class="mx-auto max-w-3xl px-4 sm:px-6">
        <header>
            <a href="{{ $basePath }}/categories/{{ $category['slug'] }}" class="mb-4 inline-flex rounded-full px-3 py-1 text-xs font-semibold text-white" style="background-color: {{ $category['color'] }}">{{ $category['name'] }}</a>
            <h1 class="text-3xl font-bold leading-tight sm:text-4xl">{{ $article['title'] }}</h1><p class="mt-4 text-lg leading-relaxed text-stone-600">{{ $article['excerpt'] }}</p>
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-y border-stone-200 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-700 text-sm font-bold text-white" aria-hidden="true">{{ collect(explode(' ', $article['author']))->map(fn($name) => mb_substr($name, 0, 1))->implode('') }}</span><div><p class="text-sm font-semibold">{{ $article['author'] }}</p><p class="text-xs text-stone-500">{{ $article['authorTitle'] }}</p></div></div><div class="flex items-center gap-4 text-xs text-stone-500"><span class="flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" />{{ $article['readTime'] }} min</span><button type="button" data-share class="flex items-center gap-1 rounded py-2 font-medium text-amber-700"><x-icon name="share" class="h-3.5 w-3.5" />Share</button></div></div>
            <p class="mt-3 text-xs text-stone-500">Published <time datetime="{{ $article['datePublished'] }}">{{ \Carbon\Carbon::parse($article['datePublished'])->format('F j, Y') }}</time> · Updated <time datetime="{{ $article['dateModified'] }}">{{ \Carbon\Carbon::parse($article['dateModified'])->format('F j, Y') }}</time></p><p data-share-status role="status" class="mt-2 text-xs text-amber-800"></p>
        </header>
        <img src="{{ $article['image'] }}" alt="{{ ($article['imageAlt'] ?? null) ?: $article['title'] }}" width="1200" height="675" fetchpriority="high" class="mt-8 aspect-[16/9] w-full rounded-2xl object-cover" data-image-fallback>
        @include('editorial.evidence', ['evidence' => $article['editorialEvidence'] ?? []])
        <nav aria-label="On this page" class="mt-8 rounded-2xl border border-stone-200 bg-white p-6"><h2 class="font-semibold">In this ranking</h2><ol class="mt-3 space-y-2 text-sm text-amber-800">@foreach($article['body'] as $index => $block)@if($block['type'] === 'heading')<li><a href="#{{ $catalog->headingId($index) }}" class="underline decoration-amber-200 underline-offset-4 hover:decoration-amber-700">{{ $block['text'] }}</a></li>@endif @endforeach @if(!empty($article['faqs']))<li><a href="#frequently-asked-questions" class="underline decoration-amber-200 underline-offset-4">Frequently asked questions</a></li>@endif</ol></nav>
        <div class="mt-8 space-y-6">
            @foreach($article['body'] as $index => $block)
                @switch($block['type'])
                @case('heading')
                    @if($block['level'] === 3)<h3 id="{{ $catalog->headingId($index) }}" class="pt-4 text-xl font-bold">{{ $block['text'] }}</h3>@else<h2 id="{{ $catalog->headingId($index) }}" class="pt-4 text-2xl font-bold">{{ $block['text'] }}</h2>@endif
                    @break
                @case('link')<p><a href="{{ $block['url'] }}" class="text-amber-800 underline underline-offset-4">{{ $block['text'] }}</a></p>@break
                @case('paragraph')<p class="text-base leading-relaxed text-stone-700">{{ $block['text'] }}</p>@break
                @case('list')<ul class="list-disc space-y-2 pl-5 text-base text-stone-700 marker:text-amber-600">@foreach($block['items'] as $listItem)<li>{{ $listItem }}</li>@endforeach</ul>@break
                @case('quote')<blockquote class="border-l-4 border-amber-500 bg-amber-50/50 py-4 pl-6 pr-4"><p class="text-lg italic text-stone-800">“{{ $block['text'] }}”</p><cite class="mt-2 block text-sm font-medium not-italic text-stone-600">— {{ $block['attribution'] }}</cite></blockquote>@break
                @endswitch
            @endforeach
        </div>
        @include('editorial.sources', ['evidence' => $article['editorialEvidence'] ?? [], 'reviewer' => $article['reviewer'] ?? null])
        @if(!empty($article['faqs']))
        <section id="frequently-asked-questions" class="mt-12 rounded-2xl border border-stone-200 bg-stone-50 p-6 sm:p-8" aria-labelledby="faq-heading"><h2 id="faq-heading" class="text-xl font-bold">Frequently asked questions</h2><div class="mt-4 divide-y divide-stone-200">@foreach($article['faqs'] as $faq)<div class="py-4"><h3 class="font-semibold">{{ $faq['question'] }}</h3><p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $faq['answer'] }}</p></div>@endforeach</div></section>
        @endif
        <p class="mt-10"><a href="{{ $basePath }}/categories/{{ $category['slug'] }}" class="inline-flex items-center gap-2 text-sm font-semibold text-amber-700"><x-icon class="h-4 w-4 rotate-180" />Back to {{ $category['name'] }}</a></p>
    </article>
    @if($related->isNotEmpty())<section class="mx-auto mt-16 max-w-7xl px-4 sm:px-6" aria-labelledby="related-heading"><h2 id="related-heading" class="mb-6 text-2xl font-bold">More in {{ $category['name'] }}</h2><div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach($related as $item)@include('partials.card', ['featured' => false])@endforeach</div></section>@endif
    <div class="mx-auto mt-16 max-w-7xl px-4 sm:px-6">@include('partials.newsletter')</div>
</div>
@endsection
