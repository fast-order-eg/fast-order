<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة رقم {{ $order->reference_number }}</title>
    <link rel="stylesheet" href="{{ asset('css/pdf.css') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            color: #333;
            background: white;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info h1 {
            color: #2563eb;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .company-info p {
            color: #666;
            font-size: 14px;
        }
        
        .invoice-title {
            text-align: center;
        }
        
        .invoice-title h2 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .detail-section h3 {
            color: #2563eb;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .detail-label {
            font-weight: bold;
            color: #374151;
        }
        
        .detail-value {
            color: #6b7280;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
        }
        
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-processing { background-color: #dbeafe; color: #1e40af; }
        .status-shipped { background-color: #d1fae5; color: #065f46; }
        .status-delivered { background-color: #dcfce7; color: #166534; }
        .status-cancelled { background-color: #fee2e2; color: #dc2626; }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .products-table th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
            border-bottom: 2px solid #d1d5db;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .totals-section {
            margin-top: 30px;
            border-top: 2px solid #e5e7eb;
            padding-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .total-row.final {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            border-top: 1px solid #d1d5db;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        
        @page {
            margin: 0mm !important;
            size: auto;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 10mm 15mm;
                background: white;
            }
            
            .invoice-container {
                margin: 0 auto;
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ طباعة الفاتورة</button>
    
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>{{ $storeName ?? 'Store' }}</h1>
                <p>متجر متخصص في بيع المنتجات عالية الجودة</p>
                @if(!empty($storePhone))
                <p>الهاتف: {{ $storePhone }}</p>
                @endif
            </div>
            <div class="invoice-title">
                <h2>فاتورة</h2>
                <p style="color: #6b7280; font-size: 14px;">
                    تاريخ الإصدار: {{ \Carbon\Carbon::parse($order->created_at)->format('Y/m/d h:i A') }}
                </p>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="invoice-details">
            <!-- Order Information -->
            <div class="detail-section">
                <h3>📋 معلومات الطلب</h3>
                <div class="detail-item">
                    <span class="detail-label">الرقم المرجعي:</span>
                    <span class="detail-value">{{ $order->reference_number }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">تاريخ الطلب:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($order->created_at)->format('Y/m/d h:i A') }}</span>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="detail-section">
                <h3>👤 معلومات العميل</h3>
                <div class="detail-item">
                    <span class="detail-label">الاسم:</span>
                    <span class="detail-value">{{ $order->customer_name }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">رقم الهاتف:</span>
                    <span class="detail-value">{{ $order->customer_phone }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">العنوان:</span>
                    <span class="detail-value">{{ $order->customer_address }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">المحافظة:</span>
                    <span class="detail-value">{{ $order->governorate }}</span>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <table class="products-table">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                @php
                    $itemName = $item['name'] ?? $item['product_name'] ?? $item['title'] ?? 'منتج';
                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                    $price = (float) ($item['price'] ?? 0);
                @endphp
                <tr>
                    <td>
                        <strong>{{ $itemName }}</strong>
                        @if(isset($item['selectedSize']) || isset($item['selectedColor']) || isset($item['size']) || isset($item['color']) || isset($item['options']))
                            <br><small style="color: #6b7280;">
                                @if(isset($item['selectedSize']) || isset($item['size'])) المقاس: {{ $item['selectedSize'] ?? $item['size'] }} @endif
                                @if(isset($item['selectedColor']) || isset($item['color'])) اللون: {{ $item['selectedColor'] ?? $item['color'] }} @endif
                                @if(isset($item['options']) && is_array($item['options']))
                                    @foreach($item['options'] as $optK => $optV)
                                        @if($optV) | {{ $optK }}: {{ $optV }} @endif
                                    @endforeach
                                @endif
                            </small>
                        @endif
                        @if(isset($item['description']))
                        <br><small style="color: #6b7280;">{{ $item['description'] }}</small>
                        @endif
                    </td>
                    <td>{{ $qty }}</td>
                    <td>{{ number_format($price, 0) }}</td>
                    <td>{{ number_format($price * $qty, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <div class="total-row">
                <span>المجموع الفرعي:</span>
                <span>{{ number_format($order->subtotal ?? ($order->total - $order->shipping_cost), 0) }} ج.م</span>
            </div>
            @if(isset($order->discount) && $order->discount > 0)
            <div class="total-row" style="color: #16a34a; font-weight: bold;">
                <span>خصم الكوبون @if(!empty($order->coupon_code)) ({{ $order->coupon_code }}) @endif:</span>
                <span>-{{ number_format($order->discount, 0) }} ج.م</span>
            </div>
            @endif
            <div class="total-row">
                <span>تكلفة الشحن:</span>
                <span>
                    @if(($order->shipping_cost ?? 0) == 0)
                        مجاني
                    @else
                        {{ number_format($order->shipping_cost, 0) }} ج.م
                    @endif
                </span>
            </div>
            <div class="total-row final">
                <span>المجموع الكلي:</span>
                <span>{{ number_format($order->total, 0) }} ج.م</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>شكراً لك على تعاملك معنا | {{ $storeName ?? 'Store' }} &copy; {{ date('Y') }}</p>
            <p>في حالة وجود أي استفسارات، يُرجى التواصل معنا على {{ $storePhone ?? '01146520922' }}</p>
        </div>
    </div>

    <script>
        // Auto-focus for printing when page loads
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>