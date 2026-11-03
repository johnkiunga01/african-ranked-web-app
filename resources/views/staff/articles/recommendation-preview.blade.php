<h2 class="text-2xl font-bold">{{ $version->title }}</h2>
<p class="mt-3 text-lg leading-relaxed text-stone-600">{{ $version->excerpt }}</p>
@if(filled($version->editorial_evidence['answer'] ?? ''))<p class="mt-4 font-semibold">{{ $version->editorial_evidence['answer'] }}</p>@endif
<div class="mt-5 space-y-4">@foreach($version->body as $block)
@switch($block['type'])
@case('heading')<h3 class="pt-3 text-xl font-bold">{{ $block['text'] }}</h3>@break
@case('list')<ul class="list-disc space-y-2 pl-5">@foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>@break
@case('link')<p><a class="text-amber-800 underline" href="{{ $block['url'] }}" rel="noopener noreferrer" target="_blank">{{ $block['text'] }}</a></p>@break
@case('image')<figure><img src="{{ $block['url'] }}" alt="{{ $block['alt'] ?? '' }}" class="w-full rounded-xl" loading="lazy">@if(filled($block['caption'] ?? ''))<figcaption>{{ $block['caption'] }}</figcaption>@endif</figure>@break
@default<p class="leading-relaxed">{{ $block['text'] ?? '' }}</p>
@endswitch
@endforeach</div>
@if($version->faqs)<section class="mt-6"><h3 class="text-xl font-bold">Reader questions</h3>@foreach($version->faqs as $faq)<h4 class="mt-4 font-semibold">{{ $faq['question'] }}</h4><p>{{ $faq['answer'] }}</p>@endforeach</section>@endif
<details class="mt-6 rounded-xl border border-stone-200 p-4"><summary class="cursor-pointer font-semibold">Search preview</summary><h3 class="mt-3 font-bold">{{ $version->seo_title ?: $version->title }}</h3><p class="mt-2 text-sm">{{ $version->meta_description ?: $version->excerpt }}</p></details>
