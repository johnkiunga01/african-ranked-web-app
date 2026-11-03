@props(['name' => 'arrow-right'])
<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('Plane')<path d="m22 2-7 20-4-9-9-4Z M22 2 11 13"/>@break
@case('UtensilsCrossed')<path d="m3 2 7 7M3 6l5 5M7 2l5 5c3 3-1 7-4 4L2 5M22 2c-5 0-9 8-6 11l6 9M2 22l8-8M17 9l5-7"/>@break
@case('Music')<path d="M9 18V5l12-2v13M9 9l12-2"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>@break
@case('TrendingUp')<path d="m3 17 6-6 4 4L21 7M15 7h6v6"/>@break
@case('Cpu')<rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/><rect x="10" y="10" width="4" height="4"/>@break
@case('PawPrint')<ellipse cx="12" cy="15" rx="6" ry="5"/><circle cx="4" cy="9" r="2"/><circle cx="9" cy="5" r="2"/><circle cx="15" cy="5" r="2"/><circle cx="20" cy="9" r="2"/>@break
@case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-4.4-4.4"/>@break
@case('chevron-down')<path d="m6 9 6 6 6-6"/>@break
@case('arrow-up-right')<path d="M7 17 17 7M8 7h9v9"/>@break
@case('layout-grid')<rect x="3" y="3" width="7" height="7" rx="1.3"/><rect x="14" y="3" width="7" height="7" rx="1.3"/><rect x="3" y="14" width="7" height="7" rx="1.3"/><rect x="14" y="14" width="7" height="7" rx="1.3"/>@break
@case('chart-no-axes-column-increasing')<path d="M4 20V13h3v7M10.5 20V9h3v11M17 20V4h3v16"/>@break
@case('image')<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('book-open')<path d="M12 7c-2-2-5-3-9-3v15c4 0 7 1 9 3 2-2 5-3 9-3V4c-4 0-7 1-9 3Zm0 0v15"/>@break
@case('check-circle')<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>@break
@case('calendar-days')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18M8 15h2M14 15h2"/>@break
@case('monitor')<rect x="2" y="3" width="20" height="15" rx="2"/><path d="M8 22h8M12 18v4"/>@break
@case('files')<path d="M6 4H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2M8 2h9l5 5v8a2 2 0 0 1-2 2H10a2 2 0 0 1-2-2Z"/><path d="M17 2v5h5"/>@break
@case('settings')<circle cx="12" cy="12" r="3"/><path d="M10 2h4l1 2.5 2 1 2.5-.8 2 3.5-2 2v2l2 2-2 3.5-2.5-.8-2 1L14 22h-4l-1-2.5-2-1-2.5.8-2-3.5 2-2v-2l-2-2 2-3.5 2.5.8 2-1Z"/>@break
@case('shield-check')<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
@case('compass')<circle cx="12" cy="12" r="9"/><path d="m16 8-3 5-5 3 3-5Z"/>@break
@case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>@break
@case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
@case('share')<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m9 11 6-4M9 13l6 4"/>@break
@case('chevron-right')<path d="m9 5 7 7-7 7"/>@break
@default<path d="M4 12h16m-6-6 6 6-6 6"/>
@endswitch
</svg>
