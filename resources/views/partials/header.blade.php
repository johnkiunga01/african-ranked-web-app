@php($immersiveHome = $pageSuffix === '')
<header data-site-header @class(['z-50 inset-x-0 top-0 transition-colors duration-300','fixed home-site-header text-white' => $immersiveHome,'sticky border-b border-stone-200 bg-white/95 text-stone-950 shadow-sm backdrop-blur-lg' => ! $immersiveHome])>
    <div class="mx-auto flex h-[76px] max-w-[1450px] items-center justify-between gap-2 px-4 sm:px-6 lg:px-10">
        <a href="{{ $basePath ?: '/' }}" class="group inline-flex min-w-0 shrink-0 items-center gap-1.5 focus-visible:rounded-lg sm:gap-2.5" aria-label="{{ $site['name'] ?? 'African Ranked' }} home">
            <img src="/africa-mark.svg?v=dotted-20261103" width="353" height="372" alt="" class="h-10 w-auto object-contain shrink-0 transition-transform duration-300 group-hover:rotate-[-5deg]" />
            <span class="text-[15px] font-extrabold tracking-tight sm:text-[22px]">{{ $site['name'] ?? 'African Ranked' }}</span>
        </a>
        <span class="hidden h-7 w-px bg-current opacity-25 xl:block" aria-hidden="true"></span>
        <nav class="hidden flex-1 items-center gap-1 lg:flex" aria-label="Main navigation">
            <a href="{{ $basePath }}/rankings" class="site-nav-link" @if($pageSuffix === '/rankings' && request('type') !== 'guide') aria-current="page" @endif>Rankings</a>
            <a href="{{ $basePath }}/rankings?type=guide" class="site-nav-link" @if(request('type') === 'guide') aria-current="page" @endif>Guides</a>
            <a href="{{ $basePath }}/strategy" class="site-nav-link" @if($pageSuffix === '/strategy') aria-current="page" @endif>Our approach</a>
        </nav>
        <div class="flex items-center gap-1 sm:gap-3">
            <details class="relative" data-search-menu>
                <summary class="site-icon-button list-none cursor-pointer" aria-label="Search African Ranked" title="Search articles"><x-icon name="search" class="h-5 w-5" /></summary>
                <form action="{{ $basePath }}/rankings" method="GET" role="search" class="absolute right-[-92px] top-full z-50 mt-3 flex w-[min(350px,calc(100vw-2rem))] items-center gap-2 rounded-2xl border border-stone-200 bg-white p-3 text-stone-950 shadow-2xl sm:right-0">
                    <label class="sr-only" for="site-search">Search rankings and guides</label>
                    <input id="site-search" type="search" name="q" maxlength="100" placeholder="Search Africa…" class="min-w-0 flex-1 rounded-lg border border-stone-300 px-3 py-2.5 text-sm focus-visible:outline-offset-0" required>
                    <button type="submit" class="rounded-lg bg-amber-400 px-4 py-2.5 text-sm font-bold text-stone-950 hover:bg-amber-300">Search</button>
                </form>
            </details>
            <details class="relative" data-region-menu>
                <summary class="site-icon-button inline-flex cursor-pointer list-none items-center gap-1 px-2.5 text-xs font-semibold sm:text-sm" aria-label="Choose regional edition" title="Change edition"><span class="text-lg">{{ $region['flag'] }}</span><span class="hidden sm:inline">{{ $region['name'] }}</span><x-icon name="chevron-down" class="h-4 w-4" /></summary>
                <nav aria-label="Regional editions" class="absolute right-0 z-50 mt-3 max-h-[70vh] w-56 overflow-auto rounded-2xl border border-stone-200 bg-white p-2 text-stone-900 shadow-2xl">
                    @foreach($regions as $edition)
                        <a href="{{ $catalog->path($edition['code'], $pageSuffix) }}" class="block rounded-xl px-3 py-2.5 text-sm transition hover:bg-amber-50 {{ $edition['code'] === $region['code'] ? 'bg-amber-50 font-bold text-amber-800' : 'text-stone-700' }}" @if($edition['code'] === $region['code']) aria-current="page" @endif>{{ $edition['flag'] }} {{ $edition['name'] }}</a>
                    @endforeach
                </nav>
            </details>
            <button type="button" class="site-icon-button lg:hidden" data-menu-toggle aria-controls="mobile-navigation" aria-expanded="false" aria-label="Open navigation"><x-icon name="menu" class="h-6 w-6" /></button>
        </div>
    </div>
    <nav id="mobile-navigation" aria-label="Mobile navigation" class="max-h-[calc(100dvh-76px)] overflow-y-auto border-t border-white/10 bg-[#15191b] px-4 pb-5 pt-3 text-white shadow-2xl lg:hidden sm:px-6" hidden>
        <form action="{{ $basePath }}/rankings" method="GET" role="search" class="mb-4 flex gap-2 rounded-xl bg-white/10 p-2">
            <label for="site-search-mobile" class="sr-only">Search articles</label>
            <input id="site-search-mobile" type="search" name="q" maxlength="100" placeholder="Search rankings and guides" class="min-w-0 flex-1 rounded-lg bg-white px-3 py-2.5 text-sm text-stone-900" required>
            <button type="submit" class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold text-stone-950">Go</button>
        </form>
        <a href="{{ $basePath }}/rankings" class="site-mobile-link">All rankings</a>
        <a href="{{ $basePath }}/rankings?type=guide" class="site-mobile-link">Practical guides</a>
        <a href="{{ $basePath }}/strategy" class="site-mobile-link">Our editorial approach</a>
        <p class="mt-5 px-3 text-xs font-semibold uppercase tracking-[.16em] text-amber-300">Explore topics</p>
        @foreach($categories as $navCategory)
            <a href="{{ $basePath }}/categories/{{ $navCategory['slug'] }}" class="site-mobile-link">{{ $navCategory['name'] }}</a>
        @endforeach
    </nav>
    <noscript><nav aria-label="Category links" class="flex flex-wrap gap-3 bg-stone-950 px-4 py-2 text-sm text-white lg:hidden">@foreach($categories as $navCategory)<a href="{{ $basePath }}/categories/{{ $navCategory['slug'] }}">{{ $navCategory['name'] }}</a>@endforeach</nav></noscript>
</header>
