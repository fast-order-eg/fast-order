import React, { useState, useEffect } from 'react';

/**
 * Format a Date object to YYYY-MM-DD string using Cairo timezone or local
 */
const formatDateStr = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

export default function CustomDateRangeModal({
    isOpen,
    onClose,
    initialSince = '',
    initialUntil = '',
    onApply,
    loading = false,
}) {
    const todayStr = formatDateStr(new Date());

    const [since, setSince] = useState(initialSince || todayStr);
    const [until, setUntil] = useState(initialUntil || todayStr);
    const [error, setError] = useState('');

    useEffect(() => {
        if (isOpen) {
            setSince(initialSince || todayStr);
            setUntil(initialUntil || todayStr);
            setError('');
        }
    }, [isOpen, initialSince, initialUntil, todayStr]);

    // Handle ESC key to close
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && isOpen && !loading) {
                onClose();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, loading, onClose]);

    if (!isOpen) return null;

    const handleQuickRange = (days) => {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - (days - 1));
        setSince(formatDateStr(start));
        setUntil(formatDateStr(end));
        setError('');
    };

    const handleThisMonth = () => {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        setSince(formatDateStr(start));
        setUntil(formatDateStr(now));
        setError('');
    };

    const handleLastMonth = () => {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0);
        setSince(formatDateStr(start));
        setUntil(formatDateStr(end));
        setError('');
    };

    const handleSubmit = (e) => {
        e?.preventDefault();
        if (!since || !until) {
            setError('يرجى تحديد تاريخ البداية وتاريخ النهاية معاً.');
            return;
        }
        if (since > until) {
            setError('تاريخ البداية لا يمكن أن يكون بعد تاريخ النهاية.');
            return;
        }
        setError('');
        onApply(since, until);
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                onClick={() => !loading && onClose()}
            />

            {/* Modal Container */}
            <div className="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
                <div
                    className="relative w-full max-w-lg transform overflow-hidden rounded-3xl bg-white text-right shadow-2xl transition-all border border-gray-100"
                    onClick={(e) => e.stopPropagation()}
                >
                    {/* Header */}
                    <div className="relative bg-gradient-to-r from-indigo-600 via-indigo-700 to-blue-700 px-6 py-5 text-white">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center shrink-0 border border-white/20 shadow-inner">
                                    <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-base sm:text-lg font-black tracking-tight text-white">
                                        تحديد فترة زمنية مخصصة
                                    </h3>
                                    <p className="text-xs text-indigo-100 mt-0.5">
                                        اختر من تاريخ إلى تاريخ لعرض أداء الإعلانات والنتائج بدقة
                                    </p>
                                </div>
                            </div>

                            {/* Close Button */}
                            <button
                                type="button"
                                onClick={onClose}
                                disabled={loading}
                                className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors disabled:opacity-50"
                                aria-label="إغلاق"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="p-5 sm:p-6 space-y-5">
                        {/* Quick Presets Shortcuts */}
                        <div>
                            <label className="block text-xs font-bold text-gray-600 mb-2">
                                اختصارات سريعة للفترة:
                            </label>
                            <div className="flex flex-wrap gap-1.5">
                                {[
                                    { label: 'آخر 3 أيام', action: () => handleQuickRange(3) },
                                    { label: 'آخر 7 أيام', action: () => handleQuickRange(7) },
                                    { label: 'آخر 14 يوم', action: () => handleQuickRange(14) },
                                    { label: 'آخر 30 يوم', action: () => handleQuickRange(30) },
                                    { label: 'الشهر الحالي', action: handleThisMonth },
                                    { label: 'الشهر السابق', action: handleLastMonth },
                                ].map((item, idx) => (
                                    <button
                                        key={idx}
                                        type="button"
                                        onClick={item.action}
                                        disabled={loading}
                                        className="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-indigo-50 hover:text-indigo-700 text-gray-700 border border-gray-200/70 transition-all"
                                    >
                                        {item.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Date Inputs Grid */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 bg-gray-50/80 p-4 rounded-2xl border border-gray-100">
                            {/* From Date */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1.5">
                                    <span>من تاريخ (البداية):</span>
                                </label>
                                <div className="relative">
                                    <input
                                        type="date"
                                        value={since}
                                        max={todayStr}
                                        onChange={(e) => {
                                            setSince(e.target.value);
                                            setError('');
                                        }}
                                        disabled={loading}
                                        className="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm font-bold text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm"
                                        required
                                    />
                                </div>
                            </div>

                            {/* To Date */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1.5">
                                    <span>إلى تاريخ (النهاية):</span>
                                </label>
                                <div className="relative">
                                    <input
                                        type="date"
                                        value={until}
                                        max={todayStr}
                                        onChange={(e) => {
                                            setUntil(e.target.value);
                                            setError('');
                                        }}
                                        disabled={loading}
                                        className="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm font-bold text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Error Message */}
                        {error && (
                            <div className="bg-red-50 border border-red-200 text-red-700 text-xs px-3.5 py-2.5 rounded-xl font-bold flex items-center gap-2">
                                <svg className="w-4 h-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{error}</span>
                            </div>
                        )}

                        {/* Selected Range Display Preview */}
                        {since && until && since <= until && (
                            <div className="text-center bg-indigo-50/70 border border-indigo-100 rounded-xl py-2 px-3 text-xs text-indigo-900 font-bold flex items-center justify-center gap-2">
                                <span>الفترة المحددة:</span>
                                <span className="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700 text-[11px] ltr:font-sans">
                                    {since}
                                </span>
                                <span>إلى</span>
                                <span className="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700 text-[11px] ltr:font-sans">
                                    {until}
                                </span>
                            </div>
                        )}

                        {/* Actions */}
                        <div className="pt-2 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                onClick={onClose}
                                disabled={loading}
                                className="px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-50"
                            >
                                إلغاء
                            </button>
                            <button
                                type="submit"
                                disabled={loading}
                                className="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white text-xs font-bold shadow-md shadow-indigo-500/25 transition-all disabled:opacity-50"
                            >
                                {loading ? (
                                    <>
                                        <svg className="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span>جاري جلب التقرير...</span>
                                    </>
                                ) : (
                                    <>
                                        <span>تطبيق وعرض التقرير</span>
                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
