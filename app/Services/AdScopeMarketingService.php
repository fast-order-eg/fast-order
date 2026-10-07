<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdScopeMarketingService
{
    private const API_URL = 'https://einasouq.bird-ads.com/api/v1/merchant/campaigns-summary';
    private const API_KEY = 'fastorder_merchant_secure_api_key_2026';
    private const CACHE_TTL_SECONDS = 300; // 5 minutes cache

    public const ALLOWED_PRESETS = [
        'today'      => 'اليوم',
        'yesterday'  => 'أمس',
        'last_7d'    => 'آخر 7 أيام',
        'this_month' => 'هذا الشهر',
        'maximum'    => 'الإجمالي',
    ];

    /**
     * Get marketing ads performance summary for a tenant.
     *
     * @param Tenant $tenant
     * @param string $datePreset
     * @param bool $forceRefresh
     * @return array
     */
    public function getCampaignsSummary(Tenant $tenant, string $datePreset = 'last_7d', bool $forceRefresh = false): array
    {
        if (!array_key_exists($datePreset, self::ALLOWED_PRESETS)) {
            $datePreset = 'last_7d';
        }

        $campaignIds = $tenant->getMetaCampaignIds();

        if (empty($campaignIds)) {
            return [
                'success'           => true,
                'has_campaigns'     => false,
                'campaign_ids'      => [],
                'date_preset'       => $datePreset,
                'currency'          => 'EGP',
                'summary'           => null,
                'campaigns'         => [],
                'cached_at'         => null,
                'last_updated_at'   => null,
                'message'           => 'لم يتم ربط حملات إعلانية لهذا المتجر حتى الآن.',
            ];
        }

        $cacheKey = "meta_ads_tenant_{$tenant->id}_{$datePreset}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $cachedData = Cache::get($cacheKey);
        if ($cachedData && is_array($cachedData)) {
            $cachedData['is_from_cache'] = true;
            return $cachedData;
        }

        // Fetch freshly from API
        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->withHeaders([
                    'x-api-key'    => self::API_KEY,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post(self::API_URL, [
                    'campaign_ids' => $campaignIds,
                    'date_preset'  => $datePreset,
                ]);

            if ($response->successful()) {
                $json = $response->json();

                if (isset($json['success']) && $json['success'] === false) {
                    return [
                        'success'         => false,
                        'has_campaigns'   => true,
                        'campaign_ids'    => $campaignIds,
                        'date_preset'     => $datePreset,
                        'error'           => $json['error'] ?? 'فشل استرجاع بيانات الإعلانات من المنصة.',
                        'last_updated_at' => now()->toIso8601String(),
                    ];
                }

                $now = now();
                $result = [
                    'success'           => true,
                    'has_campaigns'     => true,
                    'campaign_ids'      => $campaignIds,
                    'date_preset'       => $datePreset,
                    'currency'          => $json['currency'] ?? 'EGP',
                    'summary'           => $json['summary'] ?? null,
                    'campaigns'         => $json['campaigns'] ?? [],
                    'cached_at'         => $now->toIso8601String(),
                    'last_updated_at'   => $now->toIso8601String(),
                    'last_updated_formatted' => $now->locale('ar')->translatedFormat('l d F Y - h:i A'),
                    'is_from_cache'     => false,
                ];

                Cache::put($cacheKey, $result, self::CACHE_TTL_SECONDS);

                return $result;
            }

            Log::warning("AdScopeMarketingService: HTTP {$response->status()} - {$response->body()}");

            return [
                'success'         => false,
                'has_campaigns'   => true,
                'campaign_ids'    => $campaignIds,
                'date_preset'     => $datePreset,
                'error'           => 'تعذر الاتصال بخادم منصة الإعلانات (كود ' . $response->status() . ').',
                'last_updated_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::error("AdScopeMarketingService exception: " . $e->getMessage());

            return [
                'success'         => false,
                'has_campaigns'   => true,
                'campaign_ids'    => $campaignIds,
                'date_preset'     => $datePreset,
                'error'           => 'حدث خطأ أثناء جلب تقارير الإعلانات: ' . $e->getMessage(),
                'last_updated_at' => now()->toIso8601String(),
            ];
        }
    }
}
