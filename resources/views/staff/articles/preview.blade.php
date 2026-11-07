@extends('staff.layout')
@section('title', 'Draft preview')
@section('content')
<div class="mx-auto max-w-5xl">
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
    @php($quality = app(\App\Services\PublicationQuality::class)->inspect($revision))
    <section class="mt-4 rounded-xl border border-stone-200 bg-white p-4" aria-label="Editorial readiness">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <span class="font-bold text-stone-900">Editorial checks</span>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-stone-700">{{ number_format($quality['body_words']) }} substantive words</span>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-stone-700">{{ $quality['source_links'] }} cited sources</span>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-stone-700">{{ $quality['complete_faqs'] }} FAQs</span>
        </div>
        @if($quality['failures'])
            <details class="mt-3 text-sm text-amber-950">
                <summary class="cursor-pointer font-semibold">{{ count($quality['failures']) }} publication checks still require attention</summary>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($quality['failures'] as $failure)<li>{{ $failure }}</li>@endforeach
                </ul>
            </details>
        @else
            <p class="mt-2 text-xs text-stone-600">Structural checks passed. Human fact, source, authorship and image-rights approval is still required.</p>
        @endif
    </section>
    <article class="staff-panel mt-5 bg-white sm:px-10 sm:py-9">
        <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-stone-950 sm:text-4xl">{{ $revision->title }}</h1>
        <p class="mt-4 text-lg text-stone-600">{{ $revision->excerpt }}</p>
        @if(filled($revision->editorial_evidence['answer'] ?? null))
            <section class="mt-5 rounded-xl border border-amber-200 bg-amber-50/50 p-5" aria-label="Direct answer">
                <h2 class="text-lg font-bold text-stone-950">The short answer</h2>
                <p class="mt-2 text-base leading-relaxed text-stone-800">{{ $revision->editorial_evidence['answer'] }}</p>
            </section>
        @endif
        <p class="mt-3 text-sm text-stone-500">{{ $revision->author_name }}</p>
        @if($revision->image)
            <img src="{{ $revision->image }}" alt="{{ $revision->image_alt ?: $revision->title }}"
                width="1200" height="675" class="mt-7 aspect-video w-full rounded-xl object-cover" data-image-fallback>
        @endif
        @include('editorial.evidence', ['evidence' => ['facts' => $revision->editorial_evidence['facts'] ?? []]])
        @php($renderedInlineImages = [])
        <div class="ar-article-prose mt-7 space-y-6">
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
                                <li>{!! app(\App\Services\InlineEditorialMarkdown::class)->render((string) $item) !!}</li>
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
                        @if(app(\App\Services\ArticleVisuals::class)->showInline($revision->image, $block['url'] ?? null, $renderedInlineImages)
                            && filled($block['alt'] ?? null))
                            @php($renderedInlineImages[] = $block['url'])
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
                        @if(preg_match('/^H2:\\s*(.+)$/u', trim($block['text'] ?? ''), $legacyHeading))
                            <h2 class="pt-3 text-2xl font-bold">{{ $legacyHeading[1] }}</h2>
                        @elseif(preg_match('/^H3:\\s*(.+)$/u', trim($block['text'] ?? ''), $legacyHeading))
                            <h3 class="pt-3 text-xl font-bold">{{ $legacyHeading[1] }}</h3>
                        @elseif(in_array(mb_strtolower(trim($block['text'] ?? '')), ['answer block:', 'introduction'], true))
                            <h2 class="pt-3 text-2xl font-bold">{{ rtrim($block['text'], ':') }}</h2>
                        @else
                            <p class="leading-relaxed text-stone-700">{!! app(\App\Services\InlineEditorialMarkdown::class)->render($block['text'] ?? '') !!}</p>
                        @endif
                        @break
                    @default
                        {{-- Unknown block types are deliberately ignored, as in the public article renderer. --}}
                @endswitch
            @endforeach
        </div>
        @include('editorial.sources', ['evidence' => $revision->editorial_evidence ?? [], 'reviewer' => null])
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
