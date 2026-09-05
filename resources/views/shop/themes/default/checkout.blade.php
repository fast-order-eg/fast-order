<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إتمام الطلب - {{ $tenant->name ?? 'المتجر' }}</title>
    <link rel="stylesheet" href="/shop/styles.css">
  <link rel="stylesheet" href="/shop/themes/default/style.css?v=1.0.0">
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
<body class="theme-default classic-theme">

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
                        <label class="pay-opt selected" id="opt-cod" onclick="selectPayment('cod', this)">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <div class="pay-icon">🏠</div>
                            <div class="pay-info">
                                <h4>الدفع عند الاستلام (COD)</h4>
                                <p>ادفع نقداً عند وصول طلبك</p>
                            </div>
                        </label>

                        <label class="pay-opt" id="opt-transfer" onclick="selectPayment('transfer', this)">
                            <input type="radio" name="payment_method" value="transfer">
                            <div class="pay-icon">📱</div>
                            <div class="pay-info">
                                <h4>تحويل إلكتروني</h4>
                                <p>InstaPay أو فودافون كاش</p>
                            </div>
                        </label>
                    </div>

                    <div class="transfer-note" id="transfer-details">
                        <strong>📲 بيانات التحويل:</strong><br>
                        الرقم: <strong>01092308465</strong><br>
                        يرجى تحويل المبلغ وإرسال صورة الإيصال على الواتساب بعد إتمام الطلب.
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
            </div>
        </aside>

    </div>

<script>
    document.getElementById('footYear').textContent = new Date().getFullYear();

    var currentShipping = 0;
    var governorates   = [];

    /* ---- Format ---- */
    function fmt(n) {
      return new Intl.NumberFormat('en-EG', { maximumFractionDigits: 0 }).format(Math.round(Number(n || 0))) + ' EGP';
    }

    /* ---- Load governorates ---- */
    fetch('/public-api/shipping-governorates')
      .then(function(r){ return r.json(); })
      .then(function(res){
        governorates = (res.data || []).filter(function(g){ return g.is_active; });
        var sel = document.getElementById('governorateSelect');
        sel.innerHTML = '<option value="">اختر المحافظة</option>';
        governorates.sort(function(a,b){ return a.name.localeCompare(b.name,'ar'); })
          .forEach(function(g){
            var opt = document.createElement('option');
            opt.value = g.id;
            opt.textContent = g.name;
            opt.dataset.price = g.price;
            sel.appendChild(opt);
          });
        renderSummary();
      })
      .catch(function(){ });

    /* ---- Render order items ---- */
    function renderSummary() {
      var cart = BirdCart.getCart();
      var box  = document.getElementById('orderItems');

      if (!cart.length) {
        box.innerHTML = '<p style="color:#6b7280;text-align:center;padding:16px;">السلة فارغة</p>';
        return;
      }

      var subtotal = 0;
      box.innerHTML = cart.map(function(it) {
        var line = (it.price || 0) * (it.qty || 1);
        subtotal += line;

        var sizeTag  = it.selectedSize  ? '<span style="font-size:.75rem;background:#dbeafe;color:#1e40af;padding:2px 6px;border-radius:4px;margin-right:4px;">المقاس: '+it.selectedSize+'</span>' : '';
        var colorTag = it.selectedColor ? '<span style="font-size:.75rem;background:#fce7f3;color:#be185d;padding:2px 6px;border-radius:4px;margin-right:4px;">اللون: '+it.selectedColor+'</span>' : '';

        return '<div style="display:flex;justify-content:space-between;align-items:flex-start;padding:10px 0;border-bottom:1px solid #f3f4f6;">' +
          '<div style="flex:1;">' +
            '<div style="font-weight:600;color:#111827;margin-bottom:3px;">' + (it.name||'منتج') + '</div>' +
            (sizeTag||colorTag ? '<div style="margin-bottom:4px;">' + sizeTag + colorTag + '</div>' : '') +
            '<div style="font-size:14px;color:#6b7280;">الكمية: ' + (it.qty||1) + ' × ' + fmt(it.price) + '</div>' +
          '</div>' +
          '<div style="font-weight:700;color:#4f46e5;margin-right:8px;">' + fmt(line) + '</div>' +
        '</div>';
      }).join('');

      document.getElementById('sumSubtotal').textContent = fmt(subtotal);
      updateTotal(subtotal);
    }

    /* ---- Update shipping ---- */
    function updateShipping() {
      var sel = document.getElementById('governorateSelect');
      var opt = sel.options[sel.selectedIndex];
      currentShipping = opt && opt.dataset.price ? parseInt(opt.dataset.price) : 0;
      var shEl = document.getElementById('sumShipping');
      shEl.textContent = currentShipping > 0 ? fmt(currentShipping) : '—';
      shEl.style.color  = currentShipping > 0 ? '#4f46e5' : '#9ca3af';

      var cart     = BirdCart.getCart();
      var subtotal = cart.reduce(function(s,it){ return s + (it.price||0)*(it.qty||1); }, 0);
      updateTotal(subtotal);
    }

    function updateTotal(subtotal) {
      var couponCode = localStorage.getItem('fastorder_coupon_code');
      var couponType = localStorage.getItem('fastorder_coupon_type');
      var couponValue = parseFloat(localStorage.getItem('fastorder_coupon_value') || 0);
      var discount = 0;

      if (couponCode && couponValue > 0) {
        if (couponType === 'percentage') {
          discount = (subtotal * couponValue) / 100;
        } else {
          discount = couponValue;
        }
        discount = Math.min(discount, subtotal);
      }

      var row = document.getElementById('couponRow');
      var discEl = document.getElementById('sumDiscount');
      if (discount > 0 && couponCode) {
        row.style.display = 'flex';
        discEl.textContent = '−' + fmt(discount);
      } else {
        row.style.display = 'none';
      }

      var finalTotal = Math.max(0, subtotal - discount + currentShipping);
      document.getElementById('sumTotal').textContent = fmt(finalTotal);
    }

    /* ---- Phone Input Realtime Formatting & Validation ---- */
    var pInput = document.getElementById('phoneInput');
    var pErr = document.getElementById('phoneErrorMsg');
    var phoneRegex = /^01[0125][0-9]{8}$/;

    if (pInput) {
      pInput.addEventListener('input', function() {
        // Strip non-digits completely
        var val = this.value.replace(/\D/g, '');
        
        // Force starting with 01 if user types other numbers
        if (val.length >= 1 && val[0] !== '0') {
          val = '0' + val;
        }
        if (val.length >= 2 && val[1] !== '1') {
          val = '01' + val.slice(2);
        }
        
        // Enforce 11 digits max
        this.value = val.slice(0, 11);

        if (this.value.length === 11) {
          if (phoneRegex.test(this.value)) {
            this.style.borderColor = '#10b981';
            this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.15)';
            if (pErr) pErr.style.display = 'none';
          } else {
            this.style.borderColor = '#ef4444';
            this.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
            if (pErr) pErr.style.display = 'block';
          }
        } else {
          this.style.borderColor = '#e5e7eb';
          this.style.boxShadow = 'none';
          if (pErr) pErr.style.display = 'none';
        }
      });

      pInput.addEventListener('blur', function() {
        if (this.value && (!phoneRegex.test(this.value) || this.value.length !== 11)) {
          this.style.borderColor = '#ef4444';
          this.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
          if (pErr) pErr.style.display = 'block';
        }
      });

      pInput.addEventListener('paste', function(e) {
        var pasted = (e.clipboardData || window.clipboardData).getData('text');
        var clean = pasted.replace(/\D/g, '');
        if (clean.length > 0 && !clean.startsWith('01')) {
          if (clean.startsWith('1')) clean = '0' + clean;
          else clean = '01' + clean;
        }
        this.value = clean.slice(0, 11);
        e.preventDefault();

        if (this.value.length === 11 && phoneRegex.test(this.value)) {
          this.style.borderColor = '#10b981';
          this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.15)';
          if (pErr) pErr.style.display = 'none';
        }
      });
    }

    // ─── Auto-Capture للسلة المتروكة لحظياً أثناء ملء البيانات في صفحة الشيك آوت ───
    var checkoutDebounceTimer = null;
    var lastSentCheckoutPhone = '';

    var sendCheckoutTracking = function() {
      var pInp = document.getElementById('phoneInput');
      var nInp = document.querySelector('input[name="name"]');
      var aInp = document.querySelector('input[name="address"], textarea[name="address"]');
      var gSel = document.getElementById('governorateSelect');

      var phoneVal = pInp ? pInp.value.trim().replace(/[\s\+\-]/g, '') : '';
      if (phoneVal.startsWith('201')) phoneVal = '0' + phoneVal.substring(2);
      else if (phoneVal.startsWith('00201')) phoneVal = '0' + phoneVal.substring(4);

      if (!phoneVal || phoneVal.length < 8) return;

      var nameVal = nInp ? nInp.value.trim() : '';
      var addrVal = aInp ? aInp.value.trim() : '';
      var govId = gSel ? gSel.value : '';
      var govName = (gSel && gSel.selectedIndex >= 0 && gSel.options[gSel.selectedIndex])
        ? gSel.options[gSel.selectedIndex].text.split('-')[0].trim()
        : '';

      var cart = (typeof BirdCart !== 'undefined' && BirdCart.getCart) ? BirdCart.getCart() : [];
      if (!cart || !cart.length) {
        try { cart = JSON.parse(localStorage.getItem('bird_cart') || '[]'); } catch(e) { cart = []; }
      }

      var subtotal = cart.reduce(function(sum, item) { return sum + ((parseFloat(item.price) || 0) * (parseInt(item.qty) || 1)); }, 0);

      try {
        var csrfToken = (document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '') || window.__CSRF_TOKEN__ || '';
        fetch('/checkout/track-partial', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
          },
          body: JSON.stringify({
            phone: phoneVal,
            customer_phone: phoneVal,
            name: nameVal,
            customer_name: nameVal,
            address: addrVal,
            customer_address: addrVal,
            governorate_id: govId,
            governorate: govName,
            items: cart,
            subtotal: subtotal,
            total: subtotal,
            source: 'checkout'
          }),
          keepalive: true
        }).then(function() {
          lastSentCheckoutPhone = phoneVal;
        }).catch(function() {});
      } catch(e) {}
    };

    if (pInput) {
      pInput.addEventListener('input', function() {
        var val = this.value.replace(/\D/g, '');
        if (val.length >= 11 && val !== lastSentCheckoutPhone) {
          clearTimeout(checkoutDebounceTimer);
          checkoutDebounceTimer = setTimeout(sendCheckoutTracking, 600);
        }
      });
      pInput.addEventListener('blur', sendCheckoutTracking);
      pInput.addEventListener('change', sendCheckoutTracking);
    }

    var nInput = document.querySelector('input[name="name"]');
    if (nInput) nInput.addEventListener('blur', sendCheckoutTracking);

    var aInput = document.querySelector('input[name="address"], textarea[name="address"]');
    if (aInput) aInput.addEventListener('blur', sendCheckoutTracking);

    var gSelect = document.getElementById('governorateSelect');
    if (gSelect) gSelect.addEventListener('change', sendCheckoutTracking);

    window.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'hidden') sendCheckoutTracking();
    });
    window.addEventListener('pagehide', sendCheckoutTracking);
    window.addEventListener('beforeunload', sendCheckoutTracking);

    /* ---- Form submit ---- */
    document.getElementById('checkoutForm').addEventListener('submit', async function(e) {
      e.preventDefault();

      var form = e.target;
      var fd   = new FormData(form);
      var name = fd.get('name');
      var phone = (fd.get('phone') || '').replace(/[\s\+\-]/g, '');
      if (phone.startsWith('201')) phone = '0' + phone.substring(2);
      else if (phone.startsWith('00201')) phone = '0' + phone.substring(4);
      var addr = fd.get('address');

      if (!name || !phone || !addr) { alert('يرجى ملء جميع البيانات المطلوبة'); return; }

      // Enforce strictly 11 digits starting with 010, 011, 012, 015
      if (!phone || !phoneRegex.test(phone)) {
        if (pInput) {
          pInput.style.borderColor = '#ef4444';
          pInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
          pInput.focus();
        }
        if (pErr) pErr.style.display = 'block';
        return;
      }

      var sel = document.getElementById('governorateSelect');
      var opt = sel.options[sel.selectedIndex];
      if (!opt || !opt.value) { alert('يرجى اختيار المحافظة'); return; }

      var cart = BirdCart.getCart();
      if (!cart.length) { alert('السلة فارغة'); return; }

      var btn = document.getElementById('submitBtn');
      var orig = btn.innerHTML;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin" style="margin-left:8px;"></i>جاري المعالجة...';
      btn.disabled = true;

      var subtotal = cart.reduce(function(s,it){ return s+(it.price||0)*(it.qty||1); },0);
      
      var couponCode = localStorage.getItem('fastorder_coupon_code');
      var couponType = localStorage.getItem('fastorder_coupon_type');
      var couponValue = parseFloat(localStorage.getItem('fastorder_coupon_value') || 0);
      var discount = 0;

      if (couponCode && couponValue > 0) {
        if (couponType === 'percentage') {
          discount = (subtotal * couponValue) / 100;
        } else {
          discount = couponValue;
        }
        discount = Math.min(discount, subtotal);
      }

      var total = Math.max(0, subtotal - discount + currentShipping);

      try {
        var res = await fetch('/api/orders', {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json', 
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest' 
          },
          body: JSON.stringify({
            customer_name:    name,
            customer_phone:   phone,
            customer_address: addr,
            governorate_id:   opt.value,
            payment_method:   'cod',
            coupon_code:      couponCode || null,
            items:            cart,
            notes:            fd.get('notes') || null
          })
        });

        var result = await res.json();

        if (result.success) {
          sessionStorage.setItem('orderSuccess', JSON.stringify({
            reference_number: result.data.reference_number,
            customer_name:  name,
            customer_phone: phone,
            governorate:    opt.textContent,
            subtotal:       subtotal,
            shipping_cost:  currentShipping,
            discount:       discount,
            coupon_code:    couponCode,
            total:          result.data.total || total,
            items:          cart
          }));
          localStorage.removeItem('bird_cart');
          localStorage.removeItem('fastorder_coupon_code');
          localStorage.removeItem('fastorder_coupon_type');
          localStorage.removeItem('fastorder_coupon_value');
          localStorage.removeItem('fastorder_coupon_discount');
          window.location.href = '/shop/order-success.html?ref=' + result.data.reference_number;
        } else {
          var errorDetails = '';
          if (result.errors) {
             for (var key in result.errors) {
                errorDetails += '\n- ' + result.errors[key].join(', ');
             }
          }
          alert((result.message || 'حدث خطأ أثناء إتمام الطلب') + errorDetails);
          btn.innerHTML = orig;
          btn.disabled = false;
        }
      } catch (err) {
        alert('تعذر الاتصال بالخادم، يرجى المحاولة لاحقاً');
        btn.innerHTML = orig;
        btn.disabled = false;
      }
    });

    /* ---- Settings dynamic loader ---- */
    (function(){
      fetch('/public-api/settings').then(function(r){return r.json();}).then(function(res){
        var d = res.data; if(!d) return;
        if(d.logo_url) document.querySelectorAll('.brand img').forEach(function(el){el.src=d.logo_url;});
        if(d.store_name) document.querySelectorAll('.brand span').forEach(function(el){el.textContent=d.store_name;});
        if(d.whatsapp) document.querySelectorAll('a.wapp-float').forEach(function(el){el.href='https://wa.me/'+d.whatsapp;});
        if(d.phone) document.querySelectorAll('a[href^="tel:"]').forEach(function(el){el.href='tel:'+d.phone;});
      }).catch(function(){});
    })();

    /* ---- Init ---- */
    window.updateShipping = updateShipping;
    BirdCart.updateCartCount();
    renderSummary();
  </script>
</body>
</html>
