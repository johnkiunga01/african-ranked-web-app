{{-- Opt-in install interface, visible only on supported mobile browsers via pwa.js. --}}
@include('partials.pwa-launch')
<div data-pwa-root>
    <button type="button" data-pwa-open hidden
        class="fixed bottom-5 right-4 z-[60] inline-flex min-h-11 items-center gap-2 rounded-full border border-amber-300 bg-[#171c20] px-4 py-2 text-xs font-extrabold text-white shadow-xl shadow-black/20 transition hover:bg-stone-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 sm:right-6">
        <x-icon name="smartphone" class="h-4 w-4 text-amber-300" />
        Install African Ranked
    </button>
    <section data-pwa-panel hidden role="region" aria-labelledby="ar-pwa-panel-title"
        class="fixed bottom-5 left-3 right-3 z-[70] mx-auto max-w-sm rounded-2xl border border-stone-200 bg-white px-5 py-5 text-stone-900 shadow-2xl shadow-black/20 sm:left-auto sm:right-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="/apple-touch-icon.png" width="48" height="48" alt="" class="h-12 w-12 shrink-0 rounded-xl">
                <div>
                    <h2 id="ar-pwa-panel-title" class="text-base font-extrabold">African Ranked on your phone</h2>
                    <p class="mt-0.5 text-xs text-stone-600">Quick access from your home screen.</p>
                </div>
            </div>
            <button type="button" data-pwa-close aria-label="Close installation instructions" class="min-h-10 min-w-10 rounded-lg border border-stone-200 text-sm font-bold hover:bg-stone-100">✕</button>
        </div>
        <p data-pwa-help class="mt-4 text-sm leading-relaxed text-stone-700">Add African Ranked to your home screen for one-tap access.</p>
        <button type="button" data-pwa-install
            class="mt-4 min-h-11 w-full rounded-xl bg-amber-400 px-4 py-2 text-sm font-extrabold text-stone-950 transition hover:bg-amber-300">
            Add to home screen
        </button>
        <p data-pwa-feedback role="status" aria-live="polite" class="mt-3 text-xs text-stone-600"></p>
    </section>
</div>
