@extends('staff.layout')
@section('title','Page preview')
@section('content')
<div class="mx-auto max-w-3xl"><a class="text-sm text-amber-800" href="{{ route('staff.pages.edit',$page) }}">← Edit page</a><p class="mt-4 text-xs text-stone-500">Private preview · saved version {{ $revision->number }}</p><article class="staff-panel mt-5"><h1 class="text-3xl font-bold">{{ $revision->title }}</h1><p class="mt-4 text-stone-600">{{ $revision->description }}</p><div class="mt-6 space-y-5"><x-content-blocks :blocks="$revision->body" /></div></article></div>
@endsection
