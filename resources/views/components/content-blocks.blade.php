@props(['blocks'])
@foreach($blocks as $block)
@switch($block['type'])
@case('heading')@if(($block['level']??2)===3)<h3 class="pt-3 text-xl font-bold">{{ $block['text'] }}</h3>@else<h2 class="pt-3 text-2xl font-bold">{{ $block['text'] }}</h2>@endif @break
@case('list')<ul class="list-disc space-y-2 pl-5">@foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>@break
@case('link')<p><a class="text-amber-800 underline" href="{{ $block['url'] }}">{{ $block['text'] }}</a></p>@break
@case('quote')<blockquote class="border-l-4 border-amber-500 pl-4">{{ $block['text'] }}<cite class="block text-sm">{{ $block['attribution'] ?? '' }}</cite></blockquote>@break
@default<p class="leading-relaxed text-stone-700">{{ $block['text'] }}</p>
@endswitch
@endforeach
