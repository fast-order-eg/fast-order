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
        'custom'     => 'مدة محددة',
    ];

    /**
     * Get marketing ads performance summary for a tenant.
     *
     * @param Tenant $tenant
     * @param string $datePreset
     * @param bool $forceRefresh
     * @param string|null $since
     * @param string|null $until
     * @return array
     */
    public function getCampaignsSummary(
        Tenant $tenant,
        string $datePreset = 'maximum',
        bool $forceRefresh = false,
        ?string $since = null,
        ?string $until = null
    ): array {
        $isCustom = ($datePreset === 'custom' || ($since && $until));
        $cacheKey = ($isCustom && $since && $until)
            ? "meta_ads_tenant_{$tenant->id}_custom_{$since}_{$until}"
            : "meta_ads_tenant_{$tenant->id}_{$datePreset}";

        return $this->getCampaignsSummaryByCampaignIds(
            $tenant->getMetaCampaignIds(),
            $datePreset,
            $forceRefresh,
            $cacheKey,
            $since,
            $until
        );
    }

    /**
     * Get marketing ads performance summary directly by campaign IDs.
     *
     * @param array $campaignIds
     * @param string $datePreset
     * @param bool $forceRefresh
     * @param string|null $cacheKeyCustom
     * @param string|null $since
     * @param string|null $until
     * @return array
     */
    public function getCampaignsSummaryByCampaignIds(
        array $campaignIds,
        string $datePreset = 'maximum',
        bool $forceRefresh = false,
        ?string $cacheKeyCustom = null,
        ?string $since = null,
        ?string $until = null
    ): array {
        $isCustom = ($datePreset === 'custom' || ($since && $until));
        if (!$isCustom && !array_key_exists($datePreset, self::ALLOWED_PRESETS)) {
            $datePreset = 'maximum';
        }

        // Clean campaign IDs array: must be non-empty numeric IDs
        $campaignIds = array_values(array_filter(array_map('trim', array_map('strval', $campaignIds)), function ($id) {
            return !empty($id) && ctype_digit($id);
        }));

        if (empty($campaignIds)) {
            return [
                'success'           => true,
                'has_campaigns'     => false,
                'campaign_ids'      => [],
                'date_preset'       => $isCustom ? 'custom' : $datePreset,
                'since'             => $since,
                'until'             => $until,
                'currency'          => 'EGP',
                'summary'           => null,
                'campaigns'         => [],
                'cached_at'         => null,
                'last_updated_at'   => null,
                'message'           => 'لم يتم تحديد أي معرفات حملات إعلانية لهذا التقرير.',
            ];
        }

        $cacheSuffix = ($isCustom && $since && $until) ? "custom_{$since}_{$until}" : $datePreset;
        $cacheKey = $cacheKeyCustom ?: ('meta_ads_ids_' . md5(implode(',', $campaignIds)) . "_{$cacheSuffix}");

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $cachedData = Cache::get($cacheKey);
        if ($cachedData && is_array($cachedData)) {
            $cachedData['is_from_cache'] = true;
            return $cachedData;
        }

        // Build API payload
        $payload = [
            'campaign_ids' => $campaignIds,
        ];
        if ($isCustom && $since && $until) {
            $payload['since'] = $since;
            $payload['until'] = $until;
            $payload['date_preset'] = 'custom';
        } else {
            $payload['date_preset'] = $datePreset;
        }

        // Fetch freshly from API
        try {
            $response = Http::timeout(25)
                ->withoutVerifying()
                ->withHeaders([
                    'x-api-key'    => self::API_KEY,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post(self::API_URL, $payload);

            if ($response->successful()) {
                $json = $response->json();

                if (isset($json['success']) && $json['success'] === false) {
                    return [
                        'success'         => false,
                        'has_campaigns'   => true,
                        'campaign_ids'    => $campaignIds,
                        'date_preset'     => $isCustom ? 'custom' : $datePreset,
                        'since'           => $since,
                        'until'           => $until,
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
                    'date_preset'       => $isCustom ? 'custom' : $datePreset,
                    'since'             => $since,
                    'until'             => $until,
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
                'date_preset'     => $isCustom ? 'custom' : $datePreset,
                'since'           => $since,
                'until'           => $until,
                'error'           => 'تعذر الاتصال بخادم منصة الإعلانات (كود ' . $response->status() . ').',
                'last_updated_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::error("AdScopeMarketingService exception: " . $e->getMessage());

            return [
                'success'         => false,
                'has_campaigns'   => true,
                'campaign_ids'    => $campaignIds,
                'date_preset'     => $isCustom ? 'custom' : $datePreset,
                'since'           => $since,
                'until'           => $until,
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
            $apiResultType = strtolower($campaign['result_type'] ?? '');
            $apiResultLabel = $campaign['result_label'] ?? '';
            $apiPlatform = strtoupper($campaign['platform'] ?? '');

            $hasMessageCta = false;
            $hasWhatsappIndicator = ($apiResultType === 'whatsapp');
            $hasInstagramIndicator = ($apiResultType === 'instagram');
            $hasMessengerIndicator = ($apiResultType === 'messenger' || str_contains($apiResultLabel, 'ماسنجر'));

            $nameLower = mb_strtolower($name);
            if (str_contains($nameLower, 'واتس') || str_contains($nameLower, 'whatsapp')) {
                $hasWhatsappIndicator = true;
            } elseif (str_contains($nameLower, 'ماسنجر') || str_contains($nameLower, 'messenger') || str_contains($nameLower, 'ماسن')) {
                $hasMessengerIndicator = true;
            } elseif (str_contains($nameLower, 'انستا') || str_contains($nameLower, 'انستجرام') || str_contains($nameLower, 'انستقرام') || str_contains($nameLower, 'instagram') || str_contains($nameLower, 'insta')) {
                $hasInstagramIndicator = true;
            }

            // Check campaign destination / platform
            $campDest = strtoupper($campaign['destination_type'] ?? '');
            if (str_contains($campDest, 'MESSENGER')) {
                $hasMessengerIndicator = true;
            } elseif (str_contains($campDest, 'WHATSAPP')) {
                $hasWhatsappIndicator = true;
            } elseif (str_contains($campDest, 'INSTAGRAM')) {
                $hasInstagramIndicator = true;
            }

            if (($campaign['platform'] ?? '') === 'INSTAGRAM' && !$hasMessengerIndicator) {
                $hasInstagramIndicator = true;
            }

            if (!empty($campaign['ads']) && is_array($campaign['ads'])) {
                foreach ($campaign['ads'] as &$ad) {
                    $creative = $ad['creative'] ?? [];
                    $cta = strtoupper($creative['cta_type'] ?? $creative['call_to_action_type'] ?? '');
                    if ($cta === 'WHATSAPP_MESSAGE') {
                        $hasWhatsappIndicator = true;
                        $hasMessageCta = true;
                    } elseif (in_array($cta, ['MESSAGE_PAGE', 'SEND_MESSAGE'])) {
                        $hasMessengerIndicator = true;
                        $hasMessageCta = true;
                    } elseif ($cta === 'INSTAGRAM_MESSAGE' || ($cta === 'SEND_MESSAGE' && ($creative['platform'] ?? '') === 'INSTAGRAM')) {
                        $hasInstagramIndicator = true;
                        $hasMessageCta = true;
                    } elseif (in_array($cta, ['CONTACT_US', 'GET_QUOTE'])) {
                        $hasMessageCta = true;
                    }

                    $linkUrl = strtolower($creative['link_url'] ?? $creative['post_url'] ?? $ad['post_url'] ?? '');
                    if (str_contains($linkUrl, 'wa.me') || str_contains($linkUrl, 'whatsapp')) {
                        $hasWhatsappIndicator = true;
                        $hasMessageCta = true;
                    } elseif (str_contains($linkUrl, 'm.me') || str_contains($linkUrl, 'messenger')) {
                        $hasMessengerIndicator = true;
                        $hasMessageCta = true;
                    } elseif (str_contains($linkUrl, 'instagram.com') || str_contains($linkUrl, 'instagr.am')) {
                        // Only flag as instagram if it's explicitly an Instagram link and not messenger
                        if (!$hasMessengerIndicator) {
                            $hasInstagramIndicator = true;
                        }
                    }

                    $adName = mb_strtolower($ad['name'] ?? '');
                    if (str_contains($adName, 'واتس') || str_contains($adName, 'whatsapp')) {
                        $hasWhatsappIndicator = true;
                    } elseif (str_contains($adName, 'ماسنجر') || str_contains($adName, 'messenger')) {
                        $hasMessengerIndicator = true;
                    } elseif (str_contains($adName, 'انستا') || str_contains($adName, 'انستجرام') || str_contains($adName, 'انستقرام') || str_contains($adName, 'instagram') || str_contains($adName, 'insta')) {
                        if (!$hasMessengerIndicator) {
                            $hasInstagramIndicator = true;
                        }
                    }

                    // 1. Extract canonical Facebook Post link from effective_object_story_id (pageId_postId)
                    $canonicalFbUrl = null;
                    $storyId = $creative['effective_object_story_id'] ?? $creative['object_story_id'] ?? $ad['effective_object_story_id'] ?? null;
                    if (!empty($storyId) && str_contains($storyId, '_')) {
                        $parts = explode('_', $storyId, 2);
                        if (!empty($parts[0]) && !empty($parts[1]) && is_numeric($parts[0]) && is_numeric($parts[1])) {
                            $canonicalFbUrl = "https://www.facebook.com/{$parts[0]}/posts/{$parts[1]}";
                        }
                    }

                    // 2. Fallback: photo URL from image or ad experience confirmation URL
                    $fbPhotoUrl = null;
                    $imgUrl = $creative['image_url'] ?? $creative['thumbnail_url'] ?? '';
                    if (preg_match('/_(\d{15,22})_/', $imgUrl, $m)) {
                        $fbPhotoUrl = "https://www.facebook.com/photo/?fbid={$m[1]}";
                    } elseif (!empty($ad['id'])) {
                        $fbPhotoUrl = "https://www.facebook.com/ads/experience/confirmation/?ad_id={$ad['id']}";
                    }

                    // 3. Raw post URL from API
                    $rawPostUrl = $ad['post_url'] ?? $creative['post_url'] ?? $creative['preview_url'] ?? null;

                    // If rawPostUrl is generic "facebook.com/posts/{id}" (which gives 404 without page ID), prioritize canonicalFbUrl
                    $bestFbUrl = $canonicalFbUrl;
                    if (!$bestFbUrl) {
                        if (!empty($rawPostUrl) && !preg_match('#facebook\.com/posts/\d+/?$#i', trim($rawPostUrl))) {
                            $bestFbUrl = $rawPostUrl;
                        } else {
                            $bestFbUrl = $fbPhotoUrl ?: $rawPostUrl;
                        }
                    }

                    $isAdInsta = (!$hasMessengerIndicator && !$hasWhatsappIndicator && ($campaign['platform'] ?? '') !== 'FACEBOOK') && (
                        ($creative['platform'] ?? $ad['platform'] ?? '') === 'INSTAGRAM'
                        || $cta === 'INSTAGRAM_MESSAGE'
                        || (str_contains(strtolower($adName), 'انستا') || str_contains(strtolower($adName), 'instagram'))
                    );

                    $isAdWhatsapp = $hasWhatsappIndicator || ($cta === 'WHATSAPP_MESSAGE') || (str_contains(strtolower($adName), 'واتس') || str_contains(strtolower($adName), 'whatsapp'));

                    if ($isAdInsta) {
                        $postUrl = $creative['instagram_permalink_url'] ?? $rawPostUrl ?? 'https://www.instagram.com';
                        $platform = 'INSTAGRAM';
                        $platformLabel = 'إنستغرام';
                        $buttonLabel = 'فتح في إنستجرام ↗';
                    } elseif ($isAdWhatsapp) {
                        $postUrl = $bestFbUrl ?: $rawPostUrl;
                        $platform = 'WHATSAPP';
                        $platformLabel = 'واتساب';
                        $buttonLabel = 'محادثة واتساب ↗';
                    } else {
                        // Facebook is primary
                        $postUrl = $bestFbUrl ?: $fbPhotoUrl ?: $rawPostUrl;
                        $platform = 'FACEBOOK';
                        $platformLabel = 'فيسبوك';
                        $buttonLabel = 'فتح في فيسبوك ↗';
                    }

                    $ad['post_url'] = $postUrl;
                    $ad['facebook_post_url'] = $bestFbUrl ?: $fbPhotoUrl ?: $postUrl;
                    $ad['instagram_permalink_url'] = $creative['instagram_permalink_url'] ?? null;
                    $ad['platform'] = $platform;
                    $ad['platform_label'] = $platformLabel;
                    $ad['open_button_label'] = $buttonLabel;
                    if ($canonicalFbUrl) {
                        $ad['creative']['effective_object_story_id'] = $storyId;
                        $ad['creative']['facebook_post_url'] = $canonicalFbUrl;
                        $ad['creative']['post_url'] = $canonicalFbUrl;
                    }
                }
                unset($ad);
            }

            if (!empty($campaign['adsets']) && is_array($campaign['adsets'])) {
                foreach ($campaign['adsets'] as $adset) {
                    $dest = strtoupper($adset['destination_type'] ?? '');
                    if (str_contains($dest, 'MESSENGER')) {
                        $hasMessengerIndicator = true;
                        $hasMessageCta = true;
                    } elseif (str_contains($dest, 'WHATSAPP')) {
                        $hasWhatsappIndicator = true;
                        $hasMessageCta = true;
                    } elseif (str_contains($dest, 'INSTAGRAM')) {
                        $hasInstagramIndicator = true;
                        $hasMessageCta = true;
                    }

                    $adsetName = mb_strtolower($adset['name'] ?? '');
                    if (str_contains($adsetName, 'ماسنجر') || str_contains($adsetName, 'messenger')) {
                        $hasMessengerIndicator = true;
                    } elseif (str_contains($adsetName, 'واتس') || str_contains($adsetName, 'whatsapp')) {
                        $hasWhatsappIndicator = true;
                    } elseif (str_contains($adsetName, 'انستا') || str_contains($adsetName, 'انستجرام') || str_contains($adsetName, 'انستقرام') || str_contains($adsetName, 'instagram') || str_contains($adsetName, 'insta')) {
                        if (!$hasMessengerIndicator) {
                            $hasInstagramIndicator = true;
                        }
                    }
                }
            }

            $isMessages = $apiResultType === 'messenger'
                || $apiResultType === 'whatsapp'
                || $apiResultType === 'instagram'
                || $objective === 'OUTCOME_ENGAGEMENT'
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
                // 1. Messenger has highest priority when detected, or by default for message campaigns on Facebook
                if ($hasMessengerIndicator || $campDest === 'MESSENGER' || $apiResultType === 'messenger' || str_contains($apiResultLabel, 'ماسنجر')) {
                    $campaign['goal_type'] = 'messenger';
                    $campaign['goal_badge'] = 'رسائل ماسنجر';
                    $campaign['result_label'] = 'رسائل ماسنجر';
                    $campaign['cpa_label'] = 'تكلفة رسالة الماسنجر';
                    $campaign['platform'] = $campaign['platform'] ?: 'FACEBOOK';
                    $campaign['platform_label'] = $campaign['platform_label'] ?: 'فيسبوك';
                } elseif ($hasWhatsappIndicator || $campDest === 'WHATSAPP' || $apiResultType === 'whatsapp' || str_contains($apiResultLabel, 'واتساب')) {
                    $campaign['goal_type'] = 'whatsapp';
                    $campaign['goal_badge'] = 'رسائل واتساب';
                    $campaign['result_label'] = 'رسائل واتساب';
                    $campaign['cpa_label'] = 'تكلفة رسالة الواتساب';
                } elseif ($hasInstagramIndicator || $campDest === 'INSTAGRAM' || $apiResultType === 'instagram' || str_contains($apiResultLabel, 'إنستغرام') || str_contains($apiResultLabel, 'انستجرام')) {
                    $campaign['goal_type'] = 'instagram';
                    $campaign['goal_badge'] = 'رسائل إنستغرام دايركت';
                    $campaign['result_label'] = 'رسائل إنستغرام دايركت';
                    $campaign['cpa_label'] = 'تكلفة رسالة إنستغرام';
                } else {
                    // Default messages to messenger
                    $campaign['goal_type'] = 'messenger';
                    $campaign['goal_badge'] = 'رسائل ماسنجر';
                    $campaign['result_label'] = 'رسائل ماسنجر';
                    $campaign['cpa_label'] = 'تكلفة رسالة الماسنجر';
                    $campaign['platform'] = $campaign['platform'] ?: 'FACEBOOK';
                    $campaign['platform_label'] = $campaign['platform_label'] ?: 'فيسبوك';
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

