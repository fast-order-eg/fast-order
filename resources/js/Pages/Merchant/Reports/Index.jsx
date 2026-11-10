import React, { useState, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

// Format currency without decimals, English numbers
const formatEGP = (amount) => {
    const val = Math.round(Number(amount) || 0);
    return new Intl.NumberFormat('en-US').format(val) + ' ج.م';
};

// ========================================================
// مكون كرت الإحصائيات المالية
// ========================================================
function MetricCard({ title, value, subtitle, icon, color = 'indigo' }) {
    const colorClasses = {
        emerald: 'bg-emerald-50/70 border-emerald-200 text-emerald-950 icon-emerald',
        rose: 'bg-rose-50/70 border-rose-200 text-rose-950 icon-rose',
        amber: 'bg-amber-50/70 border-amber-200 text-amber-950 icon-amber',
        indigo: 'bg-indigo-50/70 border-indigo-200 text-indigo-950 icon-indigo',
        blue: 'bg-blue-50/70 border-blue-200 text-blue-950 icon-blue',
        purple: 'bg-purple-50/70 border-purple-200 text-purple-950 icon-purple',
    };

    const iconBg = {
        emerald: 'bg-emerald-500 text-white',
        rose: 'bg-rose-500 text-white',
        amber: 'bg-amber-500 text-white',
        indigo: 'bg-indigo-600 text-white',
        blue: 'bg-blue-500 text-white',
        purple: 'bg-purple-600 text-white',
    };

    return (
        <div className={`p-5 rounded-2xl border shadow-xs transition-all duration-300 hover:shadow-md bg-white ${colorClasses[color] || ''}`}>
            <div className="flex items-start justify-between">
                <div>
                    <span className="text-xs font-semibold text-gray-500 block mb-1">{title}</span>
                    <h3 className="text-2xl font-black text-gray-900 tracking-tight">{value}</h3>
                    {subtitle && <p className="text-xs text-gray-500 mt-1.5">{subtitle}</p>}
                </div>
                <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 ${iconBg[color] || 'bg-gray-800 text-white'}`}>
                    {icon}
                </div>
            </div>
        </div>
    );
}

// ========================================================
// الرسم البياني التفاعلي لصافي الربح والمبيعات
// ========================================================
function ProfitChart({ labels, sales, profit, periodLabel }) {
    const [chartMode, setChartMode] = useState('both'); // both, sales, profit
    const [hoveredIndex, setHoveredIndex] = useState(null);

    const width = 800;
    const height = 300;
    const padding = 45;

    const maxSales = useMemo(() => Math.max(...sales, ...profit, 100), [sales, profit]);

    const salesPoints = useMemo(() => {
        if (!labels.length) return [];
        return sales.map((val, idx) => {
            const x = padding + idx * ((width - 2 * padding) / Math.max(1, labels.length - 1));
            const y = height - padding - (val / maxSales) * (height - 2 * padding);
            return { x, y, val, label: labels[idx] };
        });
    }, [sales, maxSales, labels]);

    const profitPoints = useMemo(() => {
        if (!labels.length) return [];
        return profit.map((val, idx) => {
            const x = padding + idx * ((width - 2 * padding) / Math.max(1, labels.length - 1));
            const y = height - padding - (val / maxSales) * (height - 2 * padding);
            return { x, y, val, label: labels[idx] };
        });
    }, [profit, maxSales, labels]);

    const salesPath = useMemo(() => {
        if (!salesPoints.length) return '';
        return salesPoints.reduce((acc, p, i) => `${acc} ${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`, '');
    }, [salesPoints]);

    const profitPath = useMemo(() => {
        if (!profitPoints.length) return '';
        return profitPoints.reduce((acc, p, i) => `${acc} ${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`, '');
    }, [profitPoints]);

    return (
        <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 pb-4">
                <div>
                    <h3 className="font-bold text-gray-900 text-lg flex items-center gap-2">
                        <span>📈 تطور المبيعات وصافي الربح اليومي</span>
                    </h3>
                    <p className="text-xs text-gray-500 mt-0.5">{periodLabel}</p>
                </div>

                <div className="flex items-center gap-1.5 bg-gray-100 p-1 rounded-xl text-xs font-semibold">
                    <button
                        type="button"
                        onClick={() => setChartMode('both')}
                        className={`px-3 py-1.5 rounded-lg transition-all ${chartMode === 'both' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900'}`}
                    >
                        الكل
                    </button>
                    <button
                        type="button"
                        onClick={() => setChartMode('sales')}
                        className={`px-3 py-1.5 rounded-lg transition-all ${chartMode === 'sales' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-500 hover:text-gray-900'}`}
                    >
                        المبيعات 💵
                    </button>
                    <button
                        type="button"
                        onClick={() => setChartMode('profit')}
                        className={`px-3 py-1.5 rounded-lg transition-all ${chartMode === 'profit' ? 'bg-emerald-600 text-white shadow-xs' : 'text-gray-500 hover:text-gray-900'}`}
                    >
                        صافي الربح 🏆
                    </button>
                </div>
            </div>

            {/* SVG Chart */}
            <div className="relative overflow-x-auto">
                <svg viewBox={`0 0 ${width} ${height}`} className="w-full h-64 overflow-visible">
                    {/* Grid lines */}
                    {[0, 0.25, 0.5, 0.75, 1].map((pct, idx) => {
                        const y = height - padding - pct * (height - 2 * padding);
                        return (
                            <g key={idx}>
                                <line x1={padding} y1={y} x2={width - padding} y2={y} stroke="#f3f4f6" strokeDasharray="3 3" />
                                <text x={width - padding + 5} y={y + 4} fill="#9ca3af" fontSize="10" textAnchor="start">
                                    {formatEGP(pct * maxSales)}
                                </text>
                            </g>
                        );
                    })}

                    {/* Sales Line */}
                    {(chartMode === 'both' || chartMode === 'sales') && salesPath && (
                        <path d={salesPath} fill="none" stroke="#6366f1" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
                    )}

                    {/* Profit Line */}
                    {(chartMode === 'both' || chartMode === 'profit') && profitPath && (
                        <path d={profitPath} fill="none" stroke="#10b981" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                    )}

                    {/* Points & Hover Interaction */}
                    {labels.map((lbl, idx) => {
                        const sp = salesPoints[idx];
                        const pp = profitPoints[idx];
                        if (!sp || !pp) return null;
                        const isHovered = hoveredIndex === idx;

                        return (
                            <g key={idx} onMouseEnter={() => setHoveredIndex(idx)} onMouseLeave={() => setHoveredIndex(null)} className="cursor-pointer">
                                {/* Hover guide line */}
                                {isHovered && (
                                    <line x1={sp.x} y1={padding} x2={sp.x} y2={height - padding} stroke="#9ca3af" strokeDasharray="2 2" strokeWidth="1" />
                                )}

                                {(chartMode === 'both' || chartMode === 'sales') && (
                                    <circle cx={sp.x} cy={sp.y} r={isHovered ? 6 : 3} fill="#6366f1" stroke="#ffffff" strokeWidth="2" />
                                )}

                                {(chartMode === 'both' || chartMode === 'profit') && (
                                    <circle cx={pp.x} cy={pp.y} r={isHovered ? 7 : 3.5} fill="#10b981" stroke="#ffffff" strokeWidth="2" />
                                )}

                                {/* X-axis label */}
                                {(idx % Math.ceil(labels.length / 10) === 0 || idx === labels.length - 1) && (
                                    <text x={sp.x} y={height - padding + 18} fill="#6b7280" fontSize="10" textAnchor="middle font-mono">
                                        {lbl}
                                    </text>
                                )}
                            </g>
                        );
                    })}
                </svg>

                {/* Tooltip on Hover */}
                {hoveredIndex !== null && salesPoints[hoveredIndex] && profitPoints[hoveredIndex] && (
                    <div
                        className="absolute top-2 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-4 py-2 rounded-xl text-xs shadow-lg space-y-1 pointer-events-none"
                    >
                        <div className="font-bold border-b border-gray-700 pb-1 text-center">
                            يوم {labels[hoveredIndex]}
                        </div>
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-indigo-300">المبيعات:</span>
                            <span className="font-bold">{formatEGP(salesPoints[hoveredIndex].val)}</span>
                        </div>
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-emerald-300 font-bold">صافي الربح:</span>
                            <span className="font-bold text-emerald-400">{formatEGP(profitPoints[hoveredIndex].val)}</span>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

// ========================================================
// الصفحة الرئيسية للتقارير وصافي الربح
// ========================================================
export default function ReportsIndex({ chart, profitMetrics, topProducts, governorates, filters }) {
    const [period, setPeriod] = useState(filters.period || 'this_month');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    // Manual expenses state
    const [adSpend, setAdSpend] = useState(profitMetrics.ad_spend || 0);
    const [otherExpenses, setOtherExpenses] = useState(profitMetrics.other_expenses || 0);
    const [isSavingExpenses, setIsSavingExpenses] = useState(false);

    const handlePeriodChange = (newPeriod) => {
        setPeriod(newPeriod);
        router.get('/admin/reports', {
            period: newPeriod,
            ad_spend: adSpend,
            other_expenses: otherExpenses,
        }, { preserveState: true });
    };

    const handleCustomFilter = (e) => {
        e.preventDefault();
        router.get('/admin/reports', {
            period: 'custom',
            date_from: dateFrom,
            date_to: dateTo,
            ad_spend: adSpend,
            other_expenses: otherExpenses,
        }, { preserveState: true });
    };

    const handleSaveExpenses = (e) => {
        e.preventDefault();
        setIsSavingExpenses(true);

        const periodKey = period === 'custom'
            ? `custom_${dateFrom}_${dateTo}`
            : (period === 'last_month' ? 'last_month' : (period === 'all_time' ? 'all_time' : 'this_month'));

        router.post('/admin/reports/save-expenses', {
            period_key: periodKey,
            ad_spend: adSpend,
            other_expenses: otherExpenses,
        }, {
            preserveScroll: true,
            onFinish: () => setIsSavingExpenses(false),
        });
    };

    return (
        <MerchantLayout>
            <Head title="التقارير وصافي الربح" />

            <div className="space-y-6 text-right" dir="rtl">
                {/* Header & Period Switcher */}
                <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 bg-emerald-50 text-emerald-600 rounded-xl text-xl">
                                💰
                            </span>
                            <h1 className="text-2xl font-black text-gray-900">التقارير وصافي الربح الحقيقي</h1>
                        </div>
                        <p className="text-gray-500 text-sm mt-1">
                            احسب أرباحك الحقيقية بدقة بعد خصم تكلفة البضاعة المباعة (COGS)، مصاريف الشحن، المرتجعات، وصرف الإعلانات.
                        </p>
                    </div>

                    {/* Period Tabs */}
                    <div className="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            onClick={() => handlePeriodChange('this_month')}
                            className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all ${period === 'this_month' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
                        >
                            هذا الشهر
                        </button>
                        <button
                            type="button"
                            onClick={() => handlePeriodChange('last_month')}
                            className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all ${period === 'last_month' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
                        >
                            الشهر الماضي
                        </button>
                        <button
                            type="button"
                            onClick={() => handlePeriodChange('all_time')}
                            className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all ${period === 'all_time' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
                        >
                            كل الأوقات
                        </button>
                    </div>
                </div>

                {/* Custom Date Range (collapsible) */}
                <div className="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-wrap items-center justify-between gap-3 text-xs">
                    <form onSubmit={handleCustomFilter} className="flex flex-wrap items-center gap-2">
                        <span className="font-bold text-gray-700">تحديد فترة مخصصة:</span>
                        <input
                            type="date"
                            value={dateFrom}
                            onChange={(e) => setDateFrom(e.target.value)}
                            className="px-2.5 py-1.5 border rounded-lg text-xs"
                        />
                        <span className="text-gray-400">إلى</span>
                        <input
                            type="date"
                            value={dateTo}
                            onChange={(e) => setDateTo(e.target.value)}
                            className="px-2.5 py-1.5 border rounded-lg text-xs"
                        />
                        <button type="submit" className="px-3 py-1.5 bg-gray-900 text-white rounded-lg font-bold hover:bg-black transition">
                            تطبيق
                        </button>
                    </form>

                    <div className="text-gray-500 font-medium">
                        الفترة المعروضة: <span className="font-bold text-gray-900">{chart.period_label}</span>
                    </div>
                </div>

                {/* ========================================================
                    HERO: كارت صافي الربح الحقيقي
                   ======================================================== */}
                <div className="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 text-white p-6 md:p-8 rounded-3xl shadow-lg">
                    <div className="absolute top-0 right-0 -mt-10 -mr-10 w-60 h-60 bg-white/10 rounded-full blur-2xl pointer-events-none" />
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div className="space-y-2">
                            <div className="flex items-center gap-2">
                                <span className="px-2.5 py-1 rounded-full bg-white/20 text-white text-xs font-bold backdrop-blur-xs">
                                    🏆 صافي الربح الفعلي المحقق
                                </span>
                                <span className="text-emerald-200 text-xs font-semibold">
                                    هامش ربح: {profitMetrics.net_margin}%
                                </span>
                            </div>
                            <h2 className="text-4xl md:text-5xl font-black tracking-tight">
                                {formatEGP(profitMetrics.realized_net_profit)}
                            </h2>
                            <p className="text-emerald-100 text-xs md:text-sm max-w-xl leading-relaxed">
                                صافي الربح الفعلي بعد خصم تكلفة المنتجات المباعة (COGS) + تكاليف الشحن والمرتجعات + صرف الإعلانات والمصروفات.
                            </p>
                        </div>

                        <div className="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-2xl space-y-2 text-xs shrink-0 md:min-w-64">
                            <div className="flex justify-between items-center text-emerald-100">
                                <span>المبيعات المحصلة (المسلمة):</span>
                                <span className="font-bold text-white text-sm">{formatEGP(profitMetrics.delivered_revenue)}</span>
                            </div>
                            <div className="flex justify-between items-center text-emerald-100">
                                <span>مبيعات قيد التوصيل:</span>
                                <span className="font-bold text-white text-sm">{formatEGP(profitMetrics.in_transit_revenue)}</span>
                            </div>
                            <div className="pt-2 border-t border-white/15 flex justify-between items-center">
                                <span className="font-bold text-emerald-200">الربح المتوقع الإجمالي:</span>
                                <span className="font-black text-amber-300 text-base">{formatEGP(profitMetrics.projected_net_profit)}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ========================================================
                    معادلة تفكيك الأرباح والتكاليف (Profit Equation Cards)
                   ======================================================== */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <MetricCard
                        title="1. إجمالي المبيعات المحصلة"
                        value={formatEGP(profitMetrics.delivered_revenue)}
                        subtitle={`${profitMetrics.delivered_orders} طلب تم تسليمه بنجاح`}
                        icon="💵"
                        color="indigo"
                    />

                    <MetricCard
                        title="2. تكلفة البضاعة (COGS)"
                        value={`- ${formatEGP(profitMetrics.delivered_cogs)}`}
                        subtitle="تكلفة شراء وتصنيع المنتجات"
                        icon="📦"
                        color="amber"
                    />

                    <MetricCard
                        title="3. الشحن وخسائر المرتجع"
                        value={`- ${formatEGP(profitMetrics.total_shipping_cost)}`}
                        subtitle={`شحن: ${formatEGP(profitMetrics.delivered_shipping_fees)} | مرتجع: ${formatEGP(profitMetrics.return_shipping_fees)}`}
                        icon="🚚"
                        color="rose"
                    />

                    <MetricCard
                        title="4. صرف الإعلانات (مدخل)"
                        value={`- ${formatEGP(profitMetrics.ad_spend)}`}
                        subtitle="حملات فيسبوك / تيك توك / جوجل"
                        icon="📢"
                        color="purple"
                    />

                    <MetricCard
                        title="5. مصروفات أخرى (مدخل)"
                        value={`- ${formatEGP(profitMetrics.other_expenses)}`}
                        subtitle="تغليف، رواتب، مصاريف تشغيل"
                        icon="💼"
                        color="blue"
                    />
                </div>

                {/* ========================================================
                    ودجت إدخال صرف الإعلانات والمصروفات فورياً
                   ======================================================== */}
                <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <form onSubmit={handleSaveExpenses} className="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
                        <div className="space-y-1">
                            <h3 className="font-bold text-gray-900 text-base flex items-center gap-2">
                                <span>🎯 تسجيل صرف الإعلانات والمصروفات التشغيلية للفترة الحالية</span>
                            </h3>
                            <p className="text-xs text-gray-500">
                                أدخل ما تم صرفه على الإعلانات أو مصاريف التغليف لحساب صافي الربح الحقيقي بدون الحاجة لأي ربط خارجي.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 mb-1">صرف الإعلانات (ج.م):</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="10"
                                    value={adSpend}
                                    onChange={(e) => setAdSpend(e.target.value)}
                                    placeholder="مثال: 5000"
                                    className="w-36 px-3 py-2 border rounded-xl text-sm font-mono text-left dir-ltr"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 mb-1">مصروفات أخرى (ج.م):</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="10"
                                    value={otherExpenses}
                                    onChange={(e) => setOtherExpenses(e.target.value)}
                                    placeholder="مثال: 1200"
                                    className="w-36 px-3 py-2 border rounded-xl text-sm font-mono text-left dir-ltr"
                                />
                            </div>

                            <button
                                type="submit"
                                disabled={isSavingExpenses}
                                className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs disabled:opacity-50 mt-auto"
                            >
                                {isSavingExpenses ? 'جاري الحفظ...' : 'حفظ وتحديث الأرباح ✓'}
                            </button>
                        </div>
                    </form>
                </div>

                {/* ========================================================
                    مؤشرات الأداء لعمليات الدفع عند الاستلام (COD Metrics)
                   ======================================================== */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
                        <div>
                            <span className="text-xs font-semibold text-gray-500 block mb-1">نسبة التسليم الناجح (Delivery Rate)</span>
                            <span className="text-2xl font-black text-emerald-600">{profitMetrics.delivery_rate}%</span>
                            <span className="text-[11px] text-gray-400 block mt-1">{profitMetrics.delivered_orders} من {profitMetrics.delivered_orders + profitMetrics.returned_orders} شحنة منتهية</span>
                        </div>
                        <div className="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl font-bold">
                            ✅
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
                        <div>
                            <span className="text-xs font-semibold text-gray-500 block mb-1">نسبة المرتجعات (Return Rate)</span>
                            <span className={`text-2xl font-black ${profitMetrics.return_rate > 25 ? 'text-rose-600' : 'text-amber-600'}`}>{profitMetrics.return_rate}%</span>
                            <span className="text-[11px] text-gray-400 block mt-1">{profitMetrics.returned_orders} شحنات مرتجعة</span>
                        </div>
                        <div className="w-12 h-12 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center text-xl font-bold">
                            ↩️
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
                        <div>
                            <span className="text-xs font-semibold text-gray-500 block mb-1">متوسط قيمة الطلب (AOV)</span>
                            <span className="text-2xl font-black text-gray-900">{formatEGP(profitMetrics.avg_order_value)}</span>
                            <span className="text-[11px] text-gray-400 block mt-1">لكل أوردر تم استلامه</span>
                        </div>
                        <div className="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-xl font-bold">
                            🛍️
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
                        <div>
                            <span className="text-xs font-semibold text-gray-500 block mb-1">إجمالي طلبات الفترة</span>
                            <span className="text-2xl font-black text-gray-900">{profitMetrics.total_orders}</span>
                            <span className="text-[11px] text-gray-400 block mt-1">شاملة الملغية وقيد الشحن</span>
                        </div>
                        <div className="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl font-bold">
                            📊
                        </div>
                    </div>
                </div>

                {/* Chart Section */}
                <ProfitChart
                    labels={chart.labels}
                    sales={chart.sales}
                    profit={chart.profit}
                    periodLabel={chart.period_label}
                />

                {/* Bottom Row: Top Products & Governorates */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Top Products with Profit Breakdown */}
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                        <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                            <h3 className="font-bold text-gray-900 text-base flex items-center gap-2">
                                <span>🏆 المنتجات الأكثر ربحية ومبيعاً</span>
                            </h3>
                            <span className="text-xs text-gray-400">مرتبة بصافي الربح</span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-right text-xs">
                                <thead className="bg-gray-50 text-gray-500 border-b border-gray-100">
                                    <tr>
                                        <th className="py-2.5 px-3">المنتج</th>
                                        <th className="py-2.5 px-3">الكمية</th>
                                        <th className="py-2.5 px-3">سعر التكلفة</th>
                                        <th className="py-2.5 px-3">الإيراد</th>
                                        <th className="py-2.5 px-3 text-left">صافي الربح</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {topProducts.length === 0 ? (
                                        <tr>
                                            <td colSpan="5" className="text-center py-6 text-gray-400">لا توجد مبيعات مسجلة في هذه الفترة</td>
                                        </tr>
                                    ) : (
                                        topProducts.map((p) => (
                                            <tr key={p.id} className="hover:bg-gray-50/50">
                                                <td className="py-2.5 px-3 font-semibold text-gray-900">{p.name}</td>
                                                <td className="py-2.5 px-3 font-bold">{p.qty}</td>
                                                <td className="py-2.5 px-3 font-mono text-gray-500">{formatEGP(p.cost_price)}</td>
                                                <td className="py-2.5 px-3 font-mono text-gray-700">{formatEGP(p.revenue)}</td>
                                                <td className="py-2.5 px-3 font-mono font-bold text-emerald-600 text-left">{formatEGP(p.profit)}</td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Governorates Performance */}
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                        <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                            <h3 className="font-bold text-gray-900 text-base flex items-center gap-2">
                                <span>🗺️ أداء المحافظات ونسب التسليم</span>
                            </h3>
                            <span className="text-xs text-gray-400">أعلى المحافظات طلباً</span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-right text-xs">
                                <thead className="bg-gray-50 text-gray-500 border-b border-gray-100">
                                    <tr>
                                        <th className="py-2.5 px-3">المحافظة</th>
                                        <th className="py-2.5 px-3">الطلبات</th>
                                        <th className="py-2.5 px-3">تم التسليم</th>
                                        <th className="py-2.5 px-3">مرتجع</th>
                                        <th className="py-2.5 px-3 text-left">المبيعات</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {governorates.length === 0 ? (
                                        <tr>
                                            <td colSpan="5" className="text-center py-6 text-gray-400">لا توجد بيانات للمحافظات</td>
                                        </tr>
                                    ) : (
                                        governorates.map((gov, idx) => (
                                            <tr key={idx} className="hover:bg-gray-50/50">
                                                <td className="py-2.5 px-3 font-semibold text-gray-900">{gov.governorate}</td>
                                                <td className="py-2.5 px-3 font-bold">{gov.orders_count}</td>
                                                <td className="py-2.5 px-3 text-emerald-600 font-bold">{gov.delivered_count}</td>
                                                <td className="py-2.5 px-3 text-rose-600 font-bold">{gov.returned_count}</td>
                                                <td className="py-2.5 px-3 font-mono font-bold text-gray-900 text-left">{formatEGP(gov.revenue)}</td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </MerchantLayout>
    );
}
