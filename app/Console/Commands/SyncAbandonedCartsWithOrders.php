<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\Tenant;

class SyncAbandonedCartsWithOrders extends Command
{
    protected $signature = 'carts:sync-orders {--tenant= : معرف التاجر لتطبيق المزامنة عليه فقط}';
    protected $description = 'حذف السلات المتروكة التي يمتلك أصحابها طلبات فعلية أو تم تحويلها بالفعل إلى طلبات';

    public function handle(): void
    {
        $tenantId = $this->option('tenant');
        $tenantsQuery = Tenant::query();
        if ($tenantId) {
            $tenantsQuery->where('id', $tenantId);
        }
        $tenants = $tenantsQuery->pluck('id');

        $totalDeleted = 0;

        foreach ($tenants as $tId) {
            $deleted = self::syncForTenant($tId);
            $totalDeleted += $deleted;
        }

        $this->info("تمت المزامنة بنجاح. تم حذف {$totalDeleted} سلة متروكة لعملاء لديهم طلبات فعلية.");
    }

    /**
     * تنفيذ المزامنة والحذف لتاجر معين
     */
    public static function syncForTenant(int|string $tenantId): int
    {
        if (!$tenantId) return 0;

        $deletedCount = 0;

        // 1. حذف أي سلات معلمة بأنها محولة لطلبات (converted أو بها converted_order_id أو recovered_at)
        $convertedDeleted = AbandonedCart::where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->whereNotNull('converted_order_id')
                  ->orWhere('status', 'converted')
                  ->orWhereNotNull('recovered_at');
            })
            ->delete();

        $deletedCount += $convertedDeleted;

        // 2. جلب جميع أرقام هواتف العملاء الذين لديهم طلبات فعلية في هذا المتجر
        $orderPhones = Order::where('tenant_id', $tenantId)
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->pluck('customer_phone')
            ->map(fn($p) => self::normalizePhone($p))
            ->filter()
            ->unique()
            ->toArray();

        if (!empty($orderPhones)) {
            // جلب السلات المتبقية للتاجر ومقارنة أرقام الهواتف بعد تنظيفها
            $carts = AbandonedCart::where('tenant_id', $tenantId)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->get(['id', 'phone']);

            $cartIdsToDelete = [];
            foreach ($carts as $cart) {
                $norm = self::normalizePhone($cart->phone);
                if (!empty($norm) && in_array($norm, $orderPhones, true)) {
                    $cartIdsToDelete[] = $cart->id;
                }
            }

            if (!empty($cartIdsToDelete)) {
                AbandonedCart::whereIn('id', $cartIdsToDelete)->delete();
                $deletedCount += count($cartIdsToDelete);
            }
        }

        // 3. حذف أي سجلات مكررة لنفس رقم الهاتف داخل السلات المتروكة للمتجر (إبقاء الأحدث فقط)
        $remainingCarts = AbandonedCart::where('tenant_id', $tenantId)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id', 'desc')
            ->get(['id', 'phone']);

        $seenPhones = [];
        $duplicateIds = [];
        foreach ($remainingCarts as $c) {
            $norm = self::normalizePhone($c->phone);
            if (empty($norm)) continue;
            if (isset($seenPhones[$norm])) {
                $duplicateIds[] = $c->id;
            } else {
                $seenPhones[$norm] = true;
            }
        }

        if (!empty($duplicateIds)) {
            AbandonedCart::whereIn('id', $duplicateIds)->delete();
            $deletedCount += count($duplicateIds);
        }

        return $deletedCount;
    }

    /**
     * تنظيف وتوحيد رقم الهاتف (دعم الأرقام العربية، إزالة المسافات، أخذ آخر 10 أرقام)
     */
    public static function normalizePhone(?string $phone): string
    {
        if (empty($phone)) return '';
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $latin  = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $clean = str_replace($arabic, $latin, (string)$phone);
        $digits = preg_replace('/\D/', '', $clean);
        if (strlen($digits) < 7) {
            return '';
        }
        if (strlen($digits) > 10) {
            return substr($digits, -10);
        }
        return $digits;
    }
}
