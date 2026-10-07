import React, { useState } from 'react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head, router } from '@inertiajs/react';

export default function MarketingAdsIndex({
    adsData = {},
    datePreset = 'last_7d',
    allowedPresets = {},
    hasCampaigns = false,
    campaignIds = [],
    tenant = null,
}) {
    const [loading, setLoading] = useState(false);
    const [copied, setCopied] = useState(false);
    const [expandedCampaigns, setExpandedCampaigns] = useState({});

    const campaigns = adsData?.campaigns || [];
    const currency = adsData?.currency || 'EGP';
    const lastUpdatedFormatted = adsData?.last_updated_formatted || null;
    const isFromCache = adsData?.is_from_cache ?? false;

    // Helper: format numbers in English (en-US)
    const formatNumber = (num, decimals = 0) => {
        if (num === null || num === undefined || isNaN(num)) return '0';
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(num);
    };

    // Helper: format currency
    const formatCurrency = (num) => {
        return `${formatNumber(num, 2)} ${currency}`;
    };

    // Helper: toggle campaign accordion
    const toggleCampaignAccordion = (campaignId) => {
        setExpandedCampaigns((prev) => ({
            ...prev,
            [campaignId]: !prev[campaignId],
        }));
    };

    // Helper: determine campaign goal type and badges (Strictly "إعلان مبيعات" or "إعلان رسائل")
    const getCampaignGoalInfo = (campaign) => {
        const objective = (campaign.objective || '').toUpperCase();
        const name = campaign.name || '';
        
        let hasMessageCta = false;
        if (campaign.ads && Array.isArray(campaign.ads)) {
            hasMessageCta = campaign.ads.some((ad) => {
                const cta = ad.creative?.cta_type || '';
                return ['MESSAGE_PAGE', 'SEND_MESSAGE', 'WHATSAPP_MESSAGE'].includes(cta);
            });
        }

        const isMessages =
            campaign.goal_type === 'messages' ||
            objective === 'OUTCOME_ENGAGEMENT' ||
            objective === 'MESSAGES' ||
            objective.includes('ENGAGEMENT') ||
            objective.includes('MESSAGE') ||
            hasMessageCta ||
            name.includes('رسايل') ||
            name.includes('رسائل');

        if (isMessages) {
            return {
                isMessages: true,
                badgeText: 'إعلان رسائل',
                badgeIcon: '💬',
                badgeClasses: 'bg-purple-50 text-purple-700 border-purple-200',
                resultLabel: 'رسائل',
                cpaLabel: 'تكلفة الرسالة',
                showRoas: false,
            };
        }

        return {
            isMessages: false,
            badgeText: 'إعلان مبيعات',
            badgeIcon: '🛍️',
            badgeClasses: 'bg-blue-50 text-blue-700 border-blue-200',
            resultLabel: 'طلبات شراء',
            cpaLabel: 'تكلفة الطلب (CPA)',
            showRoas: true,
        };
    };

    // Helper: extract best Facebook post URL for an ad
    const getAdFacebookUrl = (ad) => {
        if (ad.facebook_post_url) return ad.facebook_post_url;
        if (ad.creative?.preview_url) return ad.creative.preview_url;

        const imgUrl = ad.creative?.image_url || ad.creative?.thumbnail_url || '';
        // Look for Facebook fbid in image url (e.g. 825267597_122165699576728779_...)
        const match = imgUrl.match(/_(\d{15,22})_/);
        if (match && match[1]) {
            return `https://www.facebook.com/photo/?fbid=${match[1]}`;
        }

        if (ad.id) {
            return `https://www.facebook.com/ads/experience/confirmation/?ad_id=${ad.id}`;
        }

        return 'https://facebook.com';
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
        const updateTime = lastUpdatedFormatted || new Date().toLocaleString('en-US');

        let reportText = `📊 تقرير أداء إعلانات فيسبوك (Meta Ads)\n🏪 المتجر: ${storeName}\n🗓️ الفترة: ${presetLabel}\n⏱️ وقت الفحص: ${updateTime}\n\n`;

        campaigns.forEach((c) => {
            const goalInfo = getCampaignGoalInfo(c);
            const statusText = c.status === 'ACTIVE' || c.effective_status === 'ACTIVE' ? 'نشط 🟢' : 'متوقف ⏸️';

            reportText += `───────────────────────\n`;
            reportText += `📢 ${c.name} (${statusText})\n`;
            reportText += `🎯 الهدف: ${goalInfo.badgeText} ${goalInfo.badgeIcon}\n`;
            reportText += `💰 المصروف: ${formatCurrency(c.spend)}\n`;
            reportText += `🎯 النتائج (${goalInfo.resultLabel}): ${formatNumber(c.results)}\n`;
            reportText += `🏷️ ${goalInfo.cpaLabel}: ${formatCurrency(c.cpa)}\n`;
            
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

                        {/* Top Action Buttons (Refresh + Copy) */}
                        {hasCampaigns && (
                            <div className="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                                <button
                                    type="button"
                                    onClick={handleRefresh}
                                    disabled={loading}
                                    className="px-3.5 py-2 bg-white/10 hover:bg-white/20 active:scale-95 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 border border-white/15 backdrop-blur-sm disabled:opacity-50 shadow-sm"
                                    title="تحديث البيانات فوراً من فيسبوك"
                                >
                                    <svg
                                        className={`w-4 h-4 ${loading ? 'animate-spin text-blue-300' : 'text-white'}`}
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
                                    <span>{loading ? 'جاري الفحص...' : 'تحديث البيانات'}</span>
                                </button>

                                <button
                                    type="button"
                                    onClick={handleCopyReport}
                                    disabled={campaigns.length === 0 || loading}
                                    className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm border ${
                                        copied
                                            ? 'bg-emerald-600 text-white border-emerald-500 scale-105'
                                            : 'bg-blue-600 hover:bg-blue-500 text-white border-blue-400/40 active:scale-95'
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

                        {/* Last Updated Timestamp & Status */}
                        {lastUpdatedFormatted && (
                            <div className="flex items-center gap-2 text-xs text-gray-500 mr-auto md:mr-0 self-end md:self-center">
                                <span className="inline-flex items-center gap-1.5 bg-gray-50 px-2.5 py-1 rounded-lg border border-gray-100 font-medium text-gray-600">
                                    <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>آخر فحص:</span>
                                    <strong className="text-gray-800 font-bold font-mono">{lastUpdatedFormatted}</strong>
                                </span>

                                {isFromCache ? (
                                    <span className="text-[11px] px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-100 font-medium" title="البيانات محفوظة مؤقتاً لمدة 5 دقائق لتسريع التصفح">
                                        ذاكرة مؤقتة (5 دقائق) 💾
                                    </span>
                                ) : (
                                    <span className="text-[11px] px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-100 font-medium">
                                        بيانات مباشرة من فيسبوك 🔄
                                    </span>
                                )}
                            </div>
                        )}
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
                                    const isActive = campaign.status === 'ACTIVE' || campaign.effective_status === 'ACTIVE';
                                    const goalInfo = getCampaignGoalInfo(campaign);

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
                                                            {/* Active Status Badge */}
                                                            <span
                                                                className={`px-2.5 py-0.5 rounded-full text-xs font-bold border ${
                                                                    isActive
                                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                                        : 'bg-gray-100 text-gray-600 border-gray-200'
                                                                }`}
                                                            >
                                                                {isActive ? 'نشطة 🟢' : 'متوقفة ⏸️'}
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

                                                        <div className="flex items-center gap-3 text-xs text-gray-400 font-mono">
                                                            <span>معرف الحملة: <span className="select-all text-gray-600 font-bold">{campaign.id}</span></span>
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
                                                                {formatNumber(campaign.spend, 2)}
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
                                                                    const fbPostUrl = getAdFacebookUrl(ad);

                                                                    return (
                                                                        <div
                                                                            key={ad.id}
                                                                            className="bg-white rounded-xl border border-slate-200/80 p-4 shadow-sm flex flex-col justify-between gap-4 hover:border-blue-300 transition-colors"
                                                                        >
                                                                            <div className="space-y-3">
                                                                                {/* Ad Header: Title, Status, and Direct Facebook Link Button */}
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

                                                                                        {/* Button to open Post directly on Facebook */}
                                                                                        <a
                                                                                            href={fbPostUrl}
                                                                                            target="_blank"
                                                                                            rel="noopener noreferrer"
                                                                                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-all active:scale-95"
                                                                                            title="فتح منشور الإعلان في فيسبوك"
                                                                                        >
                                                                                            <svg className="w-3 h-3 fill-current" viewBox="0 0 24 24">
                                                                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                                                            </svg>
                                                                                            <span>فتح في فيسبوك ↗</span>
                                                                                        </a>
                                                                                    </div>
                                                                                </div>

                                                                                {/* Creative Preview (Clicking image opens Facebook post) */}
                                                                                <div className="flex gap-3 items-start bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                                                                    {imageUrl ? (
                                                                                        <a
                                                                                            href={fbPostUrl}
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
