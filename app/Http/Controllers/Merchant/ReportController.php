<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    /**
     * Display the Net Profit & Analytics Dashboard for the merchant.
     */
    public function index(Request $request): Response
    {
        $now = Carbon::now();
        $period = $request->input('period', 'this_month');
        $dateFromInput = $request->input('date_from');
        $dateToInput = $request->input('date_to');

        // Determine date boundaries
        if ($period === 'last_month') {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            $endDate = Carbon::now()->subMonth()->endOfMonth();
            $periodLabel = $startDate->translatedFormat('F Y');
            $keySuffix = $startDate->format('Y_m');
        } elseif ($period === 'all_time') {
            $startDate = Carbon::create(2020, 1, 1);
            $endDate = Carbon::now()->endOfDay();
            $periodLabel = 'كل الأوقات';
            $keySuffix = 'all_time';
        } elseif ($period === 'custom' && $dateFromInput && $dateToInput) {
            $startDate = Carbon::parse($dateFromInput)->startOfDay();
            $endDate = Carbon::parse($dateToInput)->endOfDay();
            $periodLabel = "من {$startDate->format('Y-m-d')} إلى {$endDate->format('Y-m-d')}";
            $keySuffix = 'custom_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd');
        } else {
            // Default: this month
            $period = 'this_month';
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            $periodLabel = $now->translatedFormat('F Y');
            $keySuffix = $now->format('Y_m');
        }

        // Retrieve manual Ad Spend and Extra Expenses
        $manualAdSpend = (float) $request->input('ad_spend', Setting::get("profit_ad_spend_{$keySuffix}", 0));
        $otherExpenses = (float) $request->input('other_expenses', Setting::get("profit_other_expenses_{$keySuffix}", 0));

        // Query orders in the selected period
        $ordersQuery = Order::whereBetween('created_at', [$startDate, $endDate]);
        $orders = $ordersQuery->get();

        // Product Cost Map (cost_price per product ID)
        $productCosts = Product::pluck('cost_price', 'id')->toArray();

        // 1. Order Status Counts
        $totalOrdersCount = $orders->count();
        $deliveredOrders = $orders->filter(fn($o) => in_array($o->status, ['delivered', 'completed']));
        $shippedOrders = $orders->filter(fn($o) => in_array($o->status, ['shipped']));
        $confirmedOrders = $orders->filter(fn($o) => in_array($o->status, ['confirmed']));
        $returnedOrders = $orders->filter(fn($o) => in_array($o->status, ['returned', 'rejected', 'failed', 'refunded']));
        $cancelledOrders = $orders->filter(fn($o) => $o->status === 'cancelled');

        $deliveredCount = $deliveredOrders->count();
        $shippedCount = $shippedOrders->count();
        $confirmedCount = $confirmedOrders->count();
        $returnedCount = $returnedOrders->count();
        $cancelledCount = $cancelledOrders->count();

        // Delivery vs Return rates (COD key metrics)
        $closedCount = $deliveredCount + $returnedCount;
        $deliveryRate = $closedCount > 0 ? round(($deliveredCount / $closedCount) * 100, 1) : 0;
        $returnRate = $closedCount > 0 ? round(($returnedCount / $closedCount) * 100, 1) : 0;

        // 2. Financial Metrics
        // Delivered Revenue (المبيعات المحصلة فعلياً)
        $deliveredRevenue = (float) $deliveredOrders->sum('total');

        // Pending Revenue in Transit (المبيعات قيد التوصيل)
        $inTransitRevenue = (float) $shippedOrders->sum('total') + (float) $confirmedOrders->sum('total');

        // Gross Revenue (إجمالي كل المبيعات النشطة)
        $grossRevenue = $deliveredRevenue + $inTransitRevenue;

        // Cost of Goods Sold (COGS) for delivered orders
        $deliveredCogs = 0.0;
        foreach ($deliveredOrders as $order) {
            if (is_array($order->items)) {
                foreach ($order->items as $item) {
                    $pid = $item['id'] ?? null;
                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                    $unitCost = (float) ($productCosts[$pid] ?? 0);
                    $deliveredCogs += ($unitCost * $qty);
                }
            }
        }

        // COGS for all active orders (including shipped)
        $totalActiveCogs = $deliveredCogs;
        foreach ($shippedOrders->merge($confirmedOrders) as $order) {
            if (is_array($order->items)) {
                foreach ($order->items as $item) {
                    $pid = $item['id'] ?? null;
                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                    $unitCost = (float) ($productCosts[$pid] ?? 0);
                    $totalActiveCogs += ($unitCost * $qty);
                }
            }
        }

        // Actual shipping fees paid
        $deliveredShippingFees = (float) $deliveredOrders->sum('shipping_cost');
        if ($deliveredShippingFees == 0) {
            // Fallback estimation: 45 EGP per delivered order
            $deliveredShippingFees = $deliveredCount * 45;
        }

        // Return shipping fees & losses (مرتجع الشحن)
        $returnShippingFees = (float) $returnedOrders->sum('shipping_cost');
        if ($returnShippingFees == 0) {
            // In Egypt COD, return fee is usually standard shipping fee
            $returnShippingFees = $returnedCount * 45;
        }

        // Total logistics cost
        $totalShippingCost = $deliveredShippingFees + $returnShippingFees;

        // NET PROFIT CALCULATION (معادلة صافي الربح الحقيقي)
        // صافي الربح المحقق من المسلم = المبيعات المسلمة - تكلفة البضاعة - مصاريف الشحن - مصاريف شحن المرتجع - صرف الإعلانات - المصروفات الإضافية
        $realizedNetProfit = $deliveredRevenue - $deliveredCogs - $totalShippingCost - $manualAdSpend - $otherExpenses;

        // Estimated Net Profit (if current shipped orders deliver at the historical rate)
        $estimatedInTransitNet = ($inTransitRevenue * ($deliveryRate > 0 ? ($deliveryRate / 100) : 0.7))
            - ($totalActiveCogs - $deliveredCogs)
            - ($shippedCount * 45);
        $projectedNetProfit = $realizedNetProfit + max(0, $estimatedInTransitNet);

        // Net Margin Percentage
        $netMargin = $deliveredRevenue > 0 ? round(($realizedNetProfit / $deliveredRevenue) * 100, 1) : 0;

        // Average Order Value (AOV)
        $avgOrderValue = $deliveredCount > 0 ? round($deliveredRevenue / $deliveredCount, 2) : 0;

        // 3. Daily Profit & Sales Chart
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;
        $chartLabels = [];
        $dailySales = [];
        $dailyProfit = [];
        $dailyOrders = [];

        // If period is <= 31 days, group by day; else group by month
        if ($daysInPeriod <= 31) {
            $periodDays = Carbon::parse($startDate)->toPeriod($endDate);
            foreach ($periodDays as $d) {
                $dayStr = $d->format('Y-m-d');
                $chartLabels[] = $d->format('d/m');

                $dayOrders = $orders->filter(fn($o) => $o->created_at->format('Y-m-d') === $dayStr);
                $dayDelivered = $dayOrders->filter(fn($o) => in_array($o->status, ['delivered', 'completed', 'confirmed']));
                $daySalesVal = (float) $dayDelivered->sum('total');

                // Day estimated COGS
                $dayCogsVal = 0.0;
                foreach ($dayDelivered as $do) {
                    if (is_array($do->items)) {
                        foreach ($do->items as $it) {
                            $pid = $it['id'] ?? null;
                            $qty = (int) ($it['quantity'] ?? $it['qty'] ?? 1);
                            $dayCogsVal += ((float) ($productCosts[$pid] ?? 0)) * $qty;
                        }
                    }
                }

                $dailySales[] = round($daySalesVal, 2);
                $dailyProfit[] = round(max(0, $daySalesVal - $dayCogsVal - ($dayDelivered->count() * 45)), 2);
                $dailyOrders[] = $dayOrders->count();
            }
        }

        // 4. Top Products by Revenue & Profitability
        $productStats = [];
        foreach ($orders as $order) {
            if (!in_array($order->status, ['delivered', 'completed', 'confirmed', 'shipped'])) {
                continue;
            }
            if (is_array($order->items)) {
                foreach ($order->items as $item) {
                    $pid = $item['id'] ?? null;
                    if (!$pid) continue;

                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                    $price = (float) ($item['price'] ?? 0);
                    $subtotal = $qty * $price;
                    $unitCost = (float) ($productCosts[$pid] ?? 0);
                    $cogs = $unitCost * $qty;
                    $profit = $subtotal - $cogs;

                    if (!isset($productStats[$pid])) {
                        $productStats[$pid] = [
                            'id' => $pid,
                            'name' => $item['name'] ?? 'منتج',
                            'qty' => 0,
                            'revenue' => 0.0,
                            'cogs' => 0.0,
                            'profit' => 0.0,
                            'cost_price' => $unitCost,
                        ];
                    }

                    $productStats[$pid]['qty'] += $qty;
                    $productStats[$pid]['revenue'] += $subtotal;
                    $productStats[$pid]['cogs'] += $cogs;
                    $productStats[$pid]['profit'] += $profit;
                }
            }
        }

        $topProducts = collect($productStats)
            ->sortByDesc('profit')
            ->take(6)
            ->values()
            ->all();

        // 5. Governorates Distribution
        $governorates = $orders->groupBy(fn($o) => $o->governorate ?: 'غير محدد')
            ->map(function ($group, $gov) {
                return [
                    'governorate' => $gov,
                    'orders_count' => $group->count(),
                    'delivered_count' => $group->whereIn('status', ['delivered', 'completed'])->count(),
                    'returned_count' => $group->whereIn('status', ['returned', 'rejected', 'failed'])->count(),
                    'revenue' => round((float) $group->sum('total'), 2),
                ];
            })
            ->sortByDesc('revenue')
            ->values()
            ->take(10)
            ->all();

        return Inertia::render('Merchant/Reports/Index', [
            'chart' => [
                'labels' => $chartLabels,
                'sales' => $dailySales,
                'profit' => $dailyProfit,
                'orders' => $dailyOrders,
                'period_label' => $periodLabel,
            ],
            'profitMetrics' => [
                'realized_net_profit' => round($realizedNetProfit, 2),
                'projected_net_profit' => round($projectedNetProfit, 2),
                'net_margin' => $netMargin,
                'delivered_revenue' => round($deliveredRevenue, 2),
                'in_transit_revenue' => round($inTransitRevenue, 2),
                'gross_revenue' => round($grossRevenue, 2),
                'delivered_cogs' => round($deliveredCogs, 2),
                'total_shipping_cost' => round($totalShippingCost, 2),
                'delivered_shipping_fees' => round($deliveredShippingFees, 2),
                'return_shipping_fees' => round($returnShippingFees, 2),
                'ad_spend' => $manualAdSpend,
                'other_expenses' => $otherExpenses,
                'delivery_rate' => $deliveryRate,
                'return_rate' => $returnRate,
                'delivered_orders' => $deliveredCount,
                'returned_orders' => $returnedCount,
                'shipped_orders' => $shippedCount,
                'confirmed_orders' => $confirmedCount,
                'cancelled_orders' => $cancelledCount,
                'total_orders' => $totalOrdersCount,
                'avg_order_value' => $avgOrderValue,
            ],
            'topProducts' => $topProducts,
            'governorates' => $governorates,
            'filters' => [
                'period' => $period,
                'date_from' => $dateFromInput ?: $startDate->format('Y-m-d'),
                'date_to' => $dateToInput ?: $endDate->format('Y-m-d'),
                'ad_spend' => $manualAdSpend,
                'other_expenses' => $otherExpenses,
            ],
        ]);
    }

    /**
     * Save manual expenses (Ad Spend, Packaging, etc.) for the active period.
     */
    public function saveExpenses(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_key' => ['required', 'string'],
            'ad_spend' => ['required', 'numeric', 'min:0'],
            'other_expenses' => ['nullable', 'numeric', 'min:0'],
        ]);

        $keySuffix = preg_replace('/[^a-zA-Z0-9_]/', '', $validated['period_key']);
        $adSpend = (float) $validated['ad_spend'];
        $otherExpenses = (float) ($validated['other_expenses'] ?? 0);

        Setting::set("profit_ad_spend_{$keySuffix}", $adSpend, 'profit_reports');
        Setting::set("profit_other_expenses_{$keySuffix}", $otherExpenses, 'profit_reports');

        return redirect()->back()->with('success', 'تم حفظ وتحديث المصروفات الإعلانية والتشغيلية بنجاح ✓');
    }
}
