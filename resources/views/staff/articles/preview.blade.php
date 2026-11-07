@extends('staff.layout')
@section('title', 'Draft preview')
@section('content')
<div class="mx-auto max-w-3xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('staff.articles.edit', $article) }}" class="text-sm font-semibold text-amber-800">← Back to editing</a>
        @can('update', $article)
            @if(in_array($revision->status, [\App\Enums\RevisionStatus::Draft, \App\Enums\RevisionStatus::ChangesRequested], true))
                <form method="POST" action="{{ route('staff.articles.action', [$article, 'submit']) }}">
                    @csrf
                    <input type="hidden" name="revision_id" value="{{ $revision->id }}">
                    <button type="submit" class="staff-button">Submit for review</button>
                </form>
            @endif
        @endcan
    </div>
    <p class="mt-6 rounded-xl bg-amber-100 p-3 text-sm text-amber-950">
        Private draft preview · Revision {{ $revision->number }} · {{ str_replace('_', ' ', $revision->status->value) }}
    </p>
    <article class="staff-panel mt-5">
        <h1 class="text-3xl font-bold">{{ $revision->title }}</h1>
        <p class="mt-4 text-lg text-stone-600">{{ $revision->excerpt }}</p>
        <p class="mt-3 text-sm text-stone-500">{{ $revision->author_name }}</p>
        @if($revision->image)
            <img src="{{ $revision->image }}" alt="{{ $revision->image_alt ?: $revision->title }}"
                width="1200" height="675" class="mt-7 aspect-video w-full rounded-xl object-cover" data-image-fallback>
        @endif
        <div class="mt-7 space-y-5">
            @foreach($revision->body ?? [] as $block)
                @switch($block['type'] ?? '')
                    @case('link')
                        @if(filled($block['text'] ?? null) && filled($block['url'] ?? null))
                            <p><a href="{{ $block['url'] }}" class="text-amber-800 underline">{{ $block['text'] }}</a></p>
                        @endif
                        @break
                    @case('heading')
                        @if(($block['level'] ?? 2) === 3)
                            <h3 class="pt-3 text-xl font-bold">{{ $block['text'] ?? '' }}</h3>
                        @else
                            <h2 class="pt-3 text-2xl font-bold">{{ $block['text'] ?? '' }}</h2>
                        @endif
                        @break
                    @case('list')
                        <ul class="list-disc space-y-2 pl-5 text-stone-700">
                            @foreach($block['items'] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                        @break
                    @case('quote')
                        <blockquote class="border-l-4 border-amber-500 pl-4 italic">
                            {{ $block['text'] ?? '' }}
                            @if(filled($block['attribution'] ?? null))
                                <cite class="block text-sm">{{ $block['attribution'] }}</cite>
                            @endif
                        </blockquote>
                        @break
                    @case('image')
                        @if(filter_var($block['url'] ?? '', FILTER_VALIDATE_URL) &&
                            parse_url($block['url'], PHP_URL_SCHEME) === 'https' &&
                            filled($block['alt'] ?? null))
                            <figure class="overflow-hidden rounded-xl">
                                <img src="{{ $block['url'] }}" alt="{{ $block['alt'] }}"
                                    loading="lazy" class="w-full rounded-xl object-cover">
                                @if(filled($block['caption'] ?? null) || filled($block['credit'] ?? null))
                                    <figcaption class="mt-2 text-sm text-stone-600">
                                        {{ $block['caption'] ?? '' }}
                                        {{ $block['credit'] ?? '' }}
                                    </figcaption>
                                @endif
                            </figure>
                        @endif
                        @break
                    @case('paragraph')
                        <p class="leading-relaxed text-stone-700">{{ $block['text'] ?? '' }}</p>
                        @break
                    @default
                        {{-- Unknown block types are deliberately ignored, as in the public article renderer. --}}
                @endswitch
            @endforeach
        </div>
        @if($revision->faqs)
            <section class="mt-8 border-t border-stone-200 pt-6">
                <h2 class="text-xl font-bold">Frequently asked questions</h2>
                @foreach($revision->faqs as $faq)
                    <h3 class="mt-4 font-semibold">{{ $faq['question'] ?? '' }}</h3>
                    <p class="mt-2 text-sm text-stone-600">{{ $faq['answer'] ?? '' }}</p>
                @endforeach
            </section>
        @endif
    </article>
</div>
@endsection
