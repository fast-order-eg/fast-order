<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Fast Order') }}</title>

        <!-- Default Open Graph & SEO Fallbacks for Social & Search Crawlers -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="فاست أوردر — Fast Order">
        <meta property="og:title" content="فاست أوردر (Fast Order) | منصة إنشاء وإدارة المتاجر الإلكترونية">
        <meta property="og:description" content="المنصة الأسرع في مصر لمبيعات الدفع عند الاستلام، ربط بيكسل فيسبوك وتيك توك، وعمولة 0%. بديل ايزي اوردر وشوبيفاي.">
        <meta property="og:image" content="{{ asset('images/logo.png') }}">
        <meta property="og:image:secure_url" content="{{ asset('images/logo.png') }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="512">
        <meta property="og:image:height" content="512">
        <meta property="og:image:alt" content="فاست أوردر Fast Order">
        <meta property="og:locale" content="ar_EG">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ asset('images/logo.png') }}">

        <!-- Favicon & Brand Icons -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

        <!-- PWA & Apple Web App Meta -->
        <link rel="manifest" href="/manifest.json">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="FastOrder">
        <meta name="theme-color" content="#4f46e5">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
