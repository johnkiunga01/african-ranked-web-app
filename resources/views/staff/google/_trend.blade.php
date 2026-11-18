@php($max = max(1, collect($rows)->max($metric)))
<figure class="mt-6">
<figcaption class="text-sm font-semibold">{{ $label }}, daily</figcaption>
<div class="mt-3 flex h-28 items-end gap-[2px]" role="img" aria-label="{{ $label }} per day over the period">
@foreach($rows as $row)
<div class="flex-1 rounded-t bg-amber-500/80" style="height: {{ max(2, round($row[$metric] / $max * 100)) }}%" title="{{ $row[$dateKey] }}: {{ number_format($row[$metric]) }}"></div>
@endforeach
</div>
</figure>
