<footer class="bg-stone-950 text-stone-400">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div class="grid grid-cols-2 gap-8 md:grid-cols-4 lg:grid-cols-5">
            <div class="col-span-2">
                <a href="{{ $basePath ?: '/' }}" class="inline-flex items-center gap-2 text-lg font-bold text-white"><img src="/africa-mark.svg?v=dotted-20261103" width="353" height="372" alt="" class="h-9 w-auto object-contain">{{ $site['name'] ?? 'African Ranked' }}</a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed">{{ $site['description'] ?? 'Discover Africa through original editorial stories, researched rankings and practical guides.' }}</p>
            </div>
            <div><h2 class="text-sm font-semibold uppercase tracking-wider text-white">Categories</h2><ul class="mt-4 space-y-2 text-sm">@foreach($categories as $footerCategory)<li><a href="{{ $basePath }}/categories/{{ $footerCategory['slug'] }}" class="hover:text-amber-500">{{ $footerCategory['name'] }}</a></li>@endforeach</ul></div>
            <div><h2 class="text-sm font-semibold uppercase tracking-wider text-white">Regions</h2><ul class="mt-4 space-y-2 text-sm">@foreach($regions as $edition)<li><a href="{{ $catalog->path($edition['code']) }}" class="hover:text-amber-500">{{ $edition['name'] }}</a></li>@endforeach</ul></div>
            <div><h2 class="text-sm font-semibold uppercase tracking-wider text-white">Discover</h2><ul class="mt-4 space-y-2 text-sm"><li><a href="{{ $basePath }}/rankings" class="hover:text-amber-500">Rankings &amp; guides</a></li><li><a href="/plan-safari" class="hover:text-amber-500">Plan your safari</a></li>@if($catalog->usesLegacyStrategy())<li><a href="{{ $basePath }}/strategy" class="hover:text-amber-500">Editorial approach</a></li>@endif
@foreach(($footerPages ?? collect()) as $footerPage)<li><a href="{{ $footerPage->publicPath() }}" class="hover:text-amber-500">{{ $footerPage->publishedRevision->title }}</a></li>@endforeach
@if(filled($site['contact_email'] ?? ''))<li><a href="mailto:{{ $site['contact_email'] }}" class="hover:text-amber-500">Contact us</a></li>@endif<li><a href="/feed.xml" class="hover:text-amber-500">RSS feed</a></li><li><a href="/sitemap.xml" class="hover:text-amber-500">Sitemap</a></li></ul></div>
        </div>
        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs sm:flex-row"><p>&copy; {{ now()->year }} {{ $site['name'] ?? 'African Ranked' }}. All rights reserved.</p><div class="flex items-center gap-4"><p class="flex items-center gap-1.5"><x-icon name="compass" class="h-4 w-4 text-amber-500" />Currently viewing: {{ $region['flag'] }} {{ $region['name'] }}</p>@if(config('analytics.enabled'))<button type="button" data-analytics-settings class="text-stone-300 underline underline-offset-2 hover:text-white">Analytics preferences</button>@endif</div></div>
    </div>
</footer>
