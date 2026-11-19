@php
    $cardCategory = $catalog->category($item['category']);
    $featured = $featured ?? false;
    $href = $basePath.'/articles/'.$item['slug'];
@endphp
@if($featured)
<article data-reveal class="group relative grid overflow-hidden rounded-2xl bg-stone-950 text-white shadow-xl shadow-stone-900/10 lg:grid-cols-12">
    <div class="relative aspect-[16/10] overflow-hidden lg:col-span-7 lg:aspect-auto lg:min-h-[460px]">
        <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" width="1200" height="675" fetchpriority="high" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" data-image-fallback>
        <div class="absolute inset-0 bg-gradient-to-t from-stone-950/70 via-transparent to-transparent lg:bg-gradient-to-r lg:from-transparent lg:via-transparent lg:to-stone-950/80" aria-hidden="true"></div>
    </div>
    <div class="relative flex flex-col justify-center gap-5 p-6 sm:p-8 lg:col-span-5 lg:p-10">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-400/70 px-3 py-1 text-xs font-bold uppercase tracking-wider text-amber-300"><x-icon name="book-open" class="h-3.5 w-3.5" />Editor's pick</span>
            <span class="flex items-center gap-1.5 rounded-full border bg-stone-900 px-3 py-1 text-xs font-semibold text-white" style="border-color: {{ $cardCategory['color'] }}"><x-icon :name="$cardCategory['icon']" class="h-3 w-3" />{{ explode(' & ', $cardCategory['name'])[0] }}</span>
        </div>
        <h3 class="text-3xl font-extrabold leading-[1.1] tracking-tight text-white [text-wrap:balance] lg:text-4xl"><a href="{{ $href }}" class="after:absolute after:inset-0 focus-visible:outline-offset-4">{{ $item['title'] }}</a></h3>
        <p class="line-clamp-3 text-base leading-relaxed text-stone-300">{{ $item['excerpt'] }}</p>
        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-stone-400"><span class="font-medium text-stone-200">{{ $item['author'] }}</span><span aria-hidden="true">·</span><time datetime="{{ $item['dateModified'] }}">Updated {{ \Carbon\Carbon::parse($item['dateModified'])->format('M j, Y') }}</time><span aria-hidden="true">·</span><span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" />{{ $item['readTime'] }} min read</span></p>
        <span class="ar-btn-primary inline-flex min-h-12 w-fit items-center gap-2 rounded-2xl bg-amber-400 px-6 py-3 text-sm font-extrabold text-stone-950 shadow-lg shadow-black/20 transition group-hover:bg-amber-300">{{ $region['localizedTerms']['readMore'] }}<x-icon class="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
    </div>
</article>
@else
<article data-reveal class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white transition hover:shadow-lg">
    <div class="relative aspect-[16/10] overflow-hidden"><img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" width="600" height="375" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" data-image-fallback><span class="absolute left-3 top-3 flex items-center gap-1.5 rounded-full border bg-stone-950/95 px-2.5 py-1 text-xs font-semibold text-white" style="border-color: {{ $cardCategory['color'] }}"><x-icon :name="$cardCategory['icon']" class="h-3 w-3" />{{ explode(' & ', $cardCategory['name'])[0] }}</span></div>
    <div class="flex flex-1 flex-col p-5"><p class="flex items-center gap-1 text-xs text-stone-500"><x-icon name="clock" class="h-3 w-3" />{{ $item['readTime'] }} min read<span class="px-1">·</span><time datetime="{{ $item['dateModified'] }}">{{ \Carbon\Carbon::parse($item['dateModified'])->format('M j, Y') }}</time></p><h3 class="mt-3 text-lg font-bold leading-snug text-stone-900 group-hover:text-amber-700"><a href="{{ $href }}" class="after:absolute after:inset-0">{{ $item['title'] }}</a></h3><p class="mt-2 line-clamp-2 text-sm leading-relaxed text-stone-600">{{ $item['excerpt'] }}</p><p class="mt-auto flex items-center justify-between pt-5 text-xs"><span class="font-medium text-stone-600">{{ $item['author'] }}</span><span class="flex items-center gap-1 font-semibold text-amber-700">{{ $region['localizedTerms']['readMore'] }}<x-icon class="h-3.5 w-3.5" /></span></p></div>
</article>
@endif
