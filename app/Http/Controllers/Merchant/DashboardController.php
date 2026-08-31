<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubscriptionReceipt;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $tenantId = session()->get('tenant_id') ?? config('tenant.id') ?? 0;
        $tenant = \App\Models\Tenant::find($tenantId);

        $dashboardData = \App\Services\CacheService::getDashboardStats($tenantId, function () use ($tenant) {
            $now = Carbon::now();
            $lastMonth = Carbon::now()->subMonth();

            // ==========================================
            // إحصائيات أساسية للمتجر الحالي
            // ==========================================
            $totalOrders    = Order::count();
            $pendingOrders  = Order::where('status', 'pending')->count();
            $completedOrders = Order::where('status', 'confirmed')->count();
            $cancelledOrders = Order::where('status', 'cancelled')->count();
            $shippedOrders  = Order::where('status', 'shipped')->count();
            $deliveredOrders = Order::where('status', 'delivered')->count();

            $totalRevenue = Order::whereIn('status', ['confirmed', 'shipped', 'delivered'])
                ->sum('total');

            $avgOrderValue = $totalOrders > 0
                ? Order::whereIn('status', ['confirmed', 'shipped', 'delivered'])->avg('total')
                : 0;

            $activeProducts = Product::where('stock', '>', 0)->count();
            $totalProducts  = Product::count();

            // ==========================================
            // نسب التغيير مقارنةً بالشهر الماضي
            // ==========================================
            $currentMonthOrders = Order::whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)->count();

            $lastMonthOrders = Order::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)->count();

            $currentMonthRevenue = Order::whereIn('status', ['confirmed', 'shipped', 'delivered'])
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)->sum('total');

            $lastMonthRevenue = Order::whereIn('status', ['confirmed', 'shipped', 'delivered'])
                ->whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)->sum('total');

            $ordersChange  = $this->percentageChange($lastMonthOrders, $currentMonthOrders);
            $revenueChange = $this->percentageChange($lastMonthRevenue, $currentMonthRevenue);

            // ==========================================
            // بيانات الرسم البياني (آخر 7 أيام)
            // ==========================================
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $dailyRevenues = Order::whereIn('status', ['confirmed', 'shipped', 'delivered'])
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date_only, SUM(total) as daily_total')
                ->groupBy('date_only')
                ->pluck('daily_total', 'date_only')
                ->toArray();

            $chartLabels = [];
            $chartData   = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dateString = $date->toDateString();
                $chartLabels[] = $date->format('d/m');
                $chartData[]   = (float) ($dailyRevenues[$dateString] ?? 0.0);
            }

            // ==========================================
            // Quick Performance Report (الشهر الحالي)
            // ==========================================
            $bestDayRevenue = count($chartData) > 0 ? max($chartData) : 0;
            $bestDayIndex   = count($chartData) > 0 ? array_search($bestDayRevenue, $chartData) : null;
            $bestDayLabel   = ($bestDayIndex !== null && isset($chartLabels[$bestDayIndex])) ? $chartLabels[$bestDayIndex] : null;

            $storePhone = \App\Models\Setting::where('key', 'phone')->value('value')
                ?: \App\Models\Setting::where('key', 'whatsapp')->value('value');

            return [
                'stats' => [
                    'total_orders'            => $totalOrders,
                    'pending_orders'          => $pendingOrders,
                    'completed_orders'        => $completedOrders,
                    'cancelled_orders'        => $cancelledOrders,
                    'shipped_orders'          => $shippedOrders,
                    'delivered_orders'        => $deliveredOrders,
                    'total_revenue'           => round((float) $totalRevenue, 2),
                    'avg_order_value'         => round((float) $avgOrderValue, 2),
                    'active_products'         => $activeProducts,
                    'total_products'          => $totalProducts,
                    'orders_change'           => $ordersChange,
                    'revenue_change'          => $revenueChange,
                    'current_month_orders'    => $currentMonthOrders,
                    'last_month_orders'       => $lastMonthOrders,
                    'current_month_revenue'   => round((float) $currentMonthRevenue, 2),
                    'last_month_revenue'      => round((float) $lastMonthRevenue, 2),
                    'wallet_balance'          => $tenant ? round((float) $tenant->wallet_balance, 2) : 0,
                    'store_phone'             => $storePhone,
                    'best_day_revenue'        => round((float) $bestDayRevenue, 2),
                    'best_day_label'          => $bestDayLabel,
                ],
                'chart' => [
                    'labels' => $chartLabels,
                    'data'   => $chartData,
                ],
            ];
        });

        // ==========================================
        // آخر 8 طلبات (بدون كاش)
        // ==========================================
        $recentOrders = Order::latest()
            ->take(8)
            ->get()
            ->map(fn($order) => [
                'id'               => $order->id,
                'reference_number' => $order->reference_number,
                'customer_name'    => $order->customer_name,
                'customer_phone'   => $order->customer_phone ?? null,
                'total'            => $order->total,
                'status'           => $order->status,
                'status_text'      => $this->statusText($order->status),
                'created_at'       => $order->created_at?->format('Y-m-d h:i A'),
                'created_at_date'  => $order->created_at?->format('Y-m-d'),
                'created_at_time'  => $order->created_at?->format('h:i A'),
            ]);

        // ==========================================
        // إيصالات الدفع المعلقة للتاجر الحالي
        // ==========================================
        $pendingReceiptsList = [];
        $pendingReceiptsCount = 0;

        if ($tenant) {
            $pendingReceiptsQuery = SubscriptionReceipt::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $pendingReceiptsCount = SubscriptionReceipt::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->count();

            $pendingReceiptsList = $pendingReceiptsQuery->map(fn($r) => [
                'id'                => $r->id,
                'reference_code'    => $r->reference_code,
                'amount'            => $r->amount,
                'payment_method'    => $r->payment_method,
                'payment_reference' => $r->payment_reference,
                'type'              => $r->type,
                'created_at'        => $r->created_at?->format('Y-m-d'),
                'created_at_human'  => $r->created_at?->diffForHumans(),
            ])->toArray();
        }

        $activeSub = $tenant ? ($tenant->subscriptions()->where('status', 'active')->latest()->first() ?? $tenant->subscriptions()->latest()->first()) : null;
        $planName = $activeSub?->plan?->name ?? 'الباقة المجانية';
        $isCommission = $tenant ? $tenant->isCommissionPlan() : false;
        if (!$isCommission && $activeSub) {
            $isCommission = $activeSub->plan?->slug === 'commission' || str_contains($activeSub->plan?->name ?? '', 'عمولة') || str_contains($activeSub->plan?->name ?? '', 'المحفظة');
        }

        $isExpired = false;
        if ($tenant && !$isCommission) {
            if (!$tenant->is_active || $tenant->subscription_status === 'expired' || ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast())) {
                $isExpired = true;
            }
        }

        $endsAt = $isCommission ? null : ($activeSub?->ends_at ?? $activeSub?->trial_ends_at ?? $tenant?->subscription_ends_at ?? $tenant?->trial_ends_at);

        $subscriptionInfo = [
            'is_expired'           => $isExpired,
            'is_active'            => $tenant?->is_active ?? true,
            'subscription_status'  => $tenant?->subscription_status ?? 'active',
            'subscription_ends_at' => $endsAt ? $endsAt->format('Y-m-d') : null,
            'plan_name'            => $planName,
            'is_commission'        => $isCommission,
        ];

        // Always fetch fresh wallet balance to prevent cache mismatch
        $stats = $dashboardData['stats'];
        $freshTenant = $tenant ? $tenant->fresh() : null;
        $stats['wallet_balance'] = $freshTenant ? round((float) $freshTenant->wallet_balance, 2) : 0;

        return Inertia::render('Merchant/Dashboard', [
            'stats'                => $stats,
            'recentOrders'         => $recentOrders,
            'chart'                => $dashboardData['chart'],
            'pendingReceiptsCount' => $pendingReceiptsCount,
            'pendingReceiptsList'  => $pendingReceiptsList,
            'subscriptionInfo'     => $subscriptionInfo,
        ]);
    }

    private function percentageChange($old, $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100.0 : 0.0;
        }
        return round((($new - $old) / $old) * 100, 1);
    }

    private function statusText(string $status): string
    {
        return match ($status) {
            'pending'   => 'في الانتظار',
            'confirmed' => 'مؤكد',
            'shipped'   => 'في التوصيل',
            'delivered' => 'تم التسليم',
            'cancelled' => 'ملغي',
            default     => $status,
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'pending'   => 'yellow',
            'confirmed' => 'blue',
            'shipped'   => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            default     => 'gray',
        };
    }
}
