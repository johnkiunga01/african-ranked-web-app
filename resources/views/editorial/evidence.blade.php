@if(($section ?? 'all') !== 'facts' && !empty($evidence['answer']))
<section class="mt-8 rounded-2xl border border-amber-200 bg-amber-50/50 p-6" aria-label="The short answer">
    <h2 class="text-lg font-bold">The short answer</h2>
    <p class="mt-3 leading-relaxed text-stone-700">{{ $evidence['answer'] }}</p>
</section>
@endif

@if(($section ?? 'all') !== 'answer' && !empty($evidence['facts']))
<section class="mt-10" aria-labelledby="key-facts-heading">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-2">
        <h2 id="key-facts-heading" class="text-xl font-extrabold tracking-tight text-stone-950 sm:text-2xl">Key facts</h2>
        <p class="text-xs text-stone-500">Select a fact to read its supporting reference.</p>
    </div>
    <ol class="grid gap-3 sm:grid-cols-2" aria-label="Source-backed key facts">
        @foreach($evidence['facts'] as $fact)
            @php
                $sourceUrl = $fact['source_url'] ?? null;
                $validSource = is_string($sourceUrl)
                    && filter_var($sourceUrl, FILTER_VALIDATE_URL)
                    && parse_url($sourceUrl, PHP_URL_SCHEME) === 'https';
                $verifiedAt = $fact['verified_at'] ?? null;
            @endphp
            <li class="group flex min-w-0 flex-col justify-between gap-5 rounded-2xl border border-stone-200 bg-stone-50/75 p-5 transition-colors hover:border-amber-200 hover:bg-amber-50/40">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 shrink-0 text-xs font-extrabold tabular-nums tracking-wider text-stone-400" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="min-w-0">
                        @if($validSource)
                            <a href="{{ $sourceUrl }}" class="font-semibold leading-relaxed text-stone-900 underline decoration-amber-400 decoration-1 underline-offset-4 transition-colors hover:text-amber-800 hover:decoration-amber-700 focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700">
                                {{ $fact['fact'] ?? '' }}<span class="ml-1 inline-block text-amber-800" aria-hidden="true">↗</span>
                            </a>
                        @else
                            <p class="font-semibold leading-relaxed text-stone-900">{{ $fact['fact'] ?? '' }}</p>
                        @endif
                    </div>
                </div>
                @if($verifiedAt)
                    <p class="pl-7 text-xs font-medium text-stone-500">Verified <time datetime="{{ $verifiedAt }}">{{ $verifiedAt }}</time></p>
                @endif
            </li>
        @endforeach
    </ol>
</section>
@endif
