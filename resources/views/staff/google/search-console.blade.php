@if(! $gsc['ok'] && $gsc['error'] === 'not_configured')
@include('staff.google._setup', ['service' => 'Search Console', 'email' => $googleEmail])
@elseif(! $gsc['ok'])
<section class="staff-panel mt-6" role="alert"><p class="text-xs font-semibold uppercase tracking-widest text-red-700">Search Console · Report unavailable</p><p class="mt-2 text-sm text-stone-700">{{ $gsc['error'] }}</p><div class="mt-4">@include('staff.google._refresh')</div></section>
@else
@php($d = $gsc['data'])
<section class="staff-panel mt-6">
<div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-widest text-amber-800">Google Search Console · Live</p><h2 class="mt-2 text-xl font-bold">Google Search performance, last 28 days</h2><p class="mt-1 text-sm text-stone-500">{{ \Illuminate\Support\Carbon::parse($d['period']['startDate'])->format('M j') }} – {{ \Illuminate\Support\Carbon::parse($d['period']['endDate'])->format('M j, Y') }} (Search Console data lags about two days) · Updated {{ \Illuminate\Support\Carbon::parse($d['fetched_at'])->timezone(config('editorial.display_timezone'))->format('M j, H:i T') }}</p></div>@include('staff.google._refresh')</div>
<dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
@foreach(['clicks' => ['Clicks', fn ($v) => number_format($v), false], 'impressions' => ['Impressions', fn ($v) => number_format($v), false], 'ctr' => ['Click-through rate', fn ($v) => number_format($v * 100, 2).'%', false], 'position' => ['Average position', fn ($v) => $v ? number_format($v, 1) : '—', true]] as $key => [$label, $fmt, $lower])
<div class="rounded-xl bg-stone-50 p-4"><dt class="text-xs text-stone-500">{{ $label }}</dt><dd class="mt-1 text-2xl font-bold">{{ $fmt($d['totals'][$key]) }}</dd><dd class="mt-1">@include('staff.google._delta', ['current' => $d['totals'][$key], 'previous' => $d['previous'][$key], 'lowerIsBetter' => $lower])</dd></div>
@endforeach
</dl>
@if(count($d['daily']))@include('staff.google._trend', ['rows' => $d['daily'], 'metric' => 'impressions', 'dateKey' => 'key', 'label' => 'Impressions'])@endif
</section>
@foreach(['queries' => ['Top search queries', 'Query'], 'pages' => ['Top pages in Google', 'Page']] as $key => [$title, $col])
<section class="staff-panel mt-6"><h2 class="text-lg font-bold">{{ $title }}</h2><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[600px] text-left text-sm"><thead class="text-xs text-stone-500"><tr><th class="py-2">{{ $col }}</th><th class="py-2 text-right">Clicks</th><th class="py-2 text-right">Impressions</th><th class="py-2 text-right">CTR</th><th class="py-2 text-right">Position</th></tr></thead><tbody class="divide-y divide-stone-100">@forelse($d[$key] as $row)<tr><td class="max-w-md truncate py-2">@if($key === 'pages')<a class="text-amber-800 hover:underline" href="{{ $row['key'] }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::after($row['key'], config('app.url')) ?: '/' }}</a>@else{{ $row['key'] }}@endif</td><td class="py-2 text-right">{{ number_format($row['clicks']) }}</td><td class="py-2 text-right">{{ number_format($row['impressions']) }}</td><td class="py-2 text-right">{{ number_format($row['ctr'] * 100, 1) }}%</td><td class="py-2 text-right {{ $row['position'] > 10 && $row['position'] <= 20 ? 'font-semibold text-amber-800' : '' }}">{{ number_format($row['position'], 1) }}</td></tr>@empty<tr><td colspan="5" class="py-3 text-stone-500">No search data yet. New sites can take a few weeks to collect impressions.</td></tr>@endforelse</tbody></table></div>@if($key === 'queries')<p class="mt-3 text-xs text-stone-500">Positions 11 to 20 are highlighted: these are on page two and usually the quickest to improve.</p>@endif</section>
@endforeach
@endif
