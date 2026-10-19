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
        'this_month' => 'الشهر الحالي',
        'last_month' => 'الشهر السابق',
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
        return $this->getCampaignsSummaryByCampaignIds(
            $tenant->getMetaCampaignIds(),
            $datePreset,
            $forceRefresh,
            "meta_ads_tenant_{$tenant->id}_{$datePreset}"
        );
    }

    /**
     * Get marketing ads performance summary directly by campaign IDs.
     *
     * @param array $campaignIds
     * @param string $datePreset
     * @param bool $forceRefresh
     * @param string|null $cacheKeyCustom
     * @return array
     */
    public function getCampaignsSummaryByCampaignIds(array $campaignIds, string $datePreset = 'maximum', bool $forceRefresh = false, ?string $cacheKeyCustom = null): array
    {
        if (!array_key_exists($datePreset, self::ALLOWED_PRESETS)) {
            $datePreset = 'maximum';
        }

        // Clean campaign IDs array
        $campaignIds = array_values(array_filter(array_map('trim', $campaignIds)));

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
                'message'           => 'لم يتم تحديد أي معرفات حملات إعلانية لهذا التقرير.',
            ];
        }

        $cacheKey = $cacheKeyCustom ?: ('meta_ads_ids_' . md5(implode(',', $campaignIds)) . "_{$datePreset}");

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
            $hasWhatsappIndicator = false;
            $hasMessengerIndicator = false;

            if (!empty($campaign['ads']) && is_array($campaign['ads'])) {
                foreach ($campaign['ads'] as &$ad) {
                    $creative = $ad['creative'] ?? [];
                    $cta = strtoupper($creative['cta_type'] ?? '');
                    if ($cta === 'WHATSAPP_MESSAGE') {
                        $hasWhatsappIndicator = true;
                        $hasMessageCta = true;
                    } elseif (in_array($cta, ['MESSAGE_PAGE', 'SEND_MESSAGE'])) {
                        $hasMessengerIndicator = true;
                        $hasMessageCta = true;
                    } elseif (in_array($cta, ['CONTACT_US', 'GET_QUOTE'])) {
                        $hasMessageCta = true;
                    }

                    $linkUrl = strtolower($creative['link_url'] ?? $creative['instagram_permalink_url'] ?? '');
                    if (str_contains($linkUrl, 'wa.me') || str_contains($linkUrl, 'whatsapp')) {
                        $hasWhatsappIndicator = true;
                        $hasMessageCta = true;
                    } elseif (str_contains($linkUrl, 'm.me') || str_contains($linkUrl, 'messenger')) {
                        $hasMessengerIndicator = true;
                        $hasMessageCta = true;
                    }

                    $adName = mb_strtolower($ad['name'] ?? '');
                    if (str_contains($adName, 'واتس') || str_contains($adName, 'whatsapp')) {
                        $hasWhatsappIndicator = true;
                    } elseif (str_contains($adName, 'ماسنجر') || str_contains($adName, 'messenger')) {
                        $hasMessengerIndicator = true;
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

            if (!empty($campaign['adsets']) && is_array($campaign['adsets'])) {
                foreach ($campaign['adsets'] as $adset) {
                    $dest = strtoupper($adset['destination_type'] ?? '');
                    if (str_contains($dest, 'WHATSAPP')) {
                        $hasWhatsappIndicator = true;
                        $hasMessageCta = true;
                    } elseif (str_contains($dest, 'MESSENGER')) {
                        $hasMessengerIndicator = true;
                        $hasMessageCta = true;
                    }

                    $adsetName = mb_strtolower($adset['name'] ?? '');
                    if (str_contains($adsetName, 'واتس') || str_contains($adsetName, 'whatsapp')) {
                        $hasWhatsappIndicator = true;
                    } elseif (str_contains($adsetName, 'ماسنجر') || str_contains($adsetName, 'messenger')) {
                        $hasMessengerIndicator = true;
                    }
                }
            }

            $campDest = strtoupper($campaign['destination_type'] ?? '');
            if (str_contains($campDest, 'WHATSAPP')) {
                $hasWhatsappIndicator = true;
                $hasMessageCta = true;
            } elseif (str_contains($campDest, 'MESSENGER')) {
                $hasMessengerIndicator = true;
                $hasMessageCta = true;
            }

            $nameLower = mb_strtolower($name);
            if (str_contains($nameLower, 'واتس') || str_contains($nameLower, 'whatsapp')) {
                $hasWhatsappIndicator = true;
            } elseif (str_contains($nameLower, 'ماسنجر') || str_contains($nameLower, 'messenger')) {
                $hasMessengerIndicator = true;
            }

            $isMessages = $objective === 'OUTCOME_ENGAGEMENT'
                || $objective === 'MESSAGES'
                || str_contains($objective, 'ENGAGEMENT')
                || str_contains($objective, 'MESSAGE')
                || $hasMessageCta
                || $hasWhatsappIndicator
                || $hasMessengerIndicator
                || str_contains($nameLower, 'رسايل')
                || str_contains($nameLower, 'رسائل');

            if ($isMessages) {
                if ($hasWhatsappIndicator) {
                    $campaign['goal_type'] = 'whatsapp';
                    $campaign['goal_badge'] = 'رسائل واتساب';
                    $campaign['result_label'] = 'رسائل واتساب';
                    $campaign['cpa_label'] = 'تكلفة رسالة الواتساب';
                } else {
                    $campaign['goal_type'] = 'messenger';
                    $campaign['goal_badge'] = 'رسائل ماسنجر';
                    $campaign['result_label'] = 'رسائل ماسنجر';
                    $campaign['cpa_label'] = 'تكلفة رسالة الماسنجر';
                }
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
                    $diffHours = ((int) $now->diffInHours($endDate, false)) % 24;
                    $endPeriodAr = $endDate->format('A') === 'AM' ? 'ص' : 'م';
                    $formattedDate = $endDate->format('j-n-Y - h:i ') . $endPeriodAr;
                    $dateOnly = $endDate->format('j-n-Y');
                    $timeOnly = $endDate->format('h:i ') . $endPeriodAr;

                    if ($isEnded) {
                        $remainingText = 'انتهت الحملة ⏹️';
                        $daysText = 'انتهت الحملة ⏹️';
                        $hoursText = null;
                    } elseif ($diffDays > 0) {
                        $remainingText = "متبقي {$diffDays} يوم" . ($diffHours > 0 ? " و {$diffHours} ساعة" : "");
                        $daysText = "متبقي {$diffDays} يوم";
                        $hoursText = $diffHours > 0 ? "و {$diffHours} ساعة" : null;
                    } else {
                        $remainingHours = max(1, (int) $now->diffInHours($endDate, false));
                        $remainingText = "متبقي {$remainingHours} ساعة";
                        $daysText = "متبقي";
                        $hoursText = "{$remainingHours} ساعة";
                    }

                    $endInfo = [
                        'raw'            => $stopTime,
                        'formatted'      => $formattedDate,
                        'date_only'      => $dateOnly,
                        'time_only'      => $timeOnly,
                        'days_text'      => $daysText,
                        'hours_text'     => $hoursText,
                        'remaining_text' => $remainingText,
                        'is_ended'       => $isEnded,
                    ];
                } catch (\Throwable $e) {
                    Log::error('EndInfo error: ' . $e->getMessage());
                    $endInfo = null;
                }
            }
            $campaign['end_info'] = $endInfo;
        }
        unset($campaign);

        return $campaigns;
    }
}

