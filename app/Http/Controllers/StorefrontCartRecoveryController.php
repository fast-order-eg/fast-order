<?php

namespace App\Http\Controllers;

use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\ShippingGovernorate;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StorefrontCartRecoveryController extends Controller
{
    public function __construct(private CartService $cartService) {}

    /**
     * استعادة السلة المتروكة وتوجيه العميل لصفحة إتمام الطلب
     */
    public function recover(Request $request, string $token)
    {
        $tenant = $request->attributes->get('tenant');
        $tenantId = $tenant?->id;

        $abandonedCart = AbandonedCart::where('recovery_token', $token)
            ->whereNull('recovered_at')
            ->first();

        if (!$abandonedCart) {
            return redirect()->to('/checkout')->with('error', 'رابط استعادة السلة غير صالح أو منتهي الصلاحية.');
        }

        // الحصول على سلة المستخدم الحالية
        $cart = $this->cartService->getCart($tenantId);

        // مسح السلة الحالية قبل الاستعادة
        $this->cartService->clearCart($cart);

        // إرجاع العناصر من البيانات المخزنة
        $items = $abandonedCart->cart_data['items'] ?? [];
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? ($item['id'] ?? null);
            $quantity = $item['quantity'] ?? ($item['qty'] ?? 1);
            $variantId = $item['product_variant_id'] ?? null;

            if ($productId) {
                try {
                    $this->cartService->addItem($cart, $productId, $quantity, $variantId);
                } catch (\Exception $e) {
                    // تخطي المنتجات المحذوفة أو غير المتوفرة
                }
            }
        }

        if ($request->has('coupon')) {
            $this->cartService->applyCoupon($cart, $request->query('coupon'), $tenantId);
        }

        return redirect()->route('storefront.checkout')->with([
            'recovered_customer_name' => $abandonedCart->customer_name,
            'recovered_customer_phone' => $abandonedCart->phone,
            'recovered_customer_address' => $abandonedCart->customer_address,
            'recovered_governorate' => $abandonedCart->governorate,
        ]);
    }

    /**
     * تتبع البيانات اللحظي (Auto-Capture) أثناء الكتابة في Checkout أو صفحة المنتج
     */
    public function trackPartial(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $tenantId = $tenant?->id;

        if (!$tenantId) {
            return response()->json(['success' => false, 'message' => 'المتجر غير محدد'], 400);
        }

        $phone = $request->input('phone') ?? $request->input('customer_phone');
        $name = $request->input('name') ?? $request->input('customer_name');
        $email = $request->input('email') ?? $request->input('customer_email');
        $governorate = $request->input('governorate') ?? $request->input('governorate_name');
        $governorateId = $request->input('governorate_id');
        $address = $request->input('address') ?? $request->input('customer_address');
        $rawItems = $request->input('items', []);

        // تنظيف رقم الهاتف
        $cleanPhone = null;
        if (!empty($phone)) {
            $cleanPhone = preg_replace('/[\s\+\-]/', '', (string)$phone);
            if (str_starts_with($cleanPhone, '00201')) {
                $cleanPhone = '0' . substr($cleanPhone, 4);
            } elseif (str_starts_with($cleanPhone, '201')) {
                $cleanPhone = '0' . substr($cleanPhone, 2);
            }
        }

        // إذا لم يتم إدخال هاتف كافٍ (أقل من 8 خانات) ولا بريد إلكتروني، نتجاهل التسجيل حتى يكتب بيانات مفيدة
        if ((!$cleanPhone || strlen($cleanPhone) < 8) && (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            return response()->json(['success' => false, 'message' => 'بيانات الاتصال غير مكتملة بعد'], 422);
        }

        // حل اسم المحافظة لو أُرسلت كمعرف
        if (empty($governorate) && !empty($governorateId)) {
            $govObj = ShippingGovernorate::find($governorateId);
            if ($govObj) {
                $governorate = $govObj->name;
            }
        }

        // جمع وتحضير المنتجات
        $itemsData = [];
        $calculatedSubtotal = 0;

        if (is_array($rawItems) && count($rawItems) > 0) {
            foreach ($rawItems as $it) {
                $pId = $it['id'] ?? ($it['product_id'] ?? null);
                $pName = $it['name'] ?? 'منتج';
                $pPrice = (float) ($it['price'] ?? 0);
                $pQty = max(1, (int) ($it['qty'] ?? ($it['quantity'] ?? 1)));
                $pTotal = $pPrice * $pQty;
                $calculatedSubtotal += $pTotal;

                $itemsData[] = [
                    'id' => $pId,
                    'product_id' => $pId,
                    'name' => $pName,
                    'price' => $pPrice,
                    'quantity' => $pQty,
                    'qty' => $pQty,
                    'total' => $pTotal,
                    'image' => $it['image'] ?? null,
                    'selectedSize' => $it['selectedSize'] ?? null,
                    'selectedColor' => $it['selectedColor'] ?? null,
                    'options' => $it['options'] ?? null,
                ];
            }
        } else {
            // محاولة الجلب من سلة السيرفر
            try {
                $serverCart = $this->cartService->getCart($tenantId);
                if ($serverCart && $serverCart->activeItems()->count() > 0) {
                    $itemsData = $serverCart->activeItems->map(fn($item) => [
                        'id' => $item->product_id,
                        'product_id' => $item->product_id,
                        'name' => $item->product?->name ?? 'منتج',
                        'price' => (float) $item->price,
                        'quantity' => $item->quantity,
                        'qty' => $item->quantity,
                        'total' => (float) $item->total,
                        'image' => $item->product?->main_image_path
                            ? asset('storage/' . $item->product->main_image_path)
                            : ($item->product?->image_url ?? null),
                        'selectedSize' => null,
                        'selectedColor' => null,
                    ])->toArray();
                    $calculatedSubtotal = (float) $serverCart->subtotal;
                }
            } catch (\Throwable $e) {}
        }

        $subtotal = (float) ($request->input('subtotal') ?: $calculatedSubtotal);
        $total = (float) ($request->input('total') ?: $subtotal);

        $cartData = [
            'items' => $itemsData,
            'subtotal' => $subtotal,
            'total' => $total,
            'governorate' => $governorate,
            'address' => $address,
            'captured_from' => $request->input('source', 'checkout'),
            'updated_at' => now()->toIso8601String(),
        ];

        // البحث عن سلة متروكة نشطة لنفس المتجر والجلسة أو الهاتف في آخر 48 ساعة
        $sessionId = session()->getId();
        $abandonedCart = AbandonedCart::where('tenant_id', $tenantId)
            ->whereNull('recovered_at')
            ->where('status', '!=', 'converted')
            ->where(function ($q) use ($sessionId, $cleanPhone, $email) {
                $q->where('session_id', $sessionId);
                if ($cleanPhone) {
                    $q->orWhere('phone', $cleanPhone);
                }
                if ($email) {
                    $q->orWhere('email', $email);
                }
            })
            ->where('created_at', '>=', now()->subHours(48))
            ->latest('id')
            ->first();

        $updateData = [
            'user_id' => auth()->id(),
            'cart_data' => $cartData,
            'subtotal' => $subtotal,
            'total' => $total,
            'status' => 'abandoned',
        ];

        if ($cleanPhone) $updateData['phone'] = $cleanPhone;
        if ($name) $updateData['customer_name'] = $name;
        if ($email) $updateData['email'] = $email;
        if ($governorate) $updateData['governorate'] = $governorate;
        if ($address) $updateData['customer_address'] = $address;

        if ($abandonedCart) {
            $abandonedCart->update($updateData);
        } else {
            $updateData['tenant_id'] = $tenantId;
            $updateData['session_id'] = $sessionId;
            $updateData['recovery_token'] = Str::random(40);
            $abandonedCart = AbandonedCart::create($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ مسودة السلة المتروكة بنجاح',
            'cart_id' => $abandonedCart->id,
            'token' => $abandonedCart->recovery_token,
        ]);
    }
}
