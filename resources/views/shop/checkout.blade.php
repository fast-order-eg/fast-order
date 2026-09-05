<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $storeName = \App\Models\Setting::get('store_name') ?: ($tenant->name ?? 'المتجر');
        $storeLogo = \App\Models\Setting::get('logo') ? asset('storage/' . \App\Models\Setting::get('logo')) : ($tenant->logo ? asset('storage/' . $tenant->logo) : asset('images/logo.png'));
    @endphp
    <title>إتمام الطلب - {{ $storeName }}</title>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="إتمام الطلب - {{ $storeName }}">
    <meta property="og:image" content="{{ $storeLogo }}">
    <link rel="stylesheet" href="/shop/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary:   {{ $theme['primary_color'] }};
            --secondary: {{ $theme['secondary_color'] }};
            --font:      '{{ $theme['font_family'] }}', 'Cairo', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font);
            background: #f4f6ff;
            color: #1a1a2e;
            min-height: 100vh;
        }

        /* ─── Top bar ─── */
        .topbar {
            background: var(--primary);
            padding: 0.85rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar-brand {
            font-size: 1.3rem;
            font-weight: 900;
            color: #fff;
            text-decoration: none;
        }
        .topbar-back {
            color: rgba(255,255,255,0.85);
            font-size: 0.88rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.2s;
        }
        .topbar-back:hover { color: #fff; }

        /* ─── Steps ─── */
        .steps-bar {
            background: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: center;
            gap: 0;
            box-shadow: 0 1px 0 #eee;
        }
        .step-item {
            display: flex;
            align-items: center;
            font-size: 0.82rem;
            color: #bbb;
            font-weight: 600;
        }
        .step-item.active { color: var(--primary); }
        .step-item.done   { color: #22c55e; }
        .step-bubble {
            width: 28px; height: 28px;
            border-radius: 50%;
            border: 2px solid currentColor;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700;
            margin-left: 0.4rem;
            flex-shrink: 0;
        }
        .step-item.active .step-bubble { background: var(--primary); color: #fff; border-color: var(--primary); }
        .step-item.done   .step-bubble { background: #22c55e; color: #fff; border-color: #22c55e; }
        .step-sep { width: 48px; height: 2px; background: #e5e7eb; margin: 0 0.5rem; }
        .step-sep.done-sep { background: #22c55e; }

        /* ─── Layout ─── */
        .checkout-wrapper {
            max-width: 1120px;
            margin: 2rem auto;
            padding: 0 1rem 3rem;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }
        @media (max-width: 800px) {
            .checkout-wrapper { grid-template-columns: 1fr; }
            .order-sidebar { order: -1; }
        }

        /* ─── Cards ─── */
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            margin-bottom: 1.5rem;
        }
        .card:last-child { margin-bottom: 0; }
        .card-title {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ─── Form ─── */
        .form-group { margin-bottom: 1.1rem; }
        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            color: #555;
        }
        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-family: var(--font);
            font-size: 0.92rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fafbff;
            color: #1a1a2e;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 15%, transparent);
            background: #fff;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } }

        /* ─── Payment options ─── */
        .payment-grid { display: grid; gap: 0.75rem; }
        .pay-opt {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.9rem 1.1rem;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }
        .pay-opt:hover    { border-color: var(--primary); background: color-mix(in srgb, var(--primary) 5%, #fff); }
        .pay-opt.selected { border-color: var(--primary); background: color-mix(in srgb, var(--primary) 8%, #fff); }
        /* Focus visible indicators for WCAG 2.1 AA Compliance */
        *:focus-visible {
            outline: 3px solid var(--primary) !important;
            outline-offset: 2px !important;
        }
        .pay-opt input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .pay-opt:focus-within {
            outline: 3px solid var(--primary) !important;
            outline-offset: 2px !important;
            border-color: var(--primary) !important;
        }
        .pay-icon { font-size: 1.6rem; flex-shrink: 0; }
        .pay-info h4 { font-size: 0.92rem; font-weight: 700; margin-bottom: 0.2rem; }
        .pay-info p  { font-size: 0.78rem; color: #888; margin: 0; }
        .transfer-note {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 12px;
            padding: 0.9rem 1rem;
            font-size: 0.85rem;
            margin-top: 0.75rem;
            display: none;
        }
        .transfer-note.show { display: block; }

        /* ─── Terms ─── */
        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-top: 1rem;
            font-size: 0.82rem;
            color: #666;
        }
        .terms-row a { color: var(--primary); }
        .terms-row input { margin-top: 0.2rem; flex-shrink: 0; }

        /* ─── Order sidebar ─── */
        .sidebar-sticky {
            position: sticky;
            top: 80px;
        }
        .order-item-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .order-item-row:last-child { border-bottom: none; }
        .item-thumb {
            width: 52px; height: 52px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f0f0f0;
        }
        .item-name { font-size: 0.85rem; font-weight: 600; flex: 1; }
        .item-qty  { font-size: 0.78rem; color: #888; }
        .item-price { font-size: 0.88rem; font-weight: 700; color: var(--primary); white-space: nowrap; }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            padding: 0.35rem 0;
            color: #555;
        }
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--primary);
            padding-top: 0.5rem;
        }
        .divider { border: none; border-top: 2px solid #f0f0f0; margin: 0.75rem 0; }

        .empty-cart-msg {
            text-align: center;
            padding: 2rem;
            color: #888;
            font-size: 0.95rem;
        }

        /* ─── Place order button ─── */
        .place-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-family: var(--font);
            font-size: 1.05rem;
            font-weight: 800;
            cursor: pointer;
            margin-top: 1.25rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .place-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.15);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .place-btn:hover::after { opacity: 1; }
        .place-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 28px color-mix(in srgb, var(--primary) 40%, transparent); }
        .place-btn:disabled { opacity: 0.65; cursor: not-allowed; transform: none; box-shadow: none; }

        .secure-note { text-align: center; font-size: 0.78rem; color: #aaa; margin-top: 0.6rem; }

        /* ─── Loading spinner ─── */
        .spinner {
            display: inline-block;
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-left: 0.4rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ─── Error alert ─── */
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.88rem;
            color: #b91c1c;
            margin-bottom: 1rem;
            display: none;
        }
        .alert-error.show { display: block; }
    </style>
</head>
<body>

    {{-- Top bar --}}
    <nav class="topbar">
        <a href="/shop/" class="topbar-brand">{{ $tenant->name ?? 'المتجر' }}</a>
        <a href="/shop/cart.html" class="topbar-back"><i class="fas fa-arrow-right"></i> العودة للسلة</a>
    </nav>

    {{-- Steps --}}
    <div class="steps-bar">
        <div class="step-item done">
            <div class="step-bubble"><i class="fas fa-check"></i></div>
            السلة
        </div>
        <div class="step-sep done-sep"></div>
        <div class="step-item active">
            <div class="step-bubble">2</div>
            الشحن والدفع
        </div>
        <div class="step-sep"></div>
        <div class="step-item">
            <div class="step-bubble">3</div>
            التأكيد
        </div>
    </div>

    <div class="checkout-wrapper">

        {{-- ─── Left: Form ─── --}}
        <div class="checkout-left">

            <div id="alert-error" class="alert-error"></div>

            <!-- Guest Checkout Banner -->
            <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe; border-radius: 16px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                <i class="fas fa-user-shield" style="color: #3b82f6; font-size: 1.5rem;"></i>
                <div>
                    <span style="font-weight: 800; color: #1e3a8a; display: block; font-size: 0.95rem;">الطلب السريع كزائر (Guest Checkout)</span>
                    <span style="color: #3b82f6; font-size: 0.82rem;">يمكنك إتمام الطلب مباشرة دون الحاجة لإنشاء حساب أو تسجيل الدخول.</span>
                </div>
            </div>

            <form id="checkout-form" onsubmit="placeOrder(event)">

                {{-- Shipping info --}}
                <div class="card">
                    <h2 class="card-title"><i class="fas fa-map-marker-alt" style="color:var(--primary)"></i> بيانات الشحن</h2>

                    <div class="form-group">
                        <label class="form-label">الاسم الكامل *</label>
                        <input type="text" name="customer_name" class="form-control" placeholder="محمد أحمد" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">رقم الهاتف *</label>
                            <input type="tel" name="customer_phone" class="form-control" placeholder="01000000000" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="customer_email" class="form-control" placeholder="example@email.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">العنوان التفصيلي *</label>
                        <input type="text" name="customer_address" class="form-control" placeholder="الشارع، رقم البناية، الطابق..." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">المحافظة *</label>
                        <select name="governorate_id" id="governorate-select" class="form-control" required>
                            <option value="">جاري التحميل...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="أي تعليمات خاصة للتوصيل..."></textarea>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="card">
                    <h2 class="card-title"><i class="fas fa-credit-card" style="color:var(--primary)"></i> طريقة الدفع</h2>

                    <div class="payment-grid">
                        @php
                            $hasActiveGateways = isset($paymentGateways) && count($paymentGateways) > 0;
                            $isFirst = true;
                        @endphp

                        @if($hasActiveGateways)
                            @foreach($paymentGateways as $gw)
                                @php
                                    $gwId = $gw->provider ?? 'cod';
                                    $title = $gw->display_name ?: match($gwId) {
                                        'cod' => 'الدفع عند الاستلام (COD)',
                                        'paymob' => 'البطاقات البنكية والمحافظ (Paymob)',
                                        'kashier' => 'الدفع الإلكتروني (Kashier)',
                                        'fawry' => 'فوري باي (Fawry)',
                                        default => ucfirst($gwId)
                                    };
                                    $desc = $gw->display_description ?: match($gwId) {
                                        'cod' => 'ادفع نقداً عند وصول طلبك',
                                        'paymob' => 'ادفع بأمان عبر الفيزا، ماستركارد، ميزة والمحافظ الإلكترونية',
                                        'kashier' => 'ادفع بأمان عبر البطاقات والمحافظ الإلكترونية',
                                        'fawry' => 'ادفع برقم مرجعي عبر أي منفذ فوري في مصر',
                                        default => ''
                                    };
                                    $badge = match($gwId) {
                                        'paymob' => '/images/payments/cards_meeza_badge.svg',
                                        'kashier' => '/images/payments/cards_meeza_badge.svg',
                                        'fawry' => '/images/payments/fawry.svg',
                                        default => null
                                    };
                                    $icon = match($gwId) {
                                        'cod' => '🏠',
                                        'paymob' => '💳',
                                        'kashier' => '🟢',
                                        'fawry' => '🟡',
                                        default => '💳'
                                    };
                                    $isSelected = $isFirst;
                                    $isFirst = false;
                                @endphp
                                <label class="pay-opt {{ $isSelected ? 'selected' : '' }}" id="opt-{{ $gwId }}" onclick="selectPayment('{{ $gwId }}', this)">
                                    <input type="radio" name="payment_method" value="{{ $gwId }}" {{ $isSelected ? 'checked' : '' }}>
                                    <div class="pay-icon">{{ $icon }}</div>
                                    <div class="pay-info" style="flex: 1;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                            <h4>{{ $title }}</h4>
                                            @if($badge)
                                                <img src="{{ $badge }}" alt="{{ $gwId }}" style="height: 18px; max-width: 110px; object-fit: contain;">
                                            @endif
                                        </div>
                                        <p>{{ $desc }}</p>
                                    </div>
                                </label>
                            @endforeach
                        @else
                            <label class="pay-opt selected" id="opt-cod" onclick="selectPayment('cod', this)">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <div class="pay-icon">🏠</div>
                                <div class="pay-info">
                                    <h4>الدفع عند الاستلام (COD)</h4>
                                    <p>ادفع نقداً عند وصول طلبك</p>
                                </div>
                            </label>
                        @endif
                    </div>

                    <!-- حفظ العنوان للمرات القادمة -->
                    <div class="terms-row" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed #e5e7eb;">
                        <input type="checkbox" id="save-address-chk" checked>
                        <label for="save-address-chk" style="font-weight: 700; color: #333;">
                            حفظ هذه البيانات (الاسم، الهاتف، العنوان) لاستخدامها في طلباتي القادمة
                        </label>
                    </div>

                    <div class="terms-row">
                        <input type="checkbox" id="terms-chk" required checked>
                        <label for="terms-chk">
                            أوافق على <a href="/terms" target="_blank">الشروط والأحكام</a> وسياسة الخصوصية وأتعهد بصحة البيانات
                        </label>
                    </div>
                </div>

            </form>
        </div>

        {{-- ─── Right: Order summary ─── --}}
        <aside class="order-sidebar">
            <div class="sidebar-sticky">
                <div class="card">
                    <h2 class="card-title"><i class="fas fa-shopping-bag" style="color:var(--primary)"></i> ملخص الطلب</h2>

                    <div id="order-items-list">
                        <p class="empty-cart-msg">جاري تحميل السلة...</p>
                    </div>

                    <hr class="divider">

                    <!-- Coupon Section -->
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #555; margin-bottom: 0.4rem;"><i class="fas fa-ticket-alt" style="color:var(--primary); margin-left: 0.3rem;"></i> كوبون الخصم</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" id="coupon-input" class="form-control" placeholder="أدخل كود الخصم" style="flex:1; text-transform:uppercase; padding:0.5rem 0.75rem; font-size:0.85rem;">
                            <button type="button" id="apply-coupon-btn" onclick="applyCoupon()" style="background:var(--primary); color:#fff; border:none; border-radius:10px; padding:0 1rem; font-weight:700; cursor:pointer; font-family:var(--font); font-size:0.85rem;">تطبيق</button>
                        </div>
                        <div id="coupon-msg" style="font-size:0.8rem; margin-top:0.4rem; display:none; font-weight:700;"></div>
                    </div>

                    <hr class="divider">

                    <div class="summary-row">
                        <span>السعر</span>
                        <span id="summary-subtotal">—</span>
                    </div>
                    <div class="summary-row" id="discount-row" style="display:none; color:#16a34a; font-weight:700;">
                        <span>الخصم (<span id="coupon-code-lbl"></span>)</span>
                        <span id="summary-discount">-0 ج.م</span>
                    </div>
                    <div class="summary-row" id="shipping-row">
                        <span>الشحن</span>
                        <span id="summary-shipping" style="color:#22c55e;">—</span>
                    </div>

                    <hr class="divider">

                    <div class="summary-total">
                        <span>الإجمالي</span>
                        <span id="summary-total">—</span>
                    </div>

                    <button type="button" class="place-btn" id="place-btn" onclick="document.getElementById('checkout-form').dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}))">
                        تأكيد الطلب &rarr;
                    </button>
                    <p class="secure-note">🔒 بياناتك محمية وآمنة</p>
                </div>

                <!-- Cross-sell products on checkout -->
                <div class="card" id="checkout-cross-sells" style="display:none; margin-top: 1.5rem;">
                    <h2 class="card-title" style="font-size: 1rem; font-weight: 800; border-bottom: 2px solid #f0f0f0; padding-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-gift" style="color:var(--primary)"></i> عروض خاصة لك:
                    </h2>
                    <div id="checkout-cross-sells-list" style="display: flex; flex-direction: column; gap: 0.75rem;"></div>
                </div>
            </div>
        </aside>

    </div>

<script>
// ─── Globals ───────────────────────────────────────────────────────────────
let cartItems      = [];
let governorates   = [];
let shippingCost   = 0;

const fmt = (n) => Number(n).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ' ج.م';

let appliedDiscount = 0;
let appliedCouponCode = null;

// ─── Init ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    loadCart();
    await loadGovernorates();
    renderSummary();
    loadCheckoutRecommendations();

    // Load saved customer info if present
    try {
        const savedInfo = localStorage.getItem('saved_customer_info');
        if (savedInfo) {
            const info = JSON.parse(savedInfo);
            if (info.name) document.querySelector('input[name="customer_name"]').value = info.name;
            if (info.phone) document.querySelector('input[name="customer_phone"]').value = info.phone;
            if (info.address) document.querySelector('input[name="customer_address"]').value = info.address;
            if (info.governorate_id) {
                setTimeout(() => {
                    const sel = document.getElementById('governorate-select');
                    if (sel) {
                        sel.value = info.governorate_id;
                        const opt = sel.options[sel.selectedIndex];
                        if (opt && opt.dataset.price) {
                            shippingCost = parseFloat(opt.dataset.price || 0);
                            renderSummary();
                        }
                    }
                }, 500);
            }
        }
    } catch(e){}

    trackPartialData();
});

async function loadCheckoutRecommendations() {
    const productIds = cartItems.map(item => item.id).join(',');
    if (!productIds) return;
    
    try {
        const res = await fetch(`/public-api/recommendations?ids=${productIds}&type=cross-sell`);
        const data = await res.json();
        const recs = data.data || [];
        const box = document.getElementById('checkout-cross-sells');
        const list = document.getElementById('checkout-cross-sells-list');
        
        if (recs.length > 0 && box && list) {
            box.style.display = 'block';
            list.innerHTML = recs.map(prod => `
                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; text-align: right; direction: rtl;">
                    <img class="item-thumb" src="${prod.image_url || '/shop/placeholder.jpg'}"
                         onerror="this.src='/shop/placeholder.jpg'" alt="${prod.name}" style="width: 48px; height: 48px; border-radius: 8px; object-fit: cover;">
                    <div style="flex:1; min-width: 0; padding-right: 8px;">
                        <div class="item-name" style="font-size: 0.8rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #1a1a2e;">${prod.name}</div>
                        <div class="item-price" style="font-size: 0.8rem; font-weight: 800; color: var(--primary); margin-top: 2px;">${fmt(prod.price_after)}</div>
                    </div>
                    <button type="button" onclick="addCrossSellToCheckout(${prod.id}, '${prod.name.replace(/'/g, "\\'")}', ${prod.price_after}, '${prod.image_url || ''}')" 
                            style="background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 0.4rem 0.75rem; font-size: 0.75rem; font-weight: 800; cursor: pointer; font-family: var(--font); transition: opacity 0.2s;">
                        + إضافة
                    </button>
                </div>
            `).join('');
        } else if (box) {
            box.style.display = 'none';
        }
    } catch (e) {
        console.error(e);
    }
}

function addCrossSellToCheckout(id, name, price, image) {
    let cart = [];
    try {
        cart = JSON.parse(localStorage.getItem('bird_cart') || '[]');
    } catch(e) {}
    
    const exists = cart.find(item => item.id === id);
    if (exists) {
        exists.qty = (exists.qty || 1) + 1;
    } else {
        cart.push({
            id: id,
            name: name,
            price: price,
            image: image || null,
            shipping_type: 'free',
            qty: 1,
            selectedSize: null,
            selectedColor: null
        });
    }
    
    localStorage.setItem('bird_cart', JSON.stringify(cart));
    
    // Reload checkout interface
    loadCart();
    renderSummary();
    
    // Reload recommendations
    loadCheckoutRecommendations();
}

function trackPartialData() {
    const nameInput = document.querySelector('input[name="customer_name"]');
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const emailInput = document.querySelector('input[name="customer_email"]');
    const addressInput = document.querySelector('input[name="customer_address"]');
    const govSelect = document.getElementById('governorate-select');
    
    let debounceTimer = null;
    let lastSentPhone = '';

    const sendTracking = async () => {
        const phone = phoneInput ? phoneInput.value.trim().replace(/[\s\+\-]/g, '') : '';
        const email = emailInput ? emailInput.value.trim() : '';
        const name = nameInput ? nameInput.value.trim() : '';
        const address = addressInput ? addressInput.value.trim() : '';
        const govId = govSelect ? govSelect.value : '';
        const govName = (govSelect && govSelect.selectedIndex >= 0 && govSelect.options[govSelect.selectedIndex]) 
            ? govSelect.options[govSelect.selectedIndex].text.split('-')[0].trim() 
            : '';
        
        // لا تسجل إذا لم يكن هناك هاتف مكون من 8 أرقام على الأقل أو إيميل صالح
        if ((!phone || phone.length < 8) && !email) return;

        let items = [];
        try {
            items = JSON.parse(localStorage.getItem('bird_cart') || '[]');
        } catch (e) {
            items = [];
        }

        const subtotal = items.reduce((sum, it) => sum + ((parseFloat(it.price) || 0) * (parseInt(it.qty) || 1)), 0);
        const total = subtotal;

        try {
            const csrfMeta = document.querySelector('meta[name=csrf-token]');
            const csrf = csrfMeta ? csrfMeta.content : '';
            await fetch('/checkout/track-partial', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                body: JSON.stringify({
                    phone,
                    customer_name: name,
                    customer_email: email,
                    customer_address: address,
                    governorate_id: govId,
                    governorate: govName,
                    items,
                    subtotal,
                    total,
                    source: 'checkout'
                }),
                keepalive: true
            });
            lastSentPhone = phone;
        } catch (e) {
            // تجاهل أخطاء التتبع بالخلفية
        }
    };

    const triggerDebounced = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(sendTracking, 600);
    };

    if (phoneInput) {
        phoneInput.addEventListener('input', (e) => {
            const val = e.target.value.replace(/[\s\+\-]/g, '');
            if (val.length >= 11 && val !== lastSentPhone) {
                triggerDebounced();
            }
        });
        phoneInput.addEventListener('blur', sendTracking);
        phoneInput.addEventListener('change', sendTracking);
    }
    if (nameInput) {
        nameInput.addEventListener('blur', sendTracking);
    }
    if (emailInput) {
        emailInput.addEventListener('blur', sendTracking);
    }
    if (addressInput) {
        addressInput.addEventListener('blur', sendTracking);
    }
    if (govSelect) {
        govSelect.addEventListener('change', sendTracking);
    }

    window.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') sendTracking();
    });
    window.addEventListener('pagehide', sendTracking);
    window.addEventListener('beforeunload', sendTracking);
}


// ─── Load cart from localStorage ────────────────────────────────────────────
function loadCart() {
    try {
        cartItems = JSON.parse(localStorage.getItem('bird_cart') || '[]');
    } catch { cartItems = []; }

    const list = document.getElementById('order-items-list');

    if (!cartItems.length) {
        list.innerHTML = '<p class="empty-cart-msg">⚠️ سلتك فارغة! <a href="/shop/">تسوق الآن</a></p>';
        document.getElementById('place-btn').disabled = true;
        return;
    }

    list.innerHTML = cartItems.map(item => `
        <div class="order-item-row">
            <img class="item-thumb" src="${item.image || '/shop/placeholder.jpg'}"
                 onerror="this.src='/shop/placeholder.jpg'" alt="${item.name}">
            <div style="flex:1">
                <div class="item-name">${item.name}</div>
                <div class="item-qty">
                    ${item.selectedSize  ? 'مقاس: ' + item.selectedSize  + ' ' : ''}
                    ${item.selectedColor ? 'لون: '  + item.selectedColor  + ' ' : ''}
                    ${item.options && typeof item.options === 'object' ? Object.entries(item.options).map(([k, v]) => v ? `${k}: ${v}` : '').join(' | ') : ''}
                </div>
            </div>
            <div>
                <div class="item-qty" style="text-align:center">× ${item.qty || 1}</div>
                <div class="item-price">${fmt((item.price || 0) * (item.qty || 1))}</div>
            </div>
        </div>
    `).join('');
}

// ─── Load governorates ───────────────────────────────────────────────────────
async function loadGovernorates() {
    try {
        const res  = await fetch('/public-api/shipping-governorates');
        const data = await res.json();
        governorates = (data.data || []).filter(g => g.is_active);
    } catch { governorates = []; }

    const sel = document.getElementById('governorate-select');
    if (!governorates.length) {
        sel.innerHTML = '<option value="">لا توجد محافظات متاحة</option>';
        return;
    }
    sel.innerHTML = '<option value="">اختر المحافظة</option>' +
        governorates.map(g =>
            `<option value="${g.id}" data-price="${g.price}">${g.name} — ${g.price > 0 ? fmt(g.price) : 'شحن مجاني'}</option>`
        ).join('');

    sel.addEventListener('change', () => {
        const opt  = sel.options[sel.selectedIndex];
        shippingCost = parseFloat(opt.dataset.price || 0);
        renderSummary();
    });
}

function hasCalculatedShipping() {
    return cartItems.some(item => item.shipping_type && item.shipping_type !== 'free');
}

// ─── Render order summary totals ─────────────────────────────────────────────
function renderSummary() {
    const subtotal = cartItems.reduce((s, i) => s + (i.price || 0) * (i.qty || 1), 0);
    const hasCalc = hasCalculatedShipping();
    const govSel = document.getElementById('governorate-select');
    const isGovSelected = govSel && govSel.value !== '';

    let shippingText = '';
    let totalText = '';

    if (!hasCalc) {
        shippingText = 'مجاني';
        totalText = fmt(Math.max(0, subtotal - appliedDiscount));
    } else {
        if (!isGovSelected) {
            shippingText = 'غير محدد';
            totalText = fmt(Math.max(0, subtotal - appliedDiscount));
        } else {
            shippingText = shippingCost > 0 ? fmt(shippingCost) : 'مجاني';
            totalText = fmt(Math.max(0, subtotal - appliedDiscount + shippingCost));
        }
    }

    document.getElementById('summary-subtotal').textContent = fmt(subtotal);
    document.getElementById('summary-shipping').textContent = shippingText;
    document.getElementById('summary-total').textContent    = totalText;
}

// ─── Apply Coupon ────────────────────────────────────────────────────────────
async function applyCoupon() {
    const input = document.getElementById('coupon-input');
    const msg   = document.getElementById('coupon-msg');
    const btn   = document.getElementById('apply-coupon-btn');
    if (!input.value.trim()) {
        msg.textContent = 'أدخل كود الخصم أولاً';
        msg.style.color = '#dc2626';
        msg.style.display = 'block';
        return;
    }
    const code = input.value.trim().toUpperCase();
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';
    
    const subtotal = cartItems.reduce((s, i) => s + (i.price || 0) * (i.qty || 1), 0);

    try {
        const res = await fetch('/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ action: 'check_coupon', coupon_code: code, subtotal }),
        });
        const data = await res.json();
        if (data.success) {
            appliedDiscount   = parseFloat(data.discount || 0);
            appliedCouponCode = data.code || code;
            msg.textContent   = '✔ ' + data.message + ` (خصم ${fmt(appliedDiscount)})`;
            msg.style.color   = '#16a34a';
            msg.style.display = 'block';
            document.getElementById('discount-row').style.display = 'flex';
            document.getElementById('coupon-code-lbl').textContent = appliedCouponCode;
            document.getElementById('summary-discount').textContent = '-' + fmt(appliedDiscount);
            input.disabled = true;
            btn.textContent = 'تم';
            renderSummary();
        } else {
            appliedDiscount   = 0;
            appliedCouponCode = null;
            msg.textContent   = '✖ ' + (data.message || 'كود غير صحيح');
            msg.style.color   = '#dc2626';
            msg.style.display = 'block';
            document.getElementById('discount-row').style.display = 'none';
            renderSummary();
        }
    } catch(e) {
        msg.textContent   = '✖ حدث خطأ في التحقق من الكوبون';
        msg.style.color   = '#dc2626';
        msg.style.display = 'block';
    } finally {
        btn.disabled = false;
        if (!input.disabled) btn.textContent = 'تطبيق';
    }
}

// ─── Payment method toggle ───────────────────────────────────────────────────
function selectPayment(method, el) {
    document.querySelectorAll('.pay-opt').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    const radio = el.querySelector('input[type=radio]');
    if (radio) radio.checked = true;
    const transferDetails = document.getElementById('transfer-details');
    if (transferDetails) {
        transferDetails.classList.toggle('show', method === 'transfer');
    }
}

// ─── Place order ─────────────────────────────────────────────────────────────
async function placeOrder(e) {
    e.preventDefault();

    // Validate cart
    if (!cartItems.length) {
        showError('سلتك فارغة! أضف منتجات قبل المتابعة.');
        return;
    }

    // Validate governorate
    const govSel = document.getElementById('governorate-select');
    if (!govSel.value) {
        showError('يرجى اختيار المحافظة.');
        govSel.focus();
        return;
    }

    hideError();

    const form   = document.getElementById('checkout-form');
    const fData  = new FormData(form);
    const fields = Object.fromEntries(fData.entries());

    // Validate phone format and normalize it
    let phone = fields.customer_phone ? fields.customer_phone.trim().replace(/\s+/g, '') : '';
    if (phone.startsWith('+201')) {
        phone = phone.substring(2);
    } else if (phone.startsWith('201')) {
        phone = '0' + phone.substring(1);
    } else if (phone.startsWith('00201')) {
        phone = phone.substring(4);
    }

    const phoneRegex = /^01[0125]\d{8}$/;
    if (!phone || !phoneRegex.test(phone)) {
        showError('يرجى إدخال رقم هاتف مصري صحيح مكون من 11 رقم ويبدأ بـ 01 (مثل: 01012345678).');
        const phoneInput = form.querySelector('input[name="customer_phone"]');
        if (phoneInput) phoneInput.focus();
        return;
    }

    const btn = document.getElementById('place-btn');
    btn.disabled = true;
    btn.innerHTML = 'جاري معالجة الطلب... <span class="spinner"></span>';

    // Build items array matching backend validation
    const items = cartItems.map(item => ({
        id:            item.id,
        name:          item.name,
        price:         item.price,
        qty:           item.qty || 1,
        selectedSize:  item.selectedSize  || null,
        selectedColor: item.selectedColor || null,
    }));

    // Save address if checked
    const saveChk = document.getElementById('save-address-chk');
    if (saveChk && saveChk.checked) {
        try {
            localStorage.setItem('saved_customer_info', JSON.stringify({
                name: fields.customer_name,
                phone: phone,
                address: fields.customer_address,
                governorate_id: parseInt(fields.governorate_id)
            }));
        } catch(e){}
    }

    const payload = {
        customer_name:    fields.customer_name,
        customer_phone:   phone,
        customer_email:   fields.customer_email || null,
        customer_address: fields.customer_address,
        governorate_id:   parseInt(fields.governorate_id),
        payment_method:   fields.payment_method,
        coupon_code:      appliedCouponCode,
        save_address:     saveChk ? saveChk.checked : false,
        terms:            true,
        notes:            fields.notes || null,
        items,
    };

    try {
        let res    = await fetch('/checkout', {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify(payload),
        });

        if (res.status === 404 || res.status === 405) {
            res = await fetch('/api/orders', {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'Accept':        'application/json',
                    'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify(payload),
            });
        }

        const result = await res.json();

        if (result.success) {
            // Clear cart
            localStorage.removeItem('bird_cart');
            window.location.href = result.redirect;
        } else {
            const msg = result.errors
                ? Object.values(result.errors).flat().join(' | ')
                : (result.message || 'حدث خطأ، يرجى المحاولة مرة أخرى.');
            showError(msg);
            btn.disabled = false;
            btn.textContent = 'تأكيد الطلب →';
        }
    } catch (err) {
        showError('حدث خطأ في الاتصال، تحقق من اتصالك بالإنترنت.');
        btn.disabled = false;
        btn.textContent = 'تأكيد الطلب →';
    }
}

// ─── Error helpers ────────────────────────────────────────────────────────────
function showError(msg) {
    const el = document.getElementById('alert-error');
    el.textContent = '⚠️ ' + msg;
    el.classList.add('show');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function hideError() {
    document.getElementById('alert-error').classList.remove('show');
}
</script>
</body>
</html>
