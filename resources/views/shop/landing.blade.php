<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $storeName = \App\Models\Setting::get('store_name') ?: ($tenant->name ?? 'المتجر الرسمي');
        $storeLogo = \App\Models\Setting::get('logo') ? asset('storage/' . \App\Models\Setting::get('logo')) : ($tenant->logo ? asset('storage/' . $tenant->logo) : asset('images/logo.png'));
        $fbPixelId = $landingPage->facebook_pixel_id ?: \App\Models\Setting::get('facebook_pixel_id');
        $ttPixelId = $landingPage->tiktok_pixel_id ?: \App\Models\Setting::get('tiktok_pixel_id');
    @endphp
    <title>{{ $landingPage->seo_title ?? ($landingPage->title ?? 'عرض خاص') }} | {{ $storeName }}</title>
    <meta name="description" content="{{ $landingPage->seo_description ?? ($landingPage->title ?? 'صفحة هبوط حصرية بعروض وخصومات مميزة') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Open Graph / WhatsApp / Facebook Preview -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $landingPage->seo_title ?? ($landingPage->title ?? 'عرض خاص') }} | {{ $storeName }}">
    <meta property="og:description" content="{{ $landingPage->seo_description ?? ($landingPage->title ?? 'صفحة هبوط حصرية بعروض وخصومات مميزة') }}">
    <meta property="og:image" content="{{ $landingPage->featured_image ?? $storeLogo }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $landingPage->seo_title ?? ($landingPage->title ?? 'عرض خاص') }} | {{ $storeName }}">
    <meta name="twitter:description" content="{{ $landingPage->seo_description ?? ($landingPage->title ?? 'صفحة هبوط حصرية بعروض وخصومات مميزة') }}">
    <meta name="twitter:image" content="{{ $landingPage->featured_image ?? $storeLogo }}">

    @if ($fbPixelId)
        <!-- Facebook Pixel Code -->
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $fbPixelId }}');
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1" />
        </noscript>
        <!-- End Facebook Pixel Code -->
    @endif

    @if ($ttPixelId)
        <!-- TikTok Pixel Code -->
        <script>
            !function (w, d, t) {
                w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
                ttq.load('{{ $ttPixelId }}');
                ttq.page();
            }(window, document, 'ttq');
        </script>
        <!-- End TikTok Pixel Code -->
    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    @php
        $isDarkTheme = ($landingPage->color_theme ?? 'light') === 'dark';
    @endphp
    <style>
        :root {
            --primary: {{ $theme['primary_color'] ?? '#6c63ff' }};
            --secondary: {{ $theme['secondary_color'] ?? '#ff6584' }};
            --accent: #ffb703;
            --dark: #0f172a;
            --light: #f8fafc;
            
            @if ($isDarkTheme)
                --bg-color: #0b0f19;
                --text-main: #f8fafc;
                --text-muted: #cbd5e1;
                --card-bg: #1e293b;
                --card-bg-hover: #1e293b;
                --card-gradient: linear-gradient(145deg, #1e293b, #0f172a);
                --border-color: rgba(255, 255, 255, 0.08);
                --input-bg: rgba(15, 23, 42, 0.6);
                --input-border: rgba(255, 255, 255, 0.1);
                --input-text: #fff;
                --time-box-bg: rgba(15, 23, 42, 0.8);
                --price-container-bg: rgba(255, 255, 255, 0.03);
                --price-container-border: rgba(255, 255, 255, 0.15);
                --variant-bg: rgba(255, 255, 255, 0.05);
                --variant-border: rgba(255, 255, 255, 0.1);
                --variant-text: #cbd5e1;
                --tier-bg: rgba(255, 255, 255, 0.03);
                --tier-border: rgba(255, 255, 255, 0.08);
                --invoice-bg: rgba(255, 255, 255, 0.03);
                --variant-item-wrap-bg: rgba(255,255,255,0.02);
                --sticky-bg: rgba(15, 23, 42, 0.95);
                --nav-bg: rgba(255, 255, 255, 0.05);
                --viewers-bg: rgba(255, 255, 255, 0.1);
                --shadow-overlay: rgba(0, 0, 0, 0.6);
                --hero-gradient: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
                --hero-title-gradient: linear-gradient(to right, #fff, #cbd5e1);
                --slider-btn-bg: rgba(15, 23, 42, 0.7);
                --slider-btn-border: rgba(255, 255, 255, 0.15);
                --slider-btn-color: #ffffff;
                --slider-dot-bg: rgba(255, 255, 255, 0.4);
            @else
                --bg-color: #f8fafc;
                --text-main: #0f172a;
                --text-muted: #475569;
                --card-bg: #ffffff;
                --card-bg-hover: #f1f5f9;
                --card-gradient: linear-gradient(145deg, #ffffff, #f8fafc);
                --border-color: rgba(15, 23, 42, 0.08);
                --input-bg: #ffffff;
                --input-border: rgba(15, 23, 42, 0.12);
                --input-text: #0f172a;
                --time-box-bg: #f1f5f9;
                --price-container-bg: rgba(108, 99, 255, 0.04);
                --price-container-border: rgba(108, 99, 255, 0.1);
                --variant-bg: #f1f5f9;
                --variant-border: rgba(15, 23, 42, 0.08);
                --variant-text: #475569;
                --tier-bg: #ffffff;
                --tier-border: rgba(15, 23, 42, 0.08);
                --invoice-bg: #f8fafc;
                --variant-item-wrap-bg: #f8fafc;
                --sticky-bg: rgba(255, 255, 255, 0.98);
                --nav-bg: #ffffff;
                --viewers-bg: #f1f5f9;
                --shadow-overlay: rgba(0, 0, 0, 0.04);
                --hero-gradient: linear-gradient(135deg, #ffffff, #f1f5f9);
                --hero-title-gradient: linear-gradient(to right, #0f172a, #334155);
                --slider-btn-bg: rgba(255, 255, 255, 0.9);
                --slider-btn-border: rgba(15, 23, 42, 0.15);
                --slider-btn-color: #0f172a;
                --slider-dot-bg: rgba(15, 23, 42, 0.25);
            @endif

            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.03);
            --shadow-glow: 0 0 20px rgba(108, 99, 255, 0.15);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        *:focus-visible {
            outline: 3px solid var(--accent, #ffb703) !important;
            outline-offset: 3px !important;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            line-height: 1.7;
            overflow-x: hidden;
            @if ($isDarkTheme)
                background-image: 
                    radial-gradient(at 10% 10%, rgba(108, 99, 255, 0.08) 0px, transparent 50%),
                    radial-gradient(at 90% 90%, rgba(255, 101, 132, 0.06) 0px, transparent 50%);
            @else
                background-image: 
                    radial-gradient(at 10% 10%, rgba(108, 99, 255, 0.03) 0px, transparent 50%),
                    radial-gradient(at 90% 90%, rgba(255, 101, 132, 0.02) 0px, transparent 50%);
            @endif
            background-attachment: fixed;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ─── Top Urgency Header ─── */
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

        /* ─── Navigation Header ─── */
        .landing-nav {
            max-width: 1200px;
            margin: 1rem auto;
            padding: 0.8rem 1.5rem;
            background: var(--nav-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .store-logo {
            font-size: 1.4rem;
            font-weight: 900;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .store-logo i {
            color: var(--secondary);
            font-size: 1.6rem;
        }

        .live-viewers {
            background: var(--viewers-bg);
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid var(--border-color);
        }

        /* ─── Hero Section ─── */
        .hero-section {
            max-width: 1200px;
            margin: 2rem auto 4rem;
            padding: 3rem 2rem;
            background: var(--hero-gradient);
            border: 1px solid var(--border-color);
            border-radius: 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px var(--shadow-overlay);
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
            color: var(--text-main);
            line-height: 1.3;
            margin-bottom: 1.2rem;
            background: var(--hero-title-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-section.has-bg-image .hero-title {
            background: {{ $isDarkTheme ? 'linear-gradient(to right, #fff, #cbd5e1)' : 'linear-gradient(to right, #0f172a, #334155)' }} !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 800px;
            margin: 0 auto 2.5rem;
            font-weight: 400;
        }

        .hero-section.has-bg-image .hero-subtitle {
            color: {{ $isDarkTheme ? '#e2e8f0' : '#475569' }} !important;
        }

        /* ─── Buttons ─── */
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

        /* ─── Countdown Section ─── */
        .countdown-section {
            max-width: 900px;
            margin: -3rem auto 4rem;
            background: var(--card-bg);
            border: 2px solid var(--accent);
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 10;
            box-shadow: 0 20px 40px var(--shadow-overlay);
        }

        .countdown-title {
            color: var(--text-main);
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
            background: var(--time-box-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            min-width: 90px;
            padding: 1rem 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: inset 0 2px 4px var(--shadow-overlay);
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
            color: var(--text-muted);
            margin-top: 6px;
            font-weight: 600;
        }

        /* ─── Product Showcase ─── */
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
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        .product-card {
            background: var(--card-gradient);
            border: 1px solid var(--border-color);
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
            box-shadow: 0 20px 40px var(--shadow-overlay);
            border: 1px solid var(--border-color);
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
            background: #ef4444;
            color: #fff;
            font-weight: 900;
            font-size: 1rem;
            padding: 8px 18px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.5);
        }

        .product-details h3 {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--text-main);
            margin-bottom: 1rem;
        }

        .product-desc {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin-bottom: 1.8rem;
        }

        .price-container {
            display: flex;
            align-items: baseline;
            gap: 15px;
            margin-bottom: 2rem;
            background: var(--price-container-bg);
            padding: 1.2rem;
            border-radius: 16px;
            border: 1px dashed var(--price-container-border);
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
            color: var(--text-main);
            font-size: 1.1rem;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .features-list li i {
            color: #4ade80;
            font-size: 1.2rem;
        }

        /* ─── Features Grid ─── */
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
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem 1.8rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            background: var(--card-bg-hover);
            border-color: var(--primary);
            box-shadow: 0 15px 30px var(--shadow-overlay);
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
            color: var(--text-main);
            margin-bottom: 0.8rem;
        }

        .feature-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* ─── Testimonials ─── */
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
            background: var(--card-gradient);
            border: 1px solid var(--border-color);
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
            color: var(--text-main);
            font-size: 1.05rem;
            font-style: italic;
            margin-bottom: 1.5rem;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            border-top: 1px solid var(--border-color);
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
            color: var(--text-main);
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

        /* ─── CTA Banner ─── */
        .cta-banner {
            max-width: 1100px;
            margin: 0 auto 6rem;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px var(--shadow-overlay);
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

        /* ─── Sticky Mobile Action Bar ─── */
        .sticky-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--sticky-bg);
            border-top: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
            box-shadow: 0 -10px 25px var(--shadow-overlay);
        }

        .sticky-price {
            display: flex;
            flex-direction: column;
        }

        .sticky-price span {
            font-size: 0.85rem;
            color: var(--text-muted);
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

        /* ─── Footer ─── */
        footer {
            text-align: center;
            padding: 3rem 1rem 6rem;
            color: #64748b;
            font-size: 0.9rem;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.2rem; }
            .product-card { grid-template-columns: 1fr; padding: 1.8rem; gap: 2rem; }
            .countdown-grid { gap: 0.8rem; }
            .time-box { min-width: 70px; padding: 0.8rem 0.4rem; }
            .time-val { font-size: 1.6rem; }
            .cta-title { font-size: 2rem; }
            .btn-cta, .btn-white { width: 100%; text-align: center; font-size: 1.1rem; }
        }

        /* ─── Same Page Checkout Form & Success Styles ─── */
        .checkout-form-container {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
        }

        .form-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 1.2rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.6rem;
        }

        .landing-checkout-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            text-align: right;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-group label i {
            color: var(--primary);
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            color: var(--input-text);
            font-family: 'Cairo', sans-serif;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(108, 99, 255, 0.2);
        }

        .form-group select option {
            background: var(--card-bg);
            color: var(--text-main);
        }

        .checkout-success-container {
            text-align: center;
            padding: 2rem 1.5rem;
            background: rgba(74, 222, 128, 0.05);
            border: 1px solid rgba(74, 222, 128, 0.2);
            border-radius: 20px;
            margin-top: 2rem;
            animation: scale-up 0.4s ease;
        }

        @keyframes scale-up {
            0% { transform: scale(0.95); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-icon {
            font-size: 3rem;
            color: #4ade80;
            margin-bottom: 1rem;
        }

        .checkout-success-container h3 {
            font-size: 1.3rem;
            font-weight: 900;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .checkout-success-container p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .checkout-success-container p.desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 1rem;
            line-height: 1.6;
        }

        /* ─── Bottom Checkout Section & Container Styles ─── */
        .checkout-section-bottom {
            max-width: 800px;
            margin: 4rem auto 6rem;
            padding: 0 1.5rem;
        }

        .checkout-form-container-bottom {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            padding: 3rem 2rem;
            box-shadow: var(--shadow-lg);
        }

        .checkout-form-container-bottom .section-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .checkout-form-container-bottom .section-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            text-align: center;
        }

        /* ─── Premium Product Gallery Slider ─── */
        .product-gallery-slider {
            position: relative;
            width: 100%;
            overflow: hidden;
            border-radius: 16px;
        }

        .slider-track {
            display: flex;
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            width: 100%;
        }

        .slide-item {
            min-width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .slide-item img {
            width: 100%;
            height: auto;
            max-height: 480px;
            object-fit: contain;
            border-radius: 16px;
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1.2rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .slider-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
        }

        .slider-btn.prev { left: 12px; }
        .slider-btn.next { right: 12px; }

        .slider-dots {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 10;
        }

        .slider-dots .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .slider-dots .dot.active {
            background: var(--primary);
            width: 18px;
            border-radius: 4px;
        }

        /* ─── Variant Selector Styles ─── */
        .variant-selector-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }

        .variant-option-card {
            cursor: pointer;
            position: relative;
        }

        .variant-option-card input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .variant-option-card .option-label {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: var(--variant-bg);
            border: 1px solid var(--variant-border);
            border-radius: 12px;
            color: var(--variant-text);
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.3s ease;
            text-align: center;
        }

        .variant-option-card input:checked + .option-label {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 0 10px rgba(108, 99, 255, 0.3);
        }

        /* ─── Quantity Tiers / Bundle Card Styles ─── */
        .quantity-tiers-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 8px;
        }

        .tier-option-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.2rem;
            background: var(--tier-bg);
            border: 1px solid var(--tier-border);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .tier-option-card input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .tier-option-card .tier-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            text-align: right;
        }

        .tier-option-card .tier-info .title {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .tier-option-card .tier-info .desc {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .tier-option-card .tier-price {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .tier-option-card .tier-price strong {
            font-size: 1.2rem;
            color: var(--text-main);
        }

        .tier-option-card.active, .tier-option-card:has(input:checked) {
            border-color: var(--primary);
            background: rgba(108, 99, 255, 0.08);
        }

        .tier-option-card.active .tier-price strong, .tier-option-card:has(input:checked) .tier-price strong {
            color: var(--primary);
        }

        /* ─── Premium Dynamic Form Styles ─── */
        .order-invoice-card {
            background: var(--invoice-bg);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 1.2rem 1.5rem;
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .invoice-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 6px;
            margin-bottom: 4px;
        }

        .invoice-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .invoice-row strong {
            color: var(--text-main);
        }

        .invoice-row.total {
            border-top: 1px dashed var(--border-color);
            padding-top: 10px;
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .invoice-row.total strong {
            color: var(--primary);
            font-size: 1.3rem;
        }

        .variant-item-wrap {
            background: var(--variant-item-wrap-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 12px;
        }

        .variant-item-title {
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 4px;
        }

        /* ─── Lightbox Fullscreen Modal ─── */
        .lightbox-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            align-items: center;
            justify-content: center;
        }

        .lightbox-content {
            max-width: 90%;
            max-height: 85%;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.5);
            animation: zoom-in 0.3s ease;
            cursor: pointer;
        }

        @keyframes zoom-in {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
            z-index: 100001;
        }

        .lightbox-close:hover {
            color: var(--primary);
        }

        .lightbox-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10000;
        }
        .lightbox-arrow:hover {
            background: var(--primary);
            border-color: var(--primary);
        }
        .lightbox-arrow.prev { left: 40px; }
        .lightbox-arrow.next { right: 40px; }
        @media (max-width: 768px) {
            .lightbox-arrow { width: 40px; height: 40px; font-size: 1.2rem; }
            .lightbox-arrow.prev { left: 10px; }
            .lightbox-arrow.next { right: 10px; }
        }

        {!! $landingPage->custom_css !!}
    </style>
</head>
<body class="template-{{ $landingPage->template ?? 'classic' }}">

    <!-- Urgency Top Bar -->
    <div class="urgency-bar" role="alert" aria-live="assertive">
        <span class="pulse-dot" aria-hidden="true"></span>
        <span><span aria-hidden="true">🔥</span> تنبيه: العرض متاح لفترة محدودة أو حتى نفاذ الكمية المتاحة بالمخزون!</span>
    </div>

    <!-- Nav -->
    <nav class="landing-nav" aria-label="{{ __('ملاحة المتجر الرئيسية') }}">
        <div class="store-logo">
            <i class="fa-solid fa-store" aria-hidden="true"></i>
            <span>{{ $tenant->name ?? 'المتجر الرسمي' }}</span>
        </div>
        <div class="live-viewers">
            <i class="fa-solid fa-eye text-green-400" aria-hidden="true"></i>
            <span><strong id="viewers-count">18</strong> يشاهدون هذا العرض الآن</span>
        </div>
    </nav>

    <!-- Dynamic Sections Rendering -->
    @foreach ($sections as $index => $section)
        @php $type = $section['type'] ?? ''; @endphp

        @if ($type === 'hero')
            @php
                $heroOverlay = !empty($section['bg_image']) 
                    ? ($isDarkTheme 
                        ? 'background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), url('.$section['bg_image'].') center/cover;' 
                        : 'background: linear-gradient(135deg, rgba(255, 255, 255, 0.88), rgba(241, 245, 249, 0.94)), url('.$section['bg_image'].') center/cover;')
                    : '';
            @endphp
            <section class="hero-section {{ !empty($section['bg_image']) ? 'has-bg-image' : '' }}" style="{{ $heroOverlay }}">
                @if (!empty($section['badge']))
                    <div class="hero-badge">{{ $section['badge'] }}</div>
                @endif
                <h1 class="hero-title">{{ $section['title'] ?? 'عنوان العرض الرئيسي' }}</h1>
                <p class="hero-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                
                <a href="{{ $section['cta_link'] ?? '#product-showcase' }}" aria-label="{{ $section['cta_text'] ?? 'اطلب الآن واكسب الخصم' }}" class="btn-cta track-conversion" data-slug="{{ $landingPage->slug }}">
                    <span>{{ $section['cta_text'] ?? 'اطلب الآن واكسب الخصم' }}</span>
                    <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                </a>
            </section>
        @endif

        <!-- 2. Countdown Timer Section -->
        @if ($type === 'countdown')
            <section class="countdown-section" aria-label="{{ $section['title'] ?? 'ينتهي هذا العرض خلال' }}">
                <div class="countdown-title">
                    <i class="fa-solid fa-clock text-amber-400" aria-hidden="true"></i>
                    <span>{{ $section['title'] ?? 'ينتهي هذا العرض خلال:' }}</span>
                    @if (!empty($section['offer_badge']))
                        <span class="save-badge">{{ $section['offer_badge'] }}</span>
                    @endif
                </div>
                <div class="countdown-grid" aria-live="off" data-endtime="{{ $section['end_time'] ?? date('Y-m-d H:i:s', strtotime('+24 hours')) }}">
                    <div class="time-box">
                        <span class="time-val" id="days">00</span>
                        <span class="time-label">يوم</span>
                    </div>
                    <div class="time-box">
                        <span class="time-val" id="hours">00</span>
                        <span class="time-label">ساعة</span>
                    </div>
                    <div class="time-box">
                        <span class="time-val" id="minutes">00</span>
                        <span class="time-label">دقيقة</span>
                    </div>
                    <div class="time-box">
                        <span class="time-val" id="seconds">00</span>
                        <span class="time-label">ثانية</span>
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
                $pName = $pData['name'] ?? ($section['product_name'] ?? 'منتج فاخر');
                $pDesc = $pData['description'] ?? ($section['subtitle'] ?? '');
                $pPrice = $pData['price'] ?? ($section['custom_price'] ?? 0);
                $pOldPrice = $pData['original_price'] ?? ($section['original_price'] ?? 0);
                $pImg = $pData['image_url'] ?? ($section['image'] ?? '');
                $curr = $section['currency'] ?? 'ج.م';
                $features = $section['features'] ?? [];
                
                $discountPercent = 0;
                if ($pOldPrice > $pPrice && $pOldPrice > 0) {
                    $discountPercent = round((($pOldPrice - $pPrice) / $pOldPrice) * 100);
                }
            @endphp
            <section class="showcase-section" id="product-showcase" aria-label="{{ $section['title'] ?? 'المنتج المميز' }}">
                <div class="section-header">
                    <h2 class="section-title">{{ $section['title'] ?? 'المنتج المميز' }}</h2>
                    <p class="section-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                </div>

                <div class="product-card">
                    <div class="product-image-wrap">
                        @if ($discountPercent > 0)
                            <div class="discount-ribbon">وفر {{ $discountPercent }}%</div>
                        @endif

                        @if (!empty($pData['images']) && count($pData['images']) > 1)
                            <script>
                                window.galleryImages = {!! json_encode($pData['images'] ?? (!empty($pImg) ? [$pImg] : [])) !!};
                            </script>
                            <!-- Gallery Carousel -->
                            <div class="product-gallery-slider">
                                <div class="slider-track" id="slider-track">
                                    @foreach ($pData['images'] as $imgIndex => $gImg)
                                        <div class="slide-item">
                                            <img src="{{ $gImg }}" alt="{{ $pName }} - {{ $imgIndex + 1 }}" onclick="openLightbox({{ $imgIndex }})" style="cursor: pointer;">
                                        </div>
                                    @endforeach
                                </div>
                                <button class="slider-btn prev" type="button" onclick="changeSlide(1)">&#10095;</button>
                                <button class="slider-btn next" type="button" onclick="changeSlide(-1)">&#10094;</button>
                                <div class="slider-dots">
                                    @foreach ($pData['images'] as $imgIndex => $gImg)
                                        <span class="dot {{ $imgIndex === 0 ? 'active' : '' }}" onclick="goToSlide({{ $imgIndex }})"></span>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <script>
                                window.galleryImages = {!! json_encode(!empty($pImg) ? [$pImg] : []) !!};
                            </script>
                            <img src="{{ $pImg }}" alt="{{ $pName }}" class="product-img" onclick="openLightbox(0)" style="cursor: pointer;">
                        @endif
                    </div>

                    <div class="product-details">
                        <h3>{{ $pName }}</h3>
                        <p class="product-desc">{{ $pDesc }}</p>

                        <div class="price-container">
                            <span class="current-price">{{ number_format($pPrice) }} {{ $curr }}</span>
                            @if ($pOldPrice > $pPrice)
                                <span class="old-price">{{ number_format($pOldPrice) }} {{ $curr }}</span>
                                <span class="save-badge">وفرت {{ number_format($pOldPrice - $pPrice) }} {{ $curr }}</span>
                            @endif
                        </div>

                        @if (!empty($features) && is_array($features))
                            <ul class="features-list">
                                @foreach ($features as $feat)
                                    <li>
                                        <i class="fa-solid fa-check-circle" aria-hidden="true"></i>
                                        <span>{{ $feat }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (!empty($pData) && isset($pData['id']))
                            <button onclick="handleOrderClick('{{ $landingPage->slug }}', '{{ $pData['id'] }}')" aria-label="{{ $section['buy_button_text'] ?? 'اطلب الآن واكسب الخصم' }}" class="btn-cta" style="width: 100%; cursor: pointer;">
                                <span>{{ $section['buy_button_text'] ?? 'اطلب الآن - دفع عند الاستلام' }}</span>
                                <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                            </button>
                        @else
                            <p style="text-align: center; color: #94a3b8; font-size: 0.9rem; padding: 1rem; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; margin-top: 1.5rem;">
                                برجاء ربط منتج في لوحة التحكم لتفعيل نموذج الشراء السريع والدفع عند الاستلام.
                            </p>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <!-- 4. Features Grid -->
        @if ($type === 'features')
            <section class="features-section" aria-label="{{ $section['title'] ?? 'لماذا تختارنا' }}">
                <div class="section-header">
                    <h2 class="section-title">{{ $section['title'] ?? 'لماذا تختارنا؟' }}</h2>
                    <p class="section-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                </div>

                <div class="features-grid">
                    @foreach (($section['features'] ?? ($section['items'] ?? [])) as $item)
                        <div class="feature-card">
                            <div class="feature-icon" aria-hidden="true">
                                <i class="{{ $item['icon'] ?? 'fa-solid fa-star' }}"></i>
                            </div>
                            <h3 class="feature-title">{{ $item['title'] ?? '' }}</h3>
                            <p class="feature-desc">{{ $item['description'] ?? ($item['desc'] ?? '') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- 5. Testimonials -->
        @if ($type === 'testimonials')
            <section class="testimonials-section" aria-label="{{ $section['title'] ?? 'آراء العملاء' }}">
                <div class="section-header">
                    <h2 class="section-title">{{ $section['title'] ?? 'آراء عملائنا' }}</h2>
                    <p class="section-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                </div>

                <div class="testimonials-grid">
                    @foreach (($section['testimonials'] ?? ($section['items'] ?? [])) as $testi)
                        <div class="testimonial-card">
                            <div class="stars" aria-label="{{ __('تقييم :rating من 5 نجوم', ['rating' => $testi['rating'] ?? 5]) }}">
                                @for ($i = 0; $i < ($testi['rating'] ?? 5); $i++)
                                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                                @endfor
                            </div>
                            <p class="quote-text">"{{ $testi['comment'] ?? '' }}"</p>
                            <div class="user-meta">
                                @if (!empty($testi['avatar']))
                                    <img src="{{ $testi['avatar'] }}" alt="{{ $testi['name'] ?? '' }}" class="user-avatar">
                                @else
                                    <div class="user-avatar" aria-hidden="true" style="background: var(--primary); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold;">
                                        {{ mb_substr($testi['name'] ?? 'ع', 0, 1) }}
                                    </div>
                                @endif
                                <div class="user-info">
                                    <h4>{{ $testi['name'] ?? 'عميل موثق' }}</h4>
                                    <span>
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                        {{ $testi['role'] ?? 'مشتري مؤكد' }}
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
            <section class="cta-banner" aria-label="{{ $section['title'] ?? 'دعوة لاتخاذ إجراء' }}">
                <h2 class="cta-title">{{ $section['title'] ?? 'هل أنت جاهز للطلب؟' }}</h2>
                <p class="cta-subtitle">{{ $section['subtitle'] ?? '' }}</p>
                <button onclick="handleOrderClick('{{ $landingPage->slug }}', '')" aria-label="{{ $section['button_text'] ?? 'اطلب الآن واكسب العرض' }}" class="btn-white">
                    <span>{{ $section['button_text'] ?? 'اطلب الآن واكسب العرض' }}</span>
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </button>
            </section>
        @endif

    @endforeach

    @php
        // Find the showcase product details to bind to the checkout form at the bottom
        $showcaseProduct = null;
        foreach ($sections as $s) {
            if (($s['type'] ?? '') === 'product_showcase' && !empty($s['product_data'])) {
                $showcaseProduct = $s['product_data'];
                break;
            }
        }
    @endphp

    @if ($showcaseProduct)
        <!-- Bottom checkout form section -->
        <section class="checkout-section-bottom" id="checkout-form-section">
            <div class="checkout-form-container-bottom">
                <h2 class="section-title">سجل طلبك الآن - الدفع عند الاستلام</h2>
                <p class="section-subtitle">قم بملء البيانات التالية لتأكيد طلبك وسنتواصل معك فوراً لتأكيد الشحن</p>
                
                <div id="checkout-form-container" class="landing-checkout-form-wrap">
                    <form id="landing-checkout-form" class="landing-checkout-form" onsubmit="submitLandingOrder(event, '{{ $landingPage->slug }}', '{{ $showcaseProduct['id'] }}', '{{ $showcaseProduct['name'] }}', '{{ $showcaseProduct['price'] }}')">
                        <!-- Dynamic Variant options (Colors / Sizes per item) -->
                        <div id="dynamic-variants-container" style="margin-bottom: 1.5rem;"></div>

                        <!-- Quantity Tiers / Bundle Offers -->
                        <div class="form-group">
                            <label><i class="fa-solid fa-tags"></i> اختر العرض المناسب</label>
                            <div class="quantity-tiers-grid">
                                <!-- Option 1: Single item -->
                                <label class="tier-option-card active">
                                    <input type="radio" name="qty_tier" value="1" checked required onclick="selectQtyTier(1, {{ $showcaseProduct['price'] }}, 'قطعة واحدة')">
                                    <div class="tier-info">
                                        <span class="title">قطعة واحدة</span>
                                        <span class="desc">بسعر العرض المميز</span>
                                    </div>
                                    <div class="tier-price">
                                        <strong>{{ number_format($showcaseProduct['price']) }}</strong> {{ $curr }}
                                    </div>
                                </label>

                                <!-- Custom tiers from database if available -->
                                @if (!empty($showcaseProduct['price_tiers']) && is_array($showcaseProduct['price_tiers']))
                                    @foreach ($showcaseProduct['price_tiers'] as $tier)
                                        @php
                                            $qty = $tier['min_qty'] ?? ($tier['qty'] ?? ($tier['quantity'] ?? 2));
                                            $totalPrice = $tier['price'] ?? 0;
                                            $tierName = $tier['name'] ?? ($qty == 2 ? 'عرض قطعتين (وفر أكثر!)' : ($qty == 3 ? 'عرض 3 قطع (أفضل قيمة!)' : "عرض {$qty} قطع"));
                                        @endphp
                                        <label class="tier-option-card">
                                            <input type="radio" name="qty_tier" value="{{ $qty }}" onclick="selectQtyTier({{ $qty }}, {{ $totalPrice }}, '{{ $tierName }}')">
                                            <div class="tier-info">
                                                <span class="title">{{ $tierName }}</span>
                                                <span class="desc">توفير رائع وشحن مجاني!</span>
                                            </div>
                                            <div class="tier-price">
                                                <strong>{{ number_format($totalPrice) }}</strong> {{ $curr }}
                                            </div>
                                        </label>
                                    @endforeach
                                @else
                                    <!-- Fallback Standard Offers if no tiers configured in database -->
                                    <label class="tier-option-card">
                                        <input type="radio" name="qty_tier" value="2" onclick="selectQtyTier(2, {{ $showcaseProduct['price'] * 2 * 0.9 }}, 'عرض قطعتين (خصم 10%)')">
                                        <div class="tier-info">
                                            <span class="title">عرض قطعتين (خصم 10%)</span>
                                            <span class="desc">وفر {{ number_format($showcaseProduct['price'] * 2 * 0.1) }} ج.م + شحن مجاني!</span>
                                        </div>
                                        <div class="tier-price">
                                            <strong>{{ number_format($showcaseProduct['price'] * 2 * 0.9) }}</strong> {{ $curr }}
                                        </div>
                                    </label>
                                    <label class="tier-option-card">
                                        <input type="radio" name="qty_tier" value="3" onclick="selectQtyTier(3, {{ $showcaseProduct['price'] * 3 * 0.8 }}, 'عرض 3 قطع (خصم 20%)')">
                                        <div class="tier-info">
                                            <span class="title">عرض 3 قطع (خصم 20% - الأكثر طلباً)</span>
                                            <span class="desc">وفر {{ number_format($showcaseProduct['price'] * 3 * 0.2) }} ج.م + شحن مجاني!</span>
                                        </div>
                                        <div class="tier-price">
                                            <strong>{{ number_format($showcaseProduct['price'] * 3 * 0.8) }}</strong> {{ $curr }}
                                        </div>
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="customer_name"><i class="fa-solid fa-user"></i> الاسم بالكامل <span style="color: red;">*</span></label>
                            <input type="text" id="customer_name" name="customer_name" placeholder="الاسم ثلاثي أو ثنائي" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_phone"><i class="fa-solid fa-phone"></i> رقم الهاتف الجوال (يفضل واتساب) <span style="color: red;">*</span></label>
                            <input type="tel" id="customer_phone" name="customer_phone" placeholder="مثال: 01012345678" required>
                        </div>

                        <div class="form-group">
                            <label for="governorate_id"><i class="fa-solid fa-map-location-dot"></i> المحافظة <span style="color: red;">*</span></label>
                            <select id="governorate_id" name="governorate_id" required onchange="handleGovernorateChange(this)">
                                <option value="" data-shipping="0">-- اختر محافظة الشحن --</option>
                                @foreach ($governorates as $gov)
                                    <option value="{{ $gov->id }}" data-shipping="{{ $gov->price }}">{{ $gov->name }} (+{{ $gov->price }} ج.م شحن)</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="customer_address"><i class="fa-solid fa-map-marker-alt"></i> العنوان بالتفصيل <span style="color: red;">*</span></label>
                            <input type="text" id="customer_address" name="customer_address" placeholder="المنطقة، الشارع، رقم المنزل" required>
                        </div>

                        <div class="form-group">
                            <label for="customer_notes"><i class="fa-solid fa-comment-dots"></i> ملاحظات إضافية (اختياري)</label>
                            <textarea id="customer_notes" name="customer_notes" placeholder="أي ملاحظات بخصوص التوصيل أو مواعيد الاتصال..."></textarea>
                        </div>

                        <!-- Invoice / Summary Card -->
                        <div class="order-invoice-card" id="order-invoice-card" style="margin-top: 1.5rem; margin-bottom: 1rem;">
                            <h4 class="invoice-title">ملخص حساب الفاتورة</h4>
                            <div class="invoice-row">
                                <span>السعر:</span>
                                <strong id="invoice-subtotal">0 ج.م</strong>
                            </div>
                            <div class="invoice-row">
                                <span>الشحن:</span>
                                <strong id="invoice-shipping">0 ج.م</strong>
                            </div>
                            <div class="invoice-row total">
                                <span>الإجمالي:</span>
                                <strong id="invoice-total">0 ج.م</strong>
                            </div>
                        </div>

                        <button type="submit" class="btn-cta" style="width: 100%; border: none; margin-top: 1rem; cursor: pointer;">
                            <span>تأكيد طلب الشراء الآن</span>
                            <i class="fa-solid fa-check-circle"></i>
                        </button>
                    </form>
                </div>

                <!-- Success Message Box (Hidden by default) -->
                <div id="checkout-success-container" class="checkout-success-container" style="display: none;">
                    <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <h3>تم تسجيل طلبك بنجاح! 🎉</h3>
                    <p>رقم مرجع الطلب الخاص بك هو: <strong id="success-ref-num" style="color: var(--secondary);"></strong></p>
                    <p class="desc">شكراً لثقتك بنا. سيقوم أحد موظفي خدمة العملاء بالتواصل معك هاتفياً أو عبر الواتساب لتأكيد الشحن خلال 24 ساعة.</p>
                </div>
            </div>
        </section>
    @endif

    <!-- Sticky Bottom Bar -->
    @php
        $stickyPrice = 'أفضل سعر';
        foreach ($sections as $s) {
            if (($s['type'] ?? '') === 'product_showcase') {
                $stickyPrice = number_format($s['product_data']['price'] ?? ($s['custom_price'] ?? 0)) . ' ' . ($s['currency'] ?? 'ج.م');
                break;
            }
        }
    @endphp
    <div class="sticky-bar" role="complementary" aria-label="{{ __('شريط الشراء السريع') }}">
        <div class="sticky-price">
            <span>سعر العرض المحدود:</span>
            <strong>{{ $stickyPrice }}</strong>
        </div>
        <button onclick="handleOrderClick('{{ $landingPage->slug }}', '')" aria-label="{{ __('اطلب الآن بسعر') }} {{ $stickyPrice }}" class="sticky-btn">
            <span>اطلب الآن</span>
            <i class="fa-solid fa-cart-arrow-down" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Footer -->
    <footer>
        <p>جميع الحقوق محفوظة &copy; {{ date('Y') }} لـ {{ $tenant->name ?? 'المتجر الرسمي' }} | تم التطوير بواسطة Fast Order</p>
    </footer>

    <!-- Scripts -->
    <script>
        // Global variables for dynamic variant selections and pricing
        window.productColors = @json($showcaseProduct['colors'] ?? []);
        window.productSizes = @json($showcaseProduct['sizes'] ?? []);
        window.productPrice = {{ $showcaseProduct['price'] ?? 0 }};
        window.productShippingType = '{{ $showcaseProduct['shipping_type'] ?? 'free' }}';
        window.shippingGovernoratePrice = 0;
        window.hasSelectedGovernorate = false;

        // 1. Live Viewers Simulation
        setInterval(() => {
            const el = document.getElementById('viewers-count');
            if (el) {
                let current = parseInt(el.innerText) || 18;
                let diff = Math.floor(Math.random() * 5) - 2;
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
            const formSection = document.getElementById('checkout-form-section') || document.getElementById('checkout-form-container');
            if (formSection) {
                formSection.scrollIntoView({ behavior: 'smooth' });
            } else if (productId && productId !== '') {
                window.location.href = `/shop/checkout.html?product_id=${productId}`;
            } else {
                window.location.href = `/shop/checkout.html`;
            }
        }

        // 4. Same-page Checkout Order Submission
        window.selectedTierQty = 1;
        window.selectedTierPrice = null;
        window.selectedTierName = 'قطعة واحدة';

        function selectQtyTier(qty, price, name) {
            window.selectedTierQty = qty;
            window.selectedTierPrice = price;
            window.selectedTierName = name;
            
            // Toggle active card
            document.querySelectorAll('.tier-option-card').forEach(card => {
                card.classList.remove('active');
            });
            const selectedRadio = document.querySelector(`input[name="qty_tier"][value="${qty}"]`);
            if (selectedRadio) {
                selectedRadio.checked = true;
                selectedRadio.closest('.tier-option-card').classList.add('active');
            }

            // Regenerate variant fields for each item in the quantity bundle
            renderDynamicVariants(qty);

            // Update Invoice
            updateInvoiceTotal();
        }

        function renderDynamicVariants(qty) {
            const container = document.getElementById('dynamic-variants-container');
            if (!container) return;
            container.innerHTML = '';

            const hasColors = window.productColors && window.productColors.length > 0;
            const hasSizes = window.productSizes && window.productSizes.length > 0;

            if (!hasColors && !hasSizes) {
                container.style.display = 'none';
                return;
            }
            container.style.display = 'block';

            for (let i = 1; i <= qty; i++) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'variant-item-wrap';
                
                // Add a title indicating which item this is
                const title = document.createElement('div');
                title.className = 'variant-item-title';
                title.innerHTML = `<i class="fa-solid fa-tags"></i> خيارات القطعة رقم ${i}`;
                itemDiv.appendChild(title);

                if (hasColors) {
                    const colorGroup = document.createElement('div');
                    colorGroup.className = 'form-group';
                    colorGroup.style.marginBottom = '12px';
                    colorGroup.innerHTML = `<label style="font-size: 0.85rem; margin-bottom: 6px;"><i class="fa-solid fa-palette"></i> لون القطعة ${i}: <span style="color: red;">*</span></label>`;
                    
                    const selectorGrid = document.createElement('div');
                    selectorGrid.className = 'variant-selector-grid';

                    window.productColors.forEach((color, idx) => {
                        const label = document.createElement('label');
                        label.className = 'variant-option-card';
                        label.innerHTML = `
                            <input type="radio" name="item_${i}_color" value="${color}" required>
                            <span class="option-label">${color}</span>
                        `;
                        selectorGrid.appendChild(label);
                    });
                    colorGroup.appendChild(selectorGrid);
                    itemDiv.appendChild(colorGroup);
                }

                if (hasSizes) {
                    const sizeGroup = document.createElement('div');
                    sizeGroup.className = 'form-group';
                    sizeGroup.style.marginBottom = '6px';
                    sizeGroup.innerHTML = `<label style="font-size: 0.85rem; margin-bottom: 6px;"><i class="fa-solid fa-ruler-combined"></i> مقاس القطعة ${i}: <span style="color: red;">*</span></label>`;
                    
                    const selectorGrid = document.createElement('div');
                    selectorGrid.className = 'variant-selector-grid';

                    window.productSizes.forEach((size, idx) => {
                        const label = document.createElement('label');
                        label.className = 'variant-option-card';
                        label.innerHTML = `
                            <input type="radio" name="item_${i}_size" value="${size}" required>
                            <span class="option-label">${size}</span>
                        `;
                        selectorGrid.appendChild(label);
                    });
                    sizeGroup.appendChild(selectorGrid);
                    itemDiv.appendChild(sizeGroup);
                }

                container.appendChild(itemDiv);
            }
        }

        function handleGovernorateChange(selectEl) {
            const shipping = parseFloat(selectEl.options[selectEl.selectedIndex].getAttribute('data-shipping') || 0);
            window.shippingGovernoratePrice = shipping;
            window.hasSelectedGovernorate = selectEl.value !== '';
            updateInvoiceTotal();
        }

        function updateInvoiceTotal() {
            const subtotalVal = parseFloat(window.selectedTierPrice !== null ? window.selectedTierPrice : window.productPrice);
            
            let shippingText = '';
            let shippingVal = 0;

            if (window.productShippingType === 'free') {
                shippingText = 'شحن مجاني';
                shippingVal = 0;
            } else {
                if (!window.hasSelectedGovernorate) {
                    shippingText = 'غير محدد';
                    shippingVal = 0;
                } else {
                    shippingVal = parseFloat(window.shippingGovernoratePrice || 0);
                    shippingText = shippingVal > 0 ? (shippingVal.toLocaleString() + ' ج.م') : 'شحن مجاني';
                }
            }

            const totalVal = subtotalVal + shippingVal;

            const subtotalEl = document.getElementById('invoice-subtotal');
            const shippingEl = document.getElementById('invoice-shipping');
            const totalEl = document.getElementById('invoice-total');

            if (subtotalEl) subtotalEl.innerText = subtotalVal.toLocaleString() + ' ج.م';
            if (shippingEl) shippingEl.innerText = shippingText;
            if (totalEl) totalEl.innerText = totalVal.toLocaleString() + ' ج.م';
        }

        // Initialize variants and invoice on load
        document.addEventListener('DOMContentLoaded', () => {
            renderDynamicVariants(window.selectedTierQty);
            updateInvoiceTotal();
        });

        // Image slider carousel logic
        let currentSlideIndex = 0;
        function changeSlide(direction) {
            const track = document.getElementById('slider-track');
            if (!track) return;
            const slides = track.querySelectorAll('.slide-item');
            if (slides.length === 0) return;
            
            currentSlideIndex += direction;
            if (currentSlideIndex < 0) {
                currentSlideIndex = slides.length - 1;
            } else if (currentSlideIndex >= slides.length) {
                currentSlideIndex = 0;
            }
            
            updateSlidePosition();
        }

        function goToSlide(index) {
            const track = document.getElementById('slider-track');
            if (!track) return;
            currentSlideIndex = index;
            updateSlidePosition();
        }

        function updateSlidePosition() {
            const track = document.getElementById('slider-track');
            if (!track) return;
            track.style.transform = `translateX(${currentSlideIndex * 100}%)`;
            
            const dots = document.querySelectorAll('.slider-dots .dot');
            dots.forEach((dot, idx) => {
                if (idx === currentSlideIndex) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }

        function submitLandingOrder(event, slug, productId, productName, productPrice) {
            event.preventDefault();
            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            const finalQty = parseInt(window.selectedTierQty || 1);
            const hasColors = window.productColors && window.productColors.length > 0;
            const hasSizes = window.productSizes && window.productSizes.length > 0;

            // 1. Dynamic Variants Validation
            for (let i = 1; i <= finalQty; i++) {
                if (hasColors) {
                    const colorRadio = form.querySelector(`input[name="item_${i}_color"]:checked`);
                    if (!colorRadio) {
                        alert(`يرجى اختيار لون للقطعة رقم ${i}!`);
                        return;
                    }
                }
                if (hasSizes) {
                    const sizeRadio = form.querySelector(`input[name="item_${i}_size"]:checked`);
                    if (!sizeRadio) {
                        alert(`يرجى اختيار مقاس للقطعة رقم ${i}!`);
                        return;
                    }
                }
            }

            // 2. Phone Validation
            let phone = form.customer_phone.value.trim().replace(/\s+/g, '');
            if (phone.startsWith('+201')) {
                phone = phone.substring(2);
            } else if (phone.startsWith('201')) {
                phone = '0' + phone.substring(1);
            } else if (phone.startsWith('00201')) {
                phone = phone.substring(4);
            }

            const phoneRegex = /^01[0125]\d{8}$/;
            if (!phoneRegex.test(phone)) {
                alert('يرجى إدخال رقم هاتف مصري صحيح مكون من 11 رقم ويبدأ بـ 01 (مثال: 01012345678).');
                form.customer_phone.focus();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>جاري تسجيل طلبك...</span> <i class="fa-solid fa-spinner fa-spin"></i>';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const totalItemPrice = parseFloat(window.selectedTierPrice !== null ? window.selectedTierPrice : productPrice);
            const unitPrice = parseFloat(totalItemPrice / finalQty);

            // Assemble items with sizes and colors for each piece
            const items = [];
            for (let i = 1; i <= finalQty; i++) {
                const colorRadio = form.querySelector(`input[name="item_${i}_color"]:checked`);
                const sizeRadio = form.querySelector(`input[name="item_${i}_size"]:checked`);
                
                const itemPayload = {
                    id: parseInt(productId),
                    name: `${productName} (القطعة ${i})`,
                    price: unitPrice,
                    qty: 1
                };

                if (colorRadio) {
                    itemPayload.selectedColor = colorRadio.value;
                }
                if (sizeRadio) {
                    itemPayload.selectedSize = sizeRadio.value;
                }
                items.push(itemPayload);
            }

            const notesValue = form.customer_notes ? form.customer_notes.value.trim() : '';
            let finalNotes = `[طلب سريع من صفحة الهبوط] [العرض المختار: ${window.selectedTierName}]`;
            if (notesValue) {
                finalNotes += `\nملاحظات العميل: ${notesValue}`;
            }

            const formData = {
                customer_name: form.customer_name.value,
                customer_phone: phone,
                customer_address: form.customer_address.value,
                governorate_id: form.governorate_id.value,
                payment_method: 'cod',
                items: items,
                notes: finalNotes
            };

            fetch('/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Track conversion pixels
                    const totalOrderValue = items.reduce((s, i) => s + (i.price * i.qty), 0);
                    if (typeof fbq !== 'undefined') {
                        fbq('track', 'Purchase', {
                            value: totalOrderValue,
                            currency: 'EGP',
                            content_type: 'product',
                            content_ids: [parseInt(productId)]
                        });
                    }
                    if (typeof ttq !== 'undefined') {
                        ttq.track('CompletePayment', {
                            value: totalOrderValue,
                            currency: 'EGP',
                            contents: [{
                                content_id: parseInt(productId),
                                quantity: items.length
                            }]
                        });
                    }

                    fetch(`/lp/${slug}/convert`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ product_id: productId })
                    }).catch(e => {});

                    document.getElementById('checkout-form-container').style.display = 'none';
                    document.getElementById('success-ref-num').innerText = data.reference_number;
                    document.getElementById('checkout-success-container').style.display = 'block';
                    document.getElementById('checkout-success-container').scrollIntoView({ behavior: 'smooth' });
                } else {
                    alert(data.message || 'حدث خطأ أثناء معالجة الطلب، يرجى المحاولة مرة أخرى.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(err => {
                console.error(err);
                alert('حدث خطأ في الاتصال بالخادم، يرجى المحاولة مرة أخرى.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        }

        // ربط أزرار الـ CTA العادية
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

        // Lightbox Modal Functions
        let currentLightboxIndex = 0;

        function openLightbox(index) {
            const lightbox = document.getElementById('image-lightbox');
            const img = document.getElementById('lightbox-img');
            if (lightbox && img && window.galleryImages && window.galleryImages[index]) {
                currentLightboxIndex = index;
                img.src = window.galleryImages[index];
                lightbox.style.display = 'flex';
                document.body.style.overflow = 'hidden';

                const prevArrow = document.getElementById('lightbox-prev');
                const nextArrow = document.getElementById('lightbox-next');
                if (prevArrow && nextArrow) {
                    if (window.galleryImages.length > 1) {
                        prevArrow.style.display = 'flex';
                        nextArrow.style.display = 'flex';
                    } else {
                        prevArrow.style.display = 'none';
                        nextArrow.style.display = 'none';
                    }
                }
            }
        }

        function closeLightbox() {
            const lightbox = document.getElementById('image-lightbox');
            if (lightbox) {
                lightbox.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        function changeLightboxImage(direction) {
            if (!window.galleryImages || window.galleryImages.length <= 1) return;
            currentLightboxIndex += direction;
            if (currentLightboxIndex < 0) {
                currentLightboxIndex = window.galleryImages.length - 1;
            } else if (currentLightboxIndex >= window.galleryImages.length) {
                currentLightboxIndex = 0;
            }
            const img = document.getElementById('lightbox-img');
            if (img) {
                img.src = window.galleryImages[currentLightboxIndex];
            }
        }
    </script>

    <!-- Lightbox Fullscreen Modal -->
    <div id="image-lightbox" class="lightbox-modal" onclick="closeLightbox()">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <button id="lightbox-prev" class="lightbox-arrow prev" onclick="event.stopPropagation(); changeLightboxImage(1)">&#10095;</button>
        <img class="lightbox-content" id="lightbox-img" onclick="event.stopPropagation(); changeLightboxImage(1)">
        <button id="lightbox-next" class="lightbox-arrow next" onclick="event.stopPropagation(); changeLightboxImage(-1)">&#10094;</button>
    </div>
</body>
</html>
