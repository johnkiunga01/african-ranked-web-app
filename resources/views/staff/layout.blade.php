<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="same-origin">
    <meta name="theme-color" content="#171c20">
    <title>@yield('title', 'Editorial workspace') · African Ranked</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6f7f8] font-sans text-stone-900">
<a href="#staff-main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-amber-300 focus:p-3">Skip to content</a>
@auth
<div class="staff-shell">
    <aside class="staff-sidebar" aria-label="Editorial workspace">
        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-4 lg:px-6 lg:py-7">
            <a href="{{ route('staff.dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-lg focus-visible:outline-amber-300">
                <img src="/africa-mark.svg" width="31" height="38" alt="" class="h-10 w-8 shrink-0">
                <span class="min-w-0">
                    <span class="block truncate text-base font-extrabold tracking-tight text-white">African Ranked</span>
                    <span class="block text-[11px] font-medium text-stone-400">Editorial workspace</span>
                </span>
            </a>
            <details class="staff-mobile-nav relative lg:hidden">
                <summary class="inline-flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-xl border border-white/20 px-3 py-2 text-sm font-bold text-white">
                    <x-icon name="menu" class="h-5 w-5" /> Menu
                </summary>
                <div class="absolute right-0 top-full z-50 mt-3 max-h-[calc(100dvh-100px)] w-[min(330px,calc(100vw-2rem))] overflow-y-auto rounded-2xl border border-white/15 bg-[#171c20] p-4 shadow-2xl">
                    @include('staff.navigation')
                </div>
            </details>
        </div>
        <div class="hidden flex-1 overflow-y-auto px-3 py-7 lg:block lg:px-4">
            @include('staff.navigation')
        </div>
        <div class="mt-auto hidden border-t border-white/10 px-6 py-5 lg:block">
            <p class="truncate text-sm font-bold text-white">{{ auth()->user()->name }}</p>
            <p class="mt-1 text-xs text-stone-400">{{ auth()->user()->isSuperAdmin() ? 'Administrator' : 'Author' }}</p>
        </div>
    </aside>
    <div class="staff-workspace min-w-0">
        <header class="staff-topbar">
            <form action="{{ route('staff.articles.index') }}" method="GET" role="search" class="min-w-0 flex-1 sm:max-w-[440px]">
                <label for="workspace-search" class="sr-only">Search article titles and content</label>
                <div class="flex min-h-11 items-center gap-2 rounded-xl border border-stone-200 bg-[#f7f8fa] px-3 transition focus-within:border-amber-400 focus-within:ring-2 focus-within:ring-amber-100">
                    <x-icon name="search" class="h-4 w-4 shrink-0 text-stone-500" />
                    <input id="workspace-search" name="q" type="search" placeholder="Find an article…" value="{{ request('q', '') }}" maxlength="180" class="min-w-0 flex-1 bg-transparent py-2.5 text-sm text-stone-900 outline-none">
                    <button type="submit" class="text-xs font-bold text-amber-800 hover:underline">Search</button>
                </div>
            </form>
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <a href="/" target="_blank" rel="noopener noreferrer" class="staff-website-link inline-flex min-h-11 items-center gap-1.5 rounded-xl px-3 text-sm font-bold text-stone-700 transition hover:bg-stone-100 hover:text-amber-800">
                    <span class="hidden sm:inline">View website</span><span class="sm:hidden">Site</span><x-icon name="arrow-up-right" class="h-4 w-4" />
                </a>
                <div class="hidden h-8 w-px bg-stone-200 sm:block" aria-hidden="true"></div>
                <form action="{{ route('staff.logout') }}" method="POST">
                    @csrf
                    <button class="inline-flex min-h-11 items-center rounded-xl border border-stone-200 px-3 text-xs font-bold text-stone-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-stone-900" type="submit">Sign out</button>
                </form>
            </div>
        </header>
        <main id="staff-main" class="mx-auto w-full max-w-[1480px] px-4 py-7 sm:px-7 lg:px-9 lg:py-9" tabindex="-1">
            @if(session('status'))<p class="mb-6 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-900" role="status">{{ session('status') }}</p>@endif
            @if($errors->any())<div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert"><p class="font-bold">Please check these details:</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
</div>
@else
<header class="border-b border-stone-200 bg-white"><div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-5"><a href="/" class="flex items-center gap-2 font-extrabold"><img src="/africa-mark.svg" width="28" height="34" alt="" class="h-8 w-7"><span>African Ranked</span></a><a href="/" class="text-sm font-semibold text-stone-600 hover:text-amber-800">Visit website</a></div></header>
<main id="staff-main" class="mx-auto max-w-screen-xl px-4 py-7 sm:px-8" tabindex="-1">
    @if(session('status'))<p class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-900" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert"><p class="font-bold">Please check these details:</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
@endauth
</body>
</html>
