@php($change = $previous > 0 ? ($current - $previous) / $previous * 100 : null)
@if($change !== null)
@php($good = $lowerIsBetter ?? false ? $change <= 0 : $change >= 0)
<span class="text-xs font-semibold {{ $good ? 'text-emerald-700' : 'text-red-700' }}">{{ $change >= 0 ? '▲' : '▼' }} {{ number_format(abs($change), 1) }}%</span>
<span class="text-xs text-stone-500">vs previous 28 days</span>
@else
<span class="text-xs text-stone-500">No previous data</span>
@endif
