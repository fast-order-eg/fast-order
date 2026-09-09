<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $landingPage->title ?? '??? ???' }} | {{ $tenant->name ?? '?????? ??????' }}</title>
    <meta name="description" content="{{ $landingPage->title ?? '???? ???? ????? ????? ??????? ?????' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary: {{ $theme['primary_color'] ?? '#6c63ff' }};
            --secondary: {{ $theme['secondary_color'] ?? '#ff6584' }};
            --accent: #ffb703;
            --dark: #0f172a;
            --light: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --card-bg: rgba(255, 255, 255, 0.9);
            --border-color: rgba(226, 232, 240, 0.8);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-glow: 0 0 25px rgba(108, 99, 255, 0.35);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        /* Focus indicators for accessibility */
        *:focus-visible {
            outline: 3px solid var(--accent, #ffb703) !important;
            outline-offset: 3px !important;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: #0b0f19;
            color: var(--text-main);
            line-height: 1.7;
            overflow-x: hidden;
            background-image: 
                radial-gradient(at 10% 10%, rgba(108, 99, 255, 0.15) 0px, transparent 50%),
                radial-gradient(at 90% 90%, rgba(255, 101, 132, 0.12) 0px, transparent 50%);
            background-attachment: fixed;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* --- Top Urgency Header --- */
        .urgency-bar {
            background: linear-gradient(90deg, #dc2626, #ef4444, #dc2626);
            background-size: 200% 200%;
            animation: gradient-shift 4s ease infinite;
            color: #fff;
            text-align: center;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(220, 38, 38, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            background-color: #4ade80;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px #4ade80;
            animation: pulse-fast 1s infinite;
        }

        @keyframes pulse-fast {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.6; }
        }

        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* --- Navigation Header --- */
        .landing-nav {
            max-width: 1200px;
            margin: 1rem auto;
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .store-logo {
            font-size: 1.4rem;
            font-weight: 900;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .store-logo i {
            color: var(--secondary);
            font-size: 1.6rem;
        }

        .live-viewers {
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* --- Hero Section --- */
        .hero-section {
            max-width: 1200px;
            margin: 2rem auto 4rem;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .hero-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--secondary), #f43f5e);
            color: #fff;
            font-weight: 800;
            font-size: 0.95rem;
            padding: 6px 20px;
            border-radius: 50px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(255, 101, 132, 0.4);
            animation: bounce-slight 2s infinite;
        }

        @keyframes bounce-slight {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 900;
            color: #fff;
            line-height: 1.3;
            margin-bottom: 1.2rem;
            background: linear-gradient(to right, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: #94a3b8;
            max-width: 800px;
            margin: 0 auto 2.5rem;
            font-weight: 400;
        }

        /* --- Buttons --- */
        .btn-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            font-size: 1.3rem;
            font-weight: 800;
            padding: 1rem 3rem;
            border-radius: 50px;
            box-shadow: var(--shadow-glow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-cta::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.5s;
        }

        .btn-cta:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 0 35px rgba(255, 101, 132, 0.6);
        }

        .btn-cta:hover::after {
            left: 100%;
        }

        /* --- Countdown Section --- */
        .countdown-section {
            max-width: 900px;
            margin: -3rem auto 4rem;
            background: rgba(30, 41, 59, 0.95);
            border: 2px solid var(--accent);
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 10;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        .countdown-title {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .countdown-grid {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            direction: ltr;
        }

        .time-box {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            min-width: 90px;
            padding: 1rem 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
        }

        .time-val {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--accent);
            font-family: monospace;
            line-height: 1;
        }

        .time-label {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 6px;
            font-weight: 600;
        }

        /* --- Product Showcase --- */
        .showcase-section {
            max-width: 1200px;
            margin: 0 auto 5rem;
            padding: 0 1.5rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto;
        }

        .product-card {
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 3rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            box-shadow: var(--shadow-lg);
        }

        .product-image-wrap {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .product-img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
        }

        .product-image-wrap:hover .product-img {
            transform: scale(1.05);
        }

        .discount-ribbon {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 30;
            pointer-events: none;
            background: #ef4444;
            color: #fff;
            font-weight: 900;
            font-size: 1rem;
            padding: 8px 18px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.5);
            transition: transform 0.3s ease;
        }

        .product-image-wrap:hover .discount-ribbon {
            transform: scale(1.05);
        }

        .product-details h3 {
            font-size: 2.2rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 1rem;
        }

        .product-desc {
            color: #cbd5e1;
            font-size: 1.1rem;
            margin-bottom: 1.8rem;
        }

        .price-container {
            display: flex;
            align-items: baseline;
            gap: 15px;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.03);
            padding: 1.2rem;
            border-radius: 16px;
            border: 1px dashed rgba(255, 255, 255, 0.15);
        }

        .current-price {
            font-size: 2.5rem;
            font-weight: 900;
            color: #4ade80;
        }

        .old-price {
            font-size: 1.4rem;
            color: #64748b;
            text-decoration: line-through;
            font-weight: 600;
        }

        .save-badge {
            background: rgba(74, 222, 128, 0.15);
            color: #4ade80;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .features-list {
            list-style: none;
            margin-bottom: 2.5rem;
        }

        .features-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #e2e8f0;
            font-size: 1.1rem;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .features-list li i {
            color: #4ade80;
            font-size: 1.2rem;
        }

        /* --- Features Grid --- */
        .features-section {
            max-width: 1200px;
            margin: 0 auto 5rem;
            padding: 0 1.5rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 2.5rem 1.8rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            background: rgba(30, 41, 59, 0.9);
            border-color: var(--primary);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 20px rgba(108, 99, 255, 0.3);
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.8rem;
        }

        .feature-desc {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        /* --- Testimonials --- */
        .testimonials-section {
            max-width: 1200px;
            margin: 0 auto 5rem;
            padding: 0 1.5rem;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }

        .testimonial-card {
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 2rem;
            position: relative;
        }

        .stars {
            color: #fbbf24;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .quote-text {
            color: #e2e8f0;
            font-size: 1.05rem;
            font-style: italic;
            margin-bottom: 1.5rem;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 1.2rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .user-info h4 {
            color: #fff;
            font-weight: 800;
            font-size: 1rem;
        }

        .user-info span {
            color: #4ade80;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* --- CTA Banner --- */
        .cta-banner {
            max-width: 1100px;
            margin: 0 auto 6rem;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(108, 99, 255, 0.5);
        }

        .cta-title {
            font-size: 2.8rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 1rem;
        }

        .cta-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 700px;
            margin: 0 auto 2.5rem;
        }

        .btn-white {
            background: #fff;
            color: var(--dark);
            font-weight: 900;
            font-size: 1.3rem;
            padding: 1.1rem 3.5rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-white:hover {
            transform: scale(1.05);
            background: #f8fafc;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        /* --- Sticky Mobile Action Bar --- */
        .sticky-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(16px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
            box-shadow: 0 -10px 25px rgba(0,0,0,0.5);
        }

        .sticky-price {
            display: flex;
            flex-direction: column;
        }

        .sticky-price span {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .sticky-price strong {
            font-size: 1.4rem;
            color: #4ade80;
            font-weight: 900;
        }

        .sticky-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            font-weight: 800;
            padding: 0.7rem 1.8rem;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* --- Footer --- */
        footer {
            text-align: center;
            padding: 3rem 1rem 6rem;
            color: #64748b;
            font-size: 0.9rem;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.2rem; }
            .product-card { grid-template-columns: 1fr; padding: 1.8rem; gap: 2rem; }
            .countdown-grid { gap: 0.8rem; }
            .time-box { min-width: 70px; padding: 0.8rem 0.4rem; }
            .time-val { font-size: 1.6rem; }
            .cta-title { font-size: 2rem; }
            .btn-cta, .btn-white { width: 100%; text-align: center; font-size: 1.1rem; }
        }
    </style>
  <link rel="stylesheet" href="/shop/themes/starter/style.css?v=1.0.0">
</head>
<body class="theme-starter fast-theme">

    <!-- Urgency Top Bar -->
    <div class="urgency-bar">
        <span class="pulse-dot"></span>
        <span>?? ?????: ????? ???? ????? ?????? ?? ??? ???? ?????? ??????? ????????!</span>
    </div>

    <!-- Nav -->
    <nav class="landing-nav">
        <div class="store-logo">
            <i class="fa-solid fa-store"></i>
            <span>{{ $tenant->name ?? '?????? ??????' }}</span>
        </div>
        <div class="live-viewers">
            <i class="fa-solid fa-eye text-green-400"></i>
            <span><strong id="viewers-count">18</strong> ??????? ??? ????? ????</span>
        </div>
    </nav>

    <!-- Dynamic Sections Rendering -->
    @foreach ($sections as $index => $section)
        @php $type = $section['type'] ?? ''; @endphp

        <!-- 1. Hero Section -->
        @if ($type === 'hero')
            <section class="hero-section {{ !empty($section['bg_image']) ? 'has-bg-image' : '' }}" style="{{ !empty($section['bg_image']) ? 'background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), url('.$section['bg_image'].') center/cover;' : '' }}">
                @if (!empty($section['badge']))
                    <div class="hero-badge">{{ $section['badge'] }}</div>
                @endif
                <h1 class="hero-title">{{ $section['title'] ?? '????? ????? ???????' }}</h1>
                <p class="hero-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                
                <a href="{{ $section['cta_link'] ?? '#product-showcase' }}" class="btn-cta track-conversion" data-slug="{{ $landingPage->slug }}">
                    <span>{{ $section['cta_text'] ?? '???? ???? ????? ?????' }}</span>
                    <i class="fa-solid fa-cart-shopping"></i>
                </a>
            </section>
        @endif

        <!-- 2. Countdown Timer Section -->
        @if ($type === 'countdown')
            <section class="countdown-section">
                <div class="countdown-title">
                    <i class="fa-solid fa-clock text-amber-400"></i>
                    <span>{{ $section['title'] ?? '????? ??? ????? ????:' }}</span>
                    @if (!empty($section['offer_badge']))
                        <span class="save-badge">{{ $section['offer_badge'] }}</span>
                    @endif
                </div>
                <div class="countdown-grid" data-endtime="{{ $section['end_time'] ?? date('Y-m-d H:i:s', strtotime('+24 hours')) }}">
                    <div class="time-box">
                        <span class="time-val" id="days">00</span>
                        <span class="time-label">???</span>
                    </div>
                    <div class="time-box">
                        <span class="time-val" id="hours">00</span>
                        <span class="time-label">????</span>
                    </div>
                    <div class="time-box">
                        <span class="time-val" id="minutes">00</span>
                        <span class="time-label">?????</span>
                    </div>
                    <div class="time-box">
                        <span class="time-val" id="seconds">00</span>
                        <span class="time-label">?????</span>
                    </div>
                </div>
                @if (!empty($section['text']))
                    <p style="color: #94a3b8; margin-top: 1.2rem; font-weight: 600;">{{ $section['text'] }}</p>
                @endif
            </section>
        @endif

        <!-- 3. Product Showcase -->
        @if ($type === 'product_showcase')
            @php
                $pData = $section['product_data'] ?? [];
                $pName = $pData['name'] ?? ($section['product_name'] ?? '???? ????');
                $pDesc = $pData['description'] ?? ($section['subtitle'] ?? '');
                $pPrice = $pData['price'] ?? ($section['custom_price'] ?? 0);
                $pOldPrice = $pData['original_price'] ?? ($section['original_price'] ?? 0);
                $pImg = !empty($pData['image_url']) ? $pData['image_url'] : (!empty($section['product_image']) ? $section['product_image'] : ($section['image'] ?? ''));
                $curr = $section['currency'] ?? '?.?';
                $features = $section['features'] ?? [];
                
                $discountPercent = 0;
                if ($pOldPrice > $pPrice && $pOldPrice > 0) {
                    $discountPercent = round((($pOldPrice - $pPrice) / $pOldPrice) * 100);
                }
            @endphp
            <section class="showcase-section" id="product-showcase">
                <div class="section-header">
                    <h2 class="section-title">{{ $section['title'] ?? '?????? ??????' }}</h2>
                    <p class="section-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                </div>

                <div class="product-card">
                    <div class="product-image-wrap">
                        <img src="{{ $pImg }}" alt="{{ $pName }}" class="product-img">
                        @if ($discountPercent > 0)
                            <div class="discount-ribbon">وفر {{ $discountPercent }}%</div>
                        @endif
                    </div>

                    <div class="product-details">
                        <h3>{{ $pName }}</h3>
                        <p class="product-desc">{{ $pDesc }}</p>

                        <div class="price-container">
                            <span class="current-price">{{ number_format($pPrice) }} {{ $curr }}</span>
                            @if ($pOldPrice > $pPrice)
                                <span class="old-price">{{ number_format($pOldPrice) }} {{ $curr }}</span>
                                <span class="save-badge">???? {{ number_format($pOldPrice - $pPrice) }} {{ $curr }}</span>
                            @endif
                        </div>

                        @if (!empty($features) && is_array($features))
                            <ul class="features-list">
                                @foreach ($features as $feat)
                                    <li>
                                        <i class="fa-solid fa-check-circle"></i>
                                        <span>{{ $feat }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <button onclick="handleOrderClick('{{ $landingPage->slug }}', '{{ $pData['id'] ?? '' }}')" class="btn-cta" style="width: 100%;">
                            <span>{{ $section['buy_button_text'] ?? '???? ???? ????? ?????' }}</span>
                            <i class="fa-solid fa-bag-shopping"></i>
                        </button>
                    </div>
                </div>
            </section>
        @endif

        <!-- 4. Features Grid -->
        @if ($type === 'features')
            <section class="features-section">
                <div class="section-header">
                    <h2 class="section-title">{{ $section['title'] ?? '????? ????????' }}</h2>
                    <p class="section-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                </div>

                <div class="features-grid">
                    @foreach (($section['items'] ?? []) as $item)
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="{{ $item['icon'] ?? 'fa-solid fa-star' }}"></i>
                            </div>
                            <h3 class="feature-title">{{ $item['title'] ?? '' }}</h3>
                            <p class="feature-desc">{{ $item['description'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- 5. Testimonials -->
        @if ($type === 'testimonials')
            <section class="testimonials-section">
                <div class="section-header">
                    <h2 class="section-title">{{ $section['title'] ?? '???? ???????' }}</h2>
                    <p class="section-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                </div>

                <div class="testimonials-grid">
                    @foreach (($section['items'] ?? []) as $testi)
                        <div class="testimonial-card">
                            <div class="stars">
                                @for ($i = 0; $i < ($testi['rating'] ?? 5); $i++)
                                    <i class="fa-solid fa-star"></i>
                                @endfor
                            </div>
                            <p class="quote-text">"{{ $testi['comment'] ?? '' }}"</p>
                            <div class="user-meta">
                                @if (!empty($testi['avatar']))
                                    <img src="{{ $testi['avatar'] }}" alt="{{ $testi['name'] ?? '' }}" class="user-avatar">
                                @else
                                    <div class="user-avatar" style="background: var(--primary); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold;">
                                        {{ mb_substr($testi['name'] ?? '?', 0, 1) }}
                                    </div>
                                @endif
                                <div class="user-info">
                                    <h4>{{ $testi['name'] ?? '???? ????' }}</h4>
                                    <span>
                                        <i class="fa-solid fa-circle-check"></i>
                                        {{ $testi['role'] ?? '????? ????' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- 6. CTA Banner -->
        @if ($type === 'cta')
            <section class="cta-banner">
                <h2 class="cta-title">{{ $section['title'] ?? '?? ??? ???? ??????' }}</h2>
                <p class="cta-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                <button onclick="handleOrderClick('{{ $landingPage->slug }}', '')" class="btn-white">
                    <span>{{ $section['button_text'] ?? '???? ???? ????? ?????' }}</span>
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
            </section>
        @endif

    @endforeach

    <!-- Sticky Bottom Bar -->
    <div class="sticky-bar">
        <div class="sticky-price">
            <span>??? ????? ???????:</span>
            @php
                // ??? ?? ??? ?????? ?? ????? ?????
                $stickyPrice = '???? ???';
                foreach ($sections as $s) {
                    if (($s['type'] ?? '') === 'product_showcase') {
                        $stickyPrice = number_format($s['product_data']['price'] ?? ($s['custom_price'] ?? 0)) . ' ' . ($s['currency'] ?? '?.?');
                        break;
                    }
                }
            @endphp
            <strong>{{ $stickyPrice }}</strong>
        </div>
        <button onclick="handleOrderClick('{{ $landingPage->slug }}', '')" class="sticky-btn">
            <span>???? ????</span>
            <i class="fa-solid fa-cart-arrow-down"></i>
        </button>
    </div>

    <!-- Footer -->
    <footer>
        <p>???? ?????? ?????? &copy; {{ date('Y') }} ?? {{ $tenant->name ?? '?????? ??????' }} | ?? ??????? ?????? Fast Order</p>
    </footer>

    <!-- Scripts -->
    <script>
        // 1. Live Viewers Simulation
        setInterval(() => {
            const el = document.getElementById('viewers-count');
            if (el) {
                let current = parseInt(el.innerText) || 18;
                let diff = Math.floor(Math.random() * 5) - 2; // -2 to +2
                let next = current + diff;
                if (next < 12) next = 12;
                if (next > 35) next = 35;
                el.innerText = next;
            }
        }, 4000);

        // 2. Countdown Timer
        const grid = document.querySelector('.countdown-grid');
        if (grid) {
            let endTimeStr = grid.getAttribute('data-endtime');
            if (endTimeStr) {
                endTimeStr = endTimeStr.replace(' ', 'T');
            }
            let targetDate = new Date(endTimeStr).getTime();
            const nowTime = new Date().getTime();
            
            if (isNaN(targetDate) || targetDate <= nowTime) {
                const storageKey = 'countdown_target_' + window.location.pathname;
                let storedTarget = localStorage.getItem(storageKey);
                if (storedTarget) {
                    targetDate = parseInt(storedTarget);
                    if (targetDate <= nowTime) {
                        targetDate = nowTime + (24 * 60 * 60 * 1000);
                        localStorage.setItem(storageKey, targetDate);
                    }
                } else {
                    targetDate = nowTime + (24 * 60 * 60 * 1000);
                    localStorage.setItem(storageKey, targetDate);
                }
            }

            const updateClock = () => {
                const now = new Date().getTime();
                const distance = targetDate - now;

                if (distance < 0) {
                    document.getElementById('days').innerText = "00";
                    document.getElementById('hours').innerText = "00";
                    document.getElementById('minutes').innerText = "00";
                    document.getElementById('seconds').innerText = "00";
                    return;
                }

                const d = Math.floor(distance / (1000 * 60 * 60 * 24));
                const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('days').innerText = String(d).padStart(2, '0');
                document.getElementById('hours').innerText = String(h).padStart(2, '0');
                document.getElementById('minutes').innerText = String(m).padStart(2, '0');
                document.getElementById('seconds').innerText = String(s).padStart(2, '0');
            };

            updateClock();
            setInterval(updateClock, 1000);
        }

        // 3. Conversion Tracking & Order Navigation
        function handleOrderClick(slug, productId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            // ????? ??? ????? ??????? ?? ???????
            fetch(`/lp/${slug}/convert`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ product_id: productId })
            }).catch(e => console.log('Conversion logged or ignored'));

            // ??????? ?????? ????? ????? ????? ?? ??????
            if (productId && productId !== '') {
                window.location.href = `/shop/checkout.html?product_id=${productId}`;
            } else {
                // ??????? ????? ?????? ?????? ?? ??????? ??????
                const showcase = document.getElementById('product-showcase');
                if (showcase && window.location.hash !== '#product-showcase') {
                    showcase.scrollIntoView({ behavior: 'smooth' });
                } else {
                    window.location.href = `/shop/checkout.html`;
                }
            }
        }

        // ??? ????? ??? CTA ???????
        document.querySelectorAll('.track-conversion').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const slug = this.getAttribute('data-slug');
                if (slug) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    fetch(`/lp/${slug}/convert`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }).catch(err => {});
                }
            });
        });
    </script>
</body>
</html>
