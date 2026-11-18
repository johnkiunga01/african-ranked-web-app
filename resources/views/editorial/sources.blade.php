@if(!empty($evidence['methodology']))
<section class="mt-10 border-t border-stone-200 pt-6">
    <h2 class="text-xl font-bold">Methodology and limits</h2>
    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-stone-700">{{ $evidence['methodology'] }}</p>
</section>
@endif
@if(!empty($evidence['sources']))
<details class="mt-10 rounded-xl border border-stone-200 bg-stone-50/70 px-5 py-4" aria-label="Research references">
    <summary class="cursor-pointer text-sm font-bold text-stone-800 hover:text-amber-900">Research references and access dates</summary>
    <p class="mt-3 text-xs leading-relaxed text-stone-500">The linked key facts above point directly to their supporting references. The full research register is available for checking.</p>
    <ol class="mt-4 list-decimal space-y-3 pl-5 text-sm">
        @foreach($evidence['sources'] as $source)
            <li>
                <a class="font-semibold text-amber-800 underline underline-offset-2" href="{{ $source['url'] }}">{{ $source['title'] }}</a>
                <span class="block text-xs text-stone-500">{{ $source['publisher'] }} · Accessed {{ $source['accessed_at'] }}</span>
            </li>
        @endforeach
    </ol>
</details>
@endif
@if(!empty($evidence['last_verified_at']))
<p class="mt-6 text-xs text-stone-500">Last verified {{ $evidence['last_verified_at'] }}@if(!empty($reviewer)) · Reviewed by {{ $reviewer }}@endif</p>
@endif
