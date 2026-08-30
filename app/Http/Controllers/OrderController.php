<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingGovernorate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * عرض صفحة إتمام الشراء
     */
    public function checkout()
    {
        return redirect('/shop/checkout.html');
    }

    /**
     * معالجة تأكيد الطلب وإنشاء الرقم المرجعي
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:20',
            'customer_email'   => 'nullable|email|max:255',
            'customer_address' => 'required|string|max:1000',
            'governorate_id'   => 'required|exists:shipping_governorates,id',
            'items'            => 'required|array|min:1',
            'items.*.id'       => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selectedSize'  => 'nullable|string',
            'items.*.selectedColor' => 'nullable|string',
            'items.*.options'       => 'nullable',
            'notes'            => 'nullable|string|max:1000'
        ]);

        $governorate = ShippingGovernorate::findOrFail($validated['governorate_id']);

        $subtotal = 0;
        $orderItems = [];
        $hasNonFreeShipping = false;

        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail($item['id']);

            if (($product->shipping_type ?? 'free') !== 'free') {
                $hasNonFreeShipping = true;
            }

            if ($product->stock < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => "المنتج {$product->name} غير متوفر بالكمية المطلوبة. المتاح: {$product->stock}"
                ]);
            }

            $price = $this->getProductPrice($product, $item['quantity']);
            $itemTotal = $price * $item['quantity'];
            $subtotal += $itemTotal;

            $orderItems[] = [
                'id'            => $product->id,
                'name'          => $product->name,
                'price'         => $price,
                'quantity'      => $item['quantity'],
                'total'         => $itemTotal,
                'image'         => $product->main_image_path ? asset('storage/' . $product->main_image_path) : $product->image_url,
                'selectedSize'  => $item['selectedSize'] ?? null,
                'selectedColor' => $item['selectedColor'] ?? null,
                'options'       => $item['options'] ?? null,
            ];
        }

        $shippingCost = $hasNonFreeShipping ? $governorate->price : 0;
        $total = $subtotal + $shippingCost;
        $tenantId = $governorate->tenant_id ?? null;

        // 🛡️ فحص التكرار (Deduplication Guard): منع تكرار نفس الطلب لنفس العميل ونفس القيمة خلال 60 ثانية
        if ($tenantId && !empty($validated['customer_phone'])) {
            $recentDuplicate = Order::where('tenant_id', $tenantId)
                ->where('customer_phone', $validated['customer_phone'])
                ->where('total', $total)
                ->where('created_at', '>=', now()->subSeconds(60))
                ->latest('id')
                ->first();

            if ($recentDuplicate) {
                return redirect(url('/order-success/' . $recentDuplicate->reference_number . '?clear_cart=1'));
            }
        }

        $order = Order::createWithReference([
            'customer_name'    => $validated['customer_name'],
            'customer_phone'   => $validated['customer_phone'],
            'customer_email'   => $validated['customer_email'],
            'customer_address' => $validated['customer_address'],
            'governorate'      => $governorate->name,
            'shipping_cost'    => $shippingCost,
            'items'            => $orderItems,
            'subtotal'         => $subtotal,
            'total'            => $total,
            'status'           => 'pending',
            'notes'            => $validated['notes']
        ]);

        foreach ($validated['items'] as $item) {
            $prod = Product::find($item['id']);
            if ($prod) {
                $selectedSize  = $item['selectedSize']  ?? null;
                $selectedColor = $item['selectedColor'] ?? null;
                $options       = is_array($item['options'] ?? null) ? $item['options'] : [];
                $prod->decrementVariantStock((int)($item['quantity'] ?? 1), $selectedSize, $selectedColor, $options);
            }
        }

        // Trigger Webhook order.created
        \App\Services\WebhookSender::trigger('order.created', $order->toArray(), $order->tenant_id);

        // Web Push Notification للتاجر
        try {
            $pushSettings = \App\Models\Setting::get('push_notifications', null, $order->tenant_id);
            $isPushEnabled = is_array($pushSettings) ? ($pushSettings['enabled'] ?? true) : true;
            $isNewOrderEnabled = is_array($pushSettings) ? ($pushSettings['new_orders'] ?? true) : true;

            if ($isPushEnabled && $isNewOrderEnabled) {
                $pushService = new \App\Services\PushNotificationService();
                $pushService->notifyNewOrder($order->tenant_id, [
                    'id'               => $order->id,
                    'reference_number' => $order->reference_number,
                    'total'            => $order->total,
                    'customer_name'    => $order->customer_name ?? 'عميل',
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Push notification on new order failed: ' . $e->getMessage());
        }

        $redirectUrl = url('/order-success/' . $order->reference_number . '?clear_cart=1');
        return redirect($redirectUrl);
    }

    /**
     * إنشاء طلب جديد عبر API
     */
    public function storeApi(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_name'    => 'required|string|max:255',
                'customer_phone'   => 'required|string|max:20',
                'customer_address' => 'required|string|max:1000',
                'governorate_id'   => 'required|exists:shipping_governorates,id',
                'payment_method'   => 'nullable|string',
                'coupon_code'      => 'nullable|string',
                'items'            => 'required|array|min:1',
                'items.*.id'       => 'required|integer',
                'items.*.name'     => 'required|string',
                'items.*.price'    => 'required|numeric',
                'items.*.qty'      => 'required|integer|min:1',
                'items.*.selectedSize'  => 'nullable|string',
                'items.*.selectedColor' => 'nullable|string',
                'items.*.options'       => 'nullable',
                'notes'            => 'nullable|string|max:1000'
            ]);

            $governorate = ShippingGovernorate::findOrFail($validated['governorate_id']);

            if (!$governorate->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'المحافظة المختارة غير متاحة حالياً'
                ], 400);
            }

            $subtotal = 0;
            $orderItems = [];
            $hasNonFreeShipping = false;

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['id']);
                if ($product && ($product->shipping_type ?? 'free') !== 'free') {
                    $hasNonFreeShipping = true;
                }

                $itemTotal = $item['price'] * $item['qty'];
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'id'            => $item['id'],
                    'name'          => $item['name'],
                    'price'         => $item['price'],
                    'quantity'      => $item['qty'],
                    'total'         => $itemTotal,
                    'selectedSize'  => $item['selectedSize'] ?? null,
                    'selectedColor' => $item['selectedColor'] ?? null,
                    'options'       => $item['options'] ?? null,
                ];
            }

            // Calculate backend coupon discount if coupon_code is passed
            $discount = 0;
            $couponCode = $validated['coupon_code'] ?? null;
            if ($couponCode) {
                $coupon = \App\Models\Coupon::where('tenant_id', $governorate->tenant_id)
                    ->where('code', $couponCode)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->first();

                if ($coupon) {
                    $discount = $coupon->type === 'percentage'
                        ? ($subtotal * $coupon->value / 100)
                        : (float) $coupon->value;
                    $discount = min($discount, $subtotal);
                }
            }

            $shippingCost = $hasNonFreeShipping ? $governorate->price : 0;
            $total = max(0, $subtotal - $discount + $shippingCost);
            $tenantId = $governorate->tenant_id ?? null;

            // 🛡️ فحص التكرار (Deduplication Guard): منع تكرار نفس الطلب لنفس العميل ونفس القيمة خلال 60 ثانية
            if ($tenantId && !empty($validated['customer_phone'])) {
                $recentDuplicate = Order::where('tenant_id', $tenantId)
                    ->where('customer_phone', $validated['customer_phone'])
                    ->where('total', $total)
                    ->where('created_at', '>=', now()->subSeconds(60))
                    ->latest('id')
                    ->first();

                if ($recentDuplicate) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم استلام طلبك بنجاح',
                        'data'    => [
                            'id'               => $recentDuplicate->id,
                            'reference_number' => $recentDuplicate->reference_number,
                            'total'            => $recentDuplicate->total,
                            'redirect_url'     => '/shop/order-success.html?ref=' . $recentDuplicate->reference_number
                        ]
                    ]);
                }
            }

            $paymentMethod = $validated['payment_method'] ?? 'cod';
            $isOnlinePayment = in_array($paymentMethod, ['paymob', 'kashier', 'fawry', 'card', 'wallet']);
            $paymentStatus = $isOnlinePayment ? 'pending_payment' : 'pending_cash';

            DB::beginTransaction();

            $order = Order::createWithReference([
                'tenant_id'        => $governorate->tenant_id ?? null,
                'customer_name'    => $validated['customer_name'],
                'customer_phone'   => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'governorate'      => $governorate->name,
                'payment_method'   => $paymentMethod,
                'payment_status'   => $paymentStatus,
                'shipping_cost'    => $shippingCost,
                'coupon_code'      => $couponCode,
                'discount'         => $discount,
                'items'            => $orderItems,
                'subtotal'         => $subtotal,
                'total'            => $total,
                'status'           => 'pending',
                'notes'            => $validated['notes'] ?? null
            ]);

            // تقليل المخزون
            foreach ($validated['items'] as $item) {
                $prod = Product::find($item['id']);
                if ($prod) {
                    $pieces = static::parsePieceSelections($item);
                    $deductedQty = 0;

                    if (!empty($pieces)) {
                        foreach ($pieces as $piece) {
                            $pSize  = $piece['size'] ?? null;
                            $pColor = $piece['color'] ?? null;
                            $pOpts  = is_array($piece['options'] ?? null) ? $piece['options'] : [];
                            $prod->decrementVariantStock(1, $pSize, $pColor, $pOpts);
                            $deductedQty++;
                        }
                    } else {
                        $selectedSize  = $item['selectedSize']  ?? null;
                        $selectedColor = $item['selectedColor'] ?? null;
                        $options       = is_array($item['options'] ?? null) 
                            ? $item['options'] 
                            : (is_string($item['options'] ?? null) ? json_decode($item['options'], true) ?? [] : []);
                        $itemQty       = (int) ($item['qty'] ?? 1);

                        $prod->decrementVariantStock($itemQty, $selectedSize, $selectedColor, $options);
                        $deductedQty   = $itemQty;
                    }

                    // تسجيل حركة المخزون (صادر)
                    \App\Models\StockMovement::create([
                        'tenant_id'   => $prod->tenant_id,
                        'product_id'  => $prod->id,
                        'quantity'    => $deductedQty,
                        'type'        => 'out',
                        'description' => "مبيعات الطلب رقم #{$order->reference_number}"
                            . (!empty($item['selectedSize'])  ? " | مقاس: {$item['selectedSize']}"  : '')
                            . (!empty($item['selectedColor']) ? " | لون: {$item['selectedColor']}" : ''),
                    ]);

                    // إرسال تنبيه في حال انخفاض المخزون عن الحد المحدد
                    if ($prod->stock <= $prod->low_stock_threshold) {
                        $tenant = \App\Models\Tenant::find($order->tenant_id);
                        if ($tenant && $tenant->owner) {
                            try {
                                $tenant->owner->notify(new \App\Notifications\LowStockNotification($prod, $prod->stock));
                            } catch (\Exception $e) {
                                // لا نعطل العملية بسبب فشل إرسال التنبيه
                            }
                        }
                    }
                }
            }

            DB::commit();

            // إرسال رسالة التأكيد التلقائي عبر الواتساب (WhatsApp Meta Cloud API)
            try {
                $isAutoConfirmEnabled = (bool) \App\Models\Setting::get('auto_confirm_enabled', false);
                if ($isAutoConfirmEnabled && !$isOnlinePayment) {
                    $whatsAppService = new \App\Services\MetaWhatsAppService();
                    $whatsAppService->sendOrderConfirmation($order);
                }
            } catch (\Throwable $e) {
                \Log::warning('WhatsApp auto confirmation failed on storeApi: ' . $e->getMessage());
            }

            // Trigger Webhook order.created
            try {
                \App\Services\WebhookSender::trigger('order.created', $order->toArray(), $order->tenant_id);
            } catch (\Throwable $e) {}

            // Web Push Notification للتاجر
            try {
                $pushSettings = \App\Models\Setting::get('push_notifications', null, $order->tenant_id);
                $isPushEnabled = is_array($pushSettings) ? ($pushSettings['enabled'] ?? true) : true;
                $isNewOrderEnabled = is_array($pushSettings) ? ($pushSettings['new_orders'] ?? true) : true;

                if ($isPushEnabled && $isNewOrderEnabled) {
                    $pushService = new \App\Services\PushNotificationService();
                    $pushService->notifyNewOrder($order->tenant_id, [
                        'id'               => $order->id,
                        'reference_number' => $order->reference_number,
                        'total'            => $order->total,
                        'customer_name'    => $order->customer_name ?? 'عميل',
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('Push notification on new order failed: ' . $e->getMessage());
            }

            $redirectUrl = url('/order-success/' . $order->reference_number . '?clear_cart=1');

            if ($isOnlinePayment) {
                if ($paymentMethod === 'paymob' || $paymentMethod === 'card' || $paymentMethod === 'wallet') {
                    $gw = \App\Models\PaymentGateway::where('tenant_id', $order->tenant_id)
                        ->where('provider', 'paymob')
                        ->first();
                    $creds = $gw?->credentials ?? [];

                    $paymobService = new \App\Services\PaymobService(
                        apiKey: $creds['api_key'] ?? null,
                        publicKey: $creds['public_key'] ?? null,
                        secretKey: $creds['secret_key'] ?? null,
                        cardIntegrationId: $creds['card_integration_id'] ?? null,
                        walletIntegrationId: $creds['wallet_integration_id'] ?? null,
                        hmacSecret: $creds['hmac_secret'] ?? null,
                    );

                    $checkoutData = $paymobService->createStoreOrderCheckout($order);
                    if (!empty($checkoutData['redirect_url'])) {
                        $redirectUrl = $checkoutData['redirect_url'];
                    }
                } elseif ($paymentMethod === 'kashier') {
                    $redirectUrl = url("/checkout/payment-callback/kashier?order_id={$order->id}&status=success");
                } elseif ($paymentMethod === 'fawry') {
                    $redirectUrl = url("/order-success/{$order->reference_number}?clear_cart=1&fawry=1");
                }
            }

            return response()->json([
                'success'      => true,
                'message'      => 'تم إنشاء الطلب بنجاح',
                'redirect_url' => $redirectUrl,
                'data'         => [
                    'reference_number' => $order->reference_number,
                    'total'            => $order->total,
                    'order_id'         => $order->id,
                    'redirect_url'     => $redirectUrl,
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('OrderController::storeApi error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الطلب'
            ], 500);
        }
    }

    /**
     * عرض صفحة نجاح الطلب
     */
    public function success($referenceNumber)
    {
        $order = Order::where('reference_number', $referenceNumber)->firstOrFail();
        return view('orders.success', compact('order'));
    }

    /**
     * عرض قائمة الطلبات (للإدارة)
     */
    public function index(Request $request)
    {
        $query = Order::query();

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // فلتر التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلتر المنتج
        if ($request->filled('product_id')) {
            $pid = (int) $request->product_id;
            $query->where(function ($q) use ($pid) {
                $q->where('items', 'like', "%\"id\":{$pid},%")
                  ->orWhere('items', 'like', "%\"id\": {$pid},%")
                  ->orWhere('items', 'like', "%\"id\":{$pid}}%")
                  ->orWhere('items', 'like', "%\"id\": {$pid}}%");
            });
        }

        // إحصائيات الحالة (من نفس الفلتر بدون status filter)
        $statsQuery = Order::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $statsQuery->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('product_id')) {
            $pid = (int) $request->product_id;
            $statsQuery->where(function ($q) use ($pid) {
                $q->where('items', 'like', "%\"id\":{$pid},%")
                  ->orWhere('items', 'like', "%\"id\": {$pid},%")
                  ->orWhere('items', 'like', "%\"id\":{$pid}}%")
                  ->orWhere('items', 'like', "%\"id\": {$pid}}%");
            });
        }

        $statusCounts = [
            'total'     => $statsQuery->count(),
            'pending'   => (clone $statsQuery)->where('status', 'pending')->count(),
            'confirmed' => (clone $statsQuery)->where('status', 'confirmed')->count(),
            'shipped'   => (clone $statsQuery)->where('status', 'shipped')->count(),
            'delivered' => (clone $statsQuery)->where('status', 'delivered')->count(),
            'cancelled' => (clone $statsQuery)->where('status', 'cancelled')->count(),
        ];

        // حساب المجموع الإجمالي للطلبات المفلترة
        $totalAmount = (clone $query)->sum('total');

        $orders = $query->latest()->paginate(20);
        $orders->appends($request->all());

        // قائمة المنتجات للفلتر
        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('orders.index', compact('orders', 'totalAmount', 'statusCounts', 'products'));
    }

    /**
     * عرض تفاصيل الطلب
     */
    public function show(Order $order)
    {
        try {
            $items = collect($order->items)->map(function ($item) {
                $product = Product::find($item['id'] ?? null);
                $rawPath = null;
                if ($product) {
                    $item['product_url'] = url('/shop/product.html?id=' . $product->id);
                    $rawPath = $product->main_image_path ?: $product->image_url;
                }
                if (!$rawPath && !empty($item['image'])) {
                    $rawPath = $item['image'];
                }
                if (!$rawPath && !empty($item['image_url'])) {
                    $rawPath = $item['image_url'];
                }
                $item['image_url'] = Product::resolveImageUrl($rawPath) ?: 'https://dummyimage.com/150x150/f3f4f6/9ca3af&text=صورة+المنتج';
                return $item;
            });

            return response()->json([
                'success' => true,
                'order'   => [
                    'id'               => $order->id,
                    'reference_number' => $order->reference_number,
                    'customer_name'    => $order->customer_name,
                    'customer_phone'   => $order->customer_phone,
                    'customer_address' => $order->customer_address,
                    'governorate'      => $order->governorate,
                    'total'            => $order->total,
                    'subtotal'         => $order->subtotal,
                    'shipping_cost'    => $order->shipping_cost,
                    'status'           => $order->status,
                    'items'            => $items,
                    'notes'            => $order->notes,
                    'created_at'       => $order->created_at->format('Y/m/d h:i A')
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Error showing order: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الطلب'
            ], 500);
        }
    }

    /**
     * تحديث حالة الطلب
     */
    public function updateStatus(Request $request, Order $order)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,confirmed,shipped,delivered,cancelled'
            ]);

            $order->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الطلب بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة الطلب: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إلغاء الطلب (بدلاً من الحذف)
     */
    public function cancel(Order $order)
    {
        try {
            $order->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الطلب بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الطلب'
            ], 500);
        }
    }

    /**
     * حذف الطلب
     */
    public function destroy(Order $order)
    {
        try {
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الطلب بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الطلب'
            ], 500);
        }
    }

    /**
     * تصدير الطلبات كـ CSV (يفتح في Excel)
     */
    public function export(Request $request)
    {
        $query = Order::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('product_id')) {
            $pid = (int) $request->product_id;
            $query->where(function ($q) use ($pid) {
                $q->where('items', 'like', "%\"id\":{$pid},%")
                  ->orWhere('items', 'like', "%\"id\": {$pid},%")
                  ->orWhere('items', 'like', "%\"id\":{$pid}}%")
                  ->orWhere('items', 'like', "%\"id\": {$pid}}%");
            });
        }

        $orders = $query->latest()->get();

        $orders = $query->latest()->get();

        if ($request->format === 'excel') {
            $filename = 'orders_' . now()->format('Y-m-d_H-i') . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($orders) {
                $file = fopen('php://output', 'w');
                fputs($file, "\xEF\xBB\xBF");

                fputcsv($file, ['#', 'الرقم المرجعي', 'العميل', 'الهاتف', 'المحافظة', 'العنوان', 'المنتجات', 'المجموع الفرعي', 'الشحن', 'الإجمالي', 'ملاحظات', 'التاريخ']);

                foreach ($orders as $order) {
                    $items = collect($order->items)->map(function($i) {
                        $name = $i['name'] ?? '';
                        $qty = $i['quantity'] ?? 1;
                        $size = isset($i['selectedSize']) ? ' - مقاس: ' . $i['selectedSize'] : '';
                        $color = isset($i['selectedColor']) ? ' - لون: ' . $i['selectedColor'] : '';
                        return "{$name} x{$qty}{$size}{$color}";
                    })->implode(' | ');

                    fputcsv($file, [
                        $order->id,
                        $order->reference_number,
                        $order->customer_name,
                        $order->customer_phone,
                        $order->governorate,
                        $order->customer_address,
                        $items,
                        $order->subtotal ?? 0,
                        $order->shipping_cost ?? 0,
                        $order->total ?? 0,
                        $order->notes ?? '',
                        $order->created_at->format('Y/m/d H:i'),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return view('orders.export_print', compact('orders'));
    }

    /**
     * عرض فاتورة الطلب للطباعة
     */
    public function invoice(Order $order)
    {
        $order->items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
        $storeName = \App\Models\Setting::get('store_name', 'Store');
        $storePhone = \App\Models\Setting::get('phone', '01146520922');
        return view('orders.invoice', compact('order', 'storeName', 'storePhone'));
    }

    /**
     * تحميل فاتورة الطلب كملف PDF
     */
    public function downloadInvoice(Order $order)
    {
        $order->items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
        $storeName = \App\Models\Setting::get('store_name', 'Store');
        $storePhone = \App\Models\Setting::get('phone', '01146520922');
        $pdf = \PDF::loadView('orders.invoice', compact('order', 'storeName', 'storePhone'));
        return $pdf->download('invoice-' . $order->reference_number . '.pdf');
    }

    /**
     * حساب سعر المنتج حسب الكمية (price tiers)
     */
    private function getProductPrice(Product $product, int $quantity): float
    {
        $basePrice = $product->price_after ?? $product->price;

        if (!$product->price_tiers) {
            return $basePrice;
        }

        $tiers = is_string($product->price_tiers)
            ? json_decode($product->price_tiers, true)
            : $product->price_tiers;

        if (!is_array($tiers) || empty($tiers)) {
            return $basePrice;
        }

        // ترتيب تنازلي حسب min_qty - نختار أعلى شريحة تنطبق
        usort($tiers, fn($a, $b) => $b['min_qty'] <=> $a['min_qty']);

        foreach ($tiers as $tier) {
            if ($quantity >= (int) $tier['min_qty']) {
                return (float) $tier['price'];
            }
        }

        return $basePrice;
    }

    /**
     * تفكيك اختيار الباقات متعددة القطع إلى قطع منفصلة
     */
    public static function parsePieceSelections(array $item): array
    {
        if (!empty($item['piecesSelections']) && is_array($item['piecesSelections'])) {
            return $item['piecesSelections'];
        }

        $sizeStr  = $item['selectedSize']  ?? '';
        $colorStr = $item['selectedColor'] ?? '';
        $options  = is_array($item['options'] ?? null) 
            ? $item['options'] 
            : (is_string($item['options'] ?? null) ? json_decode($item['options'], true) ?? [] : []);

        if (str_contains($sizeStr, ' | ') || str_contains($colorStr, ' | ')) {
            $sizeParts  = $sizeStr  ? explode(' | ', $sizeStr)  : [];
            $colorParts = $colorStr ? explode(' | ', $colorStr) : [];
            $maxCount   = max(count($sizeParts), count($colorParts));

            $pieces = [];
            for ($i = 0; $i < $maxCount; $i++) {
                $sz = isset($sizeParts[$i])  ? trim(str_contains($sizeParts[$i], ':')  ? (explode(':', $sizeParts[$i], 2)[1] ?? $sizeParts[$i])  : $sizeParts[$i])  : null;
                $cl = isset($colorParts[$i]) ? trim(str_contains($colorParts[$i], ':') ? (explode(':', $colorParts[$i], 2)[1] ?? $colorParts[$i]) : $colorParts[$i]) : null;
                $pieces[] = [
                    'size'    => $sz,
                    'color'   => $cl,
                    'options' => $options,
                ];
            }
            return $pieces;
        }

        return [];
    }
}

