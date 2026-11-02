<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive"><meta name="referrer" content="same-origin">
    <title>@yield('title', 'Editorial workspace') · African Ranked</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 font-sans text-stone-900">
<a href="#staff-main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-amber-300 focus:p-3">Skip to content</a>
@auth
<div class="staff-shell">
<aside class="staff-sidebar" aria-label="Workspace sidebar">
<a href="{{ route('staff.dashboard') }}" class="flex items-center gap-3 border-b border-stone-200 px-5 py-6"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-700 text-xl font-bold text-white">A</span><span><span class="block text-sm font-bold">African Ranked</span><span class="block text-xs text-stone-500">{{ auth()->user()->isSuperAdmin() ? 'Publisher workspace' : 'Author workspace' }}</span></span></a>
<div class="hidden px-3 py-6 lg:block">@include('staff.navigation')</div>
<details class="px-3 py-3 lg:hidden"><summary class="cursor-pointer rounded-xl px-3 py-2 text-sm font-semibold">Workspace menu</summary><div class="mt-3">@include('staff.navigation')</div></details>
<div class="mt-auto hidden border-t border-stone-200 p-5 lg:block"><p class="text-xs font-semibold">The best of Africa, ranked.</p><p class="mt-2 text-xs leading-relaxed text-stone-500">Editorial quality. Audience trust. Sustainable growth.</p></div>
</aside>
<div class="min-w-0">
<header class="flex flex-wrap items-center justify-between gap-4 border-b border-stone-200 bg-white px-5 py-4 sm:px-8"><form action="{{ route('staff.articles.index') }}" method="GET" role="search" class="w-full sm:max-w-sm"><label for="workspace-search" class="sr-only">Search all articles</label><div class="flex items-center gap-2 rounded-xl border border-stone-200 bg-stone-50 px-3"><input id="workspace-search" name="q" type="search" placeholder="Search articles…" class="min-w-0 flex-1 bg-transparent py-2.5 text-sm outline-none" maxlength="180"><button class="text-xs font-semibold text-amber-800" type="submit">Search</button></div></form><div class="flex flex-wrap items-center gap-4"><a href="/" class="text-xs font-semibold text-stone-600">View website →</a><div class="border-l border-stone-200 pl-4"><p class="text-xs font-semibold">{{ auth()->user()->name }}</p><p class="mt-0.5 text-xs text-stone-500">{{ auth()->user()->isSuperAdmin() ? 'Super admin' : 'Author' }}</p></div><form action="{{ route('staff.logout') }}" method="POST">@csrf<button class="staff-secondary" type="submit">Sign out</button></form></div></header>
@else
<header class="border-b border-stone-200 bg-white"><div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5"><a href="/" class="font-bold">African Ranked</a><a href="/" class="text-sm text-stone-600">Back to site</a></div></header>
@endauth
@if(config('review.enabled'))<div class="border-b border-amber-100 bg-amber-50 px-5 py-2 text-xs text-amber-900">Review environment · Isolated data · {{ in_array(config('mail.default'), ['log', 'array'], true) ? 'Email delivery disabled' : 'Mail transport configured for testing' }}</div>@endif
<main id="staff-main" class="mx-auto max-w-screen-2xl px-4 py-7 sm:px-8" tabindex="-1">
@if(session('status'))<p class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-900" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert"><p class="font-semibold">Please check the following:</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
@auth</div></div>@endauth
</body></html>
