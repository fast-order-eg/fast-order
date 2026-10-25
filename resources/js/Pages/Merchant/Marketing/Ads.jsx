import React, { useState, useMemo } from 'react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head, router } from '@inertiajs/react';

// Helper: determine campaign real status taking schedule end date into account
const getCampaignStatus = (campaign) => {
    const isEnded = !!(
        campaign.is_ended ||
        campaign.end_info?.is_ended ||
        campaign.status === 'COMPLETED' ||
        campaign.effective_status === 'COMPLETED' ||
        campaign.status === 'ARCHIVED'
    );

    if (isEnded) {
        return {
            key: 'COMPLETED',
            label: 'منتهية',
            badgeClasses: 'bg-amber-50 text-amber-800 border-amber-200',
        };
    }

    const isActive = campaign.status === 'ACTIVE' || campaign.effective_status === 'ACTIVE';
    if (isActive) {
        return {
            key: 'ACTIVE',
            label: 'نشطة 🟢',
            badgeClasses: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        };
    }

    return {
        key: 'PAUSED',
        label: 'متوقفة ⚪',
        badgeClasses: 'bg-gray-100 text-gray-600 border-gray-200',
    };
};

export default function MarketingAdsIndex({
    adsData = {},
    datePreset = 'maximum',
    allowedPresets = {},
    hasCampaigns = false,
    campaignIds = [],
    show_summary = false,
    storeStats = { orders_count: 0, abandoned_count: 0 },
    tenant = null,
}) {
    const [loading, setLoading] = useState(false);
    const [copied, setCopied] = useState(false);
    const [expandedCampaigns, setExpandedCampaigns] = useState({});

    const rawCampaigns = adsData?.campaigns || [];
    // Always sort ACTIVE campaigns to the top
    const campaigns = useMemo(() => {
        return [...rawCampaigns].sort((a, b) => {
            const aActive = getCampaignStatus(a).key === 'ACTIVE' ? 0 : 1;
            const bActive = getCampaignStatus(b).key === 'ACTIVE' ? 0 : 1;
            return aActive - bActive;
        });
    }, [rawCampaigns]);
    const currency = adsData?.currency || 'EGP';
    const lastUpdatedFormatted = adsData?.last_updated_formatted || null;

    const isSummaryVisible = Boolean(show_summary);

    // Compute aggregated summary across campaigns or from API summary
    const totalSpend = campaigns.reduce((acc, c) => acc + (parseFloat(c.spend) || 0), 0);
    const totalResults = campaigns.reduce((acc, c) => acc + (parseInt(c.results) || 0), 0);
    const totalImpressions = campaigns.reduce((acc, c) => acc + (parseInt(c.impressions) || 0), 0);
    const totalReach = campaigns.reduce((acc, c) => acc + (parseInt(c.reach) || 0), 0);
    const totalClicks = campaigns.reduce((acc, c) => acc + (parseInt(c.clicks) || 0), 0);
    const totalPurchaseValue = campaigns.reduce((acc, c) => acc + (parseFloat(c.purchase_value) || 0), 0);

    const apiSummary = adsData?.summary || {};
    const summaryData = {
        spend: apiSummary.spend !== undefined && apiSummary.spend !== null ? parseFloat(apiSummary.spend) : totalSpend,
        results: apiSummary.results !== undefined && apiSummary.results !== null ? parseInt(apiSummary.results) : totalResults,
        impressions: apiSummary.impressions !== undefined && apiSummary.impressions !== null ? parseInt(apiSummary.impressions) : totalImpressions,
        reach: apiSummary.reach !== undefined && apiSummary.reach !== null ? parseInt(apiSummary.reach) : totalReach,
        clicks: apiSummary.clicks !== undefined && apiSummary.clicks !== null ? parseInt(apiSummary.clicks) : totalClicks,
        cpa: apiSummary.cpa !== undefined && apiSummary.cpa !== null
            ? parseFloat(apiSummary.cpa)
            : (totalResults > 0 ? totalSpend / totalResults : 0),
        ctr: apiSummary.ctr !== undefined && apiSummary.ctr !== null
            ? parseFloat(apiSummary.ctr)
            : (totalImpressions > 0 ? (totalClicks / totalImpressions) * 100 : 0),
        roas: apiSummary.roas !== undefined && apiSummary.roas !== null
            ? parseFloat(apiSummary.roas)
            : (totalSpend > 0 && totalPurchaseValue > 0 ? totalPurchaseValue / totalSpend : 0),
    };

    // Helper: format numbers in English (en-US)
    const formatNumber = (num, decimals = 0) => {
        if (num === null || num === undefined || isNaN(num)) return '0';
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(num);
    };

    // Helper: format currency (default 0 decimals for integer amount)
    const formatCurrency = (num, decimals = 0) => {
        return `${formatNumber(num, decimals)} ${currency}`;
    };

    // Helper: toggle campaign accordion
    const toggleCampaignAccordion = (campaignId) => {
        setExpandedCampaigns((prev) => ({
            ...prev,
            [campaignId]: !prev[campaignId],
        }));
    };

    // Helper: determine campaign goal type and badges ("إعلان مبيعات" أو "رسائل واتساب" أو "رسائل إنستغرام دايركت" أو "رسائل ماسنجر")
    const getCampaignGoalInfo = (campaign) => {
        const objective = (campaign.objective || '').toUpperCase();
        const name = (campaign.name || '').toLowerCase();
        const goalType = campaign.goal_type || '';

        let hasWhatsappIndicator = goalType === 'whatsapp';
        let hasInstagramIndicator = goalType === 'instagram';
        let hasMessengerIndicator = goalType === 'messenger';
        let hasMessageCta = false;

        // Check campaign name
        if (name.includes('واتس') || name.includes('whatsapp')) {
            hasWhatsappIndicator = true;
        }
        if (name.includes('انستا') || name.includes('انستجرام') || name.includes('انستقرام') || name.includes('instagram') || name.includes('insta')) {
            hasInstagramIndicator = true;
        }
        if (name.includes('ماسنجر') || name.includes('messenger') || name.includes('ماسن')) {
            hasMessengerIndicator = true;
        }

        // Check campaign-level destination / platform
        const campDest = (campaign.destination_type || '').toUpperCase();
        if (campDest.includes('INSTAGRAM')) {
            hasInstagramIndicator = true;
        } else if (campDest.includes('WHATSAPP')) {
            hasWhatsappIndicator = true;
        } else if (campDest.includes('MESSENGER')) {
            hasMessengerIndicator = true;
        }

        if ((campaign.platform || '').toUpperCase() === 'INSTAGRAM') {
            hasInstagramIndicator = true;
        }

        // Check ads creative CTA & links
        if (campaign.ads && Array.isArray(campaign.ads)) {
            campaign.ads.forEach((ad) => {
                const creative = ad.creative || {};
                const cta = (creative.cta_type || creative.call_to_action_type || '').toUpperCase();
                const link = (creative.link_url || creative.instagram_permalink_url || creative.preview_url || creative.post_url || ad.post_url || ad.facebook_post_url || '').toLowerCase();
                const adName = (ad.name || '').toLowerCase();
                const platform = (creative.platform || ad.platform || '').toUpperCase();

                if (cta === 'WHATSAPP_MESSAGE' || link.includes('wa.me') || link.includes('whatsapp') || adName.includes('واتس') || adName.includes('whatsapp')) {
                    hasWhatsappIndicator = true;
                    hasMessageCta = true;
                } else if (cta === 'INSTAGRAM_MESSAGE' || cta.includes('INSTAGRAM') || platform === 'INSTAGRAM' || Boolean(creative.instagram_permalink_url) || link.includes('instagram.com') || link.includes('instagr.am') || adName.includes('انستا') || adName.includes('انستجرام') || adName.includes('انستقرام') || adName.includes('instagram') || adName.includes('insta')) {
                    hasInstagramIndicator = true;
                    hasMessageCta = true;
                } else if (['MESSAGE_PAGE', 'SEND_MESSAGE'].includes(cta) || link.includes('m.me') || link.includes('messenger') || adName.includes('ماسنجر') || adName.includes('messenger')) {
                    hasMessengerIndicator = true;
                    hasMessageCta = true;
                } else if (['CONTACT_US', 'GET_QUOTE'].includes(cta)) {
                    hasMessageCta = true;
                }
            });
        }

        // Check adsets destination
        if (campaign.adsets && Array.isArray(campaign.adsets)) {
            campaign.adsets.forEach((adset) => {
                const dest = (adset.destination_type || '').toUpperCase();
                const adsetName = (adset.name || '').toLowerCase();
                if (dest.includes('WHATSAPP') || adsetName.includes('واتس') || adsetName.includes('whatsapp')) {
                    hasWhatsappIndicator = true;
                    hasMessageCta = true;
                } else if (dest.includes('INSTAGRAM') || adsetName.includes('انستا') || adsetName.includes('انستجرام') || adsetName.includes('انستقرام') || adsetName.includes('instagram') || adsetName.includes('insta')) {
                    hasInstagramIndicator = true;
                    hasMessageCta = true;
                } else if (dest.includes('MESSENGER') || adsetName.includes('ماسنجر') || adsetName.includes('messenger')) {
                    hasMessengerIndicator = true;
                    hasMessageCta = true;
                }
            });
        }

        const isMessages =
            hasWhatsappIndicator ||
            hasInstagramIndicator ||
            hasMessengerIndicator ||
            goalType === 'messages' ||
            goalType === 'instagram' ||
            goalType === 'whatsapp' ||
            goalType === 'messenger' ||
            objective === 'OUTCOME_ENGAGEMENT' ||
            objective === 'MESSAGES' ||
            objective.includes('ENGAGEMENT') ||
            objective.includes('MESSAGE') ||
            hasMessageCta ||
            name.includes('رسايل') ||
            name.includes('رسائل');

        if (isMessages) {
            // Prioritize WhatsApp if WhatsApp indicator exists
            if (hasWhatsappIndicator) {
                return {
                    isMessages: true,
                    goalType: 'whatsapp',
                    badgeText: 'رسائل واتساب',
                    badgeIcon: '💬',
                    badgeClasses: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    resultLabel: 'رسائل واتساب',
                    cpaLabel: 'تكلفة رسالة الواتساب',
                    showRoas: false,
                };
            }

            // Instagram Direct
            if (hasInstagramIndicator) {
                return {
                    isMessages: true,
                    goalType: 'instagram',
                    badgeText: 'رسائل إنستغرام دايركت',
                    badgeIcon: '📸',
                    badgeClasses: 'bg-gradient-to-r from-pink-50 to-purple-50 text-pink-700 border-pink-200',
                    resultLabel: 'رسائل إنستغرام دايركت',
                    cpaLabel: 'تكلفة رسالة إنستغرام',
                    showRoas: false,
                };
            }

            // Default to Messenger
            return {
                isMessages: true,
                goalType: 'messenger',
                badgeText: 'رسائل ماسنجر',
                badgeIcon: '💬',
                badgeClasses: 'bg-purple-50 text-purple-700 border-purple-200',
                resultLabel: 'رسائل ماسنجر',
                cpaLabel: 'تكلفة رسالة الماسنجر',
                showRoas: false,
            };
        }

        return {
            isMessages: false,
            goalType: 'sales',
            badgeText: 'إعلان مبيعات',
            badgeIcon: '🛍️',
            badgeClasses: 'bg-blue-50 text-blue-700 border-blue-200',
            resultLabel: 'طلبات شراء',
            cpaLabel: 'تكلفة الطلب (CPA)',
            showRoas: true,
        };
    };

    // Helper: determine ad platform, direct URL, button label, icon, and classes
    const getAdPlatformInfo = (ad, campaign = {}) => {
        const creative = ad.creative || {};
        const adName = (ad.name || '').toLowerCase();
        const campName = (campaign.name || '').toLowerCase();
        const cta = (creative.cta_type || creative.call_to_action_type || '').toUpperCase();
        const platform = (creative.platform || ad.platform || '').toUpperCase();

        const postUrl = ad.post_url
            || creative.post_url
            || creative.instagram_permalink_url
            || ad.facebook_post_url
            || creative.preview_url
            || creative.link_url
            || '';

        const postUrlLower = postUrl.toLowerCase();
        const isInstaUrl = postUrlLower.includes('instagram.com') || postUrlLower.includes('instagr.am');
        const isWhatsappUrl = postUrlLower.includes('wa.me') || postUrlLower.includes('whatsapp.com');

        const isInstagram =
            platform === 'INSTAGRAM' ||
            isInstaUrl ||
            Boolean(creative.instagram_permalink_url) ||
            cta === 'INSTAGRAM_MESSAGE' ||
            cta.includes('INSTAGRAM') ||
            adName.includes('انستا') || adName.includes('انستجرام') || adName.includes('انستقرام') || adName.includes('instagram') || adName.includes('insta') ||
            campName.includes('انستا') || campName.includes('انستجرام') || campName.includes('انستقرام') || campName.includes('instagram') || campName.includes('insta');

        const isWhatsapp =
            platform === 'WHATSAPP' ||
            isWhatsappUrl ||
            cta === 'WHATSAPP_MESSAGE' ||
            adName.includes('واتس') || adName.includes('whatsapp') ||
            campName.includes('واتس') || campName.includes('whatsapp');

        let resolvedUrl = postUrl;
        if (!resolvedUrl) {
            const imgUrl = creative.image_url || creative.thumbnail_url || '';
            const match = imgUrl.match(/_(\d{15,22})_/);
            if (match && match[1]) {
                resolvedUrl = `https://www.facebook.com/photo/?fbid=${match[1]}`;
            } else if (ad.id) {
                resolvedUrl = `https://www.facebook.com/ads/experience/confirmation/?ad_id=${ad.id}`;
            } else {
                resolvedUrl = isInstagram ? 'https://instagram.com' : 'https://facebook.com';
            }
        }

        if (isInstagram) {
            return {
                platform: 'INSTAGRAM',
                url: resolvedUrl,
                label: 'فتح في إنستجرام ↗',
                title: 'فتح منشور الإعلان في إنستجرام',
                buttonClasses: 'bg-gradient-to-r from-purple-600 via-pink-600 to-rose-500 hover:from-purple-700 hover:via-pink-700 hover:to-rose-600 text-white shadow-sm',
                iconType: 'instagram',
            };
        }

        if (isWhatsapp) {
            return {
                platform: 'WHATSAPP',
                url: resolvedUrl,
                label: 'محادثة واتساب ↗',
                title: 'فتح محادثة واتساب',
                buttonClasses: 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm',
                iconType: 'whatsapp',
            };
        }

        return {
            platform: 'FACEBOOK',
            url: resolvedUrl,
            label: 'فتح في فيسبوك ↗',
            title: 'فتح منشور الإعلان في فيسبوك',
            buttonClasses: 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm',
            iconType: 'facebook',
        };
    };

    // Helper: extract best Facebook/Instagram post URL for an ad (legacy alias)
    const getAdFacebookUrl = (ad, campaign = {}) => {
        return getAdPlatformInfo(ad, campaign).url;
    };

    // Helper: parse end_info into separate date/time and days/hours parts for mobile display
    const getEndInfoParts = (endInfo) => {
        if (!endInfo) return null;

        let dateOnly = endInfo.date_only || '';
        let timeOnly = endInfo.time_only || '';

        if (!dateOnly || !timeOnly) {
            const parts = (endInfo.formatted || '').split(' - ');
            dateOnly = parts[0] || endInfo.formatted || '';
            timeOnly = parts[1] || '';
        }

        let daysText = endInfo.days_text || '';
        let hoursText = endInfo.hours_text || '';

        if (!daysText && !hoursText) {
            const rem = endInfo.remaining_text || '';
            if (rem.includes(' و ')) {
                const remParts = rem.split(' و ');
                daysText = remParts[0] || '';
                hoursText = remParts[1] ? `و ${remParts[1]}` : '';
            } else {
                daysText = rem;
                hoursText = '';
            }
        }

        return {
            dateOnly,
            timeOnly,
            daysText,
            hoursText,
            isEnded: !!endInfo.is_ended,
        };
    };

    // Handle Date Preset Change
    const handlePresetChange = (newPreset) => {
        if (newPreset === datePreset) return;
        setLoading(true);
        router.get(
            route('merchant.marketing.ads'),
            { date_preset: newPreset },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setLoading(false),
            }
        );
    };

    // Handle Force Refresh
    const handleRefresh = () => {
        setLoading(true);
        router.get(
            route('merchant.marketing.ads'),
            { date_preset: datePreset, refresh: 1 },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setLoading(false),
            }
        );
    };

    // Handle Copy Detailed Report to Clipboard (Per campaign separately, no mixed summary)
    const handleCopyReport = () => {
        if (!campaigns || campaigns.length === 0) return;

        const presetLabel = allowedPresets[datePreset] || datePreset;
        const storeName = tenant?.name || 'متجري';
        const updateTime = lastUpdatedFormatted || '';

        let reportText = `📊 تقرير أداء إعلانات فيسبوك (Meta Ads)\n🏪 المتجر: ${storeName}\n🗓️ الفترة: ${presetLabel}\n⏱️ آخر تحديث: ${updateTime}\n🛒 عدد الأوردرات الفعلي: ${formatNumber(storeStats.orders_count)}\n🛍️ عدد أوردرات السلة المتروكة: ${formatNumber(storeStats.abandoned_count)}\n\n`;

        if (isSummaryVisible) {
            reportText += `═══════════════════════\n`;
            reportText += `📌 إجمالي نتائج الفترة (${presetLabel}):\n`;
            reportText += `💰 إجمالي المصروف: ${formatCurrency(summaryData.spend, 0)}\n`;
            reportText += `🎯 إجمالي النتائج: ${formatNumber(summaryData.results)}\n`;
            reportText += `🏷️ متوسط تكلفة النتيجة: ${formatCurrency(summaryData.cpa, 2)}\n`;
            if (summaryData.ctr > 0) {
                reportText += `👆 متوسط نسبة النقر (CTR): ${formatNumber(summaryData.ctr, 2)}%\n`;
            }
            if (summaryData.reach > 0) {
                reportText += `👥 إجمالي الوصول: ${formatNumber(summaryData.reach)}\n`;
            }
            if (summaryData.impressions > 0) {
                reportText += `👁️ مرات الظهور: ${formatNumber(summaryData.impressions)}\n`;
            }
            if (summaryData.roas > 0) {
                reportText += `📈 متوسط العائد (ROAS): ${formatNumber(summaryData.roas, 2)}x\n`;
            }
            reportText += `═══════════════════════\n\n`;
        }

        campaigns.forEach((c) => {
            const goalInfo = getCampaignGoalInfo(c);
            const statusInfo = getCampaignStatus(c);

            reportText += `───────────────────────\n`;
            reportText += `📢 ${c.name} (${statusInfo.label})\n`;
            reportText += `🎯 الهدف: ${goalInfo.badgeText} ${goalInfo.badgeIcon}\n`;
            reportText += `💰 المصروف: ${formatCurrency(c.spend, 0)}\n`;
            reportText += `🎯 النتائج (${goalInfo.resultLabel}): ${formatNumber(c.results)}\n`;
            reportText += `🏷️ ${goalInfo.cpaLabel}: ${formatCurrency(c.cpa, 2)}\n`;
            
            if (goalInfo.showRoas && c.roas > 0) {
                reportText += `📈 العائد الإعلاني (ROAS): ${formatNumber(c.roas, 2)}x\n`;
            }
            if (c.ctr) {
                reportText += `👆 نسبة النقر (CTR): ${formatNumber(c.ctr, 2)}%\n`;
            }
            if (c.reach) {
                reportText += `👥 إجمالي الوصول: ${formatNumber(c.reach)}\n`;
            }
            if (c.impressions) {
                reportText += `👁️ مرات الظهور: ${formatNumber(c.impressions)}\n`;
            }
            if (c.end_info) {
                reportText += `⏳ تاريخ الانتهاء: ${c.end_info.formatted} (${c.end_info.remaining_text})\n`;
            }
            reportText += `\n`;
        });

        reportText += `منصة FastOrder للتجارة الإلكترونية`;

        navigator.clipboard.writeText(reportText).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2500);
        });
    };

    return (
        <MerchantLayout title="إعلانات المتجر">
            <Head title="أداء وإحصائيات إعلانات فيسبوك" />

            <div className="max-w-7xl mx-auto space-y-6 pb-12" dir="rtl">
                {/* Header Banner */}
                <div className="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
                    <div className="absolute top-0 left-0 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none -translate-x-1/2 -translate-y-1/2" />
                    
                    <div className="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div className="space-y-2 max-w-2xl">
                            <div className="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-xs font-semibold text-blue-200 border border-white/10 backdrop-blur-sm">
                                <span>📢</span>
                                <span>لوحة إعلانات فيسبوك (Meta Ads)</span>
                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                            </div>
                            <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center gap-3 flex-wrap">
                                <span>أداء إعلانات فيسبوك (Meta Ads)</span>
                            </h1>
                            <p className="text-blue-200/90 text-xs md:text-sm leading-relaxed">
                                تتبع إحصائيات ونتائج حملات إعلانات متجرك على فيسبوك وإنستجرام، المصروف، تكلفة النتيجة، وتفاصيل كل إعلان بشكل مباشر.
                            </p>
                        </div>

                        {/* Top Action Button: Copy Report */}
                        {hasCampaigns && (
                            <div className="flex items-center gap-2.5 w-full md:w-auto">
                                <button
                                    type="button"
                                    onClick={handleCopyReport}
                                    disabled={campaigns.length === 0 || loading}
                                    className={`px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm border ${
                                        copied
                                            ? 'bg-emerald-600 text-white border-emerald-500 scale-105'
                                            : 'bg-white/10 hover:bg-white/20 active:scale-95 text-white border-white/20 backdrop-blur-sm'
                                    }`}
                                    title="نسخ تقرير أداء الحملات لمشاركته"
                                >
                                    {copied ? (
                                        <>
                                            <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>تم النسخ بنجاح! ✓</span>
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <span>نسخ تقرير الأداء 📋</span>
                                        </>
                                    )}
                                </button>
                            </div>
                        )}
                    </div>
                </div>

                {/* Date Filter & Last Updated Status Bar */}
                {hasCampaigns && (
                    <div className="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        {/* Date Presets Tabs */}
                        <div className="flex flex-wrap items-center gap-1.5 w-full md:w-auto">
                            <span className="text-xs font-bold text-gray-500 ml-2 hidden sm:inline">الفترة الزمنية:</span>
                            {Object.entries(allowedPresets).map(([key, label]) => {
                                const isActive = datePreset === key;
                                return (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => handlePresetChange(key)}
                                        disabled={loading}
                                        className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all ${
                                            isActive
                                                ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                                                : 'bg-gray-50 hover:bg-gray-100 text-gray-600 border border-gray-200/60'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                );
                            })}
                        </div>

                        {/* Last Updated Timestamp & Refresh Button */}
                        <div className="flex items-center gap-2.5 text-xs mr-auto md:mr-0 self-end md:self-center">
                            {lastUpdatedFormatted && (
                                <span className="inline-flex items-center gap-1.5 bg-gray-50 px-2.5 py-1.5 rounded-xl border border-gray-100 font-medium text-gray-600">
                                    <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>آخر تحديث:</span>
                                    <strong className="text-gray-800 font-bold font-mono">{lastUpdatedFormatted}</strong>
                                </span>
                            )}

                            {/* Refresh Button Placed Right Here */}
                            <button
                                type="button"
                                onClick={handleRefresh}
                                disabled={loading}
                                className="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm disabled:opacity-50"
                                title="تحديث البيانات فوراً من فيسبوك"
                            >
                                <svg
                                    className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`}
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    />
                                </svg>
                                <span>{loading ? 'جاري التحديث...' : 'تحديث'}</span>
                            </button>
                        </div>
                    </div>
                )}

                {/* Error Banner if any */}
                {adsData?.error && (
                    <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs md:text-sm font-semibold flex items-center justify-between gap-3 shadow-sm">
                        <div className="flex items-center gap-2">
                            <span>⚠️</span>
                            <span>{adsData.error}</span>
                        </div>
                        <button
                            type="button"
                            onClick={handleRefresh}
                            className="px-3 py-1 bg-rose-600 text-white rounded-lg text-xs font-bold hover:bg-rose-700"
                        >
                            إعادة المحاولة
                        </button>
                    </div>
                )}

                {/* Main Content: Either Empty State or Campaigns List */}
                {!hasCampaigns ? (
                    /* EMPTY STATE */
                    <div className="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100 max-w-2xl mx-auto my-8 space-y-6">
                        <div className="w-20 h-20 mx-auto rounded-3xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl shadow-inner border border-blue-100">
                            📢
                        </div>
                        <div className="space-y-2">
                            <h2 className="text-xl md:text-2xl font-extrabold text-gray-800">
                                لم يتم ربط حملات إعلانية لهذا المتجر حتى الآن
                            </h2>
                            <p className="text-gray-500 text-xs md:text-sm leading-relaxed max-w-md mx-auto">
                                تتيح لك لوحة إعلانات FastOrder ربط حملات فيسبوك وميتا الممولة لمتجرك ومتابعة المبيعات والرسائل والمصروف وتكلفة النتائج وتفاصيل كل إعلان مباشرة.
                            </p>
                        </div>

                        <div className="p-4 bg-gray-50 border border-gray-200/60 rounded-xl text-xs text-gray-600 space-y-1 max-w-md mx-auto text-right">
                            <div className="font-bold text-gray-700 flex items-center gap-1.5">
                                <span>💡</span>
                                <span>كيف تبدأ في تشغيل وربط إعلاناتك؟</span>
                            </div>
                            <p className="text-gray-500 leading-relaxed">
                                تواصل مع فريق الدعم الفني أو مدير حسابك في FastOrder لإطلاق حملتك الإعلانية الممولة وربط معرفات الحملات بمتجرك لتظهر نتائجها هنا مباشرة.
                            </p>
                        </div>

                        <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                            <a
                                href="https://wa.me/201027878848?text=%D9%85%D8%B1%D8%AD%D8%A8%D8%A7%D9%8B%D8%8C%20%D8%A3%D8%B1%D9%8A%D8%AF%20%D8%B1%D8%A8%D8%B7%20%D9%88%D8%A5%D8%B7%D9%84%D8%A7%D9%82%20%D8%AD%D9%85%D9%84%D8%A7%D8%AA%D9%8A%20%D8%A7%D9%84%D8%A5%D8%B9%D9%84%D8%A7%D9%86%D9%8A%D8%A9%20%D8%B9%D9%84%D9%89%20FastOrder"
                                target="_blank"
                                rel="noreferrer"
                                className="w-full sm:w-auto px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs md:text-sm font-bold shadow-md transition-all flex items-center justify-center gap-2"
                            >
                                <span>💬</span>
                                <span>تواصل مع الدعم الفني لإطلاق حملتك</span>
                            </a>
                        </div>
                    </div>
                ) : (
                    /* CAMPAIGNS LIST (Individual details per campaign, no combined totals) */
                    <div className="space-y-5">

                        {/* ── Store Stats: Real Orders & Abandoned Carts ── */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {/* Real Orders Count */}
                            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 flex items-center gap-4 hover:border-emerald-200 transition-colors">
                                <div className="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-2xl flex-shrink-0">
                                    🛒
                                </div>
                                <div className="min-w-0">
                                    <p className="text-xs font-bold text-gray-600 mb-1">عدد الأوردرات الفعلي</p>
                                    <strong className="text-2xl sm:text-3xl font-extrabold text-emerald-700 font-mono leading-none block">
                                        {formatNumber(storeStats.orders_count)}
                                    </strong>
                                </div>
                            </div>

                            {/* Abandoned Carts Count */}
                            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 flex items-center gap-4 hover:border-amber-200 transition-colors">
                                <div className="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-2xl flex-shrink-0">
                                    🛍️
                                </div>
                                <div className="min-w-0">
                                    <p className="text-xs font-bold text-gray-600 mb-1">عدد أوردرات السلة المتروكة</p>
                                    <strong className="text-2xl sm:text-3xl font-extrabold text-amber-600 font-mono leading-none block">
                                        {formatNumber(storeStats.abandoned_count)}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        {/* Overall Summary Card (Only if show_summary is enabled) */}
                        {isSummaryVisible && (
                            <div className="bg-gradient-to-br from-indigo-900 via-slate-900 to-slate-950 text-white rounded-2xl p-5 sm:p-6 shadow-lg border border-indigo-950/50 space-y-4">
                                <div className="flex items-center justify-between border-b border-white/10 pb-3 flex-wrap gap-2">
                                    <div className="flex items-center gap-2.5">
                                        <span className="w-8 h-8 rounded-xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-lg">
                                            📊
                                        </span>
                                        <div>
                                            <h3 className="text-sm sm:text-base font-extrabold text-white flex items-center gap-2">
                                                <span>إجمالي نتائج الحملات ({allowedPresets[datePreset] || datePreset})</span>
                                            </h3>
                                            <p className="text-[11px] text-indigo-200/70 font-medium">
                                                ملخص المجموع الإجمالي لكافة الحملات الإعلانية المحددة
                                            </p>
                                        </div>
                                    </div>
                                    <span className="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 font-mono">
                                        {campaigns.length} حملات
                                    </span>
                                </div>

                                {/* Metric Cards Grid */}
                                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                                    {/* Total Spend */}
                                    <div className="bg-white/5 hover:bg-white/10 transition-colors rounded-xl p-3 sm:p-4 border border-white/10">
                                        <span className="text-[11px] sm:text-xs text-indigo-200 block mb-1">💰 إجمالي المصروف</span>
                                        <strong className="text-lg sm:text-2xl font-extrabold font-mono text-emerald-400 block tracking-tight">
                                            {formatCurrency(summaryData.spend, 0)}
                                        </strong>
                                        <span className="text-[10px] text-gray-400 block mt-0.5">الإنفاق الإعلاني الكلي</span>
                                    </div>

                                    {/* Total Results */}
                                    <div className="bg-white/5 hover:bg-white/10 transition-colors rounded-xl p-3 sm:p-4 border border-white/10">
                                        <span className="text-[11px] sm:text-xs text-indigo-200 block mb-1">🎯 إجمالي النتائج</span>
                                        <strong className="text-lg sm:text-2xl font-extrabold font-mono text-blue-300 block tracking-tight">
                                            {formatNumber(summaryData.results)}
                                        </strong>
                                        <span className="text-[10px] text-gray-400 block mt-0.5">تفاعلات / طلبات</span>
                                    </div>

                                    {/* Average CPA */}
                                    <div className="bg-white/5 hover:bg-white/10 transition-colors rounded-xl p-3 sm:p-4 border border-white/10">
                                        <span className="text-[11px] sm:text-xs text-indigo-200 block mb-1">🏷️ متوسط تكلفة النتيجة</span>
                                        <strong className="text-lg sm:text-2xl font-extrabold font-mono text-amber-300 block tracking-tight">
                                            {formatCurrency(summaryData.cpa, 2)}
                                        </strong>
                                        <span className="text-[10px] text-gray-400 block mt-0.5">متوسط CPA</span>
                                    </div>

                                    {/* Average CTR */}
                                    <div className="bg-white/5 hover:bg-white/10 transition-colors rounded-xl p-3 sm:p-4 border border-white/10">
                                        <span className="text-[11px] sm:text-xs text-indigo-200 block mb-1">👆 متوسط نسبة النقر (CTR)</span>
                                        <strong className="text-lg sm:text-2xl font-extrabold font-mono text-purple-300 block tracking-tight">
                                            {formatNumber(summaryData.ctr, 2)}%
                                        </strong>
                                        <span className="text-[10px] text-gray-400 block mt-0.5">تفاعل النقرات للظهور</span>
                                    </div>
                                </div>

                                {/* Secondary Metrics Bar: Reach, Impressions, Clicks, ROAS */}
                                <div className="bg-black/20 rounded-xl p-3 flex flex-wrap items-center justify-around gap-4 text-xs border border-white/5">
                                    <div className="flex items-center gap-2">
                                        <span className="text-gray-400">👥 إجمالي الوصول:</span>
                                        <strong className="text-white font-mono font-bold">{formatNumber(summaryData.reach)}</strong>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-gray-400">👁️ مرات الظهور:</span>
                                        <strong className="text-white font-mono font-bold">{formatNumber(summaryData.impressions)}</strong>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-gray-400">🖱️ النقرات:</span>
                                        <strong className="text-white font-mono font-bold">{formatNumber(summaryData.clicks)}</strong>
                                    </div>
                                    {summaryData.roas > 0 && (
                                        <div className="flex items-center gap-2">
                                            <span className="text-gray-400">📈 متوسط العائد (ROAS):</span>
                                            <strong className="text-emerald-400 font-mono font-bold">{formatNumber(summaryData.roas, 2)}x</strong>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <span>الحملات الإعلانية المربوطة</span>
                                <span className="text-xs px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-100">
                                    {campaigns.length} حملة
                                </span>
                            </h3>
                        </div>

                        {campaigns.length === 0 ? (
                            <div className="bg-white rounded-2xl p-8 text-center text-gray-500 border border-gray-100">
                                لا توجد بيانات حملات مسجلة لهذه الفترة الزمنية المحددة.
                            </div>
                        ) : (
                            <div className="space-y-5">
                                {campaigns.map((campaign) => {
                                    const isExpanded = !!expandedCampaigns[campaign.id];
                                    const statusInfo = getCampaignStatus(campaign);
                                    const goalInfo = getCampaignGoalInfo(campaign);
                                    const endParts = getEndInfoParts(campaign.end_info);

                                    return (
                                        <div
                                            key={campaign.id}
                                            className="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden transition-all hover:border-blue-300"
                                        >
                                            {/* Campaign Top Bar: Name, Goal Badge, Status */}
                                            <div className="p-5 border-b border-gray-100 bg-gradient-to-b from-gray-50/50 to-white">
                                                <div className="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                                    <div className="space-y-1.5">
                                                        <div className="flex items-center gap-2 flex-wrap">
                                                            {/* Status Badge (نشطة / منتهية / متوقفة) */}
                                                            <span
                                                                className={`px-2.5 py-0.5 rounded-full text-xs font-bold border ${statusInfo.badgeClasses}`}
                                                            >
                                                                {statusInfo.label}
                                                            </span>

                                                            {/* Goal Badge: Strictly "إعلان مبيعات" or "إعلان رسائل" */}
                                                            <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold border flex items-center gap-1 ${goalInfo.badgeClasses}`}>
                                                                <span>{goalInfo.badgeIcon}</span>
                                                                <span>{goalInfo.badgeText}</span>
                                                            </span>

                                                            <h4 className="text-base font-bold text-gray-900 truncate" title={campaign.name}>
                                                                {campaign.name}
                                                            </h4>
                                                        </div>

                                                        <div className="flex items-center gap-3 text-xs text-gray-400 font-mono flex-wrap">
                                                            <span>معرف الحملة: <span className="select-all text-gray-600 font-bold">{campaign.id}</span></span>
                                                            
                                                            {/* End Date & Remaining Time */}
                                                            {campaign.end_info ? (
                                                                <>
                                                                    {/* Desktop view (inline) */}
                                                                    <span className="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-xs font-sans font-medium">
                                                                        <span>⏳ الانتهاء:</span>
                                                                        <strong className="font-mono text-gray-800 font-bold">{campaign.end_info.formatted}</strong>
                                                                        <span className="bg-amber-100 text-amber-900 px-1.5 py-0.2 rounded font-bold text-[11px]">
                                                                            {campaign.end_info.remaining_text}
                                                                        </span>
                                                                    </span>

                                                                    {/* Mobile view only (Date on top, Time below; Days on top, Hours below) */}
                                                                    {endParts && (
                                                                        <div className="flex sm:hidden items-center justify-between gap-3 px-3 py-2 rounded-xl bg-amber-50 text-amber-900 border border-amber-200 text-xs font-sans font-medium w-full mt-1.5">
                                                                            {/* Right side: Icon + Date & Time */}
                                                                            <div className="flex items-center gap-2 text-right">
                                                                                <span className="text-base flex-shrink-0">⏳</span>
                                                                                <div className="flex flex-col leading-tight">
                                                                                    <span className="text-[10px] text-amber-800/80 font-bold">الانتهاء:</span>
                                                                                    <strong className="font-mono text-gray-900 font-bold text-xs">{endParts.dateOnly}</strong>
                                                                                    <span className="font-mono text-gray-700 text-[11px] font-semibold">{endParts.timeOnly}</span>
                                                                                </div>
                                                                            </div>

                                                                            {/* Left side: Remaining days on top, hours below */}
                                                                            <div className="bg-amber-100/90 text-amber-950 px-2.5 py-1 rounded-lg text-center flex flex-col justify-center leading-tight border border-amber-200/60 flex-shrink-0">
                                                                                <span className="font-bold text-xs">{endParts.daysText}</span>
                                                                                {endParts.hoursText && (
                                                                                    <span className="text-[11px] font-semibold text-amber-900 mt-0.5">{endParts.hoursText}</span>
                                                                                )}
                                                                            </div>
                                                                        </div>
                                                                    )}
                                                                </>
                                                            ) : (
                                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-gray-50 text-gray-500 border border-gray-200 text-[11px] font-sans">
                                                                    <span>⏳ تاريخ الانتهاء:</span>
                                                                    <span>مستمرة (غير محدد وقت انتهاء)</span>
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Accordion Toggle Button */}
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleCampaignAccordion(campaign.id)}
                                                        className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 border self-start md:self-center ${
                                                            isExpanded
                                                                ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                                : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200 shadow-sm'
                                                        }`}
                                                    >
                                                        <span>
                                                            {isExpanded ? 'إخفاء الإعلانات' : `عرض الإعلانات الترويجية (${campaign.ads?.length || 0})`}
                                                        </span>
                                                        <svg
                                                            className={`w-4 h-4 transition-transform duration-200 ${isExpanded ? 'rotate-180' : ''}`}
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            {/* Campaign Key Stats Grid (Individual for this campaign only) */}
                                            <div className="p-5">
                                                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                                                    {/* 1. Spend */}
                                                    <div className="bg-slate-50 p-3 rounded-xl border border-slate-100 flex flex-col justify-between">
                                                        <span className="text-[11px] font-semibold text-gray-500">💰 المصروف</span>
                                                        <div className="mt-1">
                                                            <strong className="text-base font-extrabold text-gray-900 font-mono block">
                                                                {formatNumber(campaign.spend, 0)}
                                                            </strong>
                                                            <span className="text-[10px] text-gray-400 font-semibold">{currency}</span>
                                                        </div>
                                                    </div>

                                                    {/* 2. Results */}
                                                    <div className="bg-emerald-50/60 p-3 rounded-xl border border-emerald-100 flex flex-col justify-between">
                                                        <span className="text-[11px] font-semibold text-emerald-800">
                                                            🎯 النتائج ({goalInfo.resultLabel})
                                                        </span>
                                                        <div className="mt-1">
                                                            <strong className="text-base font-extrabold text-emerald-700 font-mono block">
                                                                {formatNumber(campaign.results)}
                                                            </strong>
                                                            <span className="text-[10px] text-emerald-600 font-semibold">{goalInfo.resultLabel}</span>
                                                        </div>
                                                    </div>

                                                    {/* 3. CPA / Cost Per Result */}
                                                    <div className="bg-purple-50/60 p-3 rounded-xl border border-purple-100 flex flex-col justify-between">
                                                        <span className="text-[11px] font-semibold text-purple-800">
                                                            🏷️ {goalInfo.cpaLabel}
                                                        </span>
                                                        <div className="mt-1">
                                                            <strong className="text-base font-extrabold text-purple-700 font-mono block">
                                                                {formatNumber(campaign.cpa, 2)}
                                                            </strong>
                                                            <span className="text-[10px] text-purple-600 font-semibold">{currency} / نتيجة</span>
                                                        </div>
                                                    </div>

                                                    {/* 4. ROAS (Only for Sales) or Total Impressions */}
                                                    {goalInfo.showRoas ? (
                                                        <div className="bg-amber-50/60 p-3 rounded-xl border border-amber-100 flex flex-col justify-between">
                                                            <span className="text-[11px] font-semibold text-amber-800">📈 العائد (ROAS)</span>
                                                            <div className="mt-1">
                                                                <strong className="text-base font-extrabold text-amber-700 font-mono block">
                                                                    {formatNumber(campaign.roas, 2)}x
                                                                </strong>
                                                                <span className="text-[10px] text-amber-600 font-semibold">عائد الإنفاق</span>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <div className="bg-slate-50 p-3 rounded-xl border border-slate-100 flex flex-col justify-between">
                                                            <span className="text-[11px] font-semibold text-gray-500">👁️ مرات الظهور</span>
                                                            <div className="mt-1">
                                                                <strong className="text-base font-extrabold text-gray-800 font-mono block">
                                                                    {formatNumber(campaign.impressions)}
                                                                </strong>
                                                                <span className="text-[10px] text-gray-400 font-semibold">ظهور</span>
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* 5. CTR */}
                                                    <div className="bg-indigo-50/60 p-3 rounded-xl border border-indigo-100 flex flex-col justify-between">
                                                        <span className="text-[11px] font-semibold text-indigo-800">👆 نسبة النقر (CTR)</span>
                                                        <div className="mt-1">
                                                            <strong className="text-base font-extrabold text-indigo-700 font-mono block">
                                                                {formatNumber(campaign.ctr, 2)}%
                                                            </strong>
                                                            <span className="text-[10px] text-indigo-600 font-semibold">معدل التفاعل</span>
                                                        </div>
                                                    </div>

                                                    {/* 6. Reach / Clicks */}
                                                    <div className="bg-slate-50 p-3 rounded-xl border border-slate-100 flex flex-col justify-between">
                                                        <span className="text-[11px] font-semibold text-gray-500">👥 الوصول / النقرات</span>
                                                        <div className="mt-1">
                                                            <strong className="text-sm font-extrabold text-gray-800 font-mono block">
                                                                {formatNumber(campaign.reach)} <span className="text-[10px] text-gray-400 font-normal">وصول</span>
                                                            </strong>
                                                            <span className="text-[10px] text-gray-500 font-mono">
                                                                {formatNumber(campaign.clicks)} نقرة
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Accordion Details: AdSets & Ads */}
                                            {isExpanded && (
                                                <div className="bg-slate-50/70 border-t border-gray-200/80 p-5 space-y-6">
                                                    {/* AdSets summary */}
                                                    {campaign.adsets && campaign.adsets.length > 0 && (
                                                        <div className="space-y-2">
                                                            <h5 className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                                                <span>📁 المجموعات الإعلانية (AdSets)</span>
                                                                <span className="text-[10px] text-slate-500">({campaign.adsets.length})</span>
                                                            </h5>
                                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                                {campaign.adsets.map((adset) => (
                                                                    <div key={adset.id} className="bg-white p-3.5 rounded-xl border border-slate-200/70 text-xs space-y-2">
                                                                        <div className="flex items-center justify-between gap-2">
                                                                            <span className="font-bold text-gray-900 truncate" title={adset.name}>{adset.name}</span>
                                                                            <span className="font-mono text-[10px] text-gray-400 select-all">{adset.id}</span>
                                                                        </div>
                                                                        <div className="grid grid-cols-3 gap-2 text-[11px] pt-1 border-t border-slate-100 font-mono">
                                                                            <div>
                                                                                <span className="text-gray-400 block text-[10px]">المصروف:</span>
                                                                                <strong className="text-gray-800">{formatCurrency(adset.spend)}</strong>
                                                                            </div>
                                                                            <div>
                                                                                <span className="text-gray-400 block text-[10px]">{goalInfo.resultLabel}:</span>
                                                                                <strong className="text-emerald-600">{formatNumber(adset.results)}</strong>
                                                                            </div>
                                                                            <div>
                                                                                <span className="text-gray-400 block text-[10px]">{goalInfo.cpaLabel}:</span>
                                                                                <strong className="text-purple-600">{formatCurrency(adset.cpa)}</strong>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* Ads Creatives List */}
                                                    <div className="space-y-3">
                                                        <h5 className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                                            <span>🖼️ الإعلانات ومحتواها الترويجي (Ads)</span>
                                                            <span className="text-[10px] text-slate-500">({campaign.ads?.length || 0})</span>
                                                        </h5>

                                                        {(!campaign.ads || campaign.ads.length === 0) ? (
                                                            <p className="text-xs text-gray-400 py-3">لا توجد إعلانات مسجلة داخل هذه الحملة.</p>
                                                        ) : (
                                                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                                                {campaign.ads.map((ad) => {
                                                                    const creative = ad.creative || {};
                                                                    const imageUrl = creative.image_url || creative.thumbnail_url;
                                                                    const platformInfo = getAdPlatformInfo(ad, campaign);

                                                                    return (
                                                                        <div
                                                                            key={ad.id}
                                                                            className="bg-white rounded-xl border border-slate-200/80 p-4 shadow-sm flex flex-col justify-between gap-4 hover:border-blue-300 transition-colors"
                                                                        >
                                                                            <div className="space-y-3">
                                                                                {/* Ad Header: Title, Status, and Direct Link Button */}
                                                                                <div className="flex items-start justify-between gap-2">
                                                                                    <div className="space-y-0.5 min-w-0">
                                                                                        <h6 className="text-xs font-bold text-gray-900 line-clamp-1" title={ad.name}>
                                                                                            {ad.name}
                                                                                        </h6>
                                                                                        <span className="font-mono text-[10px] text-gray-400 select-all block">معرف: {ad.id}</span>
                                                                                    </div>

                                                                                    <div className="flex items-center gap-1.5 flex-shrink-0">
                                                                                        <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                                                            {ad.status === 'ACTIVE' ? 'نشط 🟢' : 'متوقف ⏸️'}
                                                                                        </span>

                                                                                        {/* Button to open Post directly on platform (Instagram / Facebook / WhatsApp) */}
                                                                                        <a
                                                                                            href={platformInfo.url}
                                                                                            target="_blank"
                                                                                            rel="noopener noreferrer"
                                                                                            className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold shadow-sm transition-all active:scale-95 ${platformInfo.buttonClasses}`}
                                                                                            title={platformInfo.title}
                                                                                        >
                                                                                            {platformInfo.iconType === 'instagram' ? (
                                                                                                <svg className="w-3 h-3 fill-current" viewBox="0 0 24 24">
                                                                                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                                                                                                </svg>
                                                                                            ) : platformInfo.iconType === 'whatsapp' ? (
                                                                                                <svg className="w-3 h-3 fill-current" viewBox="0 0 24 24">
                                                                                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z" />
                                                                                                </svg>
                                                                                            ) : (
                                                                                                <svg className="w-3 h-3 fill-current" viewBox="0 0 24 24">
                                                                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                                                                </svg>
                                                                                            )}
                                                                                            <span>{platformInfo.label}</span>
                                                                                        </a>
                                                                                    </div>
                                                                                </div>

                                                                                {/* Creative Preview */}
                                                                                <div className="flex gap-3 items-start bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                                                                    {imageUrl ? (
                                                                                        <a
                                                                                            href={platformInfo.url}
                                                                                            target="_blank"
                                                                                            rel="noopener noreferrer"
                                                                                            className="w-20 h-20 rounded-lg overflow-hidden bg-gray-200 flex-shrink-0 border border-gray-200 relative group block cursor-pointer"
                                                                                            title="اضغط لفتح المنشور على فيسبوك"
                                                                                        >
                                                                                            <img
                                                                                                src={imageUrl}
                                                                                                alt={ad.name}
                                                                                                className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                                                                                            />
                                                                                            <div className="absolute inset-0 bg-blue-900/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity text-white text-[10px] font-bold text-center p-1">
                                                                                                <span>عرض البوست ↗</span>
                                                                                            </div>
                                                                                        </a>
                                                                                    ) : (
                                                                                        <div className="w-20 h-20 rounded-lg bg-gray-100 text-gray-400 flex items-center justify-center text-xs flex-shrink-0 border border-gray-200">
                                                                                            بدون صورة
                                                                                        </div>
                                                                                    )}

                                                                                    <div className="flex-1 min-w-0 space-y-1">
                                                                                        {creative.title && (
                                                                                            <strong className="block text-xs font-bold text-gray-800 line-clamp-1">
                                                                                                {creative.title}
                                                                                            </strong>
                                                                                        )}
                                                                                        <p className="text-[11px] text-gray-600 line-clamp-3 leading-relaxed whitespace-pre-line select-text">
                                                                                            {creative.body || 'لا يوجد نص ترويجي محدد.'}
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            {/* Ad Performance Numbers */}
                                                                            <div className="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-center font-mono text-xs">
                                                                                <div className="bg-slate-50 p-1.5 rounded">
                                                                                    <span className="text-[10px] text-gray-400 block font-sans">المصروف</span>
                                                                                    <strong className="text-gray-800 text-[11px]">{formatCurrency(ad.spend)}</strong>
                                                                                </div>
                                                                                <div className="bg-slate-50 p-1.5 rounded">
                                                                                    <span className="text-[10px] text-gray-400 block font-sans">النقرات</span>
                                                                                    <strong className="text-gray-800 text-[11px]">{formatNumber(ad.clicks)}</strong>
                                                                                </div>
                                                                                <div className="bg-slate-50 p-1.5 rounded">
                                                                                    <span className="text-[10px] text-gray-400 block font-sans">CTR</span>
                                                                                    <strong className="text-indigo-600 text-[11px]">{formatNumber(ad.ctr, 2)}%</strong>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    );
                                                                })}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </MerchantLayout>
    );
}
