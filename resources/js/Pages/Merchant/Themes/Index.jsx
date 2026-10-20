import React, { useState, useEffect } from 'react';
import { Head, router, usePage, Link } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function ThemesIndex({ themes = {}, active_theme = 'default', activeTheme = null, active_theme_config = null }) {
    const { flash = {} } = usePage().props;
    const [activatingSlug, setActivatingSlug] = useState(null);

    const currentActiveTheme = activeTheme || active_theme || 'default';

    // Normalize themes to list
    const allThemesList = Object.entries(themes).map(([key, value]) => {
        return {
            ...value,
            slug: value.slug || key,
        };
    });

    // Ensure the 2 new themes + default are present
    const defaultAvailable = [
        {
            slug: 'fast_cod',
            name: 'سريع كود (COD Express)',
            name_ar: 'سريع كود (COD Express)',
        },
        {
            slug: 'luxury_store',
            name: 'المتجر الفاخر (Luxury Store)',
            name_ar: 'المتجر الفاخر (Luxury Store)',
        },
        {
            slug: 'default',
            name: 'المتجر الكلاسيكي (Classic Store)',
            name_ar: 'المتجر الكلاسيكي (Classic Store)',
        }
    ];

    const prioritizedSlugs = ['fast_cod', 'luxury_store', 'default'];

    const themesMap = {};
    defaultAvailable.forEach(item => { themesMap[item.slug] = item; });
    allThemesList.forEach(item => { themesMap[item.slug] = { ...themesMap[item.slug], ...item }; });

    // Filter to focus on the 2 new themes + classic store
    const themesList = prioritizedSlugs.map(slug => themesMap[slug] || { slug });

    // Clean Arabic Theme Name Helper (No competitor names)
    const getCleanThemeName = (theme) => {
        switch (theme.slug) {
            case 'fast_cod':
                return 'سريع كود (COD Express)';
            case 'luxury_store':
                return 'المتجر الفاخر (Luxury Store)';
            case 'default':
                return 'المتجر الكلاسيكي (Classic Store)';
            default:
                return theme.name_ar || theme.name || theme.slug;
        }
    };

    // Preview image path helper (Desktop + Mobile Mockup)
    const getThemeMockupImage = (slug) => {
        switch (slug) {
            case 'fast_cod':
                return '/shop/images/themes/fast_cod-preview.svg';
            case 'luxury_store':
                return '/shop/images/themes/luxury_store-preview.svg';
            case 'default':
            default:
                return '/shop/images/themes/default-preview.svg';
        }
    };

    // Activate Theme Handler
    const handleActivate = (slug) => {
        if (slug === currentActiveTheme) return;
        setActivatingSlug(slug);

        router.post('/admin/themes/activate', {
            theme: slug,
        }, {
            preserveScroll: true,
            onFinish: () => setActivatingSlug(null)
        });
    };

    // Standalone Demo Store Preview URLs
    const PREVIEW_DEMO_URLS = {
        fast_cod: '/theme-previews/fast_cod.html',
        luxury_store: '/theme-previews/luxury_store.html',
        default: '/theme-previews/fast_cod.html',
    };

    // Open Live Full Preview in new tab (standalone demo store)
    const handleOpenPreview = (slug) => {
        const previewUrl = PREVIEW_DEMO_URLS[slug] || `/theme-previews/${slug}.html`;
        window.open(previewUrl, '_blank');
    };

    // Automatically activate if redirected back from the preview window with ?activate=
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const activateSlug = params.get('activate');
        if (activateSlug && activateSlug !== currentActiveTheme) {
            handleActivate(activateSlug);
            window.history.replaceState({}, '', window.location.pathname);
        }
    }, []);

    return (
        <MerchantLayout title="الثيمات والقوالب">
            <Head>
                <title>الثيمات والقوالب — فاست أوردر</title>
            </Head>

            <div className="max-w-7xl mx-auto space-y-6 pb-16 rtl text-right" dir="rtl">
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
                                <h1 className="text-2xl font-bold text-gray-900">الثيمات والقوالب</h1>
                                <p className="text-sm text-gray-500 mt-0.5">
                                    اختر الثيم الأنسب لمتجرك — يمكنك معاينة متجر كامل وتفعيله بنقرة واحدة
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Link
                            href="/admin/theme"
                            className="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition duration-150"
                        >
                            <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            تخصيص الألوان والخطوط
                        </Link>
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

                {/* Themes Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {themesList.map((theme) => {
                        const isActive = theme.slug === currentActiveTheme;
                        const isActivating = activatingSlug === theme.slug;
                        const themeName = getCleanThemeName(theme);
                        const mockupSrc = getThemeMockupImage(theme.slug);

                        return (
                            <div
                                key={theme.slug}
                                className={`bg-white rounded-2xl border transition-all duration-200 overflow-hidden flex flex-col justify-between ${
                                    isActive
                                        ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md'
                                        : 'border-gray-200 hover:border-gray-300 hover:shadow-md'
                                }`}
                            >
                                {/* 1. Mockup Image with Active Badge & Click to Preview */}
                                <div
                                    className="relative aspect-[16/10] bg-gray-100 overflow-hidden group cursor-pointer"
                                    onClick={() => handleOpenPreview(theme.slug)}
                                >
                                    <img
                                        src={mockupSrc}
                                        alt={themeName}
                                        className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        onError={(e) => {
                                            e.target.onerror = null;
                                            e.target.src = '/shop/images/themes/default-preview.svg';
                                        }}
                                    />

                                    {/* Active Badge */}
                                    {isActive && (
                                        <div className="absolute top-3 right-3 z-10">
                                            <span className="px-3 py-1 bg-emerald-600 text-white text-xs font-bold rounded-full flex items-center gap-1.5 shadow-md">
                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                                ثيمك الحالي
                                            </span>
                                        </div>
                                    )}

                                    {/* Hover Overlay with Preview Tag */}
                                    <div className="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <span className="px-4 py-2 bg-white/95 text-gray-900 text-xs font-bold rounded-xl shadow-lg flex items-center gap-2">
                                            <svg className="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            انقر لفتح المعاينة الكاملة
                                        </span>
                                    </div>
                                </div>

                                {/* 2. Theme Name and 2 Action Buttons */}
                                <div className="p-4 flex flex-col justify-between flex-1 gap-4">
                                    {/* Name */}
                                    <div className="text-center pt-1">
                                        <h3 className="text-base font-extrabold text-gray-900">
                                            {themeName}
                                        </h3>
                                    </div>

                                    {/* 3. Actions: Preview button + Activate button ONLY */}
                                    <div className="pt-3 border-t border-gray-100 flex items-center gap-2.5">
                                        {/* زرار المعاينة */}
                                        <button
                                            type="button"
                                            onClick={() => handleOpenPreview(theme.slug)}
                                            className="flex-1 py-2.5 px-3 text-xs font-bold text-gray-700 hover:text-indigo-600 bg-gray-50 hover:bg-gray-100 border border-gray-200 hover:border-indigo-200 rounded-xl transition flex items-center justify-center gap-1.5"
                                        >
                                            <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            معاينة
                                        </button>

                                        {/* زرار التفعيل */}
                                        {isActive ? (
                                            <button
                                                type="button"
                                                disabled
                                                className="flex-1 py-2.5 px-3 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-300 rounded-xl flex items-center justify-center gap-1 cursor-default"
                                            >
                                                <svg className="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                ثيمك الحالي
                                            </button>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() => handleActivate(theme.slug)}
                                                disabled={isActivating}
                                                className="flex-1 py-2.5 px-3 text-xs font-bold text-white bg-gray-900 hover:bg-indigo-600 disabled:bg-gray-400 rounded-xl shadow-sm transition duration-150 flex items-center justify-center gap-1.5"
                                            >
                                                {isActivating ? (
                                                    <>
                                                        <span className="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                                        جاري التفعيل...
                                                    </>
                                                ) : (
                                                    'تفعيل'
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
