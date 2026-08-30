<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $storeName = \App\Models\Setting::get('store_name') ?: ($tenant->name ?? 'المتجر');
        $storeLogo = \App\Models\Setting::get('logo') ? asset('storage/' . \App\Models\Setting::get('logo')) : ($tenant->logo ? asset('storage/' . $tenant->logo) : asset('images/logo.png'));
    @endphp
    <title>تتبع طلبك - {{ $storeName }}</title>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="تتبع طلبك - {{ $storeName }}">
    <meta property="og:image" content="{{ $storeLogo }}">
    <link rel="stylesheet" href="/shop/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: {{ $theme['primary_color'] ?? '#6c63ff' }};
            --secondary: {{ $theme['secondary_color'] ?? '#ff6584' }};
            --primary-light: color-mix(in srgb, var(--primary) 12%, #ffffff);
            --primary-dark: color-mix(in srgb, var(--primary) 80%, #000000);
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-page: #f8fafc;
            --card-bg: #ffffff;
        }

        /* Focus indicators for accessibility */
        *:focus-visible {
            outline: 3px solid var(--primary, #6c63ff) !important;
            outline-offset: 3px !important;
        }
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        /* ─── Top Navbar ─── */
        .navbar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .navbar-brand i { color: var(--secondary); }
        .back-to-shop {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.5rem 1.25rem;
            background: #f1f5f9;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .back-to-shop:hover {
            background: var(--primary);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ─── Main Container ─── */
        .main-container {
            max-width: 1100px;
            width: 100%;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        /* ─── Search Hero Section ─── */
        .search-hero {
            background: var(--card-bg);
            border-radius: 28px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.06);
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        .search-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        .hero-title {
            font-size: 1.85rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        .hero-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        .search-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .input-group {
            position: relative;
            text-align: right;
        }
        .input-group i {
            position: absolute;
            top: 50%;
            right: 1.25rem;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: color 0.3s;
        }
        .form-control {
            width: 100%;
            padding: 0.9rem 1.25rem 0.9rem 3rem;
            padding-right: 3.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            background: #f8fafc;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px var(--primary-light);
        }
        .form-control:focus + i {
            color: var(--primary);
        }
        .btn-search {
            padding: 0.9rem 2.25rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            border: none;
            border-radius: 16px;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            box-shadow: 0 10px 25px color-mix(in srgb, var(--primary) 30%, transparent);
            transition: all 0.3s ease;
        }
        .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px color-mix(in srgb, var(--primary) 45%, transparent);
        }

        /* ─── Alerts ─── */
        .alert-error {
            background: #fef2f2;
            border: 2px solid #fecaca;
            color: #b91c1c;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            animation: fadeIn 0.4s ease;
        }
        .alert-error i { font-size: 1.4rem; color: #ef4444; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ─── Tracking Results Card ─── */
        .tracking-card {
            background: var(--card-bg);
            border-radius: 28px;
            padding: 2.5rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.06);
            margin-bottom: 2.5rem;
            animation: fadeIn 0.5s ease;
        }

        /* Order Header Bar */
        .order-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px dashed #e2e8f0;
            margin-bottom: 2rem;
        }
        .order-id-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .order-id-label {
            font-size: 1.35rem;
            font-weight: 900;
            color: var(--text-dark);
        }
        .order-badge {
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.35rem 1rem;
            border-radius: 30px;
            font-weight: 800;
            font-size: 1rem;
            border: 1px solid var(--primary);
        }
        .status-badge {
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .order-dates {
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            width: 100%;
            margin-top: 0.5rem;
        }
        .order-dates span i { color: var(--primary); margin-left: 0.3rem; }

        /* ─── Progress Timeline (WOW UI) ─── */
        .timeline-section {
            margin: 2.5rem 0;
            padding: 2rem 1.5rem;
            background: #f8fafc;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
        }
        .timeline-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 2rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .timeline-title i { color: var(--primary); }

        /* Cancelled Banner */
        .cancelled-banner {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
            font-weight: 800;
            font-size: 1.1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .cancelled-banner i { font-size: 2.5rem; color: #ef4444; }

        /* Desktop & Tablet Timeline Track */
        .timeline-track-container {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
        }
        .timeline-bar-bg {
            position: absolute;
            top: 35px;
            right: 5%;
            left: 5%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            z-index: 1;
        }
        .timeline-bar-fill {
            position: absolute;
            top: 35px;
            right: 5%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), #10b981);
            border-radius: 10px;
            z-index: 2;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .timeline-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 3;
        }
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
            position: relative;
        }
        .step-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #fff;
            border: 4px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #94a3b8;
            margin-bottom: 0.75rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 10px rgba(0,0,0,0.04);
        }
        .timeline-step.completed .step-icon {
            background: #10b981;
            border-color: #a7f3d0;
            color: #fff;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        .timeline-step.current .step-icon {
            background: var(--primary);
            border-color: var(--primary-light);
            color: #fff;
            box-shadow: 0 0 0 8px var(--primary-light), 0 10px 25px color-mix(in srgb, var(--primary) 40%, transparent);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }
        .step-title {
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--text-dark);
            margin-bottom: 0.2rem;
        }
        .timeline-step.completed .step-title { color: #065f46; }
        .timeline-step.current .step-title { color: var(--primary); font-size: 1.05rem; }
        
        .step-subtitle {
            font-size: 0.78rem;
            color: var(--text-muted);
            max-width: 140px;
            line-height: 1.4;
            margin-bottom: 0.3rem;
        }
        .step-time {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            background: #f1f5f9;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            display: inline-block;
        }
        .timeline-step.current .step-time {
            background: var(--primary-light);
            color: var(--primary);
        }
        .timeline-step.completed .step-time {
            background: #d1fae5;
            color: #065f46;
        }

        /* ─── Details Grid (Shipping & Financials) ─── */
        .details-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 1.75rem;
            margin-top: 2.5rem;
        }
        .info-card {
            background: #fff;
            border: 2px solid #f1f5f9;
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .info-card-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-card-title i { color: var(--primary); }
        
        .info-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 0.92rem;
        }
        .info-label {
            color: var(--text-muted);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-label i { color: #94a3b8; width: 18px; text-align: center; }
        .info-val {
            font-weight: 700;
            color: var(--text-dark);
            text-align: left;
            max-width: 60%;
        }

        /* Financial summary highlights */
        .summary-total {
            margin-top: 0.5rem;
            padding-top: 1rem;
            border-top: 2px dashed #cbd5e1;
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--primary);
        }

        /* ─── Products Table Section ─── */
        .products-section {
            margin-top: 2.5rem;
        }
        .products-card {
            background: #fff;
            border: 2px solid #f1f5f9;
            border-radius: 20px;
            padding: 1.75rem;
            overflow: hidden;
        }
        .products-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .product-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }
        .product-item:hover {
            transform: translateX(-4px);
            border-color: var(--primary-light);
        }
        .product-info-wrap {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .product-thumb {
            width: 65px;
            height: 65px;
            border-radius: 12px;
            object-fit: cover;
            background: #fff;
            border: 1px solid #cbd5e1;
            padding: 2px;
        }
        .product-name {
            font-weight: 800;
            font-size: 1rem;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.8rem;
        }
        .meta-pill {
            background: #e2e8f0;
            color: #475569;
            padding: 0.15rem 0.6rem;
            border-radius: 8px;
            font-weight: 700;
        }
        .product-price-wrap {
            text-align: left;
        }
        .product-qty {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 700;
        }
        .product-total {
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--text-dark);
        }

        /* ─── Responsive Queries ─── */
        @media (max-width: 850px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
            .timeline-track-container {
                padding-right: 2rem;
            }
            .timeline-bar-bg, .timeline-bar-fill {
                top: 0;
                bottom: 0;
                right: 35px;
                left: auto;
                width: 6px;
                height: auto;
            }
            .timeline-bar-fill {
                transition: height 1s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .timeline-steps {
                flex-direction: column;
                gap: 2rem;
                align-items: flex-start;
            }
            .timeline-step {
                flex-direction: row;
                text-align: right;
                gap: 1.25rem;
                width: 100%;
            }
            .step-icon {
                margin-bottom: 0;
                flex-shrink: 0;
            }
            .step-subtitle {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    {{-- Top Navbar --}}
    <nav class="navbar">
        <a href="/shop/" class="navbar-brand">
            <i class="fas fa-shopping-bag"></i>
            <span>{{ $tenant->name ?? 'المتجر' }}</span>
        </a>
        <a href="/shop/" class="back-to-shop">
            <i class="fas fa-arrow-right"></i> العودة للمتجر
        </a>
    </nav>

    {{-- Main Content --}}
    <main class="main-container">

        {{-- Search Hero Section --}}
        <section class="search-hero">
            <h1 class="hero-title">تتبع حالة طلبك بسهولة 📦</h1>
            <p class="hero-subtitle">أدخل رقم الطلب ورقم الهاتف أو البريد الإلكتروني المسجل أثناء الشراء لمعرفة المرحلة الحالية لطلبك وموعد التوصيل.</p>

            <form action="{{ url('/tracking') }}" method="GET" class="search-form">
                <div class="input-group">
                    <input type="text" name="order_number" class="form-control" placeholder="رقم الطلب (مثال: 12345)" value="{{ $orderRef ?? '' }}" required>
                    <i class="fas fa-hashtag"></i>
                </div>
                <div class="input-group">
                    <input type="text" name="phone" class="form-control" placeholder="رقم الهاتف أو البريد الإلكتروني" value="{{ $contact ?? '' }}" required>
                    <i class="fas fa-user-check"></i>
                </div>
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i> تتبع الآن
                </button>
            </form>
        </section>

        {{-- Error Alert --}}
        @if(!empty($error))
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $error }}</span>
            </div>
        @endif

        {{-- Tracking Results Section --}}
        @if(!empty($trackingResult))
            <section class="tracking-card">

                {{-- Order Header --}}
                <div class="order-header">
                    <div class="order-id-wrap">
                        <span class="order-id-label">الطلب</span>
                        <span class="order-badge">#{{ $trackingResult['reference_number'] }}</span>
                    </div>

                    <div class="status-badge" style="background-color: {{ $trackingResult['status_bg'] }}; color: {{ $trackingResult['status_color'] }};">
                        <i class="fas fa-circle" style="font-size: 0.6rem;"></i>
                        <span>{{ $trackingResult['status_label'] }}</span>
                    </div>

                    <div class="order-dates">
                        @if(!empty($trackingResult['created_at']))
                            <span><i class="fas fa-calendar-alt"></i> تاريخ الطلب: <strong>{{ $trackingResult['created_at'] }}</strong></span>
                        @endif
                        @if(!empty($trackingResult['updated_at']))
                            <span><i class="fas fa-clock"></i> آخر تحديث: <strong>{{ $trackingResult['updated_at'] }}</strong></span>
                        @endif
                    </div>
                </div>

                {{-- Progress Timeline Section --}}
                <div class="timeline-section">
                    <h2 class="timeline-title">
                        <i class="fas fa-route"></i> مسار الطلب والجدول الزمني
                    </h2>

                    @if($trackingResult['is_cancelled'])
                        <div class="cancelled-banner">
                            <i class="fas fa-times-circle"></i>
                            <span>{{ $trackingResult['status_description'] }}</span>
                        </div>
                    @else
                        <div class="timeline-track-container" id="timelineTrack">
                            {{-- Progress Bars --}}
                            <div class="timeline-bar-bg"></div>
                            <div class="timeline-bar-fill" id="timelineBarFill" style="width: {{ $trackingResult['progress_percentage'] }}%;"></div>

                            {{-- Steps --}}
                            <div class="timeline-steps">
                                @foreach($trackingResult['timeline'] as $step)
                                    @php
                                        $stepClass = '';
                                        if ($step['is_current']) {
                                            $stepClass = 'current';
                                        } elseif ($step['is_completed']) {
                                            $stepClass = 'completed';
                                        }
                                    @endphp
                                    <div class="timeline-step {{ $stepClass }}">
                                        <div class="step-icon">
                                            <i class="{{ $step['icon'] }}"></i>
                                        </div>
                                        <div class="step-content">
                                            <div class="step-title">{{ $step['title'] }}</div>
                                            <div class="step-subtitle">{{ $step['subtitle'] }}</div>
                                            <div class="step-time">{{ $step['timestamp'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Details Grid (Shipping & Financials) --}}
                <div class="details-grid">
                    
                    {{-- Column 1: Shipping Info --}}
                    <div class="info-card">
                        <h3 class="info-card-title">
                            <i class="fas fa-map-marked-alt"></i> معلومات الشحن والتوصيل
                        </h3>
                        <ul class="info-list">
                            <li class="info-item">
                                <span class="info-label"><i class="fas fa-user"></i> اسم المستلم:</span>
                                <span class="info-val">{{ $trackingResult['shipping_info']['customer_name'] }}</span>
                            </li>
                            <li class="info-item">
                                <span class="info-label"><i class="fas fa-phone"></i> رقم الهاتف:</span>
                                <span class="info-val" dir="ltr">{{ $trackingResult['shipping_info']['customer_phone'] }}</span>
                            </li>
                            @if(!empty($trackingResult['shipping_info']['customer_email']))
                            <li class="info-item">
                                <span class="info-label"><i class="fas fa-envelope"></i> البريد الإلكتروني:</span>
                                <span class="info-val">{{ $trackingResult['shipping_info']['customer_email'] }}</span>
                            </li>
                            @endif
                            <li class="info-item">
                                <span class="info-label"><i class="fas fa-map-marker-alt"></i> المحافظة:</span>
                                <span class="info-val">{{ $trackingResult['shipping_info']['governorate'] }}</span>
                            </li>
                            <li class="info-item">
                                <span class="info-label"><i class="fas fa-home"></i> العنوان بالتفصيل:</span>
                                <span class="info-val">{{ $trackingResult['shipping_info']['customer_address'] }}</span>
                            </li>
                            <li class="info-item">
                                <span class="info-label"><i class="fas fa-money-bill-wave"></i> طريقة الدفع:</span>
                                <span class="info-val" style="color: var(--primary);">{{ $trackingResult['shipping_info']['payment_method'] }}</span>
                            </li>
                            @if(!empty($trackingResult['shipping_info']['notes']))
                            <li class="info-item">
                                <span class="info-label"><i class="fas fa-comment-dots"></i> ملاحظات الطلب:</span>
                                <span class="info-val">{{ $trackingResult['shipping_info']['notes'] }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>

                    {{-- Column 2: Financial Summary --}}
                    <div class="info-card">
                        <h3 class="info-card-title">
                            <i class="fas fa-file-invoice-dollar"></i> ملخص الحساب
                        </h3>
                        <ul class="info-list">
                            <li class="info-item">
                                <span class="info-label"><i class="fas fa-shopping-basket"></i> إجمالي المنتجات:</span>
                                <span class="info-val">{{ number_format($trackingResult['subtotal'], 0) }} ج.م</span>
                            </li>
                            <li class="info-item">
                                <span class="info-label"><i class="fas fa-shipping-fast"></i> تكلفة الشحن:</span>
                                <span class="info-val">{{ $trackingResult['shipping_cost'] > 0 ? number_format($trackingResult['shipping_cost'], 0) . ' ج.م' : 'مجاني' }}</span>
                            </li>
                            <li class="info-item summary-total">
                                <span class="info-label" style="color: var(--primary);"><i class="fas fa-calculator"></i> الإجمالي الكلي:</span>
                                <span class="info-val">{{ number_format($trackingResult['total'], 0) }} ج.م</span>
                            </li>
                        </ul>
                    </div>

                </div>

                {{-- Products Section --}}
                @if(!empty($trackingResult['items']) && count($trackingResult['items']) > 0)
                <div class="products-section">
                    <div class="products-card">
                        <h3 class="info-card-title">
                            <i class="fas fa-boxes"></i> المنتجات المطلوبة ({{ count($trackingResult['items']) }})
                        </h3>
                        <div class="products-list">
                            @foreach($trackingResult['items'] as $item)
                            <div class="product-item">
                                <div class="product-info-wrap">
                                    @if(!empty($item['image']))
                                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="product-thumb" onerror="this.src='/images/logo.png'">
                                    @else
                                        <div class="product-thumb" style="display:flex; align-items:center; justify-content:center; background:#eee; color:#aaa;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="product-name">{{ $item['name'] }}</div>
                                        <div class="product-meta">
                                            @if(!empty($item['selectedSize']))
                                                <span class="meta-pill">المقاس: {{ $item['selectedSize'] }}</span>
                                            @endif
                                            @if(!empty($item['selectedColor']))
                                                <span class="meta-pill">اللون: {{ $item['selectedColor'] }}</span>
                                            @endif
                                            <span class="meta-pill">الكمية: {{ $item['quantity'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-price-wrap">
                                    <div class="product-qty">{{ number_format($item['price'], 0) }} × {{ $item['quantity'] }}</div>
                                    <div class="product-total">{{ number_format($item['total'], 0) }} ج.م</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

            </section>
        @endif

    </main>

    <script>
        // Adjust responsive vertical timeline progress bar on window resize or load
        function adjustTimelineOrientation() {
            const track = document.getElementById('timelineTrack');
            if (!track) return;
            const fill = document.getElementById('timelineBarFill');
            const bg = track.querySelector('.timeline-bar-bg');
            if (!fill || !bg) return;

            if (window.innerWidth <= 850) {
                // Vertical orientation
                fill.style.width = '6px';
                fill.style.height = '{{ $trackingResult["progress_percentage"] ?? 0 }}%';
            } else {
                // Horizontal orientation
                fill.style.height = '6px';
                fill.style.width = '{{ $trackingResult["progress_percentage"] ?? 0 }}%';
            }
        }

        window.addEventListener('resize', adjustTimelineOrientation);
        document.addEventListener('DOMContentLoaded', adjustTimelineOrientation);
    </script>
</body>
</html>
