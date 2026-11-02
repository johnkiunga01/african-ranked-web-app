<header class="sticky inset-x-0 top-0 z-40">
    <div class="bg-amber-600 px-4 py-1.5 text-center text-xs font-medium text-amber-950">{{ $region['tagline'] }}</div>
    <div class="border-b border-stone-200 bg-white/90 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ $basePath ?: '/' }}" class="flex shrink-0 items-center gap-2" aria-label="{{ $site['name'] ?? 'African Ranked' }} {{ $region['name'] }} home">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-orange-700 text-lg font-bold text-white">A</span>
                <span><span class="block text-base font-bold leading-tight text-stone-900">{{ $site['name'] ?? 'African Ranked' }}</span><span class="block text-[10px] font-medium uppercase tracking-wider text-stone-500">{{ $region['flag'] }} {{ $region['name'] }}</span></span>
            </a>
            <nav aria-label="Main navigation" class="hidden items-center gap-1 lg:flex">
                @foreach($categories->take(5) as $navCategory)
                <a href="{{ $basePath }}/categories/{{ $navCategory['slug'] }}" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-900" @if(($category['slug'] ?? null) === $navCategory['slug']) aria-current="page" @endif>{{ explode(' & ', $navCategory['name'])[0] }}</a>
                @endforeach
            </nav>
            <div class="flex items-center gap-3">
                <details class="relative" data-region-menu>
                    <summary class="cursor-pointer list-none rounded-lg border border-stone-200 bg-white px-3 py-2 text-xs font-medium sm:text-sm" aria-label="Choose edition">{{ $region['flag'] }} <span class="hidden sm:inline">{{ $region['name'] }}</span><span class="sm:hidden">{{ strtoupper($region['code']) }}</span> <span aria-hidden="true">⌄</span></summary>
                    <nav aria-label="Regional editions" class="absolute right-0 z-50 mt-2 w-56 rounded-xl border border-stone-200 bg-white p-2 shadow-xl">
                        @foreach($regions as $edition)
                        <a href="{{ $catalog->path($edition['code'], $pageSuffix) }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-amber-50 {{ $edition['code'] === $region['code'] ? 'bg-amber-50 font-semibold text-amber-800' : 'text-stone-700' }}" @if($edition['code'] === $region['code']) aria-current="true" @endif>{{ $edition['flag'] }} {{ $edition['name'] }}</a>
                        @endforeach
                    </nav>
                </details>
                <button type="button" class="rounded-lg p-2 lg:hidden" data-menu-toggle aria-controls="mobile-navigation" aria-expanded="false" aria-label="Open navigation"><x-icon name="menu" /></button>
            </div>
        </div>
    </div>
    <nav id="mobile-navigation" aria-label="Mobile navigation" class="border-b border-stone-200 bg-white px-4 py-3 lg:hidden" hidden>
        @foreach($categories as $navCategory)<a href="{{ $basePath }}/categories/{{ $navCategory['slug'] }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-stone-100">{{ $navCategory['name'] }}</a>@endforeach
    </nav>
    <noscript><nav aria-label="Categories" class="flex flex-wrap gap-3 bg-white px-4 py-2 text-sm lg:hidden">@foreach($categories as $navCategory)<a href="{{ $basePath }}/categories/{{ $navCategory['slug'] }}">{{ explode(' & ', $navCategory['name'])[0] }}</a>@endforeach</nav></noscript>
</header>
