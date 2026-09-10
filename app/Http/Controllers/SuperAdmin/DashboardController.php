<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\SubscriptionReceipt;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // ====== Date Range Filter ======
        $range = $request->query('date_range', 'all');
        [$dateFrom, $dateTo] = $this->resolveDateRange($range);

        // 1. Platform Health Metrics
        $storesQuery = Tenant::query();
        $subscriptionsQuery = Subscription::where('status', 'active');
        $ordersQuery = Order::withoutGlobalScopes();
        $revenueQuery = SubscriptionReceipt::where('status', 'approved');

        if ($dateFrom && $dateTo) {
            $storesQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
            $subscriptionsQuery->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('starts_at', [$dateFrom, $dateTo])
                  ->orWhereBetween('created_at', [$dateFrom, $dateTo]);
            });
            $ordersQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
            $revenueQuery->whereBetween('approved_at', [$dateFrom, $dateTo]);
        }

        $totalStores = (clone $storesQuery)->count();
        $activeStores = (clone $storesQuery)->where('is_active', true)->count();
        $suspendedStores = $totalStores - $activeStores;
        
        $totalSubscriptions = $subscriptionsQuery->count();
        $pendingPaymentsCount = SubscriptionReceipt::where('status', 'pending')->count();

        $platformOrdersCount = $ordersQuery->count();
        $platformRevenue = $revenueQuery->sum('amount');

        // 2. Pending Receipts List
        $pendingReceiptsList = SubscriptionReceipt::with(['tenant', 'plan'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($receipt) {
                return [
                    'id' => $receipt->id,
                    'reference_code' => $receipt->reference_code,
                    'type' => $receipt->type,
                    'amount' => $receipt->amount,
                    'payment_method' => $receipt->payment_method,
                    'payment_reference' => $receipt->payment_reference,
                    'tenant_name' => $receipt->tenant ? $receipt->tenant->name : 'غير معروف',
                    'tenant_phone' => $receipt->tenant ? $receipt->tenant->phone : null,
                    'plan_name' => $receipt->plan ? $receipt->plan->name : 'غير محدد',
                    'created_at' => $receipt->created_at ? $receipt->created_at->format('Y-m-d h:i A') : null,
                    'created_at_human' => $receipt->created_at ? $receipt->created_at->diffForHumans() : null,
                ];
            });

        // 3. Expiring Subscriptions (Next 4 Days) - Exclude permanent commission plans
        $fourDaysFromNow = Carbon::now()->addDays(4);
        $expiringSubscriptions = Subscription::with(['tenant', 'plan'])
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $fourDaysFromNow)
            ->where('ends_at', '>=', Carbon::now())
            ->whereDoesntHave('plan', function ($q) {
                $q->where('slug', 'commission')
                  ->orWhere('name', 'like', '%عمولة%');
            })
            ->where('billing_cycle', '!=', 'commission')
            ->orderBy('ends_at', 'asc')
            ->take(10)
            ->get()
            ->map(function ($sub) {
                $days = Carbon::now()->diffInDays(Carbon::parse($sub->ends_at), false);
                return [
                    'id' => $sub->id,
                    'tenant_id' => $sub->tenant_id,
                    'tenant_name' => $sub->tenant ? $sub->tenant->name : 'غير معروف',
                    'tenant_phone' => $sub->tenant ? $sub->tenant->phone : null,
                    'plan_name' => $sub->plan ? $sub->plan->name : 'غير محدد',
                    'ends_at' => $sub->ends_at ? Carbon::parse($sub->ends_at)->format('Y-m-d') : null,
                    'days_left' => max(1, (int) round($days)),
                ];
            });

        // 4. Top Performing Stores (by Orders Count)
        $topOrdersQuery = Order::withoutGlobalScopes()
            ->select('tenant_id', DB::raw('count(*) as total_orders'));
            
        if ($dateFrom && $dateTo) {
            $topOrdersQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $topTenantsIds = $topOrdersQuery
            ->groupBy('tenant_id')
            ->orderByDesc('total_orders')
            ->limit(5)
            ->pluck('total_orders', 'tenant_id')
            ->toArray();

        $topStores = [];
        if (!empty($topTenantsIds)) {
            $tenants = Tenant::whereIn('id', array_keys($topTenantsIds))->get()->keyBy('id');
            foreach ($topTenantsIds as $tenantId => $ordersCount) {
                if (isset($tenants[$tenantId])) {
                    $topStores[] = [
                        'id' => $tenantId,
                        'name' => $tenants[$tenantId]->name,
                        'slug' => $tenants[$tenantId]->slug,
                        'total_orders' => $ordersCount,
                    ];
                }
            }
        }

        // 5. Recent Onboarding (Latest 5 Stores)
        $recentStores = Tenant::with(['owner'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'owner_name' => $tenant->owner ? $tenant->owner->name : 'غير معروف',
                    'created_at_human' => $tenant->created_at ? $tenant->created_at->diffForHumans() : null,
                    'is_active' => $tenant->is_active,
                ];
            });

        // 6. Graphs Data
        $driver = DB::connection()->getDriverName();
        $monthFormat = $driver === 'sqlite' 
            ? "strftime('%Y-%m', created_at)" 
            : "DATE_FORMAT(created_at, '%Y-%m')";
            
        $approvedMonthFormat = $driver === 'sqlite' 
            ? "strftime('%Y-%m', approved_at)" 
            : "DATE_FORMAT(approved_at, '%Y-%m')";

        $registrationsOverTime = Tenant::select(
            DB::raw("$monthFormat as month"),
            DB::raw("count(*) as count")
        )
        ->groupBy('month')
        ->orderBy('month', 'asc')
        ->take(12)
        ->get();

        $revenueOverTime = SubscriptionReceipt::select(
            DB::raw("$approvedMonthFormat as month"),
            DB::raw("sum(amount) as total_amount")
        )
        ->where('status', 'approved')
        ->groupBy('month')
        ->orderBy('month', 'asc')
        ->take(12)
        ->get();

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => [
                'total_stores' => $totalStores,
                'active_stores' => $activeStores,
                'suspended_stores' => $suspendedStores,
                'total_subscriptions' => $totalSubscriptions,
                'pending_payments' => $pendingPaymentsCount,
                'platform_orders' => $platformOrdersCount,
                'platform_revenue' => $platformRevenue,
            ],
            'currentDateRange' => $range,
            'pendingReceipts' => $pendingReceiptsList,
            'expiringSubscriptions' => $expiringSubscriptions,
            'topStores' => $topStores,
            'recentStores' => $recentStores,
            'graphs' => [
                'registrations' => $registrationsOverTime,
                'revenue' => $revenueOverTime,
            ]
        ]);
    }

    // ====== Helper: resolve date range ======
    private function resolveDateRange(string $range): array
    {
        $now = Carbon::now();
        return match($range) {
            'today'        => [$now->copy()->startOfDay(),   $now->copy()->endOfDay()],
            'yesterday'    => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7_days'  => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'current_month'=> [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month'   => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            default        => [null, null], // all
        };
    }
}
