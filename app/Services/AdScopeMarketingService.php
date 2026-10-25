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
     * Normalize campaigns: classify goal (sales vs messages), format labels, and extract Facebook/Instagram post URLs.
     */
    protected function normalizeCampaigns(array $campaigns): array
    {
        foreach ($campaigns as &$campaign) {
            $objective = strtoupper($campaign['objective'] ?? '');
            $name = $campaign['name'] ?? '';

            $hasMessageCta = false;
            $hasWhatsappIndicator = false;
            $hasInstagramIndicator = false;
            $hasMessengerIndicator = false;

            $nameLower = mb_strtolower($name);
            if (str_contains($nameLower, 'انستا') || str_contains($nameLower, 'انستجرام') || str_contains($nameLower, 'انستقرام') || str_contains($nameLower, 'instagram') || str_contains($nameLower, 'insta')) {
                $hasInstagramIndicator = true;
            }
            if (str_contains($nameLower, 'واتس') || str_contains($nameLower, 'whatsapp')) {
                $hasWhatsappIndicator = true;
            }
            if (str_contains($nameLower, 'ماسنجر') || str_contains($nameLower, 'messenger')) {
                $hasMessengerIndicator = true;
            }

            // Check campaign destination / platform
            $campDest = strtoupper($campaign['destination_type'] ?? '');
            if (str_contains($campDest, 'INSTAGRAM')) {
                $hasInstagramIndicator = true;
            } elseif (str_contains($campDest, 'WHATSAPP')) {
                $hasWhatsappIndicator = true;
            } elseif (str_contains($campDest, 'MESSENGER')) {
                $hasMessengerIndicator = true;
            }

            if (($campaign['platform'] ?? '') === 'INSTAGRAM') {
                $hasInstagramIndicator = true;
            }

            if (!empty($campaign['ads']) && is_array($campaign['ads'])) {
                foreach ($campaign['ads'] as &$ad) {
                    $creative = $ad['creative'] ?? [];
                    $cta = strtoupper($creative['cta_type'] ?? $creative['call_to_action_type'] ?? '');
                    if ($cta === 'WHATSAPP_MESSAGE') {
                        $hasWhatsappIndicator = true;
                        $hasMessageCta = true;
                    } elseif ($cta === 'INSTAGRAM_MESSAGE' || str_contains($cta, 'INSTAGRAM')) {
                        $hasInstagramIndicator = true;
                        $hasMessageCta = true;
                    } elseif (in_array($cta, ['MESSAGE_PAGE', 'SEND_MESSAGE'])) {
                        $hasMessengerIndicator = true;
                        $hasMessageCta = true;
                    } elseif (in_array($cta, ['CONTACT_US', 'GET_QUOTE'])) {
                        $hasMessageCta = true;
                    }

                    $linkUrl = strtolower($creative['link_url'] ?? $creative['instagram_permalink_url'] ?? $creative['post_url'] ?? $ad['post_url'] ?? '');
                    if (str_contains($linkUrl, 'instagram.com') || str_contains($linkUrl, 'instagr.am')) {
                        $hasInstagramIndicator = true;
                    } elseif (str_contains($linkUrl, 'wa.me') || str_contains($linkUrl, 'whatsapp')) {
                        $hasWhatsappIndicator = true;
                        $hasMessageCta = true;
                    } elseif (str_contains($linkUrl, 'm.me') || str_contains($linkUrl, 'messenger')) {
                        $hasMessengerIndicator = true;
                        $hasMessageCta = true;
                    }

                    if (($creative['platform'] ?? $ad['platform'] ?? '') === 'INSTAGRAM') {
                        $hasInstagramIndicator = true;
                    }

                    $adName = mb_strtolower($ad['name'] ?? '');
                    if (str_contains($adName, 'انستا') || str_contains($adName, 'انستجرام') || str_contains($adName, 'انستقرام') || str_contains($adName, 'instagram') || str_contains($adName, 'insta')) {
                        $hasInstagramIndicator = true;
                    } elseif (str_contains($adName, 'واتس') || str_contains($adName, 'whatsapp')) {
                        $hasWhatsappIndicator = true;
                    } elseif (str_contains($adName, 'ماسنجر') || str_contains($adName, 'messenger')) {
                        $hasMessengerIndicator = true;
                    }

                    // Extract Post link
                    $postUrl = $ad['post_url'] ?? $creative['post_url'] ?? $creative['instagram_permalink_url'] ?? $creative['preview_url'] ?? null;
                    if (empty($postUrl)) {
                        $imgUrl = $creative['image_url'] ?? $creative['thumbnail_url'] ?? '';
                        if (preg_match('/_(\d{15,22})_/', $imgUrl, $m)) {
                            $postUrl = "https://www.facebook.com/photo/?fbid={$m[1]}";
                        } elseif (!empty($ad['id'])) {
                            $postUrl = "https://www.facebook.com/ads/experience/confirmation/?ad_id={$ad['id']}";
                        }
                    }

                    $isAdInsta = ($creative['platform'] ?? $ad['platform'] ?? '') === 'INSTAGRAM'
                        || str_contains(strtolower($postUrl ?? ''), 'instagram.com')
                        || !empty($creative['instagram_permalink_url'])
                        || $cta === 'INSTAGRAM_MESSAGE'
                        || str_contains($cta, 'INSTAGRAM')
                        || str_contains($adName, 'انستا')
                        || str_contains($adName, 'انستجرام')
                        || str_contains($adName, 'انستقرام')
                        || str_contains($adName, 'instagram')
                        || $hasInstagramIndicator;

                    $ad['post_url'] = $postUrl;
                    $ad['facebook_post_url'] = $postUrl; // Backward compatibility
                    $ad['platform'] = $isAdInsta ? 'INSTAGRAM' : 'FACEBOOK';
                    $ad['platform_label'] = $isAdInsta ? 'إنستغرام' : 'فيسبوك';
                    $ad['open_button_label'] = $isAdInsta ? 'فتح في إنستجرام ↗' : 'فتح في فيسبوك ↗';
                }
                unset($ad);
            }

            if (!empty($campaign['adsets']) && is_array($campaign['adsets'])) {
                foreach ($campaign['adsets'] as $adset) {
                    $dest = strtoupper($adset['destination_type'] ?? '');
                    if (str_contains($dest, 'INSTAGRAM')) {
                        $hasInstagramIndicator = true;
                        $hasMessageCta = true;
                    } elseif (str_contains($dest, 'WHATSAPP')) {
                        $hasWhatsappIndicator = true;
                        $hasMessageCta = true;
                    } elseif (str_contains($dest, 'MESSENGER')) {
                        $hasMessengerIndicator = true;
                        $hasMessageCta = true;
                    }

                    $adsetName = mb_strtolower($adset['name'] ?? '');
                    if (str_contains($adsetName, 'انستا') || str_contains($adsetName, 'انستجرام') || str_contains($adsetName, 'انستقرام') || str_contains($adsetName, 'instagram') || str_contains($adsetName, 'insta')) {
                        $hasInstagramIndicator = true;
                    } elseif (str_contains($adsetName, 'واتس') || str_contains($adsetName, 'whatsapp')) {
                        $hasWhatsappIndicator = true;
                    } elseif (str_contains($adsetName, 'ماسنجر') || str_contains($adsetName, 'messenger')) {
                        $hasMessengerIndicator = true;
                    }
                }
            }

            $isMessages = $objective === 'OUTCOME_ENGAGEMENT'
                || $objective === 'MESSAGES'
                || str_contains($objective, 'ENGAGEMENT')
                || str_contains($objective, 'MESSAGE')
                || $hasMessageCta
                || $hasWhatsappIndicator
                || $hasInstagramIndicator
                || $hasMessengerIndicator
                || str_contains($nameLower, 'رسايل')
                || str_contains($nameLower, 'رسائل');

            if ($isMessages) {
                if ($hasWhatsappIndicator) {
                    $campaign['goal_type'] = 'whatsapp';
                    $campaign['goal_badge'] = 'رسائل واتساب';
                    $campaign['result_label'] = 'رسائل واتساب';
                    $campaign['cpa_label'] = 'تكلفة رسالة الواتساب';
                } elseif ($hasInstagramIndicator) {
                    $campaign['goal_type'] = 'instagram';
                    $campaign['goal_badge'] = 'رسائل إنستغرام دايركت';
                    $campaign['result_label'] = 'رسائل إنستغرام دايركت';
                    $campaign['cpa_label'] = 'تكلفة رسالة إنستغرام';
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

            // Resolve true campaign status taking end date into account
            $isEndedCampaign = !empty($endInfo['is_ended'])
                || ($campaign['effective_status'] ?? '') === 'COMPLETED'
                || ($campaign['status'] ?? '') === 'COMPLETED'
                || ($campaign['status'] ?? '') === 'ARCHIVED';

            if ($isEndedCampaign) {
                $campaign['status'] = 'COMPLETED';
                $campaign['effective_status'] = 'COMPLETED';
                $campaign['is_ended'] = true;
                $campaign['display_status'] = 'COMPLETED';
                $campaign['display_status_label'] = 'منتهية';
            } elseif (($campaign['status'] ?? '') === 'ACTIVE' || ($campaign['effective_status'] ?? '') === 'ACTIVE') {
                $campaign['is_ended'] = false;
                $campaign['display_status'] = 'ACTIVE';
                $campaign['display_status_label'] = 'نشطة 🟢';
            } else {
                $campaign['is_ended'] = false;
                $campaign['display_status'] = 'PAUSED';
                $campaign['display_status_label'] = 'متوقفة ⚪';
            }
        }
        unset($campaign);

        // Always sort ACTIVE campaigns to the top
        usort($campaigns, function ($a, $b) {
            $aActive = (($a['display_status'] ?? '') === 'ACTIVE') ? 0 : 1;
            $bActive = (($b['display_status'] ?? '') === 'ACTIVE') ? 0 : 1;
            return $aActive <=> $bActive;
        });

        return $campaigns;
    }
}

