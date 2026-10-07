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
    public function getCampaignsSummary(Tenant $tenant, string $datePreset = 'maximum', bool $forceRefresh = false): array
    {
        if (!array_key_exists($datePreset, self::ALLOWED_PRESETS)) {
            $datePreset = 'maximum';
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
                $campaigns = $this->normalizeCampaigns($json['campaigns'] ?? []);
                $periodAr = $now->format('A') === 'AM' ? 'ص' : 'م';

                $result = [
                    'success'           => true,
                    'has_campaigns'     => true,
                    'campaign_ids'      => $campaignIds,
                    'date_preset'       => $datePreset,
                    'currency'          => $json['currency'] ?? 'EGP',
                    'summary'           => $json['summary'] ?? null,
                    'campaigns'         => $campaigns,
                    'cached_at'         => $now->toIso8601String(),
                    'last_updated_at'   => $now->toIso8601String(),
                    'last_updated_formatted' => $now->format('j-n-Y - h:i ') . $periodAr,
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

    /**
     * Normalize campaigns: classify goal (sales vs messages), format labels, and extract Facebook post URLs.
     */
    protected function normalizeCampaigns(array $campaigns): array
    {
        foreach ($campaigns as &$campaign) {
            $objective = strtoupper($campaign['objective'] ?? '');
            $name = $campaign['name'] ?? '';

            $hasMessageCta = false;
            if (!empty($campaign['ads']) && is_array($campaign['ads'])) {
                foreach ($campaign['ads'] as &$ad) {
                    $creative = $ad['creative'] ?? [];
                    $cta = $creative['cta_type'] ?? '';
                    if (in_array($cta, ['MESSAGE_PAGE', 'SEND_MESSAGE', 'WHATSAPP_MESSAGE'])) {
                        $hasMessageCta = true;
                    }

                    // Extract Facebook post link
                    $postUrl = $creative['preview_url'] ?? null;
                    if (empty($postUrl)) {
                        $imgUrl = $creative['image_url'] ?? $creative['thumbnail_url'] ?? '';
                        if (preg_match('/_(\d{15,22})_/', $imgUrl, $m)) {
                            $postUrl = "https://www.facebook.com/photo/?fbid={$m[1]}";
                        } elseif (!empty($ad['id'])) {
                            $postUrl = "https://www.facebook.com/ads/experience/confirmation/?ad_id={$ad['id']}";
                        }
                    }
                    $ad['facebook_post_url'] = $postUrl;
                }
                unset($ad);
            }

            $isMessages = $objective === 'OUTCOME_ENGAGEMENT'
                || $objective === 'MESSAGES'
                || str_contains($objective, 'ENGAGEMENT')
                || str_contains($objective, 'MESSAGE')
                || $hasMessageCta
                || str_contains($name, 'رسايل')
                || str_contains($name, 'رسائل');

            if ($isMessages) {
                $campaign['goal_type'] = 'messages';
                $campaign['goal_badge'] = 'إعلان رسائل';
                $campaign['result_label'] = 'رسائل';
                $campaign['cpa_label'] = 'تكلفة الرسالة';
                $campaign['show_roas'] = false;
            } else {
                $campaign['goal_type'] = 'sales';
                $campaign['goal_badge'] = 'إعلان مبيعات';
                $campaign['result_label'] = 'طلبات شراء';
                $campaign['cpa_label'] = 'تكلفة الطلب (CPA)';
                $campaign['show_roas'] = true;
            }

            // Calculate end date & remaining time
            $stopTime = $campaign['stop_time'] ?? $campaign['end_time'] ?? null;
            if (!$stopTime && !empty($campaign['adsets']) && is_array($campaign['adsets'])) {
                foreach ($campaign['adsets'] as $adset) {
                    if (!empty($adset['end_time'])) {
                        $stopTime = $adset['end_time'];
                        break;
                    }
                }
            }

            $endInfo = null;
            if ($stopTime) {
                try {
                    $endDate = \Carbon\Carbon::parse($stopTime);
                    $now = now();
                    $isEnded = $endDate->isPast();
                    $diffDays = (int) $now->diffInDays($endDate, false);
                    $diffHours = (int) ($now->diffInHours($endDate, false) % 24);
                    $endPeriodAr = $endDate->format('A') === 'AM' ? 'ص' : 'م';
                    $formattedDate = $endDate->format('j-n-Y - h:i ') . $endPeriodAr;

                    if ($isEnded) {
                        $remainingText = 'انتهت الحملة ⏹️';
                    } elseif ($diffDays > 0) {
                        $remainingText = "متبقي {$diffDays} يوم" . ($diffHours > 0 ? " و {$diffHours} ساعة" : "");
                    } else {
                        $remainingHours = max(1, (int) $now->diffInHours($endDate, false));
                        $remainingText = "متبقي {$remainingHours} ساعة";
                    }

                    $endInfo = [
                        'raw'            => $stopTime,
                        'formatted'      => $formattedDate,
                        'remaining_text' => $remainingText,
                        'is_ended'       => $isEnded,
                    ];
                } catch (\Throwable) {
                    $endInfo = null;
                }
            }
            $campaign['end_info'] = $endInfo;
        }
        unset($campaign);

        return $campaigns;
    }
}

