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
        $campaignIds = $report->getCleanCampaignIds();

        $cacheKey = ($datePreset === 'custom' && $since && $until)
            ? "shared_ad_report_{$report->id}_custom_{$since}_{$until}"
            : "shared_ad_report_{$report->id}_{$datePreset}";

        $adsData = $this->adService->getCampaignsSummaryByCampaignIds(
            $campaignIds,
            $datePreset,
            $forceRefresh,
            $cacheKey,
            $since,
            $until
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
                'id'           => $report->id,
                'name'         => $report->name,
                'token'        => $report->token,
                'notes'        => $report->notes,
                'show_summary' => (bool) $report->show_summary,
                'created_at'   => $report->created_at?->format('Y-m-d'),
            ],
            'show_summary'   => (bool) $report->show_summary,
            'adsData'        => $adsData,
            'datePreset'     => $datePreset,
            'since'          => $since,
            'until'          => $until,
            'allowedPresets' => AdScopeMarketingService::ALLOWED_PRESETS,
            'hasCampaigns'   => $adsData['has_campaigns'] ?? false,
            'campaignIds'    => $campaignIds,
        ]);
    }
}
