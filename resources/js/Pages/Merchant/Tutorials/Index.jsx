import React, { useState, useMemo, useEffect, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

// ========================================================
// Category Icon Helper
// ========================================================
const getCategoryIcon = (category) => {
    switch (category) {
        case 'الكل': return '✨';
        case 'البداية والسريعة': return '🚀';
        case 'إضافة منتجات': return '🛍️';
        case 'المنتجات والأقسام': return '📂';
        case 'إعدادات المتجر والتصميم': return '🎨';
        case 'الطلبات والمبيعات': return '📦';
        case 'عام': return '⚙️';
        default: return '🎬';
    }
};

export default function TutorialsIndex({ tutorials, categories }) {
    const [selectedCategory, setSelectedCategory] = useState('الكل');
    const [searchQuery, setSearchQuery] = useState('');
    const [activeVideo, setActiveVideo] = useState(null);

    // Filter tutorials
    const filteredTutorials = useMemo(() => {
        return tutorials.filter((item) => {
            const matchesCategory = selectedCategory === 'الكل' || item.category === selectedCategory;
            const matchesSearch = searchQuery.trim() === '' ||
                item.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
                (item.description && item.description.toLowerCase().includes(searchQuery.toLowerCase()));
            return matchesCategory && matchesSearch;
        });
    }, [tutorials, selectedCategory, searchQuery]);

    // Keyboard navigation (Escape to close, Left/Right for Next/Prev)
    const activeIndex = useMemo(() => {
        if (!activeVideo) return -1;
        return tutorials.findIndex((t) => t.id === activeVideo.id);
    }, [activeVideo, tutorials]);

    const handlePrev = useCallback(() => {
        if (activeIndex > 0) {
            setActiveVideo(tutorials[activeIndex - 1]);
        }
    }, [activeIndex, tutorials]);

    const handleNext = useCallback(() => {
        if (activeIndex < tutorials.length - 1) {
            setActiveVideo(tutorials[activeIndex + 1]);
        }
    }, [activeIndex, tutorials]);

    useEffect(() => {
        const handleKeyDown = (e) => {
            if (!activeVideo) return;
            if (e.key === 'Escape') setActiveVideo(null);
            if (e.key === 'ArrowRight') handlePrev();
            if (e.key === 'ArrowLeft') handleNext();
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [activeVideo, handlePrev, handleNext]);

    // Count per category
    const countByCategory = useMemo(() => {
        const counts = { 'الكل': tutorials.length };
        tutorials.forEach((t) => {
            counts[t.category] = (counts[t.category] || 0) + 1;
        });
        return counts;
    }, [tutorials]);

    return (
        <MerchantLayout title="الشروحات والدروس">
            <Head title="مكتبة الشروحات وفيديوهات المنصة" />

            <div className="space-y-6 py-4 max-w-[1400px] mx-auto">

                {/* ====== Modern Header Banner ====== */}
                <div className="relative rounded-3xl bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 overflow-hidden shadow-xl border border-white/10">
                    {/* Decorative blurred background shapes */}
                    <div className="absolute -top-16 -right-16 w-64 h-64 bg-orange-500/15 rounded-full blur-3xl pointer-events-none"></div>
                    <div className="absolute -bottom-16 -left-16 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-2.5 max-w-xl">
                            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/20 border border-orange-500/30 text-orange-300 text-xs font-bold">
                                <span>📱</span>
                                <span>فيديوهات ريلز تعليمية سريعة</span>
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-black text-white tracking-tight">
                                دليلك الشامل لاستخدام وتطوير متجرك
                            </h1>
                            <p className="text-xs sm:text-sm text-gray-300 leading-relaxed font-medium">
                                شروحات فيديو عملية وقصيرة خطوة بخطوة تغطي كل تفاصيل المنصة من إضافة المنتجات والأقسام حتى ربط الشحن وتتبع الإعلانات.
                            </p>
                        </div>

                        {/* Search Input */}
                        <div className="w-full md:w-80 relative">
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="ابحث عن شرح أو ميزة..."
                                className="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/15 rounded-2xl text-xs text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white/15 transition-all shadow-inner"
                            />
                            {searchQuery ? (
                                <button
                                    onClick={() => setSearchQuery('')}
                                    className="absolute left-3 top-3 text-gray-400 hover:text-white text-xs font-bold"
                                >
                                    ✕
                                </button>
                            ) : (
                                <span className="absolute left-3 top-3.5 text-gray-400 text-xs">🔍</span>
                            )}
                        </div>
                    </div>
                </div>

                {/* ====== Category Tabs Filter ====== */}
                {categories.length > 1 && (
                    <div className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                        {categories.map((cat) => {
                            const isSelected = selectedCategory === cat;
                            const count = countByCategory[cat] || 0;
                            return (
                                <button
                                    key={cat}
                                    onClick={() => setSelectedCategory(cat)}
                                    className={`px-4 py-2.5 rounded-2xl text-xs font-black whitespace-nowrap transition-all border flex items-center gap-2 shadow-sm ${
                                        isSelected
                                            ? 'bg-orange-600 text-white border-orange-600 shadow-md shadow-orange-600/30 scale-[1.02]'
                                            : 'bg-white text-gray-700 border-gray-200 hover:border-orange-300 hover:bg-orange-50/50'
                                    }`}
                                >
                                    <span>{getCategoryIcon(cat)}</span>
                                    <span>{cat}</span>
                                    <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                        isSelected ? 'bg-white/25 text-white' : 'bg-gray-100 text-gray-600'
                                    }`}>
                                        {count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                )}

                {/* ====== Tutorials Video Cards Grid (Vertical Reels Format) ====== */}
                {filteredTutorials.length > 0 ? (
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-3.5 sm:gap-5">
                        {filteredTutorials.map((tutorial) => {
                            const isReel = tutorial.is_reel !== false;
                            return (
                                <div
                                    key={tutorial.id}
                                    onClick={() => setActiveVideo(tutorial)}
                                    className="group bg-white rounded-2xl sm:rounded-3xl border border-gray-200/90 overflow-hidden shadow-sm hover:shadow-xl hover:border-orange-400/60 transition-all duration-300 cursor-pointer flex flex-col justify-between hover:-translate-y-1"
                                >
                                    <div>
                                        {/* Vertical Thumbnail Container (Taller than wide for Reels) */}
                                        <div className="aspect-[9/13] sm:aspect-[9/14] bg-slate-950 relative overflow-hidden">
                                            <img
                                                src={`https://img.youtube.com/vi/${tutorial.youtube_id}/hqdefault.jpg`}
                                                alt={tutorial.title}
                                                className="w-full h-full object-cover scale-105 group-hover:scale-110 transition-transform duration-500"
                                                loading="lazy"
                                                onError={(e) => {
                                                    e.target.src = 'https://dummyimage.com/400x600/0f172a/ffffff&text=فيديو+شرح';
                                                }}
                                            />

                                            {/* Gradient Overlay */}
                                            <div className="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent flex flex-col justify-between p-2.5 sm:p-3">
                                                {/* Top Badges */}
                                                <div className="flex items-center justify-between gap-1 w-full">
                                                    <span className="px-2 py-0.5 rounded-lg bg-black/60 backdrop-blur-md text-white text-[10px] font-bold border border-white/10 truncate max-w-[120px]">
                                                        {tutorial.category}
                                                    </span>
                                                    <span className="px-1.5 py-0.5 rounded-md bg-orange-600/90 text-white text-[9px] font-black shadow-sm">
                                                        {isReel ? '📱 ريلز' : '🎬 فيديو'}
                                                    </span>
                                                </div>

                                                {/* Center Play Button on Hover */}
                                                <div className="self-center w-11 h-11 sm:w-13 sm:h-13 rounded-2xl bg-orange-600/90 text-white flex items-center justify-center text-lg sm:text-xl shadow-lg shadow-orange-600/50 group-hover:scale-110 transition-all duration-300 backdrop-blur-sm border border-white/20">
                                                    ▶
                                                </div>

                                                {/* Bottom Duration Badge */}
                                                <div className="flex items-center justify-between text-[10px] text-gray-300 font-bold">
                                                    <span className="bg-black/60 backdrop-blur-sm px-1.5 py-0.5 rounded text-white font-mono text-[9px]">
                                                        ⏱ {tutorial.duration}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Content Below Thumbnail */}
                                        <div className="p-3 sm:p-4 space-y-1.5">
                                            <h3 className="font-extrabold text-xs sm:text-sm text-gray-900 group-hover:text-orange-600 transition-colors line-clamp-2 leading-snug">
                                                {tutorial.title}
                                            </h3>
                                            <p className="text-[11px] text-gray-500 line-clamp-2 leading-relaxed hidden sm:block">
                                                {tutorial.description || 'اضغط لمشاهدة شرح هذا الدرس بالتفصيل.'}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Action footer */}
                                    <div className="p-3 sm:p-4 pt-0">
                                        <div className="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] font-extrabold text-orange-600 group-hover:translate-x-[-2px] transition-transform">
                                            <span>تشغيل الآن</span>
                                            <span>←</span>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div className="bg-white p-12 rounded-3xl border border-gray-200 text-center space-y-3">
                        <div className="text-4xl">🎬</div>
                        <h3 className="font-bold text-base text-gray-800">لا تتوفر شروحات تطابق البحث حالياً</h3>
                        <p className="text-xs text-gray-500">جرب تصفح أقسام أخرى أو حذف كلمة البحث.</p>
                        <button
                            onClick={() => { setSelectedCategory('الكل'); setSearchQuery(''); }}
                            className="px-4 py-2 bg-orange-600 text-white rounded-xl text-xs font-bold hover:bg-orange-700 transition-colors"
                        >
                            عرض جميع الشروحات
                        </button>
                    </div>
                )}

                {/* ====== Vertical Modal Player (Optimized for Reels & Shorts) ====== */}
                {activeVideo && (
                    <div
                        className="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-2 sm:p-4 animate-in fade-in duration-200"
                        onClick={() => setActiveVideo(null)}
                    >
                        {/* Container: Vertical frame for Reels, wider for landscape */}
                        <div
                            className={`bg-slate-950 border border-white/15 rounded-3xl overflow-hidden shadow-2xl flex flex-col relative w-full ${
                                activeVideo.is_reel !== false
                                    ? 'max-w-[360px] sm:max-w-[400px] max-h-[92vh]'
                                    : 'max-w-3xl max-h-[90vh]'
                            }`}
                            onClick={(e) => e.stopPropagation()}
                        >
                            {/* Header with Title and Single Circular ✕ Close Button */}
                            <div className="px-4 py-3 border-b border-white/10 flex items-center justify-between bg-slate-900/90 backdrop-blur-md z-10 shrink-0">
                                <div className="flex-1 min-w-0 pr-1">
                                    <span className="text-[10px] text-orange-400 font-black block truncate">
                                        {activeVideo.category}
                                    </span>
                                    <h3 className="text-xs sm:text-sm font-extrabold text-white truncate" title={activeVideo.title}>
                                        {activeVideo.title}
                                    </h3>
                                </div>

                                {/* Close Button: Single clean circular icon with ONLY '✕' */}
                                <button
                                    onClick={() => setActiveVideo(null)}
                                    aria-label="إغلاق"
                                    className="w-8 h-8 rounded-full bg-white/10 hover:bg-red-600/80 text-white flex items-center justify-center text-sm font-black transition-all hover:scale-110 active:scale-95 shrink-0 ml-2"
                                >
                                    ✕
                                </button>
                            </div>

                            {/* Video Player: Vertical 9:16 for Reels, 16:9 for landscape */}
                            <div className={`w-full bg-black relative flex items-center justify-center ${
                                activeVideo.is_reel !== false
                                    ? 'aspect-[9/16] max-h-[68vh]'
                                    : 'aspect-video'
                            }`}>
                                <iframe
                                    src={`${activeVideo.embed_url}?autoplay=1&rel=0&modestbranding=1`}
                                    title={activeVideo.title}
                                    className="w-full h-full border-0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowFullScreen
                                ></iframe>
                            </div>

                            {/* Navigation Bar (Next / Prev buttons) */}
                            <div className="px-4 py-2.5 bg-slate-900/80 border-t border-white/10 flex items-center justify-between text-xs shrink-0">
                                <button
                                    onClick={handlePrev}
                                    disabled={activeIndex <= 0}
                                    className={`flex items-center gap-1 font-bold px-2.5 py-1.5 rounded-lg transition-colors ${
                                        activeIndex > 0
                                            ? 'text-gray-300 hover:text-white hover:bg-white/10'
                                            : 'text-gray-600 cursor-not-allowed'
                                    }`}
                                >
                                    <span>›</span>
                                    <span>السابق</span>
                                </button>

                                <span className="text-[11px] text-gray-400 font-mono">
                                    {activeIndex + 1} / {tutorials.length}
                                </span>

                                <button
                                    onClick={handleNext}
                                    disabled={activeIndex >= tutorials.length - 1}
                                    className={`flex items-center gap-1 font-bold px-2.5 py-1.5 rounded-lg transition-colors ${
                                        activeIndex < tutorials.length - 1
                                            ? 'text-orange-400 hover:text-orange-300 hover:bg-orange-500/10'
                                            : 'text-gray-600 cursor-not-allowed'
                                    }`}
                                >
                                    <span>التالي</span>
                                    <span>‹</span>
                                </button>
                            </div>

                            {/* Description Footer (Scrollable if needed) */}
                            {activeVideo.description && (
                                <div className="p-3.5 bg-slate-950 border-t border-white/5 overflow-y-auto max-h-24 shrink-0">
                                    <p className="text-[11px] text-gray-300 leading-relaxed">
                                        {activeVideo.description}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </MerchantLayout>
    );
}
