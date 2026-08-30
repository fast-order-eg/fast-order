<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $storeName = \App\Models\Setting::get('store_name') ?: ($tenant->name ?? 'المتجر');
        $storeLogo = \App\Models\Setting::get('logo') ? asset('storage/' . \App\Models\Setting::get('logo')) : ($tenant->logo ? asset('storage/' . $tenant->logo) : asset('images/logo.png'));
    @endphp
    <title>العروض والتخفيضات الحصرية - {{ $storeName }}</title>
    <meta name="description" content="اكتشف أقوى عروض الفلاش سيل والخصومات الموسمية الحصرية من {{ $storeName }}. تسوق الآن بأفضل الأسعار قبل نفاد الكمية!">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="العروض والتخفيضات الحصرية - {{ $storeName }}">
    <meta property="og:description" content="اكتشف أقوى عروض الفلاش سيل والخصومات الموسمية الحصرية من {{ $storeName }}.">
    <meta property="og:image" content="{{ $storeLogo }}">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: {{ $theme['primary_color'] ?? '#6c63ff' }};
            --secondary: {{ $theme['secondary_color'] ?? '#ff6584' }};
            --primary-dark: color-mix(in srgb, var(--primary) 80%, black);
            --primary-light: color-mix(in srgb, var(--primary) 15%, white);
            --secondary-light: color-mix(in srgb, var(--secondary) 15%, white);
            --fire: #ff2a5f;
            --amber: #f59e0b;
            --emerald: #10b981;
            --dark: #0f172a;
            --light-bg: #f8fafc;
            --card-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07);
            --hover-shadow: 0 25px 50px -12px rgba(108, 99, 255, 0.25);
            --radius: 20px;
            --transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Focus indicators for accessibility */
        *:focus-visible {
            outline: 3px solid var(--primary, #6c63ff) !important;
            outline-offset: 3px !important;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--light-bg);
            color: #334155;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ─── Top Navbar ─── */
        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 1rem 0;
            transition: var(--transition);
        }

        .nav-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .logo i {
            color: var(--primary);
            font-size: 1.8rem;
            filter: drop-shadow(0 4px 8px rgba(108, 99, 255, 0.3));
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .btn-shop {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-shop:hover {
            background: #e2e8f0;
            color: var(--dark);
        }

        .btn-cart {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 8px 20px -6px var(--primary);
        }

        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -4px var(--primary);
        }

        /* ─── Hero Banner ─── */
        .hero {
            position: relative;
            background: linear-gradient(135deg, #090d16 0%, #151c33 50%, #1e1136 100%);
            color: white;
            padding: 5rem 1.5rem;
            text-align: center;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 42, 95, 0.2) 0%, transparent 70%);
            animation: pulse-glow 8s infinite alternate;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -50%;
            right: -20%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(108, 99, 255, 0.25) 0%, transparent 70%);
            animation: pulse-glow 10s infinite alternate-reverse;
        }

        @keyframes pulse-glow {
            0% { transform: scale(1) translate(0, 0); opacity: 0.6; }
            100% { transform: scale(1.3) translate(50px, -30px); opacity: 1; }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 42, 95, 0.15);
            border: 1px solid rgba(255, 42, 95, 0.4);
            color: #ff5e84;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            box-shadow: 0 0 20px rgba(255, 42, 95, 0.3);
            animation: bounce-subtle 2s infinite;
        }

        @keyframes bounce-subtle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1.2rem;
            background: linear-gradient(to right, #ffffff, #f1f5f9, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.15rem;
            color: #94a3b8;
            max-width: 650px;
            margin: 0 auto 2.5rem;
        }

        /* ─── Filter Tabs ─── */
        .filter-section {
            max-width: 1300px;
            margin: -2rem auto 3rem;
            padding: 0 1.5rem;
            position: relative;
            z-index: 10;
        }

        .filter-tabs {
            background: white;
            padding: 0.75rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            border: 1px solid #f1f5f9;
        }

        .tab-btn {
            background: transparent;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 16px;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .tab-btn:hover {
            color: var(--dark);
            background: #f8fafc;
        }

        .tab-btn.active {
            background: var(--dark);
            color: white;
            box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.3);
        }

        .tab-badge {
            background: rgba(100, 116, 139, 0.15);
            color: inherit;
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .tab-btn.active .tab-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* ─── Main Container ─── */
        .container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 1.5rem 5rem;
        }

        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-title h2 {
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* ─── Promotion Section Box ─── */
        .promo-block {
            background: white;
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 3.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .promo-block:hover {
            box-shadow: var(--hover-shadow);
            border-color: rgba(108, 99, 255, 0.3);
        }

        .promo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            border-right: 6px solid var(--primary);
        }

        .promo-header.flash {
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
            border-right-color: var(--fire);
        }

        .promo-info h3 {
            font-size: 1.7rem;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .type-tag {
            font-size: 0.85rem;
            padding: 0.3rem 0.85rem;
            border-radius: 50px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .type-flash_sale { background: #ffe4e6; color: var(--fire); border: 1px solid #fda4af; }
        .type-seasonal { background: #dcfce7; color: #059669; border: 1px solid #86efac; }
        .type-clearance { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .type-bundle { background: #e0e7ff; color: #4f46e5; border: 1px solid #c7d2fe; }

        .discount-highlight {
            font-size: 1.4rem;
            font-weight: 900;
            color: var(--primary);
            background: white;
            padding: 0.5rem 1.2rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .promo-header.flash .discount-highlight {
            color: var(--fire);
        }

        /* ─── Countdown Timer (Flash Sale) ─── */
        .countdown-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--dark);
            padding: 0.8rem 1.5rem;
            border-radius: 16px;
            color: white;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
        }

        .countdown-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .countdown-timer {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Outfit', sans-serif;
        }

        .time-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.4rem 0.65rem;
            border-radius: 10px;
            text-align: center;
            min-width: 46px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .time-val {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
            color: #38bdf8;
        }

        .time-label {
            font-size: 0.65rem;
            color: #cbd5e1;
            text-transform: uppercase;
            font-family: 'Cairo', sans-serif;
            margin-top: 0.15rem;
            display: block;
        }

        .time-sep {
            font-size: 1.2rem;
            font-weight: 800;
            color: #64748b;
        }

        /* ─── Products Grid ─── */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 2rem;
        }

        /* ─── Product Card ─── */
        .product-card {
            background: white;
            border-radius: var(--radius);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            position: relative;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
            border-color: rgba(108, 99, 255, 0.4);
        }

        .product-img-wrap {
            position: relative;
            width: 100%;
            padding-top: 85%;
            background: #f8fafc;
            overflow: hidden;
        }

        .product-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .product-card:hover .product-img {
            transform: scale(1.08);
        }

        .placeholder-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            color: #94a3b8;
            font-size: 3.5rem;
        }

        /* ─── Discount Badges ─── */
        .badge-discount {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #ff007f, #ff5e62);
            color: white;
            font-weight: 900;
            font-size: 0.9rem;
            padding: 0.4rem 0.9rem;
            border-radius: 50px;
            box-shadow: 0 6px 15px rgba(255, 0, 127, 0.4);
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .badge-save {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            color: #38bdf8;
            font-weight: 800;
            font-size: 0.8rem;
            padding: 0.3rem 0.75rem;
            border-radius: 50px;
            z-index: 5;
        }

        .product-details {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .product-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 3.2rem;
        }

        .price-container {
            display: flex;
            align-items: baseline;
            gap: 0.75rem;
            margin: 0.75rem 0 1.25rem;
        }

        .new-price {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--fire);
            font-family: 'Outfit', sans-serif;
        }

        .old-price {
            font-size: 1.05rem;
            color: #94a3b8;
            text-decoration: line-through;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
        }

        .btn-add-cart {
            margin-top: auto;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            color: var(--dark);
            padding: 0.75rem 1rem;
            border-radius: 14px;
            font-weight: 800;
            font-family: 'Cairo', sans-serif;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
        }

        .btn-add-cart:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 8px 20px -6px var(--primary);
        }

        /* ─── Empty State ─── */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 28px;
            box-shadow: var(--card-shadow);
            max-width: 600px;
            margin: 2rem auto;
            border: 1px solid #f1f5f9;
        }

        .empty-icon {
            width: 100px;
            height: 100px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: #94a3b8;
            font-size: 3rem;
            animation: float-empty 3s ease-in-out infinite;
        }

        @keyframes float-empty {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-state h3 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 2rem;
        }

        /* ─── Toast Notification ─── */
        #toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--dark);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            z-index: 9999;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        #toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        #toast i {
            color: var(--emerald);
            font-size: 1.3rem;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.2rem; }
            .promo-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .countdown-wrapper { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>

    <!-- ─── Top Navbar ─── -->
    <header class="navbar">
        <div class="nav-container">
            <a href="/shop/index.html" class="logo">
                <i class="fa-solid fa-bolt-lightning"></i>
                <span>{{ $tenant->name ?? 'فاست أوردر' }}</span>
            </a>
            <div class="nav-actions">
                <a href="/shop/index.html" class="btn-nav btn-shop">
                    <i class="fa-solid fa-store"></i>
                    <span>المتجر الرئيسي</span>
                </a>
                <a href="/shop/cart.html" class="btn-nav btn-cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span>السلة</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ─── Hero Banner ─── -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fa-solid fa-fire-flame-curved"></i>
                <span>عروض حصرية لفترة محدودة</span>
            </div>
            <h1>مهرجان العروض والتخفيضات الكبرى</h1>
            <p>اكتشف أقوى عروض الفلاش سيل سريعة الانتهاء، صفقات الموسم الحصرية، وباقات التوفير المميزة قبل نفاد الكمية!</p>
        </div>
    </section>

    <!-- ─── Filter Tabs ─── -->
    <section class="filter-section">
        <div class="filter-tabs" id="promoTabs">
            <button class="tab-btn active" data-target="all">
                <i class="fa-solid fa-sparkles"></i>
                <span>كل العروض</span>
                <span class="tab-badge">{{ $promotions->count() }}</span>
            </button>
            <button class="tab-btn" data-target="flash_sale">
                <i class="fa-solid fa-bolt"></i>
                <span>عروض الفلاش سيل</span>
                <span class="tab-badge">{{ $flashSales->count() }}</span>
            </button>
            <button class="tab-btn" data-target="seasonal">
                <i class="fa-solid fa-leaf"></i>
                <span>عروض موسمية</span>
                <span class="tab-badge">{{ $seasonalPromos->count() }}</span>
            </button>
            <button class="tab-btn" data-target="clearance">
                <i class="fa-solid fa-tag"></i>
                <span>تصفية ومخفّضات</span>
                <span class="tab-badge">{{ $clearancePromos->count() }}</span>
            </button>
            <button class="tab-btn" data-target="bundle">
                <i class="fa-solid fa-box-open"></i>
                <span>عروض الحزم</span>
                <span class="tab-badge">{{ $bundlePromos->count() }}</span>
            </button>
        </div>
    </section>

    <!-- ─── Main Content Container ─── -->
    <main class="container">
        @if($promotions->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa-regular fa-clock"></i>
                </div>
                <h3>لا توجد عروض نشطة في الوقت الحالي</h3>
                <p>نعمل حالياً على تجهيز أقوى العروض والخصومات الحصرية. تابعنا قريباً!</p>
                <a href="/shop/index.html" class="btn-nav btn-cart" style="display: inline-flex;">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>العودة للتسوق</span>
                </a>
            </div>
        @else
            <div id="promotionsContainer">
                @foreach($promotions as $promo)
                    <div class="promo-block" data-type="{{ $promo->type }}">
                        <div class="promo-header {{ $promo->type === 'flash_sale' ? 'flash' : '' }}">
                            <div class="promo-info">
                                <h3>
                                    @if($promo->type === 'flash_sale')
                                        <i class="fa-solid fa-bolt" style="color: var(--fire);"></i>
                                    @elseif($promo->type === 'seasonal')
                                        <i class="fa-solid fa-leaf" style="color: var(--emerald);"></i>
                                    @elseif($promo->type === 'clearance')
                                        <i class="fa-solid fa-tags" style="color: var(--amber);"></i>
                                    @else
                                        <i class="fa-solid fa-gift" style="color: var(--primary);"></i>
                                    @endif
                                    {{ $promo->name }}
                                </h3>
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem;">
                                    <span class="type-tag type-{{ $promo->type }}">{{ $promo->type_name_arabic }}</span>
                                    <span class="discount-highlight">خصم {{ $promo->discount_badge_text }}</span>
                                </div>
                            </div>

                            @if($promo->ends_at)
                                <div class="countdown-wrapper" data-countdown="{{ $promo->ends_at->toIso8601String() }}">
                                    <div class="countdown-title">
                                        <i class="fa-solid fa-stopwatch"></i>
                                        <span>ينتهي في:</span>
                                    </div>
                                    <div class="countdown-timer">
                                        <div class="time-box">
                                            <span class="time-val days">00</span>
                                            <span class="time-label">أيام</span>
                                        </div>
                                        <span class="time-sep">:</span>
                                        <div class="time-box">
                                            <span class="time-val hours">00</span>
                                            <span class="time-label">ساعة</span>
                                        </div>
                                        <span class="time-sep">:</span>
                                        <div class="time-box">
                                            <span class="time-val minutes">00</span>
                                            <span class="time-label">دقيقة</span>
                                        </div>
                                        <span class="time-sep">:</span>
                                        <div class="time-box">
                                            <span class="time-val seconds">00</span>
                                            <span class="time-label">ثانية</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- ─── Products Grid ─── -->
                        @if($promo->products_list && $promo->products_list->count() > 0)
                            <div class="products-grid">
                                @foreach($promo->products_list as $product)
                                    <div class="product-card">
                                        <!-- Badges -->
                                        <div class="badge-discount">
                                            <i class="fa-solid fa-tag"></i>
                                            <span>{{ $product->discount_badge ?? $promo->discount_badge_text }}</span>
                                        </div>
                                        @if($product->saved_percentage && $product->saved_percentage !== '0%')
                                            <div class="badge-save">وفر {{ $product->saved_percentage }}</div>
                                        @endif

                                        <div class="product-img-wrap">
                                            @if($product->main_image_path || $product->image_url)
                                                <img src="{{ $product->main_image_path ?? $product->image_url }}" alt="{{ $product->name }}" class="product-img" loading="lazy">
                                            @else
                                                <div class="placeholder-img">
                                                    <i class="fa-solid fa-image"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="product-details">
                                            <h4 class="product-title">{{ $product->name }}</h4>
                                            
                                            <div class="price-container">
                                                <span class="new-price">{{ number_format($product->discounted_price ?? $product->price, 0) }} ج.م</span>
                                                @if(($product->discounted_price ?? $product->price) < $product->price)
                                                    <span class="old-price">{{ number_format($product->price, 0) }} ج.م</span>
                                                @endif
                                            </div>

                                            <button class="btn-add-cart" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->discounted_price ?? $product->price }})">
                                                <i class="fa-solid fa-cart-plus"></i>
                                                <span>أضف للسلة</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align: center; padding: 2.5rem; color: #64748b; background: #f8fafc; border-radius: 16px;">
                                <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                <p>جميع المنتجات المشمولة في هذا العرض متاحة قريباً</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </main>

    <!-- ─── Toast Notification ─── -->
    <div id="toast">
        <i class="fa-solid fa-circle-check"></i>
        <span id="toastMsg">تمت إضافة المنتج للسلة بنجاح!</span>
    </div>

    <!-- ─── JavaScript for Timers, Tabs, & Cart ─── -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ─── 1. Filter Tabs Logic ───
            const tabButtons = document.querySelectorAll('.tab-btn');
            const promoBlocks = document.querySelectorAll('.promo-block');

            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    tabButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    const target = btn.getAttribute('data-target');
                    promoBlocks.forEach(block => {
                        if (target === 'all' || block.getAttribute('data-type') === target) {
                            block.style.display = 'block';
                            setTimeout(() => block.style.opacity = '1', 10);
                        } else {
                            block.style.opacity = '0';
                            setTimeout(() => block.style.display = 'none', 300);
                        }
                    });
                });
            });

            // ─── 2. Countdown Timers Logic ───
            const countdownElements = document.querySelectorAll('.countdown-wrapper');

            function updateTimers() {
                const now = new Date().getTime();

                countdownElements.forEach(el => {
                    const targetDateStr = el.getAttribute('data-countdown');
                    if (!targetDateStr) return;

                    const targetDate = new Date(targetDateStr).getTime();
                    const diff = targetDate - now;

                    if (diff <= 0) {
                        el.innerHTML = `<span style="color: #f43f5e; font-weight: 800;"><i class="fa-solid fa-clock-rotate-left"></i> انتهى العرض</span>`;
                        return;
                    }

                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                    el.querySelector('.days').textContent = String(days).padStart(2, '0');
                    el.querySelector('.hours').textContent = String(hours).padStart(2, '0');
                    el.querySelector('.minutes').textContent = String(minutes).padStart(2, '0');
                    el.querySelector('.seconds').textContent = String(seconds).padStart(2, '0');
                });
            }

            if (countdownElements.length > 0) {
                updateTimers();
                setInterval(updateTimers, 1000);
            }
        });

        // ─── 3. Add to Cart & Toast Logic ───
        function addToCart(productId, productName, price) {
            try {
                let cart = JSON.parse(localStorage.getItem('fast_order_cart') || '[]');
                const existingIndex = cart.findIndex(item => item.id === productId);

                if (existingIndex > -1) {
                    cart[existingIndex].quantity = (cart[existingIndex].quantity || 1) + 1;
                } else {
                    cart.push({
                        id: productId,
                        name: productName,
                        price: price,
                        quantity: 1
                    });
                }

                localStorage.setItem('fast_order_cart', JSON.stringify(cart));
                showToast(`تمت إضافة "${productName}" للسلة بنجاح!`);
            } catch (e) {
                console.error('Error saving to cart:', e);
                showToast('حدث خطأ أثناء الإضافة للسلة');
            }
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toastMsg');
            toastMsg.textContent = message;
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3500);
        }
    </script>
</body>
</html>
