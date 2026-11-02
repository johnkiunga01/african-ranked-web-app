@extends('layouts.app')
@section('content')
<article class="mx-auto max-w-3xl px-4 py-16 sm:px-6"><h1 class="text-4xl font-bold">{{ $revision->title }}</h1>@if($revision->description)<p class="mt-5 text-lg text-stone-600">{{ $revision->description }}</p>@endif<div class="mt-8 space-y-6"><x-content-blocks :blocks="$revision->body" /></div></article>
@endsection
