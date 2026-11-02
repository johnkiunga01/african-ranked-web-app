<!doctype html>
<html lang="{{ $region['locale'] }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ $seo['robots'] }}">
    <meta name="theme-color" content="#15191c">
    @if(!str_starts_with($seo['robots'], 'noindex'))<link rel="canonical" href="{{ $seo['canonical'] }}">@endif
    @foreach($seo['alternates'] as $alternate)
    <link rel="alternate" hreflang="{{ $alternate['locale'] }}" href="{{ $alternate['url'] }}">
    @endforeach
    <meta property="og:site_name" content="{{ $seo['siteName'] ?? 'African Ranked' }}">
    <meta property="og:type" content="{{ $seo['type'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:image" content="{{ $seo['image'] }}">
    <meta property="og:image:alt" content="{{ $article['title'] ?? 'Discover the best of Africa with African Ranked' }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', $region['locale'] === 'en' ? 'en-US' : $region['locale']) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['image'] }}">
    @isset($article)
    <meta property="article:published_time" content="{{ $article['datePublished'] }}">
    <meta property="article:modified_time" content="{{ $article['dateModified'] }}">
    @endisset
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate" type="application/rss+xml" title="African Ranked articles" href="{{ $catalog->url('/feed.xml') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if(!str_starts_with($seo['robots'], 'noindex'))
    <script type="application/ld+json" nonce="{{ request()->attributes->get('csp_nonce') }}">{!! json_encode($seo['jsonLd'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}</script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 font-sans text-stone-900 antialiased">
    <a href="#main-content" class="sr-only z-50 rounded bg-amber-400 p-3 font-semibold text-stone-950 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
    @include('partials.header')
    <main id="main-content" tabindex="-1">@yield('content')</main>
    @include('partials.footer')
</body>
</html>
