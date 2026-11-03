@extends('layouts.app')
@section('content')
<div class="pb-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">@include('partials.breadcrumbs')</div>
    <section class="relative overflow-hidden" style="background-color: {{ $category['color'] }}"><div class="absolute inset-0 bg-gradient-to-br from-black/80 to-black/65"></div><div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20"><h1 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">{{ $category['name'] }}</h1><p class="mt-4 max-w-2xl text-lg text-white/90">{{ $category['description'] }}</p><p class="mt-6 text-sm text-white/80">{{ $region['flag'] }} {{ $region['name'] }} edition <span class="px-2">·</span> {{ $articles->count() }} rankings</p></div></section>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6" aria-label="Articles">@if($articles->isNotEmpty())<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach($articles as $item)@include('partials.card', ['featured' => false])@endforeach</div>@else<p class="py-20 text-center text-stone-500">No rankings in this category yet for {{ $region['name'] }}. <a href="/categories/{{ $category['slug'] }}" class="text-amber-700 underline">Explore the global edition</a>.</p>@endif</section>
    <div class="mx-auto max-w-7xl px-4 sm:px-6">@include('partials.newsletter')</div>
</div>
@endsection
