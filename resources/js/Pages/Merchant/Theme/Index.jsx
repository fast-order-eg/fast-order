import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

// Color Preset Themes
const COLOR_PRESETS = [
    {
        name: 'أسود أنيق (الافتراضي)',
        primary: '#000000',
        secondary: '#1F2937',
        background: '#FFFFFF',
        class: 'from-gray-950 to-gray-800'
    },
    {
        name: 'برتقالي دافئ',
        primary: '#F97316',
        secondary: '#1F2937',
        background: '#FFFFFF',
        class: 'from-orange-500 to-gray-800'
    },
    {
        name: 'أزرق كلاسيكي',
        primary: '#2563EB',
        secondary: '#0F172A',
        background: '#F8FAFC',
        class: 'from-blue-600 to-slate-900'
    },
    {
        name: 'وردي ناعم',
        primary: '#EC4899',
        secondary: '#4D1D49',
        background: '#FFF1F2',
        class: 'from-pink-500 to-purple-950'
    },
    {
        name: 'أخضر',
        primary: '#10B981',
        secondary: '#064E3B',
        background: '#F0FDF4',
        class: 'from-emerald-500 to-emerald-950'
    },
    {
        name: 'بنفسجي حديث',
        primary: '#8B5CF6',
        secondary: '#1E1B4B',
        background: '#FAF5FF',
        class: 'from-violet-500 to-indigo-950'
    }
];

export default function ThemeIndex({ themeCustomization }) {
    const { flash } = usePage().props;

    // Form setup using Inertia's useForm helper
    const { data, setData, put, processing, errors, hasChanges } = useForm({
        primary_color: themeCustomization.primary_color || '#000000',
        secondary_color: themeCustomization.secondary_color || '#1F2937',
        background_color: themeCustomization.background_color || '#FFFFFF',
        font_family: themeCustomization.font_family || 'Almarai',
        header_layout: themeCustomization.header_layout || 'Classic',
        banner_layout: themeCustomization.banner_layout || 'Slider',
        border_radius: themeCustomization.border_radius || '8px',
    });

    const applyPreset = (preset) => {
        setData(prev => ({
            ...prev,
            primary_color: preset.primary,
            secondary_color: preset.secondary,
            background_color: preset.background
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put('/admin/theme', {
            preserveScroll: true,
        });
    };

    // Helper for Font Family styling in Live Preview
    const getFontFamilyStyle = (font) => {
        switch (font) {
            case 'Cairo':
                return { fontFamily: "'Cairo', sans-serif" };
            case 'Tajawal':
                return { fontFamily: "'Tajawal', sans-serif" };
            case 'Almarai':
            default:
                return { fontFamily: "'Almarai', sans-serif" };
        }
    };

    return (
        <MerchantLayout title="مظهر وتصميم المتجر">
            <Head>
                <title>مظهر وتصميم المتجر</title>
                <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Cairo:wght@300;400;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet" />
            </Head>

            <div className="space-y-6 rtl text-right" dir="rtl">
                {/* Title & Desc */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-extrabold text-gray-900">تصميم وتخصيص مظهر المتجر</h2>
                        <p className="text-sm text-gray-500 mt-1">
                            خصص هوية متجرك البصرية، الألوان، الخطوط، وتخطيط الصفحة الرئيسية ليتناسب مع علامتك التجارية.
                        </p>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border-r-4 border-green-500 rounded-xl text-green-800 text-sm font-medium flex items-center gap-3 shadow-sm animate-in fade-in slide-in-from-top-4 duration-200">
                        <span className="flex items-center justify-center w-5 h-5 bg-green-100 rounded-full text-green-600 text-xs">✓</span>
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border-r-4 border-red-500 rounded-xl text-red-800 text-sm font-medium flex items-center gap-3 shadow-sm animate-in fade-in slide-in-from-top-4 duration-200">
                        <span className="flex items-center justify-center w-5 h-5 bg-red-100 rounded-full text-red-600 text-xs">⚠️</span>
                        {flash.error}
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    {/* Right Side: Customization Form */}
                    <div className="lg:col-span-5 space-y-6">
                        <form onSubmit={handleSubmit} className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                            <div className="p-6 space-y-6">
                                
                                {/* 1. Color Presets */}
                                <div className="space-y-3">
                                    <label className="block text-sm font-bold text-gray-800">تجهيزات ألوان جاهزة (Presets)</label>
                                    <div className="flex flex-wrap gap-2">
                                        {COLOR_PRESETS.map((preset, idx) => (
                                            <button
                                                key={idx}
                                                type="button"
                                                onClick={() => applyPreset(preset)}
                                                className="flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 transition-all cursor-pointer shadow-sm"
                                            >
                                                <span className={`w-4 h-4 rounded-full bg-gradient-to-tr ${preset.class} border border-white shadow-inner flex-shrink-0`} />
                                                <span>{preset.name.split(' ')[0]}</span>
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                <hr className="border-gray-100" />

                                {/* 2. Custom Colors */}
                                <div className="space-y-4">
                                    <h4 className="text-sm font-bold text-gray-900 flex items-center gap-2">
                                        🎨 لوحة الألوان المخصصة
                                    </h4>
                                    
                                    <div className="grid grid-cols-1 gap-4">
                                        {/* Primary Color */}
                                        <div className="flex items-center justify-between gap-4 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                            <div className="flex-1">
                                                <label className="block text-xs font-bold text-gray-700">اللون الرئيسي (Primary)</label>
                                                <span className="text-[10px] text-gray-400">للأزرار الرئيسية والتصنيفات النشطة والعناصر الهامة.</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    value={data.primary_color}
                                                    onChange={(e) => setData('primary_color', e.target.value)}
                                                    className="w-20 px-2 py-1 text-xs border border-gray-300 rounded font-mono text-center"
                                                />
                                                <input
                                                    type="color"
                                                    value={data.primary_color}
                                                    onChange={(e) => setData('primary_color', e.target.value)}
                                                    className="w-8 h-8 rounded-lg border border-gray-300 cursor-pointer overflow-hidden p-0"
                                                />
                                            </div>
                                        </div>
                                        {errors.primary_color && <p className="text-xs text-red-600 -mt-2">{errors.primary_color}</p>}

                                        {/* Secondary Color */}
                                        <div className="flex items-center justify-between gap-4 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                            <div className="flex-1">
                                                <label className="block text-xs font-bold text-gray-700">اللون الثانوي (Secondary)</label>
                                                <span className="text-[10px] text-gray-400">للهيدر، الفوتر، النصوص الثانوية، والخلفيات الداكنة.</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    value={data.secondary_color}
                                                    onChange={(e) => setData('secondary_color', e.target.value)}
                                                    className="w-20 px-2 py-1 text-xs border border-gray-300 rounded font-mono text-center"
                                                />
                                                <input
                                                    type="color"
                                                    value={data.secondary_color}
                                                    onChange={(e) => setData('secondary_color', e.target.value)}
                                                    className="w-8 h-8 rounded-lg border border-gray-300 cursor-pointer overflow-hidden p-0"
                                                />
                                            </div>
                                        </div>
                                        {errors.secondary_color && <p className="text-xs text-red-600 -mt-2">{errors.secondary_color}</p>}

                                        {/* Background Color */}
                                        <div className="flex items-center justify-between gap-4 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                            <div className="flex-1">
                                                <label className="block text-xs font-bold text-gray-700">لون الخلفية (Background)</label>
                                                <span className="text-[10px] text-gray-400">لون خلفية صفحات المتجر العامة.</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    value={data.background_color}
                                                    onChange={(e) => setData('background_color', e.target.value)}
                                                    className="w-20 px-2 py-1 text-xs border border-gray-300 rounded font-mono text-center"
                                                />
                                                <input
                                                    type="color"
                                                    value={data.background_color}
                                                    onChange={(e) => setData('background_color', e.target.value)}
                                                    className="w-8 h-8 rounded-lg border border-gray-300 cursor-pointer overflow-hidden p-0"
                                                />
                                            </div>
                                        </div>
                                        {errors.background_color && <p className="text-xs text-red-600 -mt-2">{errors.background_color}</p>}
                                    </div>
                                </div>

                                <hr className="border-gray-100" />

                                {/* 3. Font Family Selection */}
                                <div className="space-y-3">
                                    <h4 className="text-sm font-bold text-gray-900 flex items-center gap-2">
                                        🔤 خط الكتابة الافتراضي
                                    </h4>
                                    <div className="grid grid-cols-3 gap-2">
                                        {['Almarai', 'Cairo', 'Tajawal'].map((font) => (
                                            <button
                                                key={font}
                                                type="button"
                                                onClick={() => setData('font_family', font)}
                                                className={`px-3 py-2.5 rounded-xl border text-xs font-bold transition-all text-center cursor-pointer ${
                                                    data.font_family === font
                                                        ? 'border-orange-500 bg-orange-50 text-orange-600 shadow-sm'
                                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                                }`}
                                                style={getFontFamilyStyle(font)}
                                            >
                                                {font}
                                            </button>
                                        ))}
                                    </div>
                                    {errors.font_family && <p className="text-xs text-red-600">{errors.font_family}</p>}
                                </div>

                                <hr className="border-gray-100" />

                                {/* 4. Header Layout Design */}
                                <div className="space-y-3">
                                    <h4 className="text-sm font-bold text-gray-900 flex items-center gap-2">
                                        🗂️ تصميم الهيدر (Header Layout)
                                    </h4>
                                    <div className="grid grid-cols-1 gap-2">
                                        {[
                                            { id: 'Classic', title: 'كلاسيكي (Classic)', desc: 'شعار على اليمين، القائمة في الوسط، وأيقونات التحكم على اليسار.' },
                                            { id: 'Centered', title: 'متمركز (Centered)', desc: 'شعار المتجر بالمنتصف تماماً، والقائمة الموزعة في الوسط بشكل متناسق.' },
                                            { id: 'Minimal', title: 'بسيط ناعم (Minimal)', desc: 'تصميم مدمج وبسيط بارتفاع ناعم وأزرار دائرية للمتاجر العصرية.' }
                                        ].map((item) => (
                                            <label
                                                key={item.id}
                                                onClick={() => setData('header_layout', item.id)}
                                                className={`flex items-start gap-3 p-3 rounded-xl border text-right cursor-pointer transition-all ${
                                                    data.header_layout === item.id
                                                        ? 'border-orange-500 bg-orange-50/50 shadow-sm'
                                                        : 'border-gray-200 bg-white hover:bg-gray-50'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="header_layout"
                                                    checked={data.header_layout === item.id}
                                                    onChange={() => {}}
                                                    className="mt-1 text-orange-600 focus:ring-orange-500"
                                                />
                                                <div>
                                                    <span className="block text-xs font-bold text-gray-900">{item.title}</span>
                                                    <span className="block text-[10px] text-gray-500 mt-0.5">{item.desc}</span>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                    {errors.header_layout && <p className="text-xs text-red-600">{errors.header_layout}</p>}
                                </div>

                                <hr className="border-gray-100" />

                                {/* 5. Banner Layout */}
                                <div className="space-y-3">
                                    <h4 className="text-sm font-bold text-gray-900 flex items-center gap-2">
                                        🖼️ تخصيص البانر الرئيسي (Hero Banner)
                                    </h4>
                                    <div className="grid grid-cols-1 gap-2">
                                        {[
                                            { id: 'Slider', title: 'شريط متحرك (Slider)', desc: 'عرض صور متعددة متحركة تلقائياً مع مؤشرات تنقل دائرية.' },
                                            { id: 'Grid', title: 'شبكة بانرات (Grid)', desc: 'عرض بانر رئيسي عريض وبجانبه بانرات ثانوية صغيرة وجذابة.' },
                                            { id: 'Single Banner', title: 'بانر فردي ثابت (Single)', desc: 'صورة دعائية واحدة عريضة وثابتة في أعلى الصفحة الرئيسية.' }
                                        ].map((item) => (
                                            <label
                                                key={item.id}
                                                onClick={() => setData('banner_layout', item.id)}
                                                className={`flex items-start gap-3 p-3 rounded-xl border text-right cursor-pointer transition-all ${
                                                    data.banner_layout === item.id
                                                        ? 'border-orange-500 bg-orange-50/50 shadow-sm'
                                                        : 'border-gray-200 bg-white hover:bg-gray-50'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="banner_layout"
                                                    checked={data.banner_layout === item.id}
                                                    onChange={() => {}}
                                                    className="mt-1 text-orange-600 focus:ring-orange-500"
                                                />
                                                <div>
                                                    <span className="block text-xs font-bold text-gray-900">{item.title}</span>
                                                    <span className="block text-[10px] text-gray-500 mt-0.5">{item.desc}</span>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                    {errors.banner_layout && <p className="text-xs text-red-600">{errors.banner_layout}</p>}
                                </div>

                                <hr className="border-gray-100" />

                                {/* 6. Border Radius Customization */}
                                <div className="space-y-3">
                                    <h4 className="text-sm font-bold text-gray-900 flex items-center gap-2">
                                        📐 درجة انحناء الحواف (Border Radius)
                                    </h4>
                                    <p className="text-xs text-gray-500">تحكم في نسبة تدوير زوايا العناصر والبطاقات والأزرار والبانرات عبر المتجر:</p>
                                    
                                    <div className="grid grid-cols-3 gap-2">
                                        {[
                                            { id: '0px', title: 'مربع صريح', val: '0px' },
                                            { id: '4px', title: 'انحناء خفيف', val: '4px' },
                                            { id: '8px', title: 'انحناء متناسق', val: '8px' },
                                            { id: '12px', title: 'انحناء متوسط', val: '12px' },
                                            { id: '16px', title: 'انحناء كبير', val: '16px' },
                                            { id: '20px', title: 'انحناء دافئ', val: '20px' },
                                        ].map((item) => (
                                            <button
                                                key={item.id}
                                                type="button"
                                                onClick={() => setData('border_radius', item.id)}
                                                className={`flex flex-col items-center justify-center p-2.5 rounded-xl border text-center transition-all cursor-pointer ${
                                                    (data.border_radius || '8px') === item.id
                                                        ? 'border-orange-500 bg-orange-50/60 text-orange-900 ring-2 ring-orange-500/20 shadow-sm'
                                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                                }`}
                                            >
                                                <div 
                                                    className="w-10 h-5 border border-current mb-1 bg-gray-100 flex items-center justify-center text-[8px] font-bold"
                                                    style={{ borderRadius: item.val }}
                                                >
                                                    كارت
                                                </div>
                                                <span className="text-[11px] font-bold block">{item.title}</span>
                                                <span className="text-[9px] opacity-60 block">{item.val}</span>
                                            </button>
                                        ))}
                                    </div>
                                    {errors.border_radius && <p className="text-xs text-red-600">{errors.border_radius}</p>}
                                </div>

                            </div>

                            {/* Footer Submit Button */}
                            <div className="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                                <span className="text-xs text-gray-400">
                                    {hasChanges ? 'لديك تعديلات غير محفوظة' : 'المظهر متطابق مع المحفوظ'}
                                </span>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className={`px-5 py-2.5 rounded-xl text-xs font-bold text-white transition-all flex items-center gap-2 cursor-pointer shadow-md ${
                                        processing
                                            ? 'bg-gray-400 cursor-not-allowed'
                                            : 'bg-orange-500 hover:bg-orange-600 hover:shadow-lg active:scale-95'
                                    }`}
                                >
                                    {processing && (
                                        <svg className="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                    )}
                                    حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Left Side: Live Storefront Mobile Mockup */}
                    <div className="lg:col-span-7 flex flex-col items-center">
                        <div className="w-full max-w-sm sticky top-6">
                            
                            <div className="text-center mb-3">
                                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse" />
                                    معاينة حية للمتجر (Live Preview)
                                </span>
                                <p className="text-[11px] text-gray-400 mt-1">تتأثر المعاينة تلقائياً بالخيارات المختارة على اليمين</p>
                            </div>

                            {/* Smartphone Container Mockup */}
                            <div className="relative mx-auto border-[8px] border-slate-800 bg-slate-800 rounded-[2.5rem] shadow-2xl overflow-hidden aspect-[9/18] w-full max-w-[320px]">
                                
                                {/* Phone Notch/Speaker */}
                                <div className="absolute top-2 left-1/2 -translate-x-1/2 w-28 h-4 bg-slate-900 rounded-full z-20 flex items-center justify-center">
                                    <div className="w-12 h-1 bg-gray-800 rounded-full" />
                                </div>

                                {/* Phone Screen View */}
                                <div 
                                    className="w-full h-full pt-6 flex flex-col text-right select-none overflow-y-auto"
                                    style={{ 
                                        backgroundColor: data.background_color,
                                        ...getFontFamilyStyle(data.font_family)
                                    }}
                                >
                                    
                                    {/* SIMULATED HEADER */}
                                    <div 
                                        className="py-3 px-3 shadow-sm flex items-center justify-between border-b transition-all duration-300 z-10 bg-white border-gray-100"
                                    >
                                        {/* Layout: Classic */}
                                        {data.header_layout === 'Classic' && (
                                            <>
                                                {/* Right: Brand Name */}
                                                <div className="flex items-center gap-1.5">
                                                    <span className="w-5 h-5 rounded bg-orange-500 flex items-center justify-center text-white text-[10px] font-black" style={{ backgroundColor: data.primary_color }}>F</span>
                                                    <span className="text-[11px] font-bold text-gray-900">متجري الأنيق</span>
                                                </div>
                                                {/* Left: Cart & Search */}
                                                <div className="text-gray-600 flex items-center gap-2">
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                    </svg>
                                                </div>
                                            </>
                                        )}

                                        {/* Layout: Centered */}
                                        {data.header_layout === 'Centered' && (
                                            <>
                                                {/* Right: Hamburger Menu */}
                                                <div className="text-gray-600">
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                                                    </svg>
                                                </div>
                                                {/* Center: Brand Name */}
                                                <div className="flex items-center gap-1 mx-auto">
                                                    <span className="w-5 h-5 rounded bg-orange-500 flex items-center justify-center text-white text-[10px] font-black" style={{ backgroundColor: data.primary_color }}>F</span>
                                                    <span className="text-[11px] font-bold text-gray-900">متجري الأنيق</span>
                                                </div>
                                                {/* Left: Cart & Search */}
                                                <div className="text-gray-600 flex items-center gap-2">
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                    </svg>
                                                </div>
                                            </>
                                        )}

                                        {/* Layout: Minimal (Clean Soft Light Mode) */}
                                        {data.header_layout === 'Minimal' && (
                                            <>
                                                {/* Right: Compact Brand Pill */}
                                                <div className="flex items-center gap-1.5 px-2 py-0.5 bg-gray-50 rounded-full border border-gray-100">
                                                    <span className="w-3.5 h-3.5 rounded-full bg-gray-900 text-white flex items-center justify-center text-[8px] font-black">F</span>
                                                    <span className="text-[10px] font-bold text-gray-800">متجري</span>
                                                </div>
                                                {/* Left: Soft Light Buttons */}
                                                <div className="flex items-center gap-1.5">
                                                    <div className="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 text-[9px]">
                                                        🛒
                                                    </div>
                                                    <div className="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 text-[9px]">
                                                        ☰
                                                    </div>
                                                </div>
                                            </>
                                        )}

                                    </div>

                                    {/* SIMULATED BODY SCROLL CONTAINER */}
                                    <div className="flex-1 p-2.5 space-y-4">
                                        
                                        {/* A. BANNERS PREVIEW */}
                                        {data.banner_layout === 'Slider' && (
                                            <div 
                                                className="relative overflow-hidden aspect-[2.2/1] bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-100 flex items-center justify-center transition-all duration-300"
                                                style={{ borderRadius: data.border_radius || '8px' }}
                                            >
                                                <div className="absolute inset-0 bg-black/5 flex flex-col justify-end p-2.5 text-right">
                                                    <span className="text-[7px] font-bold text-white bg-red-500 px-1 py-0.5 rounded w-fit mb-1">عرض خاص 🔥</span>
                                                    <h5 className="text-[10px] font-black text-white leading-tight">خصومات الصيف الكبرى تصل إلى 50%</h5>
                                                </div>
                                                {/* Dots indicator */}
                                                <div className="absolute bottom-1 right-1/2 translate-x-1/2 flex gap-1">
                                                    <span className="w-1.5 h-1.5 rounded-full bg-white" />
                                                    <span className="w-1.5 h-1.5 rounded-full bg-white/40" />
                                                    <span className="w-1.5 h-1.5 rounded-full bg-white/40" />
                                                </div>
                                            </div>
                                        )}

                                        {data.banner_layout === 'Grid' && (
                                            <div className="grid grid-cols-3 gap-1.5">
                                                <div 
                                                    className="col-span-2 overflow-hidden aspect-[1.3/1] bg-indigo-100 flex flex-col justify-end p-2 text-right relative transition-all duration-300"
                                                    style={{ borderRadius: data.border_radius || '8px' }}
                                                >
                                                    <div className="absolute inset-0 bg-black/10" />
                                                    <h5 className="text-[8px] font-black text-white leading-tight z-10">جديد الأزياء الرجالية</h5>
                                                </div>
                                                <div className="col-span-1 flex flex-col gap-1.5">
                                                    <div 
                                                        className="flex-1 bg-orange-100 flex items-center justify-center p-1 text-center font-bold text-[7px] text-orange-700 transition-all duration-300"
                                                        style={{ borderRadius: data.border_radius || '8px' }}
                                                    >نسائي</div>
                                                    <div 
                                                        className="flex-1 bg-teal-100 flex items-center justify-center p-1 text-center font-bold text-[7px] text-teal-700 transition-all duration-300"
                                                        style={{ borderRadius: data.border_radius || '8px' }}
                                                    >أطفال</div>
                                                </div>
                                            </div>
                                        )}

                                        {data.banner_layout === 'Single Banner' && (
                                            <div 
                                                className="overflow-hidden aspect-[1/1.2] bg-gradient-to-br from-indigo-950 via-gray-900 to-black flex flex-col justify-end p-4 text-right text-white relative shadow-md transition-all duration-300"
                                                style={{ borderRadius: data.border_radius || '8px' }}
                                            >
                                                <div className="absolute inset-0 bg-black/20" />
                                                <div className="z-10 space-y-1">
                                                    <span className="text-[7px] font-bold text-amber-400 uppercase tracking-widest block">مجموعة حصرية 2026</span>
                                                    <h5 className="text-[12px] font-black leading-tight">أناقة الروعة والتميز</h5>
                                                    <p className="text-[7px] text-gray-300">تشكيلة فاخرة مستوحاة من أحدث صيحات الموضة العالمية</p>
                                                </div>
                                            </div>
                                        )}

                                        {/* B. CATEGORIES */}
                                        <div className="space-y-1.5">
                                            <div className="flex justify-between items-center px-0.5">
                                                <span className="text-[9px] font-bold text-gray-800">أقسام المتجر</span>
                                                <span className="text-[7px] text-orange-500 font-bold" style={{ color: data.primary_color }}>عرض الكل</span>
                                            </div>
                                            <div className="flex gap-2.5 overflow-x-auto pb-1 no-scrollbar">
                                                {[
                                                    { name: 'أزياء', icon: '👔' },
                                                    { name: 'أحذية', icon: '👟' },
                                                    { name: 'حقائب', icon: '👜' },
                                                    { name: 'ساعات', icon: '⌚' }
                                                ].map((cat, i) => (
                                                    <div key={i} className="flex flex-col items-center gap-1 flex-shrink-0">
                                                        <div 
                                                            className="w-9 h-9 bg-white border flex items-center justify-center text-sm shadow-sm transition-all duration-300"
                                                            style={{ 
                                                                borderRadius: data.border_radius || '8px',
                                                                borderColor: i === 0 ? data.primary_color : 'rgba(0,0,0,0.06)',
                                                                backgroundColor: i === 0 ? `${data.primary_color}10` : '#FFFFFF'
                                                            }}
                                                        >
                                                            {cat.icon}
                                                        </div>
                                                        <span className="text-[8px] font-bold text-gray-600">{cat.name}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* C. PRODUCTS GRID */}
                                        <div className="space-y-1.5">
                                            <span className="text-[9px] font-bold text-gray-800 px-0.5">أحدث المنتجات</span>
                                            <div className="grid grid-cols-2 gap-2">
                                                
                                                {/* Product 1 */}
                                                <div 
                                                    className="bg-white border border-gray-100 overflow-hidden shadow-sm flex flex-col justify-between transition-all duration-300"
                                                    style={{ borderRadius: data.border_radius || '8px' }}
                                                >
                                                    <div className="aspect-[1.1/1] bg-gray-50 flex items-center justify-center relative text-lg">
                                                        👕
                                                        <span className="absolute top-1 right-1 px-1 py-0.5 bg-red-500 text-white text-[6px] font-bold" style={{ borderRadius: data.border_radius || '4px' }}>15%-</span>
                                                    </div>
                                                    <div className="p-2 space-y-1">
                                                        <span className="text-[6px] text-gray-400 block">ملابس رجالية</span>
                                                        <h6 className="text-[8px] font-bold text-gray-800 truncate">قميص قطن فاخر ملون</h6>
                                                        <div className="flex items-center gap-1">
                                                            <span className="text-[8px] font-bold text-orange-600" style={{ color: data.primary_color }}>120 ج.م</span>
                                                            <span className="text-[6px] text-gray-400 line-through">140 ج.م</span>
                                                        </div>
                                                        <button 
                                                            type="button" 
                                                            className="w-full py-1 text-[8px] font-bold text-white transition-all text-center shadow-sm cursor-pointer"
                                                            style={{ 
                                                                backgroundColor: data.primary_color,
                                                                borderRadius: data.border_radius || '8px'
                                                            }}
                                                        >
                                                            أضف إلى السلة
                                                        </button>
                                                    </div>
                                                </div>

                                                {/* Product 2 */}
                                                <div 
                                                    className="bg-white border border-gray-100 overflow-hidden shadow-sm flex flex-col justify-between transition-all duration-300"
                                                    style={{ borderRadius: data.border_radius || '8px' }}
                                                >
                                                    <div className="aspect-[1.1/1] bg-gray-50 flex items-center justify-center relative text-lg">
                                                        👟
                                                    </div>
                                                    <div className="p-2 space-y-1">
                                                        <span className="text-[6px] text-gray-400 block">أحذية رياضية</span>
                                                        <h6 className="text-[8px] font-bold text-gray-800 truncate">حذاء رياضي مريح للجري</h6>
                                                        <div className="flex items-center gap-1">
                                                            <span className="text-[8px] font-bold text-orange-600" style={{ color: data.primary_color }}>350 ج.م</span>
                                                        </div>
                                                        <button 
                                                            type="button" 
                                                            className="w-full py-1 text-[8px] font-bold text-white transition-all text-center shadow-sm cursor-pointer"
                                                            style={{ 
                                                                backgroundColor: data.primary_color,
                                                                borderRadius: data.border_radius || '8px'
                                                            }}
                                                        >
                                                            أضف إلى السلة
                                                        </button>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                    </div>

                                    {/* SIMULATED BOTTOM NAVIGATION BAR */}
                                    <div 
                                        className="py-2.5 px-4 shadow-inner flex items-center justify-between text-white transition-all duration-300"
                                        style={{ backgroundColor: data.secondary_color }}
                                    >
                                        <div className="flex flex-col items-center gap-0.5 cursor-pointer">
                                            <span className="text-[9px] text-white">🏠</span>
                                            <span className="text-[7px] text-white font-medium">الرئيسية</span>
                                        </div>
                                        <div className="flex flex-col items-center gap-0.5 cursor-pointer opacity-70">
                                            <span className="text-[9px] text-white">🔍</span>
                                            <span className="text-[7px] text-white font-medium">البحث</span>
                                        </div>
                                        <div className="flex flex-col items-center gap-0.5 cursor-pointer opacity-70">
                                            <span className="text-[9px] text-white">🛒</span>
                                            <span className="text-[7px] text-white font-medium">السلة</span>
                                        </div>
                                        <div className="flex flex-col items-center gap-0.5 cursor-pointer opacity-70">
                                            <span className="text-[9px] text-white">📞</span>
                                            <span className="text-[7px] text-white font-medium">اتصل بنا</span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            
                        </div>
                    </div>

                </div>
            </div>
        </MerchantLayout>
    );
}
