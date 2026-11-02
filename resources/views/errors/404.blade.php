@php
    $catalog = app(\App\Support\Catalog::class);
    $region = $catalog->region();
    $regions = $catalog->regions();
    $categories = $catalog->categories();
    $basePath = '';
    $pageSuffix = '';
    $seo = app(\App\Support\Seo::class)->page('not-found', $region);
@endphp
@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-2xl px-4 py-24 text-center"><p class="text-sm font-semibold uppercase tracking-widest text-amber-700">404 · Off the beaten path</p><h1 class="mt-4 text-4xl font-bold">This page could not be found.</h1><p class="mt-4 text-stone-600">There is still plenty of Africa to discover. Find your next ranking from our home page.</p><a href="/" class="mt-8 inline-flex rounded-xl bg-amber-500 px-6 py-3 font-semibold text-stone-950 hover:bg-amber-400">Explore African Ranked</a></section>
@endsection
