@props(['blocks'])
@foreach($blocks as $block)
@switch($block['type'])
@case('heading')@if(($block['level']??2)===3)<h3 class="pt-3 text-xl font-bold">{{ $block['text'] }}</h3>@else<h2 class="pt-3 text-2xl font-bold">{{ $block['text'] }}</h2>@endif @break
@case('list')<ul class="list-disc space-y-2 pl-5">@foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>@break
@case('image')
@if(filter_var($block['url'] ?? '', FILTER_VALIDATE_URL) && parse_url($block['url'], PHP_URL_SCHEME) === 'https' && filled($block['alt'] ?? null))
<figure class="my-8 overflow-hidden rounded-xl border border-stone-200 bg-stone-50">
<img src="{{ $block['url'] }}" alt="{{ $block['alt'] }}" loading="lazy" decoding="async" class="aspect-[16/10] w-full object-cover" />
@if(filled($block['caption'] ?? null) || filled($block['credit'] ?? null))
<figcaption class="px-4 py-3 text-sm text-stone-600">
{{ $block['caption'] ?? '' }}
@if(filled($block['credit'] ?? null) && filter_var($block['source'] ?? '', FILTER_VALIDATE_URL) && parse_url($block['source'], PHP_URL_SCHEME) === 'https')
<a href="{{ $block['source'] }}" rel="noopener noreferrer" target="_blank" class="text-amber-800 underline underline-offset-2">{{ $block['credit'] }}</a>
@endif
</figcaption>
@endif
</figure>
@endif
@break
@case('link')<p><a class="text-amber-800 underline" href="{{ $block['url'] }}">{{ $block['text'] }}</a></p>@break
@case('quote')<blockquote class="border-l-4 border-amber-500 pl-4">{{ $block['text'] }}<cite class="block text-sm">{{ $block['attribution'] ?? '' }}</cite></blockquote>@break
@default<p class="leading-relaxed text-stone-700">{{ $block['text'] }}</p>
@endswitch
@endforeach
