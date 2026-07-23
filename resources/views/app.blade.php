<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $seo = $page['props']['seo'] ?? [];
            $title = $seo['title'] ?? config('app.name');
            $description = $seo['description'] ?? null;
        @endphp

        <title inertia>{{ $title }}</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicons/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicons/android-chrome-192x192.png') }}">
        <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('favicons/android-chrome-512x512.png') }}">
        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif
        @if (!empty($seo['tags']))
            <meta name="keywords" content="{{ implode(',', $seo['tags']) }}">
        @endif
        @if (!empty($seo['canonical']))
            <link rel="canonical" href="{{ $seo['canonical'] }}">
        @endif

        {{-- OpenGraph / Twitter — read directly here (not injected client-side)
             so non-JS crawlers and social-preview bots see real values. --}}
        <meta property="og:type" content="{{ $seo['ogType'] ?? 'website' }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $title }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        @if (!empty($seo['canonical']))
            <meta property="og:url" content="{{ $seo['canonical'] }}">
        @endif
        @if (!empty($seo['ogImage']))
            <meta property="og:image" content="{{ $seo['ogImage'] }}">
            @if (!empty($seo['ogImageWidth']))
                <meta property="og:image:width" content="{{ $seo['ogImageWidth'] }}">
            @endif
            @if (!empty($seo['ogImageHeight']))
                <meta property="og:image:height" content="{{ $seo['ogImageHeight'] }}">
            @endif
        @endif
        @if (!empty($seo['publishedTime']))
            <meta property="article:published_time" content="{{ $seo['publishedTime'] }}">
        @endif

        <meta name="twitter:card" content="{{ $seo['twitterCard'] ?? 'summary_large_image' }}">
        <meta name="twitter:title" content="{{ $title }}">
        @if ($description)
            <meta name="twitter:description" content="{{ $description }}">
        @endif
        @if (!empty($seo['ogImage']))
            <meta name="twitter:image" content="{{ $seo['ogImage'] }}">
        @endif

        @foreach (($seo['jsonLd'] ?? []) as $jsonLdBlock)
            <script type="application/ld+json">{!! json_encode($jsonLdBlock) !!}</script>
        @endforeach

        {{-- Self-hosted fonts (see resources/css/app.css for @font-face) —
             preloaded explicitly since there's no next/font equivalent here. --}}
        <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/lilita-one-400.woff2') }}" crossorigin>
        <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/comic-neue-400.woff2') }}" crossorigin>

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="bg-cream-100 text-espresso-900 font-sans antialiased">
        <div id="app" data-page="{{ json_encode($page) }}">
            {{-- Non-JS fallback: React replaces this the instant it mounts
                 (client-only createRoot render, not hydration — see app.tsx),
                 so this markup is only ever seen by crawlers/tools that don't
                 execute JS. Keeps the page's core topic (H1 + description)
                 readable without depending on Inertia SSR. --}}
            <noscript>
                <main style="max-width: 42rem; margin: 4rem auto; padding: 0 1.5rem; font-family: sans-serif;">
                    <h1>{{ $title }}</h1>
                    @if ($description)
                        <p>{{ $description }}</p>
                    @endif
                    <p><a href="/">{{ config('company.name') }}</a> — JavaScript diperlukan untuk pengalaman penuh situs ini.</p>
                </main>
            </noscript>
        </div>
    </body>
</html>
