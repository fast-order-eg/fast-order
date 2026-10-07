<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AdScopeMarketingService;
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
     * Display marketing ads performance dashboard for the current merchant.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $tenantId = session()->get('tenant_id') ?? config('tenant.id') ?? 0;
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            // Fallback to first tenant or empty
            $tenant = Tenant::first();
        }

        $datePreset = $request->query('date_preset', 'last_7d');
        if (!array_key_exists($datePreset, AdScopeMarketingService::ALLOWED_PRESETS)) {
            $datePreset = 'last_7d';
        }

        $forceRefresh = (bool) $request->boolean('refresh');

        $adsData = $tenant
            ? $this->adService->getCampaignsSummary($tenant, $datePreset, $forceRefresh)
            : [
                'success'         => true,
                'has_campaigns'   => false,
                'campaign_ids'    => [],
                'date_preset'     => $datePreset,
                'currency'        => 'EGP',
                'summary'         => null,
                'campaigns'       => [],
                'cached_at'       => null,
                'last_updated_at' => null,
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
            'allowedPresets' => AdScopeMarketingService::ALLOWED_PRESETS,
            'hasCampaigns'   => $adsData['has_campaigns'] ?? false,
            'campaignIds'    => $adsData['campaign_ids'] ?? [],
            'tenant'         => $tenant ? [
                'id'   => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ] : null,
        ]);
    }
}
