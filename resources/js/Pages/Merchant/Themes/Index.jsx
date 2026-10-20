import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function ThemesIndex({ themes = {}, active_theme = 'default', active_theme_config = null }) {
    const { flash = {} } = usePage().props;
    const [activatingSlug, setActivatingSlug] = useState(null);

    // Transform themes object or array to array with keys
    const themesList = Object.entries(themes).map(([key, value]) => {
        return {
            ...value,
            slug: value.slug || key,
        };
    });

    const activeThemeObj = themesList.find(t => t.slug === active_theme) || active_theme_config || {
        slug: active_theme,
        name: active_theme === 'fast_cod' ? 'ثيم Fast COD Express (إيزي أوردر)' : (active_theme === 'luxury_store' ? 'ثيم Luxury Brand Store' : 'الثيم الافتراضي'),
        description: 'الثيم المفعّل حالياً في متجرك',
        version: '1.0.0',
    };

    const handleActivate = (slug) => {
        if (slug === active_theme) return;
        setActivatingSlug(slug);

        router.post('/admin/themes/activate', {
            theme: slug,
        }, {
            preserveScroll: true,
            onFinish: () => setActivatingSlug(null)
        });
    };

    // Helper for theme badges and tags
    const getThemeBadge = (slug) => {
        switch (slug) {
            case 'fast_cod':
                return { text: 'الأعلى تحويلاً وإيزي أوردر 🔥', bg: 'bg-emerald-500/10 text-emerald-600 border-emerald-200' };
            case 'luxury_store':
                return { text: 'شوبيفاي & براندات الموضة ✨', bg: 'bg-purple-500/10 text-purple-600 border-purple-200' };
            case 'default':
                return { text: 'كلاسيكي شامل وموثوق 🛍️', bg: 'bg-blue-500/10 text-blue-600 border-blue-200' };
            case 'modern':
            case 'modern_minimalist':
                return { text: 'عصري وناعم (Minimal) 💎', bg: 'bg-slate-500/10 text-slate-700 border-slate-200' };
            case 'bold':
                return { text: 'جريء ونابض بالألوان 🎨', bg: 'bg-rose-500/10 text-rose-600 border-rose-200' };
            case 'starter':
                return { text: 'خفيف وفائق السرعة ⚡', bg: 'bg-amber-500/10 text-amber-700 border-amber-200' };
            default:
                return { text: 'ثيم متكامل 🌟', bg: 'bg-gray-100 text-gray-700 border-gray-200' };
        }
    };

    // Helper for visual theme gradients
    const getThemeBannerGradient = (slug) => {
        switch (slug) {
            case 'fast_cod':
                return 'from-emerald-600 via-green-600 to-teal-700';
            case 'luxury_store':
                return 'from-zinc-900 via-neutral-900 to-stone-800';
            case 'default':
                return 'from-indigo-600 via-blue-600 to-indigo-800';
            case 'modern':
            case 'modern_minimalist':
                return 'from-slate-800 via-slate-900 to-zinc-950';
            case 'bold':
                return 'from-pink-600 via-rose-600 to-orange-500';
            case 'starter':
                return 'from-amber-500 via-orange-500 to-red-500';
            default:
                return 'from-indigo-600 to-blue-700';
        }
    };

    return (
        <MerchantLayout title="الثيمات والقوالب">
            <Head>
                <title>الثيمات والقوالب — فاست أوردر</title>
            </Head>

            <div className="max-w-7xl mx-auto space-y-8 pb-16">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <div>
                        <div className="flex items-center gap-3">
                            <span className="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                </svg>
                            </span>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">مكتبة الثيمات والقوالب</h1>
                                <p className="text-sm text-gray-500 mt-1">
                                    بدّل مظهر وتجربة متجرك فوراً بنقرة واحدة — اختر بين أسلوب إيزي أوردر السريع وشوبيفاي للماركات
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <a
                            href="/admin/theme"
                            className="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition duration-150"
                        >
                            <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            تخصيص الألوان والخطوط
                        </a>
                        <a
                            href="/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition duration-150"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            زيارة واجهة المتجر الحية
                        </a>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-800 text-sm font-medium animate-fadeIn">
                        <svg className="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{flash.success}</span>
                    </div>
                )}

                {/* Active Theme Highlight Hero Card */}
                <div className="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                    <div className="p-6 md:p-8">
                        <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                            <div className="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                                <div className={`w-20 h-20 rounded-2xl bg-gradient-to-br ${getThemeBannerGradient(active_theme)} flex items-center justify-center text-white shadow-md flex-shrink-0`}>
                                    <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <div className="flex items-center gap-3 flex-wrap">
                                        <span className="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full flex items-center gap-1.5 border border-emerald-200">
                                            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            الثيم النشط حالياً في متجرك
                                        </span>
                                        <span className="text-xs font-medium text-gray-400">الإصدار {activeThemeObj.version || '1.0.0'}</span>
                                    </div>
                                    <h2 className="text-2xl font-extrabold text-gray-900 mt-2">
                                        {activeThemeObj.name}
                                    </h2>
                                    <p className="text-gray-600 text-sm mt-1 max-w-2xl leading-relaxed">
                                        {activeThemeObj.description}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 w-full lg:w-auto">
                                <a
                                    href="/admin/theme"
                                    className="flex-1 lg:flex-none text-center px-5 py-3 text-sm font-bold text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-xl transition duration-150"
                                >
                                    تخصيص الثيم
                                </a>
                                <a
                                    href="/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex-1 lg:flex-none text-center px-6 py-3 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition duration-150"
                                >
                                    معاينة المتجر الحي
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Available Themes Section Header */}
                <div className="flex items-center justify-between border-b border-gray-200 pb-4">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900">مكتبة الثيمات المتاحة</h2>
                        <p className="text-xs text-gray-500 mt-1">اختر أي ثيم للتبديل الفوري دون فقدان أي بيانات لمنتجاتك أو إعداداتك</p>
                    </div>
                    <span className="text-sm font-semibold text-gray-500 bg-gray-100 px-3 py-1 rounded-lg">
                        {themesList.length} قوالب متاحة
                    </span>
                </div>

                {/* Themes Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {themesList.map((theme) => {
                        const isActive = theme.slug === active_theme;
                        const badge = getThemeBadge(theme.slug);
                        const isActivating = activatingSlug === theme.slug;

                        return (
                            <div
                                key={theme.slug}
                                className={`bg-white rounded-2xl border transition-all duration-200 overflow-hidden flex flex-col justify-between ${
                                    isActive
                                        ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md'
                                        : 'border-gray-200 hover:border-gray-300 hover:shadow-md'
                                }`}
                            >
                                {/* Theme Visual Preview Header */}
                                <div className={`h-40 bg-gradient-to-br ${getThemeBannerGradient(theme.slug)} p-6 flex flex-col justify-between text-white relative overflow-hidden`}>
                                    <div className="flex items-center justify-between z-10">
                                        <span className={`text-[11px] font-bold px-2.5 py-1 rounded-full border bg-white/95 backdrop-blur-sm shadow-sm ${badge.bg}`}>
                                            {badge.text}
                                        </span>
                                        {isActive && (
                                            <span className="px-2.5 py-1 bg-emerald-500 text-white text-[11px] font-bold rounded-full flex items-center gap-1 shadow-sm">
                                                <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                                نشط الآن
                                            </span>
                                        )}
                                    </div>

                                    <div className="z-10">
                                        <h3 className="text-lg font-bold text-white drop-shadow-sm">
                                            {theme.name}
                                        </h3>
                                        <p className="text-xs text-white/80 line-clamp-1 mt-0.5">
                                            {theme.author || 'Fast Order Team'}
                                        </p>
                                    </div>

                                    {/* Subtle decorative circles */}
                                    <div className="absolute -bottom-6 -left-6 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                                    <div className="absolute -top-6 -right-6 w-24 h-24 bg-black/10 rounded-full blur-xl"></div>
                                </div>

                                {/* Card Body */}
                                <div className="p-5 flex-1 flex flex-col justify-between">
                                    <div>
                                        <p className="text-xs text-gray-600 leading-relaxed min-h-[48px]">
                                            {theme.description}
                                        </p>

                                        {/* Highlights */}
                                        <div className="mt-4 pt-3 border-t border-gray-100 flex flex-wrap gap-1.5">
                                            {theme.slug === 'fast_cod' && (
                                                <>
                                                    <span className="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[11px] rounded-md font-medium">فورم بالصفحة</span>
                                                    <span className="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[11px] rounded-md font-medium">شريط عائم</span>
                                                    <span className="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[11px] rounded-md font-medium">باقات كميات</span>
                                                </>
                                            )}
                                            {theme.slug === 'luxury_store' && (
                                                <>
                                                    <span className="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[11px] rounded-md font-medium">ستايل شوبيفاي</span>
                                                    <span className="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[11px] rounded-md font-medium">فلاتر جانبية</span>
                                                    <span className="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[11px] rounded-md font-medium">سلة أنيقة</span>
                                                </>
                                            )}
                                            {theme.slug === 'default' && (
                                                <>
                                                    <span className="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[11px] rounded-md font-medium">كلاسيك شامل</span>
                                                    <span className="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[11px] rounded-md font-medium">RTL كامل</span>
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="mt-5 pt-3 border-t border-gray-100">
                                        {isActive ? (
                                            <div className="flex items-center gap-2">
                                                <div className="flex-1 py-2.5 px-3 bg-emerald-50 text-emerald-700 font-bold text-xs rounded-xl text-center border border-emerald-200 flex items-center justify-center gap-1.5">
                                                    <svg className="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    هو الثيم النشط لمتجرك
                                                </div>
                                                <a
                                                    href="/admin/theme"
                                                    className="py-2.5 px-3 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition"
                                                >
                                                    تخصيص
                                                </a>
                                            </div>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() => handleActivate(theme.slug)}
                                                disabled={isActivating}
                                                className="w-full py-2.5 px-4 text-xs font-bold text-white bg-gray-900 hover:bg-indigo-600 disabled:bg-gray-400 rounded-xl shadow-sm transition duration-150 flex items-center justify-center gap-2"
                                            >
                                                {isActivating ? (
                                                    <>
                                                        <span className="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                                        جاري التفعيل...
                                                    </>
                                                ) : (
                                                    <>
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        تفعيل هذا الثيم
                                                    </>
                                                )}
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </MerchantLayout>
    );
}
