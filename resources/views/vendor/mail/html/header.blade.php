@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('brand-icon.png') }}?v=dotted-20261103" width="38" height="38" alt="" style="display: block; width: 38px; height: 38px; margin: 0 auto 10px; border: 0;">
{{ $slot }}
</a>
</td>
</tr>
