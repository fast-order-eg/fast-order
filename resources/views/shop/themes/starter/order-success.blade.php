<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم الطلب بنجاح - {{ $tenant->name ?? 'المتجر' }}</title>
    <link rel="stylesheet" href="/shop/styles.css">
  <link rel="stylesheet" href="/shop/themes/starter/style.css?v=1.0.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary:   {{ $theme['primary_color'] }};
            --secondary: {{ $theme['secondary_color'] }};
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f4f6ff 0%, #fdf4ff 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* ─── Confetti burst (CSS only) ─── */
        .confetti-wrap {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        .dot {
            position: absolute;
            top: -10px;
            border-radius: 50%;
            animation: fall linear forwards;
        }
        @keyframes fall {
            to { transform: translateY(110vh) rotate(720deg); opacity: 0; }
        }

        /* ─── Card ─── */
        .success-card {
            background: #fff;
            border-radius: 28px;
            padding: 2.5rem 2rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 24px 64px rgba(0,0,0,0.1);
            animation: slideUp 0.55s cubic-bezier(.22,.84,.44,1) both;
            position: relative;
            z-index: 1;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── Icon ─── */
        .success-icon-wrap {
            width: 90px; height: 90px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: popIn 0.5s 0.3s cubic-bezier(.22,.84,.44,1) both;
            box-shadow: 0 8px 24px rgba(34,197,94,0.4);
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.4); }
            to   { opacity: 1; transform: scale(1); }
        }
        .success-icon-wrap i { font-size: 2.4rem; color: #fff; }

        /* ─── Heading ─── */
        .success-title {
            font-size: 1.75rem;
            font-weight: 900;
            color: #1a1a2e;
            margin-bottom: 0.4rem;
        }
        .success-subtitle { font-size: 0.92rem; color: #777; margin-bottom: 1.5rem; }

        /* ─── Order number badge ─── */
        .order-badge {
            display: inline-block;
            background: color-mix(in srgb, var(--primary) 12%, #fff);
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 0.45rem 1.5rem;
            border-radius: 30px;
            font-weight: 800;
            font-size: 1rem;
            margin-bottom: 1.75rem;
            letter-spacing: 0.03em;
        }

        /* ─── Details box ─── */
        .order-details {
            background: #f8f9ff;
            border-radius: 16px;
            padding: 1.25rem 1.25rem;
            margin-bottom: 1.5rem;
            text-align: right;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            font-size: 0.88rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #888; }
        .detail-value { font-weight: 700; color: #1a1a2e; }

        /* ─── Status badge ─── */
        .status-badge {
            background: #fef3c7;
            color: #d97706;
            padding: 0.25rem 0.85rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* ─── Transfer info ─── */
        .transfer-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            margin-bottom: 1.5rem;
            text-align: right;
            font-size: 0.88rem;
            line-height: 1.7;
        }
        .transfer-box strong { color: #92400e; }

        /* ─── Action buttons ─── */
        .actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 16px color-mix(in srgb, var(--primary) 35%, transparent);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px color-mix(in srgb, var(--primary) 45%, transparent); }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-secondary:hover { background: #e2e8f0; }

        /* ─── Items list (collapsed by default) ─── */
        .items-toggle {
            font-size: 0.82rem;
            color: var(--primary);
            cursor: pointer;
            background: none;
            border: none;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
            width: 100%;
            text-align: center;
        }
        .items-list { display: none; text-align: right; }
        .items-list.open { display: block; }
        .item-entry {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 0.3rem 0;
            border-bottom: 1px dashed #eee;
        }
        .item-entry:last-child { border: none; }
    </style>
</head>
<body class="theme-starter fast-theme">

    {{-- Confetti --}}
    <div class="confetti-wrap" id="confetti"></div>

    <div class="success-card">

        {{-- Icon --}}
        <div class="success-icon-wrap">
            <i class="fas fa-check"></i>
        </div>

        <h1 class="success-title">تم طلبك بنجاح! 🎉</h1>
        <p class="success-subtitle">شكراً لك، سيتواصل معك فريقنا قريباً لتأكيد الطلب.</p>

        <div class="order-badge">
            رقم الطلب: {{ $order->reference_number }}
        </div>

        {{-- Details --}}
        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">اسم العميل</span>
                <span class="detail-value">{{ $order->customer_name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">الهاتف</span>
                <span class="detail-value" dir="ltr">{{ $order->customer_phone }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">المحافظة</span>
                <span class="detail-value">{{ $order->governorate }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">طريقة الدفع</span>
                <span class="detail-value">
                    {{ $order->payment_method === 'cod' ? 'الدفع عند الاستلام' : 'تحويل إلكتروني' }}
                </span>
            </div>
            @if($order->shipping_cost > 0)
            <div class="detail-row">
                <span class="detail-label">الشحن</span>
                <span class="detail-value">{{ number_format($order->shipping_cost, 0) }} ج.م</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">الإجمالي</span>
                <span class="detail-value" style="color:var(--primary); font-size:1rem;">
                    {{ number_format($order->total, 0) }} ج.م
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">الحالة</span>
                <span class="status-badge">قيد المراجعة</span>
            </div>
        </div>

        {{-- Items toggle --}}
        @php
            $items = is_array($order->items) ? $order->items : json_decode($order->items ?? '[]', true);
        @endphp
        @if(!empty($items))
        <button class="items-toggle" onclick="toggleItems(this)">
            عرض تفاصيل المنتجات ({{ count($items) }}) <i class="fas fa-chevron-down" style="font-size:0.75rem;"></i>
        </button>
        <div class="items-list" id="items-list">
            @foreach($items as $item)
            <div class="item-entry">
                <span>{{ $item['name'] }} <span style="color:#888;">× {{ $item['quantity'] }}</span></span>
                <span style="font-weight:700;">{{ number_format($item['total'] ?? ($item['price'] * $item['quantity']), 0) }} ج.م</span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Transfer note --}}
        @if($order->payment_method === 'transfer')
        <div class="transfer-box">
            <strong>📲 لإتمام الدفع:</strong><br>
            حوّل المبلغ <strong>{{ number_format($order->total, 0) }} ج.م</strong> إلى:<br>
            <strong>01092308465</strong> (InstaPay / فودافون كاش)<br>
            وأرسل صورة الإيصال مع رقم الطلب على الواتساب.
        </div>
        @endif

        {{-- Actions --}}
        <div class="actions">
            <a href="/shop/" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> متابعة التسوق
            </a>
            <a href="/tracking?order_number={{ $order->reference_number }}&phone={{ urlencode($order->customer_phone) }}" class="btn btn-secondary">
                <i class="fas fa-box"></i> تتبع الطلب
            </a>
        </div>

    </div>

<script>
function toggleItems(btn) {
    const list = document.getElementById('items-list');
    list.classList.toggle('open');
    btn.innerHTML = list.classList.contains('open')
        ? 'إخفاء تفاصيل المنتجات <i class="fas fa-chevron-up" style="font-size:.75rem;"></i>'
        : 'عرض تفاصيل المنتجات ({{ count($items ?? []) }}) <i class="fas fa-chevron-down" style="font-size:.75rem;"></i>';
}

// ─── Confetti ────────────────────────────────────────────────────────────────
(function() {
    const wrap   = document.getElementById('confetti');
    const colors = ['#6c63ff','#ff6584','#22c55e','#fcd34d','#f97316','#06b6d4'];
    for (let i = 0; i < 60; i++) {
        const dot = document.createElement('div');
        const size = 6 + Math.random() * 10;
        dot.className   = 'dot';
        dot.style.cssText = `
            left: ${Math.random() * 100}%;
            width: ${size}px;
            height: ${size}px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            animation-duration: ${1.5 + Math.random() * 2.5}s;
            animation-delay: ${Math.random() * 0.8}s;
            opacity: ${0.7 + Math.random() * 0.3};
        `;
        wrap.appendChild(dot);
    }
    // Remove after animation
    setTimeout(() => { wrap.remove(); }, 4000);
})();
</script>
</body>
</html>
