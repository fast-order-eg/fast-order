<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $pageTitle = $title ?? $attributes->get('title') ?? 'فاست أوردر (Fast Order) — منصة إنشاء المتاجر الإلكترونية في مصر | بديل ايزي اوردر';
        $pageDesc  = $description ?? $attributes->get('description') ?? 'أنشئ متجرك الإلكتروني في دقيقة مع فاست أوردر (Fast Order). أسرع شيك أوت لزيادة مبيعات الدفع عند الاستلام، ربط بيكسل فيسبوك وتيك توك، وعمولة 0%. البديل الأسرع والأوفر لمنصة ايزي اوردر وشوبيفاي في مصر.';
        $pageKeys  = $keywords ?? $attributes->get('keywords') ?? 'فاست أوردر, فاست اوردر, Fast Order, ايزي اوردر, بديل ايزي اوردر, Easy Order, إنشاء متجر إلكتروني, عمل متجر الكتروني في مصر, ستور اونلاين, انشاء ستور, متجر الكتروني, تصميم متجر الكتروني, منصة تجارة الكترونية, شوبيفاي مصر, دفع عند الاستلام, بيكسل فيسبوك, بيكسل تيك توك';
        $pageImg   = $ogImage ?? $attributes->get('og-image') ?? asset('images/logo.png');
        if (!str_starts_with($pageImg, 'http')) {
            $pageImg = url($pageImg);
        }
        $pageUrl   = $canonicalUrl ?? $attributes->get('canonical-url') ?? url()->current();
        $pageRobots = $robots ?? $attributes->get('robots') ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    @endphp

    <!-- Primary SEO Meta Tags -->
    <title>{{ $pageTitle }}</title>
    <meta name="title" content="{{ $pageTitle }}">
    <meta name="description" content="{{ $pageDesc }}">
    <meta name="keywords" content="{{ $pageKeys }}">
    <meta name="author" content="Fast Order">
    <meta name="robots" content="{{ $pageRobots }}">
    <link rel="canonical" href="{{ $pageUrl }}">

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="فاست أوردر — Fast Order">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDesc }}">
    <meta property="og:image" content="{{ $pageImg }}">
    <meta property="og:image:secure_url" content="{{ $pageImg }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:image:alt" content="شعار فاست أوردر Fast Order">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:locale" content="ar_EG">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $pageUrl }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDesc }}">
    <meta name="twitter:image" content="{{ $pageImg }}">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $pageTitle,
        'description' => $pageDesc,
        'url' => $pageUrl,
        'inLanguage' => 'ar',
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Fast Order',
            'alternateName' => ['فاست أوردر', 'فاست اوردر', 'بديل ايزي اوردر'],
            'url' => 'https://fast-order-eg.tech',
            'logo' => asset('images/logo.png'),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            min-height: 100vh;
            min-height: 100dvh;
        }
        body {
            font-family: 'Cairo', sans-serif;
            background: #0f0c29;
            background: linear-gradient(135deg, #0f0c29 0%, #1a1a4e 50%, #24243e 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 12px;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            color: #fff;
        }
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 40%, rgba(99,102,241,0.12) 0%, transparent 50%),
                        radial-gradient(circle at 70% 60%, rgba(139,92,246,0.10) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite alternate;
            pointer-events: none;
        }
        @keyframes pulse {
            0% { transform: scale(1) rotate(0deg); }
            100% { transform: scale(1.05) rotate(3deg); }
        }

        .card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 40px 32px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
            position: relative;
            z-index: 1;
            margin: auto;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px 8px;
            }
            .card {
                padding: 28px 18px;
                border-radius: 20px;
            }
        }

        .logo-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 28px;
            text-align: center;
        }
        .logo-icon {
            width: 110px; height: 110px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
            border-radius: 50%;
            padding: 3px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.25);
            transition: all 0.3s ease;
            flex-shrink: 0;
            text-decoration: none;
        }
        .logo-icon-inner {
            background: #111026;
            border-radius: 50%;
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            padding: 8px;
            overflow: hidden;
        }
        .logo-icon-inner img {
            width: 100%; height: 100%;
            object-fit: contain;
            transform: scale(1.46);
        }
        .logo-text h1 { font-size: 22px; font-weight: 900; color: #fff; line-height: 1.2; }

        label { display: block; font-size: 13px; font-weight: 600; color: #a5b4fc; margin-bottom: 8px; text-align: right; }

        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 14px 16px;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            color: #fff;
            outline: none;
            transition: all 0.2s;
            text-align: right;
        }
        input::placeholder { color: #6b7280; }
        input:focus {
            border-color: #6366f1;
            background: rgba(99,102,241,0.08);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px 20px;
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 8px 24px rgba(99,102,241,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(99,102,241,0.5);
            background: linear-gradient(135deg, #5558e6 0%, #7c4dff 100%);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-wrap">
            <a href="/" class="logo-icon">
                <div class="logo-icon-inner">
                    @php
                        $isTenantDomain = false;
                        if (function_exists('tenant') && tenant()) {
                            $isTenantDomain = true;
                        }
                        $logoPath = $isTenantDomain ? \App\Models\Setting::get('logo') : null;
                        $logoUrl  = $logoPath ? asset('storage/' . $logoPath) : asset('images/logo.png?v=202604031');
                    @endphp
                    <img src="{{ $logoUrl }}" alt="Fast Order" />
                </div>
            </a>
            <div class="logo-text">
                <h1>Fast Order</h1>
            </div>
        </div>

        {{ $slot }}
    </div>
</body>
</html>
