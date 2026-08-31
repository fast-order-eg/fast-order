import React, { useState } from 'react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { usePage, Link } from '@inertiajs/react';

// ========================================================
// Helpers
// ========================================================
const formatCurrency = (amount) =>
    new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(amount || 0)) + ' ج.م';

// ========================================================
// Pending Receipts Alert Banner
// ========================================================
function PendingReceiptsAlert({ count, receipts }) {
    const [expanded, setExpanded] = useState(false);

    if (!count || count === 0) return null;

    const paymentMethodLabel = (method) => {
        if (method === 'vodafone_cash') return 'فودافون كاش';
        if (method === 'instapay') return 'إنستا باي';
        return method || '—';
    };

    const typeLabel = (type) => {
        if (type === 'wallet') return 'شحن محفظة';
        return 'اشتراك';
    };

    return (
        <div className="mb-6 rounded-2xl border border-amber-200 bg-amber-50 shadow-sm overflow-hidden">
            {/* Header */}
            <div className="flex items-center justify-between px-5 py-4">
                <div className="flex items-center gap-3">
                    {/* Pulse dot */}
                    <span className="relative flex h-3 w-3">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    </span>
                    <div>
                        <p className="font-bold text-amber-800 text-sm">
                            {count === 1
                                ? 'يوجد إيصال دفع واحد قيد المراجعة'
                                : `يوجد ${count} إيصالات دفع قيد المراجعة`}
                        </p>
                        <p className="text-xs text-amber-600 mt-0.5">
                            بانتظار تأكيد الفريق — سيتم تحديث رصيد محفظتك أو اشتراكك فور الموافقة
                        </p>
                    </div>
                </div>
                <button
                    onClick={() => setExpanded(!expanded)}
                    className="flex items-center gap-1.5 text-xs font-semibold text-amber-700 hover:text-amber-900 bg-amber-100 hover:bg-amber-200 px-3 py-1.5 rounded-lg transition-colors"
                >
                    {expanded ? 'إخفاء التفاصيل ▲' : 'عرض التفاصيل ▼'}
                </button>
            </div>

            {/* Expanded Details */}
            {expanded && receipts && receipts.length > 0 && (
                <div className="border-t border-amber-200 px-5 py-4 bg-white/60">
                    <div className="overflow-x-auto">
                        <table className="w-full text-xs text-right">
                            <thead>
                                <tr className="text-amber-700 border-b border-amber-100">
                                    <th className="pb-2 font-semibold">رقم المرجع</th>
                                    <th className="pb-2 font-semibold">النوع</th>
                                    <th className="pb-2 font-semibold">المبلغ</th>
                                    <th className="pb-2 font-semibold">طريقة الدفع</th>
                                    <th className="pb-2 font-semibold">رقم التحويل</th>
                                    <th className="pb-2 font-semibold">تاريخ الإرسال</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-amber-50">
                                {receipts.map((r) => (
                                    <tr key={r.id} className="text-gray-700 hover:bg-amber-50/50 transition-colors">
                                        <td className="py-2.5 font-mono font-bold text-amber-800">#{r.reference_code}</td>
                                        <td className="py-2.5">
                                            <span className={`px-2 py-0.5 rounded-full text-[11px] font-semibold ${
                                                r.type === 'wallet'
                                                    ? 'bg-indigo-100 text-indigo-700'
                                                    : 'bg-green-100 text-green-700'
                                            }`}>
                                                {typeLabel(r.type)}
                                            </span>
                                        </td>
                                        <td className="py-2.5 font-bold text-gray-900">{formatCurrency(r.amount)}</td>
                                        <td className="py-2.5">{paymentMethodLabel(r.payment_method)}</td>
                                        <td className="py-2.5 font-mono text-gray-500">{r.payment_reference || '—'}</td>
                                        <td className="py-2.5 text-gray-500">
                                            <span title={r.created_at}>{r.created_at_human}</span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <p className="text-[11px] text-amber-600 mt-3 text-center">
                        ⏳ إيصالاتك قيد المراجعة من الفريق. للاستفسار تواصل مع الدعم الفني.
                    </p>
                </div>
            )}
        </div>
    );
}

// ========================================================
// Stat Card Component
// ========================================================
function StatCard({ title, value, subtitle, icon, color, change, changeLabel }) {
    const colors = {
        indigo:  { bg: 'bg-indigo-50',  icon: 'bg-indigo-500',  text: 'text-indigo-600', border: 'border-indigo-100' },
        green:   { bg: 'bg-green-50',   icon: 'bg-green-500',   text: 'text-green-600',  border: 'border-green-100' },
        amber:   { bg: 'bg-amber-50',   icon: 'bg-amber-500',   text: 'text-amber-600',  border: 'border-amber-100' },
        blue:    { bg: 'bg-blue-50',    icon: 'bg-blue-500',    text: 'text-blue-600',   border: 'border-blue-100' },
        rose:    { bg: 'bg-rose-50',    icon: 'bg-rose-500',    text: 'text-rose-600',   border: 'border-rose-100' },
        purple:  { bg: 'bg-purple-50',  icon: 'bg-purple-500',  text: 'text-purple-600', border: 'border-purple-100' },
        teal:    { bg: 'bg-teal-50',    icon: 'bg-teal-500',    text: 'text-teal-600',   border: 'border-teal-100' },
    };
    const c = colors[color] || colors.indigo;
    const isPositive = change >= 0;

    return (
        <div className={`${c.bg} rounded-2xl p-5 border ${c.border} shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5`}>
            <div className="flex items-start justify-between mb-4">
                <div className={`${c.icon} w-11 h-11 rounded-xl flex items-center justify-center text-white shadow-sm`}>
                    {icon}
                </div>
                {change !== undefined && (
                    <span className={`inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full ${
                        isPositive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                    }`}>
                        {isPositive ? '▲' : '▼'} {Math.abs(change)}%
                    </span>
                )}
            </div>
            <div>
                <p className={`text-2xl font-bold ${c.text} leading-none mb-1`}>{value}</p>
                <p className="text-sm font-semibold text-gray-700 mt-1">{title}</p>
                {subtitle && <p className="text-xs text-gray-500 mt-0.5">{subtitle}</p>}
                {changeLabel && <p className="text-xs text-gray-400 mt-1">{changeLabel}</p>}
            </div>
        </div>
    );
}

// ========================================================
// Status Badge
// ========================================================
function StatusBadge({ statusKey, color, text }) {
    const statusConfig = {
        pending:   { text: 'في الانتظار', color: 'bg-yellow-50 text-yellow-700 border-yellow-100' },
        confirmed: { text: 'مؤكد',        color: 'bg-blue-50 text-blue-700 border-blue-100' },
        shipped:   { text: 'في التوصيل', color: 'bg-purple-50 text-purple-700 border-purple-100' },
        delivered: { text: 'تم التسليم', color: 'bg-green-50 text-green-700 border-green-100' },
        cancelled: { text: 'ملغي',        color: 'bg-red-50 text-red-700 border-red-100' },
    };
    const conf = statusConfig[statusKey] || { text: text || statusKey, color: 'bg-gray-50 text-gray-700 border-gray-100' };

    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ${conf.color}`}>
            {text || conf.text}
        </span>
    );
}

// ========================================================
// Modern Revenue Chart
// ========================================================
function ModernRevenueChart({ labels, data }) {
    const total7Days = data ? data.reduce((acc, curr) => acc + (Number(curr) || 0), 0) : 0;
    const maxVal = data ? Math.max(...data, 1) : 1;
    const avgVal = Math.round(total7Days / (data?.length || 1));
    const [hoveredIdx, setHoveredIdx] = useState(null);

    if (!labels || !data || labels.length === 0) {
        return (
            <div className="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm h-64 flex items-center justify-center text-gray-400 text-sm">
                لا توجد بيانات إيرادات حالياً
            </div>
        );
    }

    return (
        <div className="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex flex-col justify-between space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-50 pb-3">
                <div className="flex items-center gap-2.5">
                    <div className="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-base shadow-inner">
                        📈
                    </div>
                    <div>
                        <h3 className="text-sm font-bold text-gray-900">مؤشر حركة المبيعات</h3>
                        <p className="text-[11px] text-gray-400">ملخص مبيعات الـ 7 أيام الأخيرة</p>
                    </div>
                </div>
                <div className="flex items-center gap-2 bg-indigo-50/70 border border-indigo-100/80 px-3.5 py-1.5 rounded-xl self-start sm:self-auto">
                    <span className="text-xs text-indigo-700 font-medium">إجمالي الفترة:</span>
                    <span className="text-sm font-extrabold text-indigo-900">{formatCurrency(total7Days)}</span>
                </div>
            </div>

            <div className="relative pt-6 pb-2">
                <div className="flex items-end gap-3 h-36 w-full justify-between">
                    {data.map((val, i) => {
                        const numVal = Number(val) || 0;
                        const heightPct = Math.max(Math.round((numVal / maxVal) * 100), numVal > 0 ? 14 : 8);
                        const isHovered = hoveredIdx === i;
                        return (
                            <div
                                key={i}
                                className="flex-1 flex flex-col items-center gap-2 group relative"
                                onMouseEnter={() => setHoveredIdx(i)}
                                onMouseLeave={() => setHoveredIdx(null)}
                            >
                                {isHovered && (
                                    <div className="absolute -top-11 z-20 bg-slate-900 text-white text-[11px] font-bold px-2.5 py-1 rounded-lg shadow-xl whitespace-nowrap pointer-events-none">
                                        <div className="text-slate-300 font-normal text-[9px] text-center">{labels[i]}</div>
                                        <div>{formatCurrency(numVal)}</div>
                                    </div>
                                )}
                                <div className="w-full h-full flex items-end justify-center bg-gray-50/80 rounded-xl p-1">
                                    <div
                                        className={`w-full rounded-lg transition-all duration-300 ease-out cursor-pointer ${
                                            numVal > 0
                                                ? isHovered
                                                    ? 'bg-gradient-to-t from-orange-500 via-amber-500 to-amber-400 shadow-md shadow-orange-200 scale-105'
                                                    : 'bg-gradient-to-t from-indigo-600 via-indigo-500 to-indigo-400 shadow-sm'
                                                : isHovered ? 'bg-gray-300' : 'bg-gray-200/80'
                                        }`}
                                        style={{ height: `${heightPct}%` }}
                                    />
                                </div>
                                <span className={`text-[11px] font-medium transition-colors ${isHovered ? 'text-indigo-600 font-bold' : 'text-gray-400'}`}>
                                    {labels[i]}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>

            <div className="grid grid-cols-2 gap-2 pt-2 border-t border-gray-50 text-xs">
                <div className="bg-gray-50/70 rounded-xl p-2.5 flex items-center justify-between border border-gray-100">
                    <span className="text-gray-500 text-[11px]">المتوسط اليومي:</span>
                    <span className="font-bold text-gray-800">{formatCurrency(avgVal)}</span>
                </div>
                <div className="bg-gray-50/70 rounded-xl p-2.5 flex items-center justify-between border border-gray-100">
                    <span className="text-gray-500 text-[11px]">أعلى يوم مبيعات:</span>
                    <span className="font-bold text-emerald-600">{formatCurrency(Math.max(...data, 0))}</span>
                </div>
            </div>
        </div>
    );
}

// ========================================================
// Quick Performance Report
// ========================================================
function QuickReport({ stats }) {
    const monthChange = stats.orders_change;
    const revenueChange = stats.revenue_change;

    const items = [
        {
            label: 'طلبات هذا الشهر',
            value: stats.current_month_orders?.toLocaleString('en-US') ?? '—',
            sub: `مقارنة بـ ${stats.last_month_orders ?? 0} الشهر الماضي`,
            change: monthChange,
            icon: '📦',
        },
        {
            label: 'إيرادات هذا الشهر',
            value: formatCurrency(stats.current_month_revenue),
            sub: `مقارنة بـ ${formatCurrency(stats.last_month_revenue)} الشهر الماضي`,
            change: revenueChange,
            icon: '💰',
        },
        {
            label: 'أعلى يوم مبيعات (7 أيام)',
            value: formatCurrency(stats.best_day_revenue),
            sub: stats.best_day_label ? `يوم ${stats.best_day_label}` : 'لا توجد مبيعات',
            icon: '🏆',
        },
        {
            label: 'متوسط قيمة الطلب',
            value: formatCurrency(stats.avg_order_value),
            sub: 'الطلبات المكتملة والمشحونة',
            icon: '📊',
        },
    ];

    return (
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
            <div className="flex items-center gap-2 mb-4">
                <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm shadow-sm">
                    📋
                </div>
                <div>
                    <h3 className="text-sm font-bold text-gray-800">تقرير الأداء السريع</h3>
                    <p className="text-[11px] text-gray-400">مقارنة الأداء بين الشهر الحالي والماضي</p>
                </div>
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                {items.map((item, idx) => (
                    <div key={idx} className="bg-gray-50 rounded-xl p-3.5 border border-gray-100 hover:bg-gray-100/60 transition-colors">
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-xl">{item.icon}</span>
                            {item.change !== undefined && (
                                <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ${
                                    item.change >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                }`}>
                                    {item.change >= 0 ? '▲' : '▼'} {Math.abs(item.change)}%
                                </span>
                            )}
                        </div>
                        <p className="text-base font-extrabold text-gray-900 leading-tight">{item.value}</p>
                        <p className="text-xs font-semibold text-gray-600 mt-0.5">{item.label}</p>
                        <p className="text-[10px] text-gray-400 mt-0.5 leading-tight">{item.sub}</p>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ========================================================
// Main Dashboard Page
// ========================================================
export default function Dashboard({ stats, recentOrders, chart, pendingReceiptsCount, pendingReceiptsList, subscriptionInfo }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [copied, setCopied] = useState(false);

    const hasPhone = Boolean(
        (user?.phone && String(user.phone).trim() !== '') ||
        (stats?.store_phone && String(stats.store_phone).trim() !== '')
    );

    const storeUrl = typeof window !== 'undefined' ? window.location.origin : '';

    const handleCopyStoreUrl = () => {
        if (!storeUrl) return;
        navigator.clipboard.writeText(storeUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
    };

    const isExpiredStore = subscriptionInfo?.is_expired || stats.is_active === false || stats.subscription_status === 'expired';

    return (
        <MerchantLayout title="الرئيسية - لوحة التحكم">

            {/* ====== Alert الأحمر الكبيرة: توقف المتجر لانتهاء الاشتراك فوق خالص ====== */}
            {isExpiredStore && (
                <div className="bg-gradient-to-r from-red-600 via-rose-600 to-red-700 text-white rounded-2xl p-5 mb-6 shadow-xl border-2 border-red-400">
                    <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div className="flex items-start gap-4">
                            <div className="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl flex-shrink-0 border border-white/30">
                                🚨
                            </div>
                            <div>
                                <h3 className="text-base md:text-lg font-extrabold text-white leading-tight">
                                    تنبيه هام: متجرك متوقف حالياً أمام العملاء لانتهاء مدة الاشتراك!
                                </h3>
                                <p className="text-sm text-red-100 mt-1 leading-relaxed">
                                    انتهى اشتراك متجرك في <span className="font-extrabold underline text-amber-300 mx-1">{subscriptionInfo?.plan_name || 'الباقة الحالية'}</span> 
                                    {subscriptionInfo?.subscription_ends_at && (
                                        <span> بتاريخ <span className="font-mono dir-ltr font-bold text-white px-2 py-0.5 bg-black/25 rounded mx-1">{subscriptionInfo.subscription_ends_at}</span></span>
                                    )}.
                                </p>
                                <p className="text-xs text-red-200 mt-1">
                                    لوحة التحكم الخاصة بك تعمل بكامل صلاحياتها. يرجى تجديد الاشتراك لإعادة تفعيل المتجر واستقبال طلبات العملاء فوراً.
                                </p>
                            </div>
                        </div>

                        <Link
                            href={route('merchant.subscription.index')}
                            className="w-full md:w-auto px-6 py-3 bg-amber-400 hover:bg-amber-300 text-gray-900 font-extrabold text-sm rounded-xl transition-all shadow-md hover:shadow-xl hover:scale-105 flex items-center justify-center gap-2 flex-shrink-0"
                        >
                            <span>تجديد الاشتراك الآن 🚀</span>
                        </Link>
                    </div>
                </div>
            )}

            {/* ====== Alert: حالة الباقة وتاريخ الانتهاء بالضبط ====== */}
            {!isExpiredStore && (
                <div className="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 text-white rounded-2xl p-4 mb-6 shadow-md border border-indigo-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 flex items-center justify-center font-bold text-lg flex-shrink-0">
                            ⭐
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-xs text-indigo-300 font-semibold">باقة متجرك الحالية:</span>
                                <span className="text-xs font-bold px-2.5 py-0.5 rounded-full bg-indigo-500/30 text-indigo-200 border border-indigo-400/40">
                                    {subscriptionInfo?.plan_name || 'الباقة المجانية'}
                                </span>
                            </div>
                            <p className="text-sm font-extrabold text-white mt-1">
                                {subscriptionInfo?.is_commission ? (
                                    'الاشتراك مفعّل وشغّال بكفاءة'
                                ) : subscriptionInfo?.subscription_ends_at ? (
                                    <>تاريخ انتهاء الاشتراك بالظبط: <span className="font-mono dir-ltr text-amber-300 font-bold bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20">{subscriptionInfo.subscription_ends_at}</span></>
                                ) : (
                                    'الاشتراك مفعّل وشغّال بكفاءة'
                                )}
                            </p>
                        </div>
                    </div>
                    <Link
                        href={route('merchant.subscription.index')}
                        className="text-xs font-extrabold text-white bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 rounded-xl transition-all shadow-sm hover:shadow-indigo-500/25 flex-shrink-0"
                    >
                        ترقية الباقة أو التفاصيل 🚀
                    </Link>
                </div>
            )}

            {/* ====== Alert: إيصالات الدفع المعلقة ====== */}
            <PendingReceiptsAlert count={pendingReceiptsCount} receipts={pendingReceiptsList} />

            {/* ====== Phone Alert ====== */}
            {!hasPhone && (
                <div className="bg-amber-50 border-r-4 border-amber-500 p-4 mb-6 rounded-l-lg shadow-sm">
                    <div className="flex items-center">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="mr-3">
                            <p className="text-sm text-amber-800 font-bold">تنبيه: برجاء كتابة رقم الهاتف</p>
                            <p className="text-xs text-amber-700 mt-1">
                                لم تقم بإضافة رقم الهاتف الخاص بك. يرجى إضافته مرة واحدة فقط.
                                <Link href={route('profile.edit')} className="font-bold underline mr-2 hover:text-amber-900">
                                    إضافة الآن
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* ====== Welcome Banner ====== */}
            <div className="bg-gradient-to-l from-indigo-600 to-indigo-800 rounded-2xl p-6 mb-6 text-white shadow-lg">
                <div className="flex flex-col md:flex-row items-center justify-between gap-6 w-full">
                    <div className="flex-1 w-full">
                        <h2 className="text-xl font-bold mb-1">مرحباً بك في لوحة التحكم 👋</h2>
                        <p className="text-indigo-200 text-sm">هنا ملخص أداء متجرك اليوم</p>
                    </div>

                    {/* Wallet Section */}
                    <div className="bg-white/10 backdrop-blur-sm rounded-xl p-4 min-w-[220px] border border-white/20 w-full md:w-auto">
                        <span className="block text-indigo-100 text-sm font-medium mb-1">رصيد المحفظة</span>
                        <div className="flex items-end justify-between gap-4">
                            <span className="text-2xl font-bold text-white leading-none">
                                {formatCurrency(stats.wallet_balance || 0)}
                            </span>
                            <Link
                                href={route('merchant.wallet.index')}
                                className="px-3.5 py-1.5 bg-white text-indigo-700 hover:bg-indigo-50 rounded-lg text-xs font-black transition-all shadow-sm hover:scale-105"
                            >
                                اشحن
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Store URL Bar */}
                <div className="flex flex-wrap items-center gap-3 pt-4 border-t border-white/10 mt-4">
                    <a
                        href="/shop/index.html"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-indigo-900 hover:bg-indigo-50 rounded-xl text-xs font-extrabold transition-all shadow-sm shrink-0"
                    >
                        <span>👁️</span>
                        <span>معاينة المتجر</span>
                    </a>
                    <div className="flex items-center gap-2 bg-black/20 backdrop-blur-sm rounded-xl p-1.5 pr-3 border border-white/10 text-xs text-indigo-100 flex-1 min-w-[260px]">
                        <span className="truncate flex-1 text-left font-mono text-indigo-200 select-all" dir="ltr">{storeUrl}</span>
                        <button
                            type="button"
                            onClick={handleCopyStoreUrl}
                            className={`px-3.5 py-1.5 rounded-lg font-bold text-xs transition-all shrink-0 flex items-center gap-1.5 shadow-sm ${
                                copied ? 'bg-emerald-500 text-white' : 'bg-white/20 text-white hover:bg-white/30'
                            }`}
                        >
                            <span>{copied ? '✓' : '📋'}</span>
                            <span>{copied ? 'تم النسخ!' : 'نسخ الرابط'}</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* ====== Stats Grid (6 cards) ====== */}
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                <StatCard
                    title="إجمالي الطلبات"
                    value={stats.total_orders?.toLocaleString('en-US') ?? 0}
                    subtitle={`${stats.pending_orders} في الانتظار`}
                    color="indigo"
                    change={stats.orders_change}
                    changeLabel="مقارنة بالشهر الماضي"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    }
                />
                <StatCard
                    title="إجمالي الإيرادات"
                    value={formatCurrency(stats.total_revenue)}
                    subtitle="الطلبات المكتملة والمشحونة"
                    color="green"
                    change={stats.revenue_change}
                    changeLabel="مقارنة بالشهر الماضي"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    }
                />
                <StatCard
                    title="قيد الانتظار"
                    value={stats.pending_orders?.toLocaleString('en-US') ?? 0}
                    subtitle="تحتاج متابعة فورية"
                    color="amber"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    }
                />
                <StatCard
                    title="في التوصيل"
                    value={stats.shipped_orders?.toLocaleString('en-US') ?? 0}
                    subtitle="طلبات خرجت للتوصيل"
                    color="purple"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    }
                />
                <StatCard
                    title="الطلبات الملغية"
                    value={stats.cancelled_orders?.toLocaleString('en-US') ?? 0}
                    subtitle="طلبات ملغاة"
                    color="rose"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    }
                />
                <StatCard
                    title="إجمالي المنتجات"
                    value={stats.total_products?.toLocaleString('en-US') ?? 0}
                    subtitle={`${stats.active_products} منتج متاح`}
                    color="teal"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    }
                />
            </div>

            {/* ====== Quick Performance Report ====== */}
            <QuickReport stats={stats} />

            {/* ====== Chart + Order Status Row ====== */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                {/* Order Status Mini Cards */}
                <div className="lg:col-span-1 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 gap-2.5 content-start">
                    {[
                        { label: 'في الانتظار', value: stats.pending_orders,   bg: 'bg-yellow-50',   border: 'border-yellow-100',  text: 'text-yellow-600',   sub: 'text-yellow-700' },
                        { label: 'مؤكدة',       value: stats.completed_orders, bg: 'bg-blue-50',     border: 'border-blue-100',    text: 'text-blue-600',     sub: 'text-blue-700' },
                        { label: 'في التوصيل',  value: stats.shipped_orders,   bg: 'bg-purple-50',   border: 'border-purple-100',  text: 'text-purple-600',   sub: 'text-purple-700' },
                        { label: 'تم التسليم',  value: stats.delivered_orders, bg: 'bg-emerald-50',  border: 'border-emerald-100', text: 'text-emerald-600',  sub: 'text-emerald-700' },
                        { label: 'ملغية',        value: stats.cancelled_orders, bg: 'bg-red-50',      border: 'border-red-100',      text: 'text-red-600',      sub: 'text-red-700' },
                    ].map((item, idx) => (
                        <div key={idx} className={`${item.bg} border ${item.border} rounded-2xl p-4 text-center hover:shadow-sm transition-shadow`}>
                            <p className={`text-2xl font-bold ${item.text}`}>{item.value ?? 0}</p>
                            <p className={`text-xs ${item.sub} font-medium mt-1`}>{item.label}</p>
                        </div>
                    ))}
                </div>

                {/* Revenue Chart */}
                <div className="lg:col-span-2">
                    <ModernRevenueChart labels={chart?.labels} data={chart?.data} />
                </div>
            </div>

            {/* ====== Recent Orders Table ====== */}
            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="flex items-center justify-between px-6 py-4 border-b border-gray-50">
                    <div>
                        <h3 className="text-sm font-bold text-gray-800">آخر الطلبات</h3>
                        <p className="text-[11px] text-gray-400 mt-0.5">أحدث 8 طلبات واردة على متجرك</p>
                    </div>
                    <a
                        href="/admin/orders"
                        className="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg"
                    >
                        عرض الكل ←
                    </a>
                </div>

                {recentOrders && recentOrders.length > 0 ? (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-gray-50 text-gray-500 text-xs">
                                    <th className="px-5 py-3 text-right font-semibold">الرقم المرجعي</th>
                                    <th className="px-5 py-3 text-right font-semibold">العميل</th>
                                    <th className="px-5 py-3 text-right font-semibold">الهاتف</th>
                                    <th className="px-5 py-3 text-right font-semibold">الإجمالي</th>
                                    <th className="px-5 py-3 text-right font-semibold">الحالة</th>
                                    <th className="px-5 py-3 text-right font-semibold">التاريخ</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {recentOrders.map((order) => (
                                    <tr key={order.id} className="hover:bg-gray-50/80 transition-colors group">
                                        <td className="px-5 py-4 font-mono font-bold text-gray-900">
                                            <a
                                                href={`/admin/orders/${order.id}`}
                                                className="hover:text-indigo-600 transition-colors group-hover:underline"
                                            >
                                                {order.reference_number || order.id}
                                            </a>
                                        </td>
                                        <td className="px-5 py-4 font-semibold text-gray-900">
                                            {order.customer_name}
                                        </td>
                                        <td className="px-5 py-4 text-gray-500 font-mono text-xs">
                                            {order.customer_phone ? (
                                                <a href={`tel:${order.customer_phone}`} className="hover:text-indigo-600 transition-colors">
                                                    {order.customer_phone}
                                                </a>
                                            ) : <span className="text-gray-300">—</span>}
                                        </td>
                                        <td className="px-5 py-4 font-extrabold text-indigo-600">
                                            {formatCurrency(order.total)}
                                        </td>
                                        <td className="px-5 py-4">
                                            <StatusBadge statusKey={order.status} text={order.status_text} />
                                        </td>
                                        <td className="px-5 py-4 text-xs text-gray-500 whitespace-nowrap">
                                            <div className="font-semibold text-gray-700">{order.created_at_date || order.created_at?.split(' ')[0]}</div>
                                            <div className="text-[11px] text-gray-400 font-mono mt-0.5">{order.created_at_time || order.created_at?.split(' ')[1]}</div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="text-center py-16 text-gray-400">
                        <svg className="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p className="text-sm font-medium">لا توجد طلبات حتى الآن</p>
                        <p className="text-xs mt-1">ستظهر هنا الطلبات الجديدة عند وصولها</p>
                    </div>
                )}
            </div>
        </MerchantLayout>
    );
}
