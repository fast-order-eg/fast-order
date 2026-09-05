<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة الفواتير المجمعة ({{ count($orders) }} طلب)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif;
            direction: rtl;
            color: #1f2937;
            background: #f3f4f6;
        }

        @page {
            margin: 0mm !important;
            size: auto;
        }

        .print-toolbar {
            position: fixed;
            top: 15px;
            left: 20px;
            z-index: 9999;
            display: flex;
            gap: 10px;
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 16px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            backdrop-filter: blur(8px);
            align-items: center;
        }

        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .print-btn:hover {
            background: #1d4ed8;
        }

        .order-invoice-page {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            page-break-after: always;
            break-after: page;
        }

        .order-invoice-page:last-child {
            page-break-after: avoid;
            break-after: avoid;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 18px;
            margin-bottom: 22px;
        }

        .company-info h1 {
            color: #2563eb;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .company-info p {
            color: #4b5563;
            font-size: 13px;
        }

        .invoice-title {
            text-align: left;
        }

        .invoice-title h2 {
            font-size: 22px;
            font-weight: 800;
            color: #111827;
        }

        .ref-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            padding: 4px 10px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }

        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .detail-section {
            background: #f9fafb;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .detail-section h3 {
            color: #1f2937;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .detail-label {
            color: #6b7280;
            font-weight: 600;
        }

        .detail-value {
            color: #111827;
            font-weight: 600;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .products-table th {
            background-color: #f3f4f6;
            color: #374151;
            padding: 10px 12px;
            text-align: right;
            border-bottom: 2px solid #e5e7eb;
            font-size: 13px;
            font-weight: 700;
        }

        .products-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }

        .totals-section {
            width: 320px;
            margin-right: auto;
            margin-left: 0;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 8px;
            color: #4b5563;
        }

        .total-row.final {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
            margin-top: 8px;
        }

        .invoice-footer {
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
            border-top: 1px dashed #e5e7eb;
            padding-top: 12px;
        }

        @media print {
            body {
                background: white;
                margin: 0;
                padding: 10mm 15mm;
            }

            .print-toolbar {
                display: none !important;
            }

            .order-invoice-page {
                margin: 0 auto;
                padding: 0;
                box-shadow: none;
                border: none;
                border-radius: 0;
                page-break-after: always;
                break-after: page;
            }

            .order-invoice-page:last-child {
                page-break-after: avoid;
                break-after: avoid;
            }
        }
    </style>
</head>
<body>

    <div class="print-toolbar">
        <span style="font-size: 13px; font-weight: bold; color: #374151;">فواتير الطباعة ({{ count($orders) }} طلب)</span>
        <button class="print-btn" onclick="window.print()">🖨️ طباعة الآن</button>
    </div>

    @foreach($orders as $order)
    @php
        $items = is_string($order->items) ? json_decode($order->items, true) : ($order->items ?? []);
        
        // Clean customer notes from any technical WhatsApp texts
        $cleanNotes = null;
        if (!empty($order->notes)) {
            $nLines = preg_split("/\r\n|\n|\r/", $order->notes);
            $cL = array_filter($nLines, fn($l) => !str_contains($l, 'واتساب') && !str_contains($l, 'whatsapp') && !str_contains($l, 'WhatsApp'));
            $cleanNotes = trim(implode("\n", $cL));
        }
    @endphp
    <div class="order-invoice-page">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>{{ $storeName ?: 'المتجر' }}</h1>
                @if(!empty($storePhone))
                    <p>الهاتف: {{ $storePhone }}</p>
                @endif
            </div>
            <div class="invoice-title">
                <h2>فاتورة طلب</h2>
                <div class="ref-badge">#{{ $order->reference_number }}</div>
                <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                    {{ \Carbon\Carbon::parse($order->created_at)->format('Y/m/d h:i A') }}
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="invoice-details">
            <div class="detail-section">
                <h3>📋 بيانات الطلب</h3>
                <div class="detail-item">
                    <span class="detail-label">رقم الطلب:</span>
                    <span class="detail-value">#{{ $order->reference_number }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">طريقة الدفع:</span>
                    <span class="detail-value">
                        {{ in_array(strtolower($order->payment_method ?? ''), ['cod', 'cash', '']) ? 'دفع عند الاستلام' : 'دفع إلكتروني' }}
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">حالة الطلب:</span>
                    <span class="detail-value">{{ $order->status === 'shipped' ? 'مع شركة الشحن' : ($order->status === 'confirmed' ? 'مؤكد' : $order->status) }}</span>
                </div>
            </div>

            <div class="detail-section">
                <h3>👤 بيانات العميل</h3>
                <div class="detail-item">
                    <span class="detail-label">الاسم:</span>
                    <span class="detail-value">{{ $order->customer_name }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">الهاتف:</span>
                    <span class="detail-value" dir="ltr">{{ $order->customer_phone }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">المحافظة:</span>
                    <span class="detail-value">{{ $order->governorate }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">العنوان:</span>
                    <span class="detail-value">{{ $order->customer_address }}</span>
                </div>
            </div>
        </div>

        <!-- Products -->
        <table class="products-table">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th style="text-align: center; width: 70px;">الكمية</th>
                    <th style="text-align: left; width: 100px;">السعر</th>
                    <th style="text-align: left; width: 110px;">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                @php
                    $iName = $item['name'] ?? $item['product_name'] ?? 'منتج';
                    $iQty = max(1, (int)($item['quantity'] ?? $item['qty'] ?? 1));
                    $iPrice = (float)($item['price'] ?? 0);
                @endphp
                <tr>
                    <td>
                        <strong>{{ $iName }}</strong>
                        @if(isset($item['selectedSize']) || isset($item['selectedColor']) || isset($item['size']) || isset($item['color']) || isset($item['options']))
                            <div style="color: #6b7280; font-size: 11px; margin-top: 2px;">
                                @if(isset($item['selectedSize']) || isset($item['size'])) المقاس: {{ $item['selectedSize'] ?? $item['size'] }} @endif
                                @if(isset($item['selectedColor']) || isset($item['color'])) | اللون: {{ $item['selectedColor'] ?? $item['color'] }} @endif
                                @if(isset($item['options']) && is_array($item['options']))
                                    @foreach($item['options'] as $optK => $optV)
                                        @if($optV) | {{ $optK }}: {{ $optV }} @endif
                                    @endforeach
                                @endif
                            </div>
                        @endif
                    </td>
                    <td style="text-align: center;">{{ $iQty }}</td>
                    <td style="text-align: left;" dir="ltr">{{ number_format($iPrice, 0) }} ج.م</td>
                    <td style="text-align: left; font-weight: bold;" dir="ltr">{{ number_format($iPrice * $iQty, 0) }} ج.م</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals & Notes -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
            <div style="flex: 1;">
                @if(!empty($cleanNotes))
                <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 10px; font-size: 12px; color: #92400e;">
                    <strong>ملاحظات العميل:</strong>
                    <p style="margin-top: 3px; white-space: pre-line;">{{ $cleanNotes }}</p>
                </div>
                @endif
            </div>

            <div class="totals-section">
                <div class="total-row">
                    <span>المجموع الفرعي:</span>
                    <span dir="ltr">{{ number_format($order->subtotal ?: ($order->total - $order->shipping_cost), 0) }} ج.م</span>
                </div>
                @if(($order->discount ?? 0) > 0)
                <div class="total-row" style="color: #16a34a; font-weight: bold;">
                    <span>الخصم:</span>
                    <span dir="ltr">-{{ number_format($order->discount, 0) }} ج.م</span>
                </div>
                @endif
                <div class="total-row">
                    <span>مصاريف الشحن:</span>
                    <span>{{ ($order->shipping_cost ?? 0) == 0 ? 'مجاني' : number_format($order->shipping_cost, 0) . ' ج.م' }}</span>
                </div>
                <div class="total-row final">
                    <span>المطلوب تحصيله:</span>
                    <span dir="ltr" style="color: #2563eb;">{{ number_format($order->total, 0) }} ج.م</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            شكراً لتعاملكم معنا | {{ $storeName ?: 'المتجر' }} &copy; {{ date('Y') }}
        </div>
    </div>
    @endforeach

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>