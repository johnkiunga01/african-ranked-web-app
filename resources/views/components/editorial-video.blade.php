@props(['block'])
@php($videoId = \App\Support\YouTubeVideo::id($block['url'] ?? ''))
@if($videoId && filled($block['title'] ?? null))
<figure data-youtube-video="{{ $videoId }}" data-video-title="{{ $block['title'] }}" class="my-8 overflow-hidden rounded-2xl border border-stone-200 bg-stone-950 text-white">
    <div data-video-player class="flex aspect-video items-center justify-center p-5 sm:p-8">
        <div class="max-w-md text-center"><p class="text-lg font-bold sm:text-xl">{{ $block['title'] }}</p><p class="mt-3 text-sm leading-relaxed text-stone-300">Loading this video connects to YouTube, which receives information about your visit.</p><button type="button" data-load-video class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-amber-400 px-5 py-3 text-sm font-bold text-stone-950 hover:bg-amber-300" aria-label="Load video: {{ $block['title'] }}"><span aria-hidden="true">▶</span> Load video</button></div>
    </div>
    <figcaption class="border-t border-white/15 px-5 py-4 text-sm"><a href="https://www.youtube.com/watch?v={{ $videoId }}" target="_blank" rel="noopener noreferrer" class="text-amber-300 underline underline-offset-4">Watch on YouTube: {{ $block['title'] }}</a></figcaption>
</figure>
@endif
