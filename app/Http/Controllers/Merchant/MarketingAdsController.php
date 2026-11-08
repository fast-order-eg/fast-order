<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\AdScopeMarketingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketingAdsController extends Controller
{
    public function __construct(
        protected AdScopeMarketingService $adService
    ) {}

    /**
     * Build Carbon date range from a date preset string or custom range.
     */
    private function getDateRange(string $preset, ?string $since = null, ?string $until = null): array
    {
        if ($preset === 'custom' && $since && $until) {
            try {
                return [Carbon::parse($since)->startOfDay(), Carbon::parse($until)->endOfDay()];
            } catch (\Throwable) {
                return [null, null];
            }
        }

        $now = Carbon::now();
        return match ($preset) {
            'today'      => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday'  => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7d'    => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            default      => [null, null], // maximum = no date filter
        };
    }

    /**
     * Resolve current tenant accurately.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        if ($tenant = $request->attributes->get('tenant')) {
            return $tenant;
        }

        $tenantId = session()->get('tenant_id')
            ?? config('tenant.id')
            ?? auth()->user()?->tenant_id;

        if ($tenantId) {
            return Tenant::find($tenantId);
        }

        return Tenant::first();
    }

    /**
     * Display marketing ads performance dashboard for the current merchant.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $datePreset = $request->query('date_preset', 'maximum');
        $since = $request->query('since');
        $until = $request->query('until');

        // Sanitize since and until (must be YYYY-MM-DD)
        if ($since && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
            $since = null;
        }
        if ($until && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $until)) {
            $until = null;
        }

        if ($since && $until) {
            $datePreset = 'custom';
        } elseif (!array_key_exists($datePreset, AdScopeMarketingService::ALLOWED_PRESETS)) {
            $datePreset = 'maximum';
        }

        $forceRefresh = (bool) $request->boolean('refresh');

        $adsData = $tenant
            ? $this->adService->getCampaignsSummary($tenant, $datePreset, $forceRefresh, $since, $until)
            : [
                'success'         => true,
                'has_campaigns'   => false,
                'campaign_ids'    => [],
                'date_preset'     => $datePreset,
                'since'           => $since,
                'until'           => $until,
                'currency'        => 'EGP',
                'summary'         => null,
                'campaigns'       => [],
                'cached_at'       => null,
                'last_updated_at' => null,
            ];

        // ── Store stats: real orders count & abandoned carts count ──
        [$dateFrom, $dateTo] = $this->getDateRange($datePreset, $since, $until);

        $ordersQuery = Order::where('tenant_id', $tenant?->id ?? 0);
        $abandonedQuery = AbandonedCart::where('tenant_id', $tenant?->id ?? 0)
            ->where('status', '!=', 'converted')
            ->whereNull('recovered_at');

        if ($dateFrom && $dateTo) {
            $ordersQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
            $abandonedQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $storeStats = [
            'orders_count'   => $ordersQuery->count(),
            'abandoned_count' => $abandonedQuery->count(),
        ];

        // If AJAX JSON request (e.g. background polling or quick refresh API)
        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'data'    => $adsData,
            ]);
        }

        return Inertia::render('Merchant/Marketing/Ads', [
            'adsData'        => $adsData,
            'datePreset'     => $datePreset,
            'since'          => $since,
            'until'          => $until,
            'allowedPresets' => AdScopeMarketingService::ALLOWED_PRESETS,
            'hasCampaigns'   => $adsData['has_campaigns'] ?? false,
            'campaignIds'    => $adsData['campaign_ids'] ?? [],
            'show_summary'   => (bool) ($tenant->meta_show_summary ?? false),
            'storeStats'     => $storeStats,
            'tenant'         => $tenant ? [
                'id'   => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ] : null,
        ]);
    }
}
