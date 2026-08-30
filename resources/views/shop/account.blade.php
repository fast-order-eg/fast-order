<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $storeName = \App\Models\Setting::get('store_name') ?: ($tenant->name ?? 'المتجر');
        $storeLogo = \App\Models\Setting::get('logo') ? asset('storage/' . \App\Models\Setting::get('logo')) : ($tenant->logo ? asset('storage/' . $tenant->logo) : asset('images/logo.png'));
    @endphp
    <title>حسابي - {{ $storeName }}</title>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="حسابي - {{ $storeName }}">
    <meta property="og:image" content="{{ $storeLogo }}">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: {{ $theme['primary_color'] ?? '#6c63ff' }};
            --secondary: {{ $theme['secondary_color'] ?? '#ff6584' }};
            --bg-light: #f4f6fa;
            --text-dark: #1a1a2e;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 8px 25px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 15px 35px rgba(108, 99, 255, 0.15);
        }

        /* Focus indicators for accessibility */
        *:focus-visible {
            outline: 3px solid var(--primary, #6c63ff) !important;
            outline-offset: 3px !important;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Top Navigation Bar */
        .top-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }
        .top-bar a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }
        .top-bar a:hover { transform: translateX(3px); opacity: 0.85; }
        .store-brand { font-size: 1.3rem; font-weight: 900; color: var(--text-dark); display: flex; align-items: center; gap: 0.5rem; }
        .store-brand span { color: var(--primary); }

        /* Layout Container */
        .account-wrapper { max-width: 1150px; margin: 2rem auto; padding: 0 1.25rem; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        .page-title { font-size: 1.75rem; font-weight: 900; color: var(--text-dark); display: flex; align-items: center; gap: 0.75rem; }

        /* Grid Structure */
        .account-grid { display: grid; grid-template-columns: 300px 1fr; gap: 2rem; align-items: start; }
        @media (max-width: 850px) { .account-grid { grid-template-columns: 1fr; } }

        /* Sidebar Profile Card */
        .sidebar {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 2rem 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        .user-avatar-box {
            position: relative;
            width: 90px;
            height: 90px;
            margin: 0 auto 1.25rem;
        }
        .user-avatar {
            width: 100%; height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex; align-items: center; justify-content: center;
            font-size: 2.25rem; color: white; font-weight: 900;
            box-shadow: var(--shadow-lg);
            border: 3px solid white;
        }
        .user-name { text-align: center; font-weight: 800; font-size: 1.15rem; color: var(--text-dark); margin-bottom: 0.2rem; }
        .user-email { text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem; word-break: break-all; }
        .user-phone-badge {
            display: inline-block; background: #f1f5f9; color: #475569;
            padding: 0.2rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
            margin: 0 auto 1rem; display: table;
        }
        .user-since { text-align: center; font-size: 0.75rem; color: #94a3b8; margin-bottom: 1.5rem; }

        /* Quick Stats in Sidebar */
        .stats-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;
            margin-bottom: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: 16px;
        }
        .stat-box { text-align: center; }
        .stat-val { font-size: 1.25rem; font-weight: 900; color: var(--primary); }
        .stat-lbl { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; }

        .nav-divider { height: 1px; background: #f1f5f9; margin: 1rem 0; }

        /* Navigation Menu */
        .sidebar-nav { display: flex; flex-direction: column; gap: 0.35rem; }
        .nav-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.85rem 1.2rem; border-radius: 14px;
            cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: none; background: transparent; width: 100%;
            font-family: 'Cairo', sans-serif; font-size: 0.95rem; font-weight: 600;
            color: #475569; text-align: right;
        }
        .nav-item-left { display: flex; align-items: center; gap: 0.85rem; }
        .nav-item:hover { background: #f8fafc; color: var(--primary); transform: translateX(-3px); }
        .nav-item.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; font-weight: 700;
            box-shadow: 0 6px 15px rgba(108, 99, 255, 0.3);
            transform: translateX(0);
        }
        .nav-item.logout-btn { color: #ef4444; margin-top: 0.5rem; }
        .nav-item.logout-btn:hover { background: #fee2e2; color: #dc2626; transform: none; }
        .nav-item.danger-btn { color: #e11d48; }
        .nav-item.danger-btn:hover { background: #fff1f2; }

        /* Main Content Box */
        .content-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 2.25rem;
            box-shadow: var(--shadow-md);
            min-height: 520px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: tabFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes tabFadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        
        .tab-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.75rem; padding-bottom: 1.25rem; border-bottom: 2px solid #f1f5f9;
        }
        .tab-title { font-size: 1.35rem; font-weight: 900; color: var(--text-dark); display: flex; align-items: center; gap: 0.75rem; }

        /* Form Styles */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 650px) { .form-grid { grid-template-columns: 1fr; } }
        .form-group { margin-bottom: 1.25rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-label { display: block; font-size: 0.9rem; font-weight: 700; margin-bottom: 0.5rem; color: #334155; }
        .form-input {
            width: 100%; padding: 0.85rem 1.1rem;
            border: 2px solid #e2e8f0; border-radius: 14px;
            font-family: 'Cairo', sans-serif; font-size: 0.95rem; color: var(--text-dark);
            transition: all 0.2s; outline: none; background: #f8fafc;
        }
        .form-input:focus {
            border-color: var(--primary); background: #ffffff;
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.12);
        }
        
        .btn-primary {
            padding: 0.85rem 2.25rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; border: none; border-radius: 14px;
            cursor: pointer; font-family: 'Cairo', sans-serif; font-weight: 700; font-size: 1rem;
            transition: all 0.25s; box-shadow: 0 6px 16px rgba(108, 99, 255, 0.3);
            display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(108, 99, 255, 0.4); }
        .btn-primary:active { transform: translateY(0); }
        
        .btn-outline {
            padding: 0.75rem 1.5rem; background: transparent; color: var(--primary);
            border: 2px solid var(--primary); border-radius: 12px; cursor: pointer;
            font-family: 'Cairo', sans-serif; font-weight: 700; font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-outline:hover { background: #f8f9ff; transform: translateY(-1px); }

        .btn-danger {
            padding: 0.85rem 2rem; background: #ef4444; color: white;
            border: none; border-radius: 14px; cursor: pointer;
            font-family: 'Cairo', sans-serif; font-weight: 700; font-size: 0.95rem;
            transition: all 0.2s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        .btn-danger:hover { background: #dc2626; transform: translateY(-1px); }

        /* Orders Filter Tabs */
        .filter-pills { display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
        .filter-pill {
            padding: 0.45rem 1.1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 700;
            background: #f1f5f9; color: #64748b; border: none; cursor: pointer;
            font-family: 'Cairo', sans-serif; white-space: nowrap; transition: all 0.2s;
        }
        .filter-pill.active, .filter-pill:hover { background: var(--primary); color: white; }

        /* Orders Card */
        .order-card {
            border: 2px solid #f1f5f9; border-radius: 18px;
            padding: 1.5rem; margin-bottom: 1.25rem;
            transition: all 0.25s; background: #ffffff;
        }
        .order-card:hover { border-color: var(--primary); box-shadow: 0 8px 25px rgba(108, 99, 255, 0.1); transform: translateY(-2px); }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .order-num-box { display: flex; align-items: center; gap: 0.75rem; }
        .order-num { font-weight: 900; font-size: 1.1rem; color: var(--text-dark); }
        .order-date { font-size: 0.82rem; color: #94a3b8; }
        .status-badge {
            padding: 0.35rem 1rem; border-radius: 30px; font-size: 0.82rem; font-weight: 800;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .order-body { display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-top: 1px dashed #e2e8f0; border-bottom: 1px dashed #e2e8f0; margin-bottom: 1rem; }
        .order-items-preview { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: 0.9rem; color: #475569; }
        .order-total-box { text-align: left; }
        .order-total-lbl { font-size: 0.75rem; color: #94a3b8; font-weight: 600; }
        .order-total-val { font-size: 1.25rem; font-weight: 900; color: var(--primary); }
        .order-footer { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
        
        .btn-track {
            padding: 0.6rem 1.25rem; background: #f8fafc; border: 1.5px solid #cbd5e1;
            border-radius: 12px; font-family: 'Cairo', sans-serif; font-weight: 700; font-size: 0.85rem;
            color: #334155; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.4rem;
        }
        .btn-track:hover { background: #f1f5f9; border-color: var(--primary); color: var(--primary); }

        /* Addresses Grid */
        .addresses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
        .address-card {
            border: 2px solid #e2e8f0; border-radius: 18px; padding: 1.5rem;
            position: relative; background: #ffffff; transition: all 0.2s;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .address-card.default { border-color: var(--primary); background: #fdfcff; box-shadow: 0 4px 15px rgba(108, 99, 255, 0.08); }
        .address-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .address-title { font-weight: 800; font-size: 1.05rem; color: var(--text-dark); display: flex; align-items: center; gap: 0.5rem; }
        .default-tag { background: rgba(108, 99, 255, 0.12); color: var(--primary); padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 800; }
        .address-text { font-size: 0.9rem; color: #475569; margin-bottom: 0.5rem; line-height: 1.5; }
        .address-phone { font-size: 0.85rem; color: #64748b; font-weight: 700; margin-bottom: 1.25rem; }
        .address-actions { display: flex; gap: 0.5rem; border-top: 1px solid #f1f5f9; padding-top: 1rem; }
        .addr-btn {
            flex: 1; padding: 0.5rem; border-radius: 10px; font-size: 0.8rem; font-weight: 700;
            border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; cursor: pointer;
            transition: all 0.2s; font-family: 'Cairo', sans-serif;
        }
        .addr-btn:hover { background: #f1f5f9; color: var(--primary); border-color: var(--primary); }
        .addr-btn.del:hover { background: #fee2e2; color: #ef4444; border-color: #ef4444; }

        /* Wishlist Grid */
        .wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.25rem; }
        .wishlist-card {
            border: 2px solid #f1f5f9; border-radius: 18px; overflow: hidden;
            background: #ffffff; transition: all 0.25s; display: flex; flex-direction: column;
        }
        .wishlist-card:hover { border-color: var(--primary); transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .wishlist-img-box { position: relative; width: 100%; height: 180px; overflow: hidden; background: #f8fafc; }
        .wishlist-img-box img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
        .wishlist-card:hover .wishlist-img-box img { transform: scale(1.08); }
        .wishlist-del-btn {
            position: absolute; top: 0.75rem; left: 0.75rem; width: 34px; height: 34px;
            border-radius: 50%; background: rgba(255, 255, 255, 0.9); color: #ef4444;
            border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.2s;
        }
        .wishlist-del-btn:hover { background: #ef4444; color: white; transform: scale(1.1); }
        .wishlist-info { padding: 1rem; display: flex; flex-direction: column; flex: 1; justify-content: space-between; }
        .wishlist-name { font-size: 0.95rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.5rem; }
        .wishlist-price { font-size: 1.1rem; font-weight: 900; color: var(--primary); margin-bottom: 0.75rem; }
        .wishlist-link {
            display: block; text-align: center; padding: 0.6rem; background: #f8fafc;
            border: 1.5px solid var(--primary); color: var(--primary); border-radius: 12px;
            font-size: 0.85rem; font-weight: 700; text-decoration: none; transition: all 0.2s;
        }
        .wishlist-link:hover { background: var(--primary); color: white; }

        /* Empty State */
        .empty-state { text-align: center; padding: 4rem 1.5rem; color: #64748b; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 1rem; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .empty-state h3 { font-size: 1.25rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.5rem; }
        .empty-state p { font-size: 0.95rem; color: var(--text-muted); max-width: 350px; margin: 0 auto 1.5rem; }

        /* Danger Zone Box */
        .danger-zone {
            border: 2px solid #fecdd3; background: #fff1f2; border-radius: 18px;
            padding: 1.75rem; margin-top: 2rem; display: flex; justify-content: space-between;
            align-items: center; flex-wrap: wrap; gap: 1.25rem;
        }
        .danger-info h4 { font-size: 1.15rem; font-weight: 800; color: #9f1239; margin-bottom: 0.35rem; }
        .danger-info p { font-size: 0.9rem; color: #881337; }

        /* Modals (Order Timeline & Address & Deletion) */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(6px);
            display: none; align-items: center; justify-content: center;
            z-index: 1000; padding: 1rem; opacity: 0; transition: opacity 0.3s ease;
        }
        .modal-overlay.open { display: flex; opacity: 1; }
        .modal-box {
            background: white; border-radius: 24px; width: 100%; max-width: 650px;
            max-height: 90vh; overflow-y: auto; padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .modal-overlay.open .modal-box { transform: scale(1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0; }
        .modal-title { font-size: 1.35rem; font-weight: 900; color: var(--text-dark); }
        .modal-close { background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.25rem; cursor: pointer; color: #64748b; transition: all 0.2s; }
        .modal-close:hover { background: #e2e8f0; color: var(--text-dark); }

        /* Timeline Styles */
        .timeline-container { position: relative; padding: 1rem 0; margin-bottom: 2rem; }
        .timeline-container::before {
            content: ''; position: absolute; top: 1.5rem; bottom: 1.5rem; right: 19px;
            width: 3px; background: #e2e8f0; z-index: 1;
        }
        .timeline-step { position: relative; display: flex; gap: 1.25rem; margin-bottom: 1.75rem; z-index: 2; }
        .timeline-step:last-child { margin-bottom: 0; }
        .timeline-icon {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; font-weight: 900; flex-shrink: 0;
            background: #f1f5f9; color: #94a3b8; border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); transition: all 0.3s;
        }
        .timeline-step.completed .timeline-icon { background: #22c55e; color: white; }
        .timeline-step.current .timeline-icon {
            background: var(--primary); color: white;
            box-shadow: 0 0 0 6px rgba(108, 99, 255, 0.2);
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(108, 99, 255, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(108, 99, 255, 0); } 100% { box-shadow: 0 0 0 0 rgba(108, 99, 255, 0); } }
        
        .timeline-content { flex: 1; padding-top: 0.25rem; }
        .timeline-title { font-weight: 800; font-size: 1.05rem; color: var(--text-dark); margin-bottom: 0.2rem; }
        .timeline-step.completed .timeline-title { color: #16a34a; }
        .timeline-step.current .timeline-title { color: var(--primary); font-weight: 900; }
        .timeline-desc { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.25rem; }
        .timeline-date { font-size: 0.75rem; color: #94a3b8; font-weight: 700; }

        /* Order Items inside Modal */
        .order-modal-section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #e2e8f0; }
        .section-title { font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-dark); }
        .modal-item-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8fafc; border-radius: 12px; margin-bottom: 0.5rem; }
        .modal-item-left { display: flex; align-items: center; gap: 0.75rem; }
        .modal-item-img { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; background: white; border: 1px solid #e2e8f0; }
        .modal-item-name { font-weight: 700; font-size: 0.95rem; color: var(--text-dark); }
        .modal-item-meta { font-size: 0.8rem; color: var(--text-muted); }
        .modal-item-price { font-weight: 800; color: var(--primary); font-size: 1rem; }

        /* Toast Notification */
        .toast {
            position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%) translateY(20px);
            padding: 1rem 2rem; border-radius: 16px; z-index: 9999; font-weight: 700; font-size: 0.95rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); opacity: 0; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex; align-items: center; gap: 0.75rem; pointer-events: none;
        }
        .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .toast.success { background: #15803d; color: white; }
        .toast.error { background: #b91c1c; color: white; }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <div class="top-bar">
        <a href="/shop/">← العودة للمتجر</a>
        <div class="store-brand">🛍️ <span>{{ $tenant->name }}</span></div>
        <a href="/shop/cart.html" style="background: rgba(108,99,255,0.1); padding: 0.5rem 1.2rem; border-radius: 20px;">🛒 السلة</a>
    </div>

    <!-- Main Account Wrapper -->
    <div class="account-wrapper">
        <div class="page-header">
            <h1 class="page-title">👤 حسابي الإلكتـروني</h1>
        </div>

        <div class="account-grid">
            <!-- Sidebar Navigation -->
            <aside class="sidebar">
                <div class="user-avatar-box">
                    <div class="user-avatar" id="user-avatar">👤</div>
                </div>
                <h3 class="user-name" id="user-name">جاري التحميل...</h3>
                <p class="user-email" id="user-email"></p>
                <div class="user-phone-badge" id="user-phone-badge" style="display:none;"></div>
                <p class="user-since" id="user-since"></p>

                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-val" id="stat-orders">0</div>
                        <div class="stat-lbl">📦 طلباتي</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-val" id="stat-wishlist">0</div>
                        <div class="stat-lbl">❤️ المفضلة</div>
                    </div>
                </div>

                <div class="nav-divider"></div>

                <nav class="sidebar-nav">
                    <button class="nav-item active" onclick="showTab('orders', this)">
                        <span class="nav-item-left">📦 طلباتي وسجل الشراء</span>
                    </button>
                    <button class="nav-item" onclick="showTab('addresses', this)">
                        <span class="nav-item-left">📍 عناوين الشحن</span>
                    </button>
                    <button class="nav-item" onclick="showTab('wishlist', this)">
                        <span class="nav-item-left">❤️ قائمة الأمنيات</span>
                    </button>
                    <button class="nav-item" onclick="showTab('returns', this)">
                        <span class="nav-item-left">↩️ طلبات المرتجعات</span>
                    </button>
                    <button class="nav-item" onclick="showTab('profile', this)">
                        <span class="nav-item-left">👤 الملف الشخصي</span>
                    </button>
                    <button class="nav-item" onclick="showTab('password', this)">
                        <span class="nav-item-left">🔒 كلمة المرور والأمان</span>
                    </button>
                    <button class="nav-item danger-btn" onclick="showTab('account-settings', this)">
                        <span class="nav-item-left">⚠️ إدارة الحساب</span>
                    </button>

                    <div class="nav-divider"></div>

                    <form id="logout-form" method="POST" action="/logout" style="display:none;">@csrf</form>
                    <button type="button" class="nav-item logout-btn" onclick="document.getElementById('logout-form').submit()">
                        <span class="nav-item-left">🚪 تسجيل الخروج</span>
                    </button>
                </nav>
            </aside>

            <!-- Main Content Card -->
            <main class="content-card">

                <!-- TAB 1: ORDERS & TIMELINE -->
                <div class="tab-content active" id="tab-orders">
                    <div class="tab-header">
                        <h2 class="tab-title">📦 طلباتي وسجل الشراء</h2>
                    </div>

                    <!-- Filter Pills -->
                    <div class="filter-pills">
                        <button class="filter-pill active" onclick="filterOrders('all', this)">الكل</button>
                        <button class="filter-pill" onclick="filterOrders('pending', this)">⏳ قيد المراجعة</button>
                        <button class="filter-pill" onclick="filterOrders('confirmed', this)">👍 مؤكد</button>
                        <button class="filter-pill" onclick="filterOrders('processing', this)">📦 جاري التجهيز</button>
                        <button class="filter-pill" onclick="filterOrders('shipped', this)">🚚 في الشحن</button>
                        <button class="filter-pill" onclick="filterOrders('delivered', this)">✅ تم التوصيل</button>
                        <button class="filter-pill" onclick="filterOrders('cancelled', this)">❌ ملغي</button>
                    </div>

                    <div id="orders-list">
                        <div class="empty-state"><div class="icon">⏳</div><p>جاري تحميل طلباتك...</p></div>
                    </div>
                    <div id="load-more-orders" style="text-align:center; margin-top:1.5rem;"></div>
                </div>
                
                <!-- TAB 1.5: RETURN REQUESTS -->
                <div class="tab-content" id="tab-returns">
                    <div class="tab-header">
                        <h2 class="tab-title">↩️ طلبات الإرجاع والاستبدال</h2>
                    </div>
                    <div id="returns-list">
                        <div class="empty-state"><div class="icon">↩️</div><p>جاري تحميل المرتجعات...</p></div>
                    </div>
                </div>

                <!-- TAB 2: SHIPPING ADDRESSES -->
                <div class="tab-content" id="tab-addresses">
                    <div class="tab-header">
                        <h2 class="tab-title">📍 عناوين الشحن المحفوظة</h2>
                        <button type="button" class="btn-primary" style="padding:0.6rem 1.25rem; font-size:0.9rem;" onclick="openAddressModal()">➕ إضافة عنوان جديد</button>
                    </div>
                    <div id="addresses-list" class="addresses-grid">
                        <div class="empty-state" style="grid-column: 1/-1;"><div class="icon">⏳</div><p>جاري تحميل العناوين...</p></div>
                    </div>
                </div>

                <!-- TAB 3: WISHLIST -->
                <div class="tab-content" id="tab-wishlist">
                    <div class="tab-header">
                        <h2 class="tab-title">❤️ قائمة الأمنيات والمفضلة</h2>
                    </div>
                    <div id="account-wishlist">
                        <div class="empty-state"><div class="icon">⏳</div><p>جاري تحميل قائمة أمنياتك...</p></div>
                    </div>
                </div>

                <!-- TAB 4: PROFILE -->
                <div class="tab-content" id="tab-profile">
                    <div class="tab-header">
                        <h2 class="tab-title">👤 تعديل البيانات الشخصية</h2>
                    </div>
                    <form onsubmit="updateProfile(event)" id="profile-form">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label class="form-label">الاسم الكامل</label>
                                <input type="text" id="profile-name" class="form-input" required placeholder="ادخل اسمك الكامل">
                            </div>
                            <div class="form-group">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" id="profile-email" class="form-input" required placeholder="example@email.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">رقم الهاتف الأساسي</label>
                                <input type="text" id="profile-phone" class="form-input" placeholder="01xxxxxxxxx">
                            </div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <button type="submit" class="btn-primary" id="save-profile-btn">💾 حفظ التعديلات</button>
                        </div>
                    </form>
                </div>

                <!-- TAB 5: PASSWORD -->
                <div class="tab-content" id="tab-password">
                    <div class="tab-header">
                        <h2 class="tab-title">🔒 تغيير كلمة المرور والأمان</h2>
                    </div>
                    <form onsubmit="updatePassword(event)" style="max-width: 480px;">
                        <div class="form-group">
                            <label class="form-label">كلمة المرور الحالية</label>
                            <input type="password" id="current-password" class="form-input" required placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label class="form-label">كلمة المرور الجديدة</label>
                            <input type="password" id="new-password" class="form-input" required minlength="8" placeholder="8 أحرف على الأقل">
                        </div>
                        <div class="form-group">
                            <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                            <input type="password" id="new-password-confirmation" class="form-input" required minlength="8" placeholder="••••••••">
                        </div>
                        <div style="margin-top: 1.5rem;">
                            <button type="submit" class="btn-primary">🔑 تحديث كلمة المرور</button>
                        </div>
                    </form>
                </div>

                <!-- TAB 6: ACCOUNT MANAGEMENT (DELETION) -->
                <div class="tab-content" id="tab-account-settings">
                    <div class="tab-header">
                        <h2 class="tab-title" style="color: #e11d48;">⚠️ إدارة وحذف الحساب</h2>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">يمكنك من هنا إدارة حالة حسابك في متجر {{ $tenant->name }}. يرجى الملاحظة أن بعض الإجراءات لا يمكن التراجع عنها.</p>
                    
                    <div class="danger-zone">
                        <div class="danger-info">
                            <h4>حذف الحساب نهائياً</h4>
                            <p>سيتم حذف جميع بياناتك الشخصية، العناوين المحفوظة، وسجل طلباتك نهائياً من هذا المتجر.</p>
                        </div>
                        <button type="button" class="btn-danger" onclick="openDeleteModal()">🗑️ حذف حسابي نهائياً</button>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL 1: ORDER TIMELINE & DETAILS -->
    <div class="modal-overlay" id="order-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-order-title">#XXXXX تفاصيل وتتبع الطلب</h3>
                <button type="button" class="modal-close" onclick="closeModal('order-modal')">✕</button>
            </div>
            
            <div id="modal-order-body">
                <!-- Timeline will be dynamically injected here -->
            </div>
        </div>
    </div>

    <!-- MODAL 2: ADD/EDIT ADDRESS -->
    <div class="modal-overlay" id="address-modal">
        <div class="modal-box" style="max-width: 520px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-address-title">➕ إضافة عنوان شحن جديد</h3>
                <button type="button" class="modal-close" onclick="closeModal('address-modal')">✕</button>
            </div>
            <form onsubmit="saveAddress(event)">
                <input type="hidden" id="addr-id">
                <div class="form-group">
                    <label class="form-label">عنوان مخصص (مثال: المنزل، العمل)</label>
                    <input type="text" id="addr-title" class="form-input" required placeholder="المنزل">
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">المحافظة / المدينة</label>
                        <select id="addr-gov" class="form-input" required></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">رقم هاتف المستلم</label>
                        <input type="text" id="addr-phone" class="form-input" required placeholder="01xxxxxxxxx">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">العنوان بالتفصيل (الشارع، رقم العقار، الدور)</label>
                    <textarea id="addr-text" class="form-input" required rows="3" placeholder="اكتب العنوان بالتفصيل لضمان سرعة التوصيل..."></textarea>
                </div>
                <div class="form-group" style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="addr-default" style="width:18px; height:18px;">
                    <label for="addr-default" style="font-weight:700; font-size:0.9rem; cursor:pointer;">⭐ تعيين كعنوان الشحن الافتراضي</label>
                </div>
                <div style="margin-top: 1.5rem; text-align: left;">
                    <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">💾 حفظ العنوان</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: ORDER RETURN REQUEST -->
    <div class="modal-overlay" id="return-modal">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-return-title">تقديم طلب إرجاع / استبدال</h3>
                <button type="button" class="modal-close" onclick="closeModal('return-modal')">✕</button>
            </div>
            <form onsubmit="submitReturnRequest(event)">
                <input type="hidden" id="return-order-id">
                
                <div class="form-group">
                    <label class="form-label" style="color: var(--primary); font-weight: bold; margin-bottom: 0.75rem;">🛍️ حدد المنتجات المراد إرجاعها والكمية:</label>
                    <div id="return-items-list" style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.75rem; max-height: 250px; overflow-y: auto; padding: 0.25rem;">
                        <!-- Will be dynamically populated with items -->
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1.25rem;">
                    <label class="form-label">📝 سبب الإرجاع بالتفصيل</label>
                    <textarea id="return-reason" class="form-input" required rows="3" placeholder="اكتب هنا سبب إرجاع المنتجات بالتفصيل..."></textarea>
                </div>

                <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                    <button type="button" class="btn-outline" style="flex: 1;" onclick="closeModal('return-modal')">تراجع</button>
                    <button type="submit" class="btn-primary" style="flex: 2; justify-content: center;">📤 إرسال طلب الإرجاع</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: ACCOUNT DELETION CONFIRMATION -->
    <div class="modal-overlay" id="delete-modal">
        <div class="modal-box" style="max-width: 480px; text-align: center;">
            <div style="font-size: 3.5rem; margin-bottom: 0.5rem;">⚠️</div>
            <h3 style="font-size: 1.35rem; font-weight: 900; color: #9f1239; margin-bottom: 0.75rem;">هل أنت متأكد من حذف حسابك؟</h3>
            <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.6;">هذا الإجراء نهائي ولا يمكن التراجع عنه. يرجى إدخال كلمة المرور الحالية لتأكيد هويتـك.</p>
            
            <form onsubmit="confirmDeleteAccount(event)">
                <div class="form-group" style="text-align: right;">
                    <label class="form-label">كلمة المرور الحالية للتأكيد</label>
                    <input type="password" id="delete-confirm-password" class="form-input" required placeholder="••••••••">
                </div>
                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn-outline" style="flex:1;" onclick="closeModal('delete-modal')">تراجع</button>
                    <button type="submit" class="btn-danger" style="flex:1;">نعم، احذف حسابي</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        var allOrders = [];
        var currentFilter = 'all';
        var ordersPage = 1;
        var userAddresses = [];
        var availableGovs = [];

        function showTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.nav-item').forEach(function(b) { b.classList.remove('active'); });
            document.getElementById('tab-' + tabId).classList.add('active');
            if (btn) btn.classList.add('active');
            if (tabId === 'orders') { ordersPage = 1; loadOrders(1); }
            if (tabId === 'returns') loadReturns();
            if (tabId === 'wishlist') loadAccountWishlist();
            if (tabId === 'addresses') renderAddresses();
        }

        function loadProfile() {
            fetch('/api/account/profile')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) { window.location.href = '/login'; return; }
                    var u = data.user;
                    document.getElementById('user-name').textContent = u.name || 'مستخدم';
                    document.getElementById('user-email').textContent = u.email || '';
                    document.getElementById('user-since').textContent = 'عضو منذ ' + (u.created_at || '');
                    
                    if (u.phone) {
                        var badge = document.getElementById('user-phone-badge');
                        badge.textContent = '📞 ' + u.phone;
                        badge.style.display = 'table';
                        document.getElementById('profile-phone').value = u.phone;
                    }

                    document.getElementById('profile-name').value = u.name || '';
                    document.getElementById('profile-email').value = u.email || '';
                    var initial = u.name ? u.name.charAt(0).toUpperCase() : '👤';
                    document.getElementById('user-avatar').textContent = initial;

                    document.getElementById('stat-orders').textContent = u.orders_count || 0;
                    document.getElementById('stat-wishlist').textContent = u.wishlist_count || 0;

                    userAddresses = data.addresses || [];
                    availableGovs = data.governorates || [];
                    renderAddresses();
                })
                .catch(function() { window.location.href = '/login'; });
        }

        function loadOrders(page) {
            page = page || 1;
            fetch('/api/account/orders?page=' + page)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var container = document.getElementById('orders-list');
                    if (!data.orders || data.orders.length === 0) {
                        if (page === 1) {
                            container.innerHTML = '<div class="empty-state"><div class="icon">📦</div><h3>لا توجد طلبات بعد</h3><p>لم تقم بإجراء أي طلب من المتجر حتى الآن.</p><a href="/shop/" class="btn-primary">🛍️ تصفح المنتجات</a></div>';
                        }
                        document.getElementById('load-more-orders').innerHTML = '';
                        return;
                    }
                    
                    if (page === 1) allOrders = data.orders;
                    else allOrders = allOrders.concat(data.orders);

                    renderOrdersList();

                    var loadMoreEl = document.getElementById('load-more-orders');
                    if (data.has_more) {
                        var nextPage = page + 1;
                        loadMoreEl.innerHTML = '<button class="btn-outline" onclick="loadOrders(' + nextPage + ')">⌛ تحميل المزيد من الطلبات</button>';
                    } else {
                        loadMoreEl.innerHTML = '';
                    }
                })
                .catch(function() {
                    document.getElementById('orders-list').innerHTML = '<div class="empty-state"><div class="icon">⚠️</div><h3>حدث خطأ أثناء تحميل الطلبات</h3><p>يرجى إعادة المحاولة لاحقاً</p></div>';
                });
        }

        function filterOrders(status, btn) {
            currentFilter = status;
            document.querySelectorAll('.filter-pill').forEach(function(p) { p.classList.remove('active'); });
            btn.classList.add('active');
            renderOrdersList();
        }

        function renderOrdersList() {
            var container = document.getElementById('orders-list');
            var filtered = allOrders.filter(function(o) {
                return currentFilter === 'all' || o.status === currentFilter;
            });

            if (filtered.length === 0) {
                container.innerHTML = '<div class="empty-state" style="padding:2rem;"><div class="icon" style="font-size:2.5rem;">📭</div><p>لا توجد طلبات تطابق هذا التصنيف</p></div>';
                return;
            }

            container.innerHTML = filtered.map(function(o) {
                var itemsPreview = '';
                if (o.items && o.items.length > 0) {
                    itemsPreview = o.items.slice(0, 3).map(function(it) {
                        return '<span style="background:#f1f5f9; padding:0.25rem 0.6rem; border-radius:8px; font-size:0.82rem; font-weight:700;">' + it.name + ' × ' + (it.quantity || it.qty || 1) + '</span>';
                    }).join('');
                    if (o.items.length > 3) {
                        itemsPreview += '<span style="color:var(--text-muted); font-size:0.8rem; font-weight:700;">+' + (o.items.length - 3) + ' أخرى</span>';
                    }
                } else {
                    itemsPreview = '<span>' + o.items_count + ' منتجات</span>';
                }

                return '<div class="order-card">' +
                    '<div class="order-header">' +
                        '<div class="order-num-box">' +
                            '<span class="order-num">#' + o.order_number + '</span>' +
                            '<span class="order-date">📅 ' + o.created_at + '</span>' +
                        '</div>' +
                        '<span class="status-badge" style="background:' + o.status_label.bg + '; color:' + o.status_label.color + ';">' + (o.status_label.icon || '') + ' ' + o.status_label.text + '</span>' +
                    '</div>' +
                    '<div class="order-body">' +
                        '<div class="order-items-preview">' + itemsPreview + '</div>' +
                        '<div class="order-total-box">' +
                            '<div class="order-total-lbl">الإجمالي:</div>' +
                            '<div class="order-total-val">' + o.total + ' ج.م</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="order-footer">' +
                        '<span style="font-size:0.85rem; color:#64748b; font-weight:700;">' + (o.payment_method_label || '💵 الدفع عند الاستلام') + '</span>' +
                        '<div style="display:flex; gap:0.5rem; flex-wrap:wrap;">' +
                            '<button type="button" class="btn-track" onclick="openOrderModal(' + o.id + ')">👁️ تتبع الحالة والجدول الزمني</button>' +
                            '<a href="/track-order?order=' + o.order_number + '" class="btn-track" style="text-decoration:none; color:var(--primary); border-color:var(--primary);">🔗 تتبع مباشر</a>' +
                            (o.status === 'delivered' ? '<button type="button" class="btn-track" style="color:#ef4444; border-color:#fecdd3; background:#fff5f5;" onclick="openReturnModal(' + o.id + ')">↩️ طلب إرجاع / استبدال</button>' : '') +
                        '</div>' +
                    '</div>' +
                '</div>';
            }).join('');
        }

        function openOrderModal(orderId) {
            var o = allOrders.find(function(item) { return item.id === orderId; });
            if (!o) return;

            document.getElementById('modal-order-title').textContent = '#' + o.order_number + ' تفاصيل وتتبع الطلب';

            // Timeline HTML
            var timelineHtml = '<div class="timeline-container">' + (o.timeline || []).map(function(t) {
                var stepClass = t.completed ? (t.current ? 'timeline-step current' : 'timeline-step completed') : 'timeline-step';
                var iconText  = t.completed ? (t.current ? '🔵' : '✓') : '⏳';
                if (t.is_cancelled) iconText = '✕';
                return '<div class="' + stepClass + '">' +
                    '<div class="timeline-icon">' + iconText + '</div>' +
                    '<div class="timeline-content">' +
                        '<div class="timeline-title">' + t.title + '</div>' +
                        '<div class="timeline-desc">' + t.desc + '</div>' +
                        '<div class="timeline-date">' + (t.date || '') + '</div>' +
                    '</div>' +
                '</div>';
            }).join('') + '</div>';

            // Items HTML
            var itemsHtml = '<div class="order-modal-section"><h4 class="section-title">🛍️ منتجات الطلب (' + o.items_count + ')</h4>';
            if (o.items && o.items.length > 0) {
                itemsHtml += o.items.map(function(it) {
                    var img = it.image || '/shop/placeholder.jpg';
                    var meta = [];
                    if (it.selectedSize) meta.push('المقاس: ' + it.selectedSize);
                    if (it.selectedColor) meta.push('اللون: ' + it.selectedColor);
                    return '<div class="modal-item-row">' +
                        '<div class="modal-item-left">' +
                            '<img src="' + img + '" class="modal-item-img" onerror="this.src=\'/shop/placeholder.jpg\'">' +
                            '<div>' +
                                '<div class="modal-item-name">' + it.name + '</div>' +
                                '<div class="modal-item-meta">' + meta.join(' · ') + '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div style="text-align:left;">' +
                            '<div class="modal-item-price">' + parseFloat(it.price || 0).toFixed(0) + ' ج.م</div>' +
                            '<div style="font-size:0.8rem; color:#64748b; font-weight:700;">الكمية: ' + (it.quantity || it.qty || 1) + '</div>' +
                        '</div>' +
                    '</div>';
                }).join('');
            } else {
                itemsHtml += '<p style="color:var(--text-muted); font-size:0.9rem;">تفاصيل المنتجات الفردية غير متاحة في هذا السجل</p>';
            }
            itemsHtml += '</div>';

            // Shipping & Payment Summary
            var summaryHtml = '<div class="order-modal-section"><h4 class="section-title">📍 بيانات التوصيل والدفع</h4>' +
                '<div style="background:#f8fafc; padding:1.25rem; border-radius:16px; font-size:0.9rem; line-height:1.8;">' +
                    '<p><strong>الاسم:</strong> ' + (o.customer_name || 'غير محدد') + '</p>' +
                    '<p><strong>رقم الهاتف:</strong> ' + (o.customer_phone || 'غير محدد') + '</p>' +
                    '<p><strong>العنوان:</strong> ' + (o.shipping_address || '') + ' (' + (o.governorate || '') + ')</p>' +
                    '<p><strong>طريقة الدفع:</strong> ' + (o.payment_method_label || 'الدفع عند الاستلام') + '</p>' +
                    '<div style="height:1px; background:#e2e8f0; margin:0.75rem 0;"></div>' +
                    '<div style="display:flex; justify-content:space-between;"><span>المجموع الفرعي:</span><span>' + o.subtotal + ' ج.م</span></div>' +
                    '<div style="display:flex; justify-content:space-between;"><span>رسوم الشحن:</span><span>' + o.shipping_cost + ' ج.م</span></div>' +
                    '<div style="display:flex; justify-content:space-between; font-weight:900; font-size:1.1rem; color:var(--primary); margin-top:0.5rem;"><span>الإجمالي الكلي:</span><span>' + o.total + ' ج.م</span></div>' +
                '</div></div>';

            document.getElementById('modal-order-body').innerHTML = timelineHtml + itemsHtml + summaryHtml;
            document.getElementById('order-modal').classList.add('open');
        }

        // Addresses Functions
        function renderAddresses() {
            var container = document.getElementById('addresses-list');
            if (!userAddresses || userAddresses.length === 0) {
                container.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><div class="icon">📍</div><h3>لا توجد عناوين شحن محفوظة</h3><p>أضف عنوان شحن جديد لتسريع إتمام طلباتك القادمة بكل سهولة.</p><button type="button" class="btn-primary" onclick="openAddressModal()">➕ إضافة عنوان جديد</button></div>';
                return;
            }

            container.innerHTML = userAddresses.map(function(a) {
                return '<div class="address-card ' + (a.is_default ? 'default' : '') + '">' +
                    '<div>' +
                        '<div class="address-header">' +
                            '<span class="address-title">🏠 ' + (a.title || 'عنوان شحن') + '</span>' +
                            (a.is_default ? '<span class="default-tag">⭐ الافتراضي</span>' : '') +
                        '</div>' +
                        '<p class="address-text">' + (a.address || '') + ' · <strong>' + (a.governorate || '') + '</strong></p>' +
                        '<p class="address-phone">📞 ' + (a.phone || '') + '</p>' +
                    '</div>' +
                    '<div class="address-actions">' +
                        '<button type="button" class="addr-btn" onclick="editAddress(\'' + a.id + '\')">✏️ تعديل</button>' +
                        (!a.is_default ? '<button type="button" class="addr-btn" onclick="setDefaultAddress(\'' + a.id + '\')">⭐ افتراضي</button>' : '') +
                        '<button type="button" class="addr-btn del" onclick="deleteAddress(\'' + a.id + '\')">🗑️ حذف</button>' +
                    '</div>' +
                '</div>';
            }).join('');
        }

        function openAddressModal(addrId) {
            var select = document.getElementById('addr-gov');
            if (availableGovs && availableGovs.length > 0) {
                select.innerHTML = '<option value="">اختر المحافظة / المدينة</option>' + availableGovs.map(function(g) {
                    return '<option value="' + g.name + '">' + g.name + ' (' + g.price + ' ج.م)</option>';
                }).join('');
            } else {
                select.innerHTML = '<option value="القاهرة">القاهرة</option><option value="الجيزة">الجيزة</option><option value="الإسكندرية">الإسكندرية</option><option value="أخرى">أخرى</option>';
            }

            if (addrId) {
                var a = userAddresses.find(function(item) { return item.id === addrId; });
                if (a) {
                    document.getElementById('modal-address-title').textContent = '✏️ تعديل عنوان الشحن';
                    document.getElementById('addr-id').value = a.id;
                    document.getElementById('addr-title').value = a.title || '';
                    document.getElementById('addr-gov').value = a.governorate || '';
                    document.getElementById('addr-phone').value = a.phone || '';
                    document.getElementById('addr-text').value = a.address || '';
                    document.getElementById('addr-default').checked = !!a.is_default;
                }
            } else {
                document.getElementById('modal-address-title').textContent = '➕ إضافة عنوان شحن جديد';
                document.getElementById('addr-id').value = '';
                document.getElementById('addr-title').value = 'المنزل';
                document.getElementById('addr-gov').value = '';
                document.getElementById('addr-phone').value = document.getElementById('profile-phone').value || '';
                document.getElementById('addr-text').value = '';
                document.getElementById('addr-default').checked = userAddresses.length === 0;
            }

            document.getElementById('address-modal').classList.add('open');
        }

        function editAddress(id) { openAddressModal(id); }

        function saveAddress(e) {
            e.preventDefault();
            var csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch('/api/account/profile', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    action: 'save_address',
                    id: document.getElementById('addr-id').value,
                    title: document.getElementById('addr-title').value,
                    governorate: document.getElementById('addr-gov').value,
                    phone: document.getElementById('addr-phone').value,
                    address: document.getElementById('addr-text').value,
                    is_default: document.getElementById('addr-default').checked
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                showToast(data.message || 'تم حفظ العنوان بنجاح', data.success);
                if (data.success) {
                    userAddresses = data.addresses || [];
                    renderAddresses();
                    closeModal('address-modal');
                }
            });
        }

        function deleteAddress(id) {
            if (!confirm('هل أنت متأكد من رغبتك في حذف هذا العنوان؟')) return;
            var csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch('/api/account/profile', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ action: 'delete_address', id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                showToast(data.message || 'تم الحذف بنجاح', data.success);
                if (data.success) {
                    userAddresses = data.addresses || [];
                    renderAddresses();
                }
            });
        }

        function setDefaultAddress(id) {
            var csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch('/api/account/profile', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ action: 'set_default_address', id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                showToast(data.message || 'تم تعيين الافتراضي', data.success);
                if (data.success) {
                    userAddresses = data.addresses || [];
                    renderAddresses();
                }
            });
        }

        // Wishlist Functions
        function loadAccountWishlist() {
            fetch('/api/wishlist')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var container = document.getElementById('account-wishlist');
                    if (!data.items || data.items.length === 0) {
                        container.innerHTML = '<div class="empty-state"><div class="icon">❤️</div><h3>قائمتك المفضلة فارغة</h3><p>لم تقم بإضافة أي منتجات لقائمة أمنياتك بعد.</p><a href="/shop/" class="btn-primary">🛍️ تصفح المتجر الآن</a></div>';
                        return;
                    }
                    container.innerHTML = '<div class="wishlist-grid">' + data.items.map(function(item) {
                        return '<div class="wishlist-card">' +
                            '<div class="wishlist-img-box">' +
                                '<img src="' + (item.image || '/shop/placeholder.jpg') + '" alt="' + item.name + '" onerror="this.src=\'/shop/placeholder.jpg\'">' +
                                '<button type="button" class="wishlist-del-btn" title="إزالة من المفضلة" onclick="removeWishitem(' + item.product_id + ', this)">🗑️</button>' +
                            '</div>' +
                            '<div class="wishlist-info">' +
                                '<div>' +
                                    '<div class="wishlist-name">' + item.name + '</div>' +
                                    '<div class="wishlist-price">' + parseFloat(item.price).toFixed(0) + ' ج.م</div>' +
                                '</div>' +
                                '<a href="/shop/product.html?id=' + item.product_id + '" class="wishlist-link">🛒 عرض المنتج بالمتجر</a>' +
                            '</div>' +
                        '</div>';
                    }).join('') + '</div>';
                    
                    document.getElementById('stat-wishlist').textContent = data.items.length;
                })
                .catch(function() {
                    document.getElementById('account-wishlist').innerHTML = '<div class="empty-state"><div class="icon">⚠️</div><h3>حدث خطأ أثناء تحميل المفضلة</h3></div>';
                });
        }

        function removeWishitem(productId, btn) {
            var csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch('/api/wishlist/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ product_id: productId })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast('تمت الإزالة من المفضلة', true);
                    loadAccountWishlist();
                }
            });
        }

        // Profile & Password & Delete Account
        function updateProfile(e) {
            e.preventDefault();
            var csrf = document.querySelector('meta[name=csrf-token]').content;
            var btn = document.getElementById('save-profile-btn');
            btn.disabled = true; btn.textContent = '⏳ جاري الحفظ...';

            fetch('/api/account/profile', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    action: 'update_profile',
                    name: document.getElementById('profile-name').value,
                    email: document.getElementById('profile-email').value,
                    phone: document.getElementById('profile-phone').value
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false; btn.textContent = '💾 حفظ التعديلات';
                showToast(data.message || 'تم التحديث بنجاح', data.success);
                if (data.success) loadProfile();
            });
        }

        function updatePassword(e) {
            e.preventDefault();
            var newPass = document.getElementById('new-password').value;
            var confirmPass = document.getElementById('new-password-confirmation').value;
            if (newPass !== confirmPass) { showToast('كلمتا المرور غير متطابقتين', false); return; }

            var csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch('/api/account/password', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    current_password: document.getElementById('current-password').value,
                    new_password: newPass,
                    new_password_confirmation: confirmPass
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                showToast(data.message || 'تم تحديث كلمة المرور', data.success);
                if (data.success) {
                    document.getElementById('current-password').value = '';
                    document.getElementById('new-password').value = '';
                    document.getElementById('new-password-confirmation').value = '';
                }
            });
        }

        function openDeleteModal() { document.getElementById('delete-modal').classList.add('open'); }

        function confirmDeleteAccount(e) {
            e.preventDefault();
            var csrf = document.querySelector('meta[name=csrf-token]').content;
            var pass = document.getElementById('delete-confirm-password').value;

            fetch('/api/account/profile', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ action: 'delete_account', password: pass })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast('تم حذف الحساب بنجاح... جاري التوجيه', true);
                    setTimeout(function() { window.location.href = data.redirect || '/'; }, 1500);
                } else {
                    showToast(data.message || 'حدث خطأ أثناء الحذف', false);
                }
            });
        }

        var allReturns = [];

        function loadReturns() {
            var container = document.getElementById('returns-list');
            fetch('/api/account/returns')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.returns || data.returns.length === 0) {
                        container.innerHTML = '<div class="empty-state"><div class="icon">↩️</div><h3>لا توجد طلبات إرجاع</h3><p>لم تقم بتقديم أي طلبات إرجاع أو استبدال للمنتجات بعد.</p></div>';
                        return;
                    }
                    allReturns = data.returns;
                    container.innerHTML = data.returns.map(function(r) {
                        var itemsHtml = (r.items || []).map(function(it) {
                            var sizeColor = [];
                            if (it.selectedSize) sizeColor.push('المقاس: ' + it.selectedSize);
                            if (it.selectedColor) sizeColor.push('اللون: ' + it.selectedColor);
                            var meta = sizeColor.length > 0 ? ' <small style="color:#64748b">(' + sizeColor.join(' - ') + ')</small>' : '';
                            return '<div style="font-size:0.9rem; font-weight:700; margin-bottom:0.25rem;">' +
                                it.name + meta + ' × ' + it.quantity + ' <span style="color:var(--primary)">(' + it.price + ' ج.م)</span>' +
                            '</div>';
                        }).join('');

                        var badgeBg = '#f1f5f9';
                        var badgeColor = '#475569';
                        if (r.status === 'pending') { badgeBg = '#fef3c7'; badgeColor = '#d97706'; }
                        else if (r.status === 'approved') { badgeBg = '#dbeafe'; badgeColor = '#2563eb'; }
                        else if (r.status === 'completed') { badgeBg = '#dcfce7'; badgeColor = '#16a34a'; }
                        else if (r.status === 'rejected') { badgeBg = '#fee2e2'; badgeColor = '#dc2626'; }

                        return '<div class="order-card">' +
                            '<div class="order-header">' +
                                '<div class="order-num-box">' +
                                    '<span class="order-num">طلب إرجاع لطلب #' + r.order_number + '</span>' +
                                    '<span class="order-date">📅 ' + r.created_at + '</span>' +
                                '</div>' +
                                '<span class="status-badge" style="background:' + badgeBg + '; color:' + badgeColor + ';">' + r.status_label + '</span>' +
                            '</div>' +
                            '<div style="padding:1rem 0; border-top:1px dashed #e2e8f0; border-bottom:1px dashed #e2e8f0; margin-bottom:1rem;">' +
                                '<div style="margin-bottom:0.75rem;">' + itemsHtml + '</div>' +
                                '<div style="font-size:0.88rem; color:#475569;"><strong style="color:var(--text-dark)">سبب الإرجاع:</strong> ' + r.reason + '</div>' +
                                (r.notes ? '<div style="font-size:0.88rem; color:#b45309; margin-top:0.5rem; background:#fffbeb; padding:0.5rem 0.75rem; border-radius:8px; border:1px solid #fde68a;"><strong>ملاحظات التاجر:</strong> ' + r.notes + '</div>' : '') +
                            '</div>' +
                            '<div class="order-footer">' +
                                '<div class="order-total-box">' +
                                    '<div class="order-total-lbl">المبلغ المسترد المتوقع:</div>' +
                                    '<div class="order-total-val">' + r.refund_amount + ' ج.م</div>' +
                                </div>' +
                            '</div>' +
                        '</div>';
                    }).join('');
                })
                .catch(function() {
                    container.innerHTML = '<div class="empty-state"><div class="icon">⚠️</div><h3>حدث خطأ أثناء تحميل المرتجعات</h3></div>';
                });
        }

        function openReturnModal(orderId) {
            var o = allOrders.find(function(item) { return item.id === orderId; });
            if (!o) return;

            document.getElementById('return-order-id').value = orderId;
            document.getElementById('return-reason').value = '';
            
            var itemsList = document.getElementById('return-items-list');
            itemsList.innerHTML = (o.items || []).map(function(it, idx) {
                var sizeColor = [];
                if (it.selectedSize) sizeColor.push('المقاس: ' + it.selectedSize);
                if (it.selectedColor) sizeColor.push('اللون: ' + it.selectedColor);
                var meta = sizeColor.length > 0 ? ' (' + sizeColor.join(' - ') + ')' : '';
                var maxQty = it.quantity || it.qty || 1;
                
                var qtySelect = '<select class="form-input" style="width:70px; padding:0.25rem; font-size:0.85rem; border-radius:8px; border:1.5px solid #cbd5e1;" data-max="' + maxQty + '">';
                for(var q = 1; q <= maxQty; q++) {
                    qtySelect += '<option value="' + q + '">' + q + '</option>';
                }
                qtySelect += '</select>';

                return '<div style="display:flex; align-items:center; justify-content:between; gap:0.5rem; background:#f8fafc; padding:0.6rem 1rem; border-radius:12px; border:1px solid #e2e8f0;" class="return-item-row" data-id="' + it.id + '" data-size="' + (it.selectedSize || '') + '" data-color="' + (it.selectedColor || '') + '">' +
                    '<div style="display:flex; align-items:center; gap:0.5rem; flex:1;">' +
                        '<input type="checkbox" style="width:18px; height:18px;" class="return-item-checkbox">' +
                        '<span style="font-size:0.88rem; font-weight:700; color:var(--text-dark);">' + it.name + '<small style="color:#64748b; font-weight:normal;">' + meta + '</small></span>' +
                    '</div>' +
                    '<div style="display:flex; align-items:center; gap:0.5rem;">' +
                        '<span style="font-size:0.8rem; color:#64748b;">الكمية:</span>' +
                        qtySelect +
                    '</div>' +
                '</div>';
            }).join('');

            document.getElementById('return-modal').classList.add('open');
        }

        function submitReturnRequest(e) {
            e.preventDefault();
            var orderId = document.getElementById('return-order-id').value;
            var reason = document.getElementById('return-reason').value;
            var rows = document.querySelectorAll('.return-item-row');
            var items = [];

            rows.forEach(function(row) {
                var checkbox = row.querySelector('.return-item-checkbox');
                if (checkbox.checked) {
                    var select = row.querySelector('select');
                    items.push({
                        id: row.getAttribute('data-id'),
                        quantity: parseInt(select.value),
                        selectedSize: row.getAttribute('data-size') || null,
                        selectedColor: row.getAttribute('data-color') || null
                    });
                }
            });

            if (items.length === 0) {
                showToast('يرجى تحديد منتج واحد على الأقل لإرجاعه', false);
                return;
            }

            var csrf = document.querySelector('meta[name=csrf-token]').content;
            
            fetch('/api/account/returns', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    order_id: parseInt(orderId),
                    reason: reason,
                    items: items
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast(data.message, true);
                    closeModal('return-modal');
                    showTab('returns', document.querySelector('[onclick="showTab(\'returns\', this)"]'));
                    loadOrders(1);
                } else {
                    showToast(data.message || 'حدث خطأ أثناء تقديم الطلب', false);
                }
            })
            .catch(function() {
                showToast('حدث خطأ بالاتصال بالخادم', false);
            });
        }

        function closeModal(id) { document.getElementById(id).classList.remove('open'); }

        function showToast(msg, success) {
            var toast = document.createElement('div');
            toast.className = 'toast ' + (success ? 'success' : 'error');
            toast.innerHTML = (success ? '✅ ' : '❌ ') + msg;
            document.body.appendChild(toast);
            setTimeout(function() { toast.classList.add('show'); }, 50);
            setTimeout(function() {
                toast.classList.remove('show');
                setTimeout(function() { toast.remove(); }, 300);
            }, 3500);
        }

        // Initialize App
        loadProfile();
        loadOrders(1);
    </script>
</body>
</html>
