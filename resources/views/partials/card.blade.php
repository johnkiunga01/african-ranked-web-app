@php
    $cardCategory = $catalog->category($item['category']);
    $featured = $featured ?? false;
    $href = $basePath.'/articles/'.$item['slug'];
@endphp
@if($featured)
<article data-reveal class="group relative overflow-hidden rounded-2xl bg-stone-900">
    <div class="relative h-[420px] overflow-hidden"><img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" width="1200" height="675" fetchpriority="high" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" data-image-fallback><div class="absolute inset-0 bg-gradient-to-t from-stone-950 via-stone-950/50 to-transparent"></div></div>
    <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
        <div class="mb-3 flex items-center gap-2"><span class="flex items-center gap-1.5 rounded-full border bg-stone-950/95 px-3 py-1 text-xs font-semibold text-white" style="border-color: {{ $cardCategory['color'] }}"><x-icon :name="$cardCategory['icon']" class="h-3 w-3" />{{ explode(' & ', $cardCategory['name'])[0] }}</span><span class="flex items-center gap-1 text-xs font-medium text-stone-300"><x-icon name="clock" class="h-3 w-3" />{{ $item['readTime'] }} min read</span></div>
        <h3 class="text-2xl font-bold leading-tight text-white sm:text-3xl"><a href="{{ $href }}" class="after:absolute after:inset-0">{{ $item['title'] }}</a></h3>
        <p class="mt-2 line-clamp-2 max-w-2xl text-sm text-stone-300 sm:text-base">{{ $item['excerpt'] }}</p>
        <p class="mt-4 flex items-center gap-2 text-sm font-semibold text-amber-400">{{ $region['localizedTerms']['readMore'] }}<x-icon class="h-4 w-4" /></p>
    </div>
</article>
@else
<article data-reveal class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white transition hover:shadow-lg">
    <div class="relative aspect-[16/10] overflow-hidden"><img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" width="600" height="375" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" data-image-fallback><span class="absolute left-3 top-3 flex items-center gap-1.5 rounded-full border bg-stone-950/95 px-2.5 py-1 text-xs font-semibold text-white" style="border-color: {{ $cardCategory['color'] }}"><x-icon :name="$cardCategory['icon']" class="h-3 w-3" />{{ explode(' & ', $cardCategory['name'])[0] }}</span></div>
    <div class="flex flex-1 flex-col p-5"><p class="flex items-center gap-1 text-xs text-stone-500"><x-icon name="clock" class="h-3 w-3" />{{ $item['readTime'] }} min read<span class="px-1">·</span><time datetime="{{ $item['dateModified'] }}">{{ \Carbon\Carbon::parse($item['dateModified'])->format('M j, Y') }}</time></p><h3 class="mt-3 text-lg font-bold leading-snug text-stone-900 group-hover:text-amber-700"><a href="{{ $href }}" class="after:absolute after:inset-0">{{ $item['title'] }}</a></h3><p class="mt-2 line-clamp-2 text-sm leading-relaxed text-stone-600">{{ $item['excerpt'] }}</p><p class="mt-auto flex items-center justify-between pt-5 text-xs"><span class="font-medium text-stone-600">{{ $item['author'] }}</span><span class="flex items-center gap-1 font-semibold text-amber-700">{{ $region['localizedTerms']['readMore'] }}<x-icon class="h-3.5 w-3.5" /></span></p></div>
</article>
@endif
