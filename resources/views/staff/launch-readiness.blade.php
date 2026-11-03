@extends('staff.layout')
@section('title', 'Release readiness')
@section('content')
<div class="space-y-6">
    <header class="max-w-4xl">
        <p class="text-xs font-extrabold uppercase tracking-[.17em] text-amber-800">Release assurance</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-stone-950 sm:text-4xl">Production readiness</h1>
        <p class="mt-3 text-sm leading-7 text-stone-600">
            African Ranked uses Railway's managed HTTPS address as its approved current production host.
            A custom domain can be added later. These checks separately assess security, content, operations and permission to enable public indexing.
        </p>
    </header>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="staff-panel">
            <p class="text-xs font-bold text-stone-500">Automated checks passed</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-stone-950">{{ $report['passed'] }} <span class="text-lg font-semibold text-stone-400">/ {{ $report['total'] }}</span></p>
            <p class="mt-2 text-xs leading-relaxed text-stone-500">Configuration and structural checks, not browser acceptance testing.</p>
        </div>
        <div class="staff-panel">
            <p class="text-xs font-bold text-stone-500">Outstanding automated checks</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-stone-950">{{ $report['total'] - $report['passed'] }}</p>
            <p class="mt-2 text-xs leading-relaxed text-stone-500">Each blocked result is explained below.</p>
        </div>
        <div class="staff-panel">
            <p class="text-xs font-bold text-stone-500">Manual release sign-offs</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-stone-950">{{ count($report['manual_gates']) }}</p>
            <p class="mt-2 text-xs leading-relaxed text-stone-500">Require real evidence and owner approval.</p>
        </div>
    </div>

    <section class="staff-panel !p-0" aria-labelledby="auto-check-heading">
        <div class="border-b border-stone-100 px-5 py-5 sm:px-6">
            <h2 id="auto-check-heading" class="text-lg font-extrabold text-stone-950">Automated checks</h2>
            <p class="mt-1 text-sm text-stone-600">Results reflect the current application configuration. They do not change settings or publish content.</p>
        </div>
        <div class="overflow-x-auto" role="region" aria-label="Production prerequisite results" tabindex="0">
            <table class="min-w-[690px] w-full text-left text-sm">
                <caption class="sr-only">Automated production prerequisites and evidence</caption>
                <thead class="bg-stone-50 text-xs font-bold uppercase tracking-wide text-stone-500">
                    <tr><th scope="col" class="px-5 py-3 sm:px-6">Check</th><th scope="col" class="px-3 py-3">Status</th><th scope="col" class="px-5 py-3 sm:px-6">Reason / next step</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach($report['checks'] as $check)
                        <tr>
                            <th scope="row" class="px-5 py-4 align-top font-bold text-stone-900 sm:px-6">{{ $check['name'] }}</th>
                            <td class="px-3 py-4 align-top">
                                <span class="font-bold {{ $check['passed'] ? 'text-green-800' : 'text-amber-900' }}">
                                    {{ $check['passed'] ? 'Pass' : 'Action needed' }}
                                </span>
                            </td>
                            <td class="max-w-xl px-5 py-4 align-top leading-relaxed text-stone-600 sm:px-6">{{ $check['detail'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="staff-panel" aria-labelledby="signoffs-heading">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="signoffs-heading" class="text-lg font-extrabold text-stone-950">Manual sign-offs</h2>
                <p class="mt-2 text-sm leading-relaxed text-stone-600">These nine controls remain unverified until documented tests and review are completed. They are not automatically approved by a healthy Railway deployment.</p>
            </div>
        </div>
        <ol class="mt-5 grid gap-3 md:grid-cols-2">
            @foreach($report['manual_gates'] as $gate)
                <li class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-xs font-extrabold text-stone-700 shadow-sm">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold leading-6 text-stone-900">{{ $gate }}</span>
                        <span class="mt-1 block text-xs text-stone-500">Evidence and sign-off required</span>
                    </span>
                </li>
            @endforeach
        </ol>
        <p class="mt-5 border-t border-stone-100 pt-4 text-xs leading-relaxed text-stone-500">Safety rule: do not disable noindex, start marketing, or enable incomplete email features solely to increase the checklist score. A later custom domain does not require rebuilding the site.</p>
    </section>
</div>
@endsection
