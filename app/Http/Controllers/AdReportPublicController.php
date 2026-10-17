<?php

namespace App\Http\Controllers;

use App\Models\AdCampaignReport;
use App\Services\AdScopeMarketingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdReportPublicController extends Controller
{
    public function __construct(
        protected AdScopeMarketingService $adService
    ) {}

    /**
     * Display a public shared marketing ad report without requiring authentication.
     */
    public function show(Request $request, string $token): Response|JsonResponse
    {
        $report = AdCampaignReport::where('token', $token)->first();

        if (!$report) {
            abort(404, 'رابط تقرير الإعلانات غير موجود أو غير صالح.');
        }

        if (!$report->is_active) {
            abort(403, 'تم إيقاف هذا الرابط مؤقتاً من قِبل إدارة المنصة.');
        }

        $datePreset = $request->query('date_preset', 'maximum');
        if (!array_key_exists($datePreset, AdScopeMarketingService::ALLOWED_PRESETS)) {
            $datePreset = 'maximum';
        }

        $forceRefresh = (bool) $request->boolean('refresh');
        $campaignIds = $report->getCleanCampaignIds();

        $adsData = $this->adService->getCampaignsSummaryByCampaignIds(
            $campaignIds,
            $datePreset,
            $forceRefresh,
            "shared_ad_report_{$report->id}_{$datePreset}"
        );

        // If AJAX JSON request (e.g. background polling or quick refresh API)
        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'data'    => $adsData,
            ]);
        }

        return Inertia::render('Public/SharedAdReport', [
            'report'         => [
                'id'         => $report->id,
                'name'       => $report->name,
                'token'      => $report->token,
                'notes'      => $report->notes,
                'created_at' => $report->created_at?->format('Y-m-d'),
            ],
            'adsData'        => $adsData,
            'datePreset'     => $datePreset,
            'allowedPresets' => AdScopeMarketingService::ALLOWED_PRESETS,
            'hasCampaigns'   => $adsData['has_campaigns'] ?? false,
            'campaignIds'    => $campaignIds,
        ]);
    }
}
