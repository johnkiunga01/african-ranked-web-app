@if(! $ga4['ok'] && $ga4['error'] === 'not_configured')
@include('staff.google._setup', ['service' => 'Google Analytics', 'email' => $googleEmail])
@elseif(! $ga4['ok'])
<section class="staff-panel mt-6" role="alert"><p class="text-xs font-semibold uppercase tracking-widest text-red-700">Google Analytics · Report unavailable</p><p class="mt-2 text-sm text-stone-700">{{ $ga4['error'] }}</p><div class="mt-4">@include('staff.google._refresh')</div></section>
@else
@php($d = $ga4['data'])
<section class="staff-panel mt-6">
<div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-widest text-amber-800">Google Analytics 4 · Live</p><h2 class="mt-2 text-xl font-bold">Audience, last 28 days</h2><p class="mt-1 text-sm text-stone-500">{{ \Illuminate\Support\Carbon::parse($d['period']['startDate'])->format('M j') }} – {{ \Illuminate\Support\Carbon::parse($d['period']['endDate'])->format('M j, Y') }} · Updated {{ \Illuminate\Support\Carbon::parse($d['fetched_at'])->timezone(config('editorial.display_timezone'))->format('M j, H:i T') }}. Only visitors who accepted analytics are counted.</p></div>@include('staff.google._refresh')</div>
<dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
@foreach(['activeUsers' => ['Users', fn ($v) => number_format($v)], 'sessions' => ['Sessions', fn ($v) => number_format($v)], 'screenPageViews' => ['Page views', fn ($v) => number_format($v)], 'engagementRate' => ['Engagement rate', fn ($v) => number_format($v * 100, 1).'%'], 'averageSessionDuration' => ['Avg. session', fn ($v) => gmdate($v >= 3600 ? 'G:i:s' : 'i:s', (int) $v)]] as $key => [$label, $fmt])
<div class="rounded-xl bg-stone-50 p-4"><dt class="text-xs text-stone-500">{{ $label }}</dt><dd class="mt-1 text-2xl font-bold">{{ $fmt($d['totals'][$key]) }}</dd><dd class="mt-1">@include('staff.google._delta', ['current' => $d['totals'][$key], 'previous' => $d['previous'][$key]])</dd></div>
@endforeach
</dl>
@if(count($d['daily']))@include('staff.google._trend', ['rows' => $d['daily'], 'metric' => 'activeUsers', 'dateKey' => 'date', 'label' => 'Users'])@endif
</section>
<div class="mt-6 grid gap-6 lg:grid-cols-2">
<section class="staff-panel lg:col-span-2"><h2 class="text-lg font-bold">Top pages</h2><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[560px] text-left text-sm"><thead class="text-xs text-stone-500"><tr><th class="py-2">Page</th><th class="py-2 text-right">Views</th><th class="py-2 text-right">Users</th><th class="py-2 text-right">Avg. session</th></tr></thead><tbody class="divide-y divide-stone-100">@forelse($d['pages'] as $row)<tr><td class="max-w-md truncate py-2"><a class="text-amber-800 hover:underline" href="{{ url($row['pagePath']) }}" target="_blank" rel="noopener">{{ $row['pagePath'] }}</a></td><td class="py-2 text-right">{{ number_format($row['screenPageViews']) }}</td><td class="py-2 text-right">{{ number_format($row['activeUsers']) }}</td><td class="py-2 text-right">{{ gmdate('i:s', (int) $row['averageSessionDuration']) }}</td></tr>@empty<tr><td colspan="4" class="py-3 text-stone-500">No page views recorded yet.</td></tr>@endforelse</tbody></table></div></section>
@foreach(['channels' => ['How visitors arrive', 'sessionDefaultChannelGroup', 'sessions', 'Sessions'], 'sources' => ['Sources', 'sessionSourceMedium', 'sessions', 'Sessions'], 'countries' => ['Countries', 'country', 'activeUsers', 'Users'], 'devices' => ['Devices', 'deviceCategory', 'activeUsers', 'Users']] as $key => [$title, $dim, $metric, $metricLabel])
<section class="staff-panel"><h2 class="text-lg font-bold">{{ $title }}</h2><table class="mt-4 w-full text-left text-sm"><thead class="text-xs text-stone-500"><tr><th class="py-2"><span class="sr-only">{{ $title }}</span></th><th class="py-2 text-right">{{ $metricLabel }}</th></tr></thead><tbody class="divide-y divide-stone-100">@forelse($d[$key] as $row)<tr><td class="py-2">{{ $row[$dim] ?: '(not set)' }}</td><td class="py-2 text-right">{{ number_format($row[$metric]) }}</td></tr>@empty<tr><td colspan="2" class="py-3 text-stone-500">No data yet.</td></tr>@endforelse</tbody></table></section>
@endforeach
</div>
@endif
