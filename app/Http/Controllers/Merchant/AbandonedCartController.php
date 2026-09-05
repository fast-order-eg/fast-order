<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingGovernorate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AbandonedCartController extends Controller
{
    /**
     * عرض قائمة السلات المتروكة مع الإحصائيات
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant') ?? auth()->user()?->tenant;
        $tenantId = $tenant?->id;

        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = AbandonedCart::where('tenant_id', $tenantId)
            ->with(['order:id,reference_number,total,status'])
            ->latest('updated_at');

        // فلترة بالبحث
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('governorate', 'like', "%{$search}%");
            });
        }

        // فلترة بالحالة
        if ($status !== '' && $status !== 'all') {
            if ($status === 'abandoned') {
                $query->where('status', 'abandoned')->whereNull('recovered_at');
            } elseif ($status === 'contacted') {
                $query->where('status', 'contacted')->whereNull('recovered_at');
            } elseif ($status === 'converted') {
                $query->where(function ($q) {
                    $q->where('status', 'converted')->orWhereNotNull('recovered_at');
                });
            }
        }

        // فلترة بالتاريخ
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $abandonedCarts = $query->paginate(20)->withQueryString();

        // حساب الإحصائيات الشاملة
        $totalCarts = AbandonedCart::where('tenant_id', $tenantId)->count();
        $abandonedCount = AbandonedCart::where('tenant_id', $tenantId)
            ->where('status', 'abandoned')
            ->whereNull('recovered_at')
            ->count();
        $contactedCount = AbandonedCart::where('tenant_id', $tenantId)
            ->where('status', 'contacted')
            ->whereNull('recovered_at')
            ->count();
        $convertedCount = AbandonedCart::where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('status', 'converted')->orWhereNotNull('recovered_at');
            })
            ->count();

        $lostRevenue = (float) AbandonedCart::where('tenant_id', $tenantId)
            ->whereNull('recovered_at')
            ->sum('total');

        $recoveredRevenue = (float) AbandonedCart::where('tenant_id', $tenantId)
            ->whereNotNull('recovered_at')
            ->sum('total');

        $recoveryRate = $totalCarts > 0 ? round(($convertedCount / $totalCarts) * 100, 1) : 0;

        $stats = [
            'total_carts'       => $totalCarts,
            'abandoned_count'   => $abandonedCount,
            'contacted_count'   => $contactedCount,
            'converted_count'   => $convertedCount,
            'lost_revenue'      => $lostRevenue,
            'recovered_revenue' => $recoveredRevenue,
            'recovery_rate'     => $recoveryRate,
        ];

        return Inertia::render('Merchant/AbandonedCarts/Index', [
            'abandonedCarts' => $abandonedCarts,
            'stats'          => $stats,
            'filters'        => [
                'search'    => $search,
                'status'    => $status,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
            'tenant'         => [
                'name'  => $tenant?->name,
                'phone' => $tenant?->phone,
            ],
        ]);
    }

    /**
     * تحويل السلة المتروكة إلى طلب رسمي مؤكد
     */
    public function convert(Request $request, AbandonedCart $abandonedCart)
    {
        $tenant = $request->attributes->get('tenant') ?? auth()->user()?->tenant;
        if ($abandonedCart->tenant_id !== $tenant?->id) {
            abort(403);
        }

        if ($abandonedCart->status === 'converted' && $abandonedCart->converted_order_id) {
            return back()->with('error', 'تم تحويل هذه السلة بالفعل مسبقاً.');
        }

        $items = $abandonedCart->cart_data['items'] ?? [];
        if (empty($items)) {
            return back()->with('error', 'لا يمكن تحويل سلة فارغة إلى طلب. برجاء التأكد من وجود منتجات.');
        }

        $validated = $request->validate([
            'customer_name'    => 'nullable|string|max:255',
            'customer_phone'   => 'nullable|string|max:30',
            'customer_address' => 'nullable|string|max:1000',
            'governorate'      => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $customerName = $validated['customer_name'] ?? $abandonedCart->customer_name ?: 'عميل';
        $customerPhone = $validated['customer_phone'] ?? $abandonedCart->phone ?: '0000000000';
        $customerAddress = $validated['customer_address'] ?? $abandonedCart->customer_address ?: 'غير محدد';
        $governorate = $validated['governorate'] ?? $abandonedCart->governorate ?: 'القاهرة';

        // محاولة جلب تكلفة الشحن للمحافظة
        $shippingGov = ShippingGovernorate::where('tenant_id', $tenant->id)
            ->where('name', 'like', "%{$governorate}%")
            ->first();
        $shippingCost = $shippingGov ? (float) $shippingGov->price : 0;

        $subtotal = (float) ($abandonedCart->subtotal ?: 0);
        $total = max(0, $subtotal + $shippingCost);

        DB::beginTransaction();
        try {
            $notes = "[تم الاسترجاع والتحويل من السلة المتروكة رقم #{$abandonedCart->id}]";
            if (!empty($validated['notes'])) {
                $notes .= "\n" . $validated['notes'];
            }

            $order = Order::createWithReference([
                'tenant_id'        => $tenant->id,
                'customer_name'    => $customerName,
                'customer_phone'   => $customerPhone,
                'customer_email'   => $abandonedCart->email,
                'customer_address' => $customerAddress,
                'governorate'      => $governorate,
                'payment_method'   => 'cod',
                'payment_status'   => 'pending_cash',
                'shipping_cost'    => $shippingCost,
                'items'            => $items,
                'subtotal'         => $subtotal,
                'total'            => $total,
                'status'           => 'confirmed', // أوردر تم تأكيده مع العميل
                'notes'            => $notes,
            ]);

            // خصم المخزون
            foreach ($items as $item) {
                $pId = $item['product_id'] ?? ($item['id'] ?? null);
                if ($pId) {
                    $prod = Product::find($pId);
                    if ($prod) {
                        $qty = max(1, (int) ($item['quantity'] ?? ($item['qty'] ?? 1)));
                        $prod->decrementVariantStock(
                            $qty,
                            $item['selectedSize'] ?? null,
                            $item['selectedColor'] ?? null,
                            $item['options'] ?? []
                        );
                    }
                }
            }

            // تحديث السلة المتروكة
            $abandonedCart->update([
                'status'             => 'converted',
                'recovered_at'       => now(),
                'converted_order_id' => $order->id,
                'customer_name'      => $customerName,
                'customer_address'   => $customerAddress,
                'governorate'        => $governorate,
            ]);

            DB::commit();

            return back()->with('success', "تم تحويل السلة بنجاح إلى طلب مؤكد برقم مرجعي: {$order->reference_number}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء إنشاء الطلب: ' . $e->getMessage());
        }
    }

    /**
     * تسجيل التواصل مع العميل (مثلاً عند النقر على مراسلة واتساب)
     */
    public function markContacted(Request $request, AbandonedCart $abandonedCart)
    {
        $tenant = $request->attributes->get('tenant') ?? auth()->user()?->tenant;
        if ($abandonedCart->tenant_id !== $tenant?->id) {
            abort(403);
        }

        if ($abandonedCart->status !== 'converted') {
            $abandonedCart->update([
                'status'            => 'contacted',
                'last_contacted_at' => now(),
            ]);
        }

        return back()->with('success', 'تم تحديث حالة السلة إلى "تم التواصل" بنجاح.');
    }

    /**
     * حذف سلة متروكة
     */
    public function destroy(Request $request, AbandonedCart $abandonedCart)
    {
        $tenant = $request->attributes->get('tenant') ?? auth()->user()?->tenant;
        if ($abandonedCart->tenant_id !== $tenant?->id) {
            abort(403);
        }

        $abandonedCart->delete();

        return back()->with('success', 'تم حذف السلة المتروكة بنجاح.');
    }
}
