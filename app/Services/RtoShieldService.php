<?php

namespace App\Services;

use App\Models\BlacklistRecord;
use App\Models\Order;
use App\Scopes\TenantScope;

class RtoShieldService
{
    /**
     * Generate Egyptian phone variations for reliable lookup.
     */
    public static function getPhoneVariants(?string $phone): array
    {
        if (empty($phone)) {
            return [];
        }

        $raw = trim($phone);
        $digits = preg_replace('/[^0-9]/', '', $raw);

        $variants = [$raw, $digits];

        if (str_starts_with($digits, '20')) {
            $without20 = substr($digits, 2);
            $variants[] = $without20;
            $variants[] = '0' . $without20;
            $variants[] = '+20' . $without20;
        } elseif (str_starts_with($digits, '0')) {
            $without0 = substr($digits, 1);
            $variants[] = $without0;
            $variants[] = '20' . $without0;
            $variants[] = '+20' . $without0;
        } else {
            $variants[] = '0' . $digits;
            $variants[] = '20' . $digits;
            $variants[] = '+20' . $digits;
        }

        return array_values(array_filter(array_unique($variants)));
    }

    /**
     * Assess risk for a given customer phone number.
     * Returns has_issues = true ONLY if customer has blacklists, returns, or cancellations.
     */
    public static function assessRisk(?string $phone, ?int $tenantId = null): array
    {
        if (empty($phone)) {
            return [
                'phone' => '',
                'has_issues' => false,
                'risk_level' => 'clean',
                'badge_text' => '',
                'badge_color' => 'gray',
                'is_blocked' => false,
                'is_whitelisted' => false,
                'is_local_blacklisted' => false,
                'is_global_blacklisted' => false,
                'reports_count' => 0,
                'total_orders' => 0,
                'delivered_orders' => 0,
                'returned_orders' => 0,
                'cancelled_orders' => 0,
                'delivery_rate' => 0,
                'reasons' => [],
            ];
        }

        $variants = self::getPhoneVariants($phone);

        // 1. Check if number is whitelisted by SuperAdmin
        $isWhitelisted = BlacklistRecord::withoutGlobalScope(TenantScope::class)
            ->where('is_whitelisted', true)
            ->whereIn('value', $variants)
            ->exists();

        // 2. Check local and global blacklist records
        $localQuery = BlacklistRecord::where('type', 'phone')->whereIn('value', $variants);
        if ($tenantId) {
            $localQuery->where('tenant_id', $tenantId);
        }
        $isLocalBlacklisted = !$isWhitelisted && $localQuery->exists();

        $globalRecord = BlacklistRecord::withoutGlobalScope(TenantScope::class)
            ->where('is_global', true)
            ->where('type', 'phone')
            ->whereIn('value', $variants)
            ->first();
        $isGlobalBlacklisted = !$isWhitelisted && (bool) $globalRecord;

        // Count how many unique merchants reported this phone
        $reportingTenantsCount = 0;
        if (!$isWhitelisted) {
            $reportingTenantsCount = BlacklistRecord::withoutGlobalScope(TenantScope::class)
                ->where('type', 'phone')
                ->whereIn('value', $variants)
                ->where('is_whitelisted', false)
                ->distinct('tenant_id')
                ->count('tenant_id');
        }

        // 3. Platform-wide Order History stats for this customer phone
        $ordersStats = Order::withoutGlobalScope(TenantScope::class)
            ->whereIn('customer_phone', $variants)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('delivered', 'completed') THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status IN ('returned', 'rejected', 'failed', 'refunded') THEN 1 ELSE 0 END) as returned,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        $totalOrders = (int) ($ordersStats->total ?? 0);
        $deliveredOrders = (int) ($ordersStats->delivered ?? 0);
        $returnedOrders = (int) ($ordersStats->returned ?? 0);
        $cancelledOrders = (int) ($ordersStats->cancelled ?? 0);

        $closedOrders = $deliveredOrders + $returnedOrders;
        $deliveryRate = $closedOrders > 0 ? round(($deliveredOrders / $closedOrders) * 100, 1) : 0;

        $isBlocked = $isLocalBlacklisted || $isGlobalBlacklisted;

        // Has issues flag: ONLY true if there is a real problem (block, return, cancellation, or reports)
        $hasIssues = $isBlocked || $returnedOrders > 0 || $cancelledOrders > 0 || $reportingTenantsCount > 0;

        $reasons = [];

        if ($isLocalBlacklisted) {
            $reasons[] = 'مسجل في القائمة السوداء لمتجرك.';
        }

        if ($isGlobalBlacklisted) {
            $reasons[] = 'مسجل في قائمة الحظر العام للمنصة.';
        }

        if ($reportingTenantsCount > 1) {
            $reasons[] = "تم الإبلاغ عنه من قبل {$reportingTenantsCount} متاجر أخرى.";
        } elseif ($reportingTenantsCount === 1 && !$isLocalBlacklisted) {
            $reasons[] = 'تم الإبلاغ عنه من متجر آخر.';
        }

        if ($returnedOrders > 0) {
            $reasons[] = "لديه {$returnedOrders} شحنة مرتجعة سابقة لم يستلمها.";
        }

        if ($cancelledOrders > 0) {
            $reasons[] = "لديه {$cancelledOrders} طلب تم إلغاؤه مسبقاً.";
        }

        // Decision logic
        if (!$hasIssues) {
            return [
                'phone' => $phone,
                'has_issues' => false,
                'risk_level' => 'clean',
                'badge_text' => '',
                'badge_color' => 'gray',
                'is_blocked' => false,
                'is_whitelisted' => $isWhitelisted,
                'is_local_blacklisted' => false,
                'is_global_blacklisted' => false,
                'reports_count' => 0,
                'total_orders' => $totalOrders,
                'delivered_orders' => $deliveredOrders,
                'returned_orders' => 0,
                'cancelled_orders' => 0,
                'delivery_rate' => $deliveryRate,
                'reasons' => [],
            ];
        }

        // If there ARE issues:
        $riskLevel = 'medium';
        $badgeText = 'تحذير طلب ⚠️';
        $badgeColor = 'amber';

        if ($isBlocked || $returnedOrders >= 2 || $reportingTenantsCount >= 2) {
            $riskLevel = 'high';
            $badgeText = $isBlocked ? 'محظور 🚫' : 'خطر مرتجع عالي 🚨';
            $badgeColor = 'red';
        } elseif ($returnedOrders === 1) {
            $badgeText = 'مرتجع سابق ⚠️';
            $badgeColor = 'amber';
        } elseif ($cancelledOrders >= 1) {
            $badgeText = 'طلب ملغي سابق ⚠️';
            $badgeColor = 'amber';
        } elseif ($reportingTenantsCount === 1) {
            $badgeText = 'بلاغ من متجر آخر ⚠️';
            $badgeColor = 'amber';
        }

        return [
            'phone' => $phone,
            'has_issues' => true,
            'risk_level' => $riskLevel,
            'badge_text' => $badgeText,
            'badge_color' => $badgeColor,
            'is_blocked' => $isBlocked,
            'is_whitelisted' => $isWhitelisted,
            'is_local_blacklisted' => $isLocalBlacklisted,
            'is_global_blacklisted' => $isGlobalBlacklisted,
            'reports_count' => $reportingTenantsCount,
            'total_orders' => $totalOrders,
            'delivered_orders' => $deliveredOrders,
            'returned_orders' => $returnedOrders,
            'cancelled_orders' => $cancelledOrders,
            'delivery_rate' => $deliveryRate,
            'reasons' => $reasons,
        ];
    }
}
