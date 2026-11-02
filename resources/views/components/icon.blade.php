@props(['name' => 'arrow-right'])
<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('Plane')<path d="m22 2-7 20-4-9-9-4Z M22 2 11 13"/>@break
@case('UtensilsCrossed')<path d="m3 2 7 7M3 6l5 5M7 2l5 5c3 3-1 7-4 4L2 5M22 2c-5 0-9 8-6 11l6 9M2 22l8-8M17 9l5-7"/>@break
@case('Music')<path d="M9 18V5l12-2v13M9 9l12-2"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>@break
@case('TrendingUp')<path d="m3 17 6-6 4 4L21 7M15 7h6v6"/>@break
@case('Cpu')<rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/><rect x="10" y="10" width="4" height="4"/>@break
@case('PawPrint')<ellipse cx="12" cy="15" rx="6" ry="5"/><circle cx="4" cy="9" r="2"/><circle cx="9" cy="5" r="2"/><circle cx="15" cy="5" r="2"/><circle cx="20" cy="9" r="2"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
@case('compass')<circle cx="12" cy="12" r="9"/><path d="m16 8-3 5-5 3 3-5Z"/>@break
@case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>@break
@case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
@case('share')<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m9 11 6-4M9 13l6 4"/>@break
@case('chevron-right')<path d="m9 5 7 7-7 7"/>@break
@default<path d="M4 12h16m-6-6 6 6-6 6"/>
@endswitch
</svg>
