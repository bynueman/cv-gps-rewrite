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
        @inertia
    </body>
</html>
