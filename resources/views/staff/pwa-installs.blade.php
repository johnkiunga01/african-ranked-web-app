@extends('staff.layout')
@section('title', 'PWA installations')
@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold">PWA installations</h1>
        <p class="mt-2 text-sm text-stone-600">Anonymous, aggregate-only install signals for the last 30 days, recorded in the African Ranked database.</p>
    </div>
    <a class="staff-secondary" href="/" target="_blank" rel="noopener noreferrer">View website</a>
</div>

<div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['Android installs confirmed', $totals['android_confirmed'], 'Browser appinstalled events'],
        ['iOS first app opens', $totals['ios_first_opens'], 'Estimate: first standalone launch'],
        ['Other first app opens', $totals['other_first_opens'], 'Other platforms, first standalone launch'],
        ['Install guidance opened', $totals['help_opened'], 'Help panels, not installations'],
    ] as [$label, $value, $detail])
        <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-stone-700">{{ $label }}</h2>
            <p class="mt-3 text-3xl font-extrabold tabular-nums text-stone-950">{{ number_format($value) }}</p>
            <p class="mt-2 text-xs text-stone-500">{{ $detail }}</p>
        </section>
    @endforeach
</div>

<div class="mt-6 rounded-2xl border border-stone-200 bg-white p-5">
    <h2 class="text-base font-extrabold">How to interpret these numbers</h2>
    <p class="mt-2 text-sm leading-relaxed text-stone-600">Android installs are reported when a supported browser fires its installation-complete event. Safari on iOS offers no equivalent event: the first launch in standalone mode is a proxy, not a verified install. Never add Android installs and other standalone opens together as a count of unique people. Browser privacy settings, reinstallations and reporting failures may change totals. The system stores only daily counts, platform and event type—no user, device or network identifiers.</p>
    <p class="mt-3 text-xs font-semibold text-stone-600">Android native prompts accepted (not confirmed installed): {{ number_format($totals['prompt_accepted']) }}.</p>
</div>

<div class="mt-6 overflow-hidden rounded-2xl border border-stone-200 bg-white">
    <div class="border-b border-stone-200 px-5 py-4"><h2 class="text-lg font-extrabold">Daily activity</h2></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-600">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Android confirmed</th><th class="px-4 py-3">iOS first opens</th><th class="px-4 py-3">Other first opens</th><th class="px-4 py-3">Install help opened</th></tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse($daily as $day)
                    <tr><td class="px-4 py-3 font-semibold">{{ $day['date'] }}</td><td class="px-4 py-3 tabular-nums">{{ number_format($day['android_confirmed']) }}</td><td class="px-4 py-3 tabular-nums">{{ number_format($day['ios_first_opens']) }}</td><td class="px-4 py-3 tabular-nums">{{ number_format($day['other_first_opens']) }}</td><td class="px-4 py-3 tabular-nums">{{ number_format($day['help_opened']) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">No installation signals have been recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
