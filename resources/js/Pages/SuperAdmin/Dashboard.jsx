import React, { useState } from 'react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import { Head, Link } from '@inertiajs/react';

const formatCurrency = (amount) =>
    new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(amount || 0)) + ' ج.م';
const formatNumber = (num) =>
    new Intl.NumberFormat('en-US').format(num || 0);

// ========================================================
// Alerts Components
// ========================================================
function PendingReceiptsAlert({ count, receipts }) {
    const [expanded, setExpanded] = useState(true);

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
        <div className="mb-6 rounded-2xl border border-red-200 bg-red-50 shadow-sm overflow-hidden">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 sm:px-6 sm:py-5 gap-3">
                <div className="flex items-center gap-3">
                    <span className="relative flex h-4 w-4 shrink-0">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-4 w-4 bg-red-500"></span>
                    </span>
                    <div>
                        <p className="font-bold text-red-900 text-base sm:text-lg">
                            عاجل: {count === 1 ? 'يوجد إيصال دفع واحد قيد المراجعة' : `يوجد ${count} إيصالات دفع قيد المراجعة`}
                        </p>
                        <p className="text-xs sm:text-sm text-red-700 mt-0.5">
                            يرجى مراجعة هذه الطلبات في أسرع وقت لتفعيل خدمات التجار لتجنب توقف متاجرهم.
                        </p>
                    </div>
                </div>
                <div className="flex items-center gap-2 w-full sm:w-auto justify-end shrink-0">
                    <Link
                        href={route('superadmin.subscriptions.receipts')}
                        className="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 sm:px-4 rounded-xl transition-colors shadow-sm text-xs sm:text-sm"
                    >
                        المراجعة الآن
                    </Link>
                    <button
                        onClick={() => setExpanded(!expanded)}
                        className="flex items-center gap-1 text-xs sm:text-sm font-semibold text-red-800 hover:text-red-900 bg-red-100 hover:bg-red-200 px-2.5 sm:px-3 py-2 rounded-xl transition-colors"
                    >
                        {expanded ? 'إخفاء ▲' : 'عرض التفاصيل ▼'}
                    </button>
                </div>
            </div>

            {expanded && receipts && receipts.length > 0 && (
                <div className="border-t border-red-200 bg-white overflow-x-auto">
                    <table className="w-full text-xs sm:text-sm text-right min-w-[550px]">
                        <thead>
                            <tr className="bg-red-50/50 text-red-800 border-b border-red-100">
                                <th className="px-4 sm:px-6 py-3 font-bold">التاجر</th>
                                <th className="px-4 sm:px-6 py-3 font-bold">رقم المرجع</th>
                                <th className="px-4 sm:px-6 py-3 font-bold">النوع</th>
                                <th className="px-4 sm:px-6 py-3 font-bold">المبلغ</th>
                                <th className="px-4 sm:px-6 py-3 font-bold">طريقة الدفع / رقم التحويل</th>
                                <th className="px-4 sm:px-6 py-3 font-bold">تاريخ الإرسال</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-red-50">
                            {receipts.map((r) => (
                                <tr key={r.id} className="text-gray-800 hover:bg-red-50/30 transition-colors">
                                    <td className="px-4 sm:px-6 py-4">
                                        <div className="font-bold text-gray-900">{r.tenant_name}</div>
                                        {r.tenant_phone && <div className="text-xs text-gray-500 font-mono mt-0.5">{r.tenant_phone}</div>}
                                    </td>
                                    <td className="px-4 sm:px-6 py-4 font-mono font-bold text-indigo-700">#{r.reference_code}</td>
                                    <td className="px-4 sm:px-6 py-4">
                                        <span className={`px-2.5 py-1 rounded-lg text-[11px] font-bold ${
                                            r.type === 'wallet' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800'
                                        }`}>
                                            {typeLabel(r.type)}
                                        </span>
                                    </td>
                                    <td className="px-4 sm:px-6 py-4 font-extrabold text-gray-900">{formatCurrency(r.amount)}</td>
                                    <td className="px-4 sm:px-6 py-4">
                                        <div className="font-semibold text-xs">{paymentMethodLabel(r.payment_method)}</div>
                                        <div className="text-[11px] text-gray-500 font-mono mt-0.5">{r.payment_reference || '—'}</div>
                                    </td>
                                    <td className="px-4 sm:px-6 py-4 text-gray-600 text-xs">
                                        <span title={r.created_at} className="font-semibold">{r.created_at_human}</span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

// ========================================================
// Expiring Subscriptions Alert Component
// ========================================================
function ExpiringSubscriptionsAlert({ subscriptions }) {
    if (!subscriptions || subscriptions.length === 0) return null;

    return (
        <div className="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden mb-6">
            <div className="p-4 sm:p-5 bg-amber-50/60 border-b border-amber-100 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="text-amber-600 font-bold text-lg">⚠️</span>
                    <div>
                        <h3 className="font-bold text-amber-950 text-sm">اشتراكات تنتهي قريباً (خلال 7 أيام)</h3>
                        <p className="text-[11px] text-amber-800 mt-0.5">يرجى متابعتهم للتجديد قبل التوقف التلقائي</p>
                    </div>
                </div>
                <span className="bg-amber-200 text-amber-900 font-extrabold px-2.5 py-1 rounded-full text-xs">
                    {subscriptions.length}
                </span>
            </div>
            <div className="p-3 divide-y divide-gray-50 max-h-64 overflow-y-auto">
                {subscriptions.map((sub) => (
                    <div key={sub.id} className="py-2.5 px-2 flex items-center justify-between text-xs hover:bg-slate-50 rounded-xl transition-colors">
                        <div>
                            <p className="text-sm font-bold text-gray-900">{sub.tenant_name}</p>
                            <p className="text-xs text-gray-500 font-mono mt-0.5">{sub.tenant_phone || 'لا يوجد هاتف'}</p>
                        </div>
                        <div className="text-left">
                            <p className="text-xs font-semibold text-gray-700">{sub.plan_name}</p>
                            <span className={`inline-block mt-1 px-2 py-0.5 rounded-md text-[10px] font-bold ${
                                Math.round(Number(sub.days_left)) <= 2 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'
                            }`}>
                                ينتهي بعد {Math.max(1, Math.round(Number(sub.days_left)))} يوم
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ========================================================
// Stats Card Component
// ========================================================
function StatCard({ title, value, sub, icon, color }) {
    const colors = {
        indigo:  { bg: 'bg-indigo-50',  icon: 'bg-indigo-600',  text: 'text-indigo-900' },
        emerald: { bg: 'bg-emerald-50', icon: 'bg-emerald-600', text: 'text-emerald-900' },
        blue:    { bg: 'bg-blue-50',    icon: 'bg-blue-600',    text: 'text-blue-900' },
        purple:  { bg: 'bg-purple-50',  icon: 'bg-purple-600',  text: 'text-purple-900' },
        orange:  { bg: 'bg-orange-50',  icon: 'bg-orange-600',  text: 'text-orange-900' },
    };
    const c = colors[color] || colors.indigo;

    return (
        <div className="bg-white rounded-2xl border border-gray-100 p-4 sm:p-5 shadow-sm hover:shadow-md transition-all group flex flex-col justify-between relative overflow-hidden">
            <div className="flex justify-between items-start mb-3">
                <div className={`w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shadow-sm text-white ${c.icon}`}>
                    {icon}
                </div>
            </div>
            <div>
                <h3 className={`text-2xl sm:text-3xl font-extrabold ${c.text} mb-1 tracking-tight`}>{value}</h3>
                <p className="text-xs sm:text-sm font-semibold text-gray-600">{title}</p>
                {sub && <p className="text-[11px] text-gray-400 mt-1 font-medium leading-normal">{sub}</p>}
            </div>
            <div className={`absolute -bottom-6 -left-6 w-24 h-24 rounded-full ${c.bg} opacity-50 group-hover:scale-150 transition-transform duration-500 ease-in-out`}></div>
        </div>
    );
}

// ========================================================
// Dashboard Page
// ========================================================
export default function Dashboard({ stats, pendingReceipts, expiringSubscriptions, topStores, recentStores, graphs }) {
    return (
        <SuperAdminLayout>
            <Head title="مركز القيادة - لوحة تحكم الإدارة" />

            <div className="p-2 sm:p-4 md:p-6 max-w-[1600px] mx-auto bg-[#F8FAFC] min-h-screen">
                
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-end justify-between mb-6 sm:mb-8 gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">مركز القيادة <span className="text-indigo-600">والتحليلات</span></h1>
                        <p className="text-xs sm:text-sm text-slate-500 mt-1 font-medium">نظرة شاملة وعميقة على أداء المنصة والمتاجر المشتركة</p>
                    </div>
                    <div className="flex gap-3">
                        <Link href={route('superadmin.tenants.index')} className="w-full sm:w-auto text-center px-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl font-bold text-xs sm:text-sm hover:bg-slate-50 shadow-sm transition-all">
                            إدارة المتاجر
                        </Link>
                    </div>
                </div>

                {/* Main Alert */}
                <PendingReceiptsAlert count={stats.pending_payments} receipts={pendingReceipts} />

                {/* Health Metrics Grid */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
                    <StatCard 
                        title="إجمالي المتاجر" 
                        value={formatNumber(stats.total_stores)} 
                        sub={`${formatNumber(stats.active_stores)} نشط | ${formatNumber(stats.suspended_stores)} موقوف`}
                        color="indigo"
                        icon={<svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>}
                    />
                    <StatCard 
                        title="الاشتراكات النشطة" 
                        value={formatNumber(stats.total_subscriptions)} 
                        sub="متاجر تعمل حالياً ببااقات مفعلة"
                        color="emerald"
                        icon={<svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
                    />
                    <StatCard 
                        title="أرباح المنصة" 
                        value={formatCurrency(stats.platform_revenue)} 
                        sub="إجمالي الإيصالات المعتمدة تاريخياً"
                        color="orange"
                        icon={<svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
                    />
                    <StatCard 
                        title="طلبات المنصة" 
                        value={formatNumber(stats.platform_orders)} 
                        sub="إجمالي الطلبات عبر جميع المتاجر"
                        color="purple"
                        icon={<svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>}
                    />
                </div>

                {/* Complex Data Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    
                    {/* Left Column (Lists) */}
                    <div className="lg:col-span-1 space-y-6">
                        <ExpiringSubscriptionsAlert subscriptions={expiringSubscriptions} />

                        {/* Top Stores */}
                        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm">
                            <div className="p-5 border-b border-gray-50 flex items-center justify-between">
                                <div>
                                    <h3 className="font-bold text-gray-900 text-sm">أفضل المتاجر أداءً 🏆</h3>
                                    <p className="text-[11px] text-gray-400 mt-0.5">الأعلى من حيث حجم الطلبات الإجمالي</p>
                                </div>
                            </div>
                            <div className="p-2">
                                {topStores && topStores.length > 0 ? (
                                    topStores.map((store, idx) => (
                                        <div key={store.id} className="flex items-center gap-4 p-3 hover:bg-slate-50 rounded-xl transition-colors">
                                            <div className="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 font-black flex items-center justify-center text-xs">
                                                #{idx + 1}
                                            </div>
                                            <div className="flex-1">
                                                <p className="text-sm font-bold text-gray-900">{store.name}</p>
                                                <p className="text-[10px] text-gray-400 font-mono">/{store.slug}</p>
                                            </div>
                                            <div className="text-left bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100">
                                                <p className="text-xs font-black text-indigo-700">{formatNumber(store.total_orders)}</p>
                                                <p className="text-[9px] font-bold text-indigo-400 uppercase">طلب</p>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="p-8 text-center text-gray-400 text-sm">لا توجد طلبات بعد</div>
                                )}
                            </div>
                        </div>

                        {/* Recent Onboarding */}
                        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm">
                            <div className="p-5 border-b border-gray-50 flex items-center justify-between">
                                <div>
                                    <h3 className="font-bold text-gray-900 text-sm">أحدث المتاجر المنضمة 🚀</h3>
                                </div>
                            </div>
                            <div className="p-0">
                                {recentStores && recentStores.length > 0 ? (
                                    recentStores.map((store) => (
                                        <div key={store.id} className="flex flex-col p-4 border-b border-gray-50 last:border-0 hover:bg-slate-50 transition-colors">
                                            <div className="flex items-center justify-between mb-1">
                                                <span className="text-sm font-bold text-gray-900">{store.name}</span>
                                                <span className="text-[10px] text-gray-400 font-semibold">{store.created_at_human}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-xs text-gray-500">
                                                <span>المالك: <span className="font-semibold text-gray-700">{store.owner_name}</span></span>
                                                <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold ${store.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                                                    {store.is_active ? 'نشط' : 'موقوف'}
                                                </span>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="p-8 text-center text-gray-400 text-sm">لا توجد متاجر مسجلة</div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Right Column (Graphs) */}
                    <div className="lg:col-span-2 space-y-6">
                        
                        {/* Registrations Chart/List */}
                        <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm h-auto lg:h-[48%] flex flex-col justify-center">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-100">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                </div>
                                <div>
                                    <h4 className="text-lg font-black text-gray-900">نمو المتاجر الشهري</h4>
                                    <p className="text-xs text-gray-500 font-medium mt-0.5">عدد المتاجر الجديدة المسجلة كل شهر</p>
                                </div>
                            </div>
                            
                            {graphs.registrations && graphs.registrations.length > 0 ? (
                                <div className="space-y-4">
                                    {graphs.registrations.map((item, idx) => (
                                        <div key={idx} className="flex items-center justify-between group">
                                            <span className="text-sm text-gray-600 font-bold w-20">{item.month}</span>
                                            <div className="flex-1 mx-4 bg-slate-100 h-2.5 rounded-full overflow-hidden border border-slate-200">
                                                <div 
                                                    className="bg-indigo-500 h-full rounded-full transition-all duration-1000 group-hover:bg-indigo-400 relative" 
                                                    style={{ width: `${Math.max(2, Math.min(100, (item.count / Math.max(1, ...graphs.registrations.map(r => r.count))) * 100))}%` }}
                                                >
                                                    <div className="absolute top-0 right-0 bottom-0 left-0 bg-white/20" style={{ backgroundImage: 'linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent)', backgroundSize: '1rem 1rem' }}></div>
                                                </div>
                                            </div>
                                            <span className="text-sm font-black text-indigo-700 w-16 text-left">{formatNumber(item.count)} متجر</span>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-8 text-gray-400">
                                    <p className="text-sm font-medium">لا توجد بيانات تسجيلات حالياً</p>
                                </div>
                            )}
                        </div>

                        {/* Revenue List */}
                        <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm h-auto lg:h-[48%] flex flex-col justify-center">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-sm border border-emerald-100">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <h4 className="text-lg font-black text-gray-900">الأرباح الشهرية</h4>
                                    <p className="text-xs text-gray-500 font-medium mt-0.5">إجمالي إيصالات الدفع المعتمدة كل شهر</p>
                                </div>
                            </div>

                            {graphs.revenue && graphs.revenue.length > 0 ? (
                                <div className="space-y-4">
                                    {graphs.revenue.map((item, idx) => (
                                        <div key={idx} className="flex items-center justify-between group">
                                            <span className="text-sm text-gray-600 font-bold w-20">{item.month}</span>
                                            <div className="flex-1 mx-4 bg-slate-100 h-2.5 rounded-full overflow-hidden border border-slate-200">
                                                <div 
                                                    className="bg-emerald-500 h-full rounded-full transition-all duration-1000 group-hover:bg-emerald-400 relative" 
                                                    style={{ width: `${Math.max(2, Math.min(100, (item.total_amount / Math.max(1, ...graphs.revenue.map(r => r.total_amount))) * 100))}%` }}
                                                >
                                                     <div className="absolute top-0 right-0 bottom-0 left-0 bg-white/20" style={{ backgroundImage: 'linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent)', backgroundSize: '1rem 1rem' }}></div>
                                                </div>
                                            </div>
                                            <span className="text-sm font-black text-emerald-700 w-24 text-left">{formatCurrency(item.total_amount)}</span>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-8 text-gray-400">
                                    <p className="text-sm font-medium">لا توجد أرباح مسجلة حالياً</p>
                                </div>
                            )}
                        </div>

                    </div>
                </div>
            </div>
        </SuperAdminLayout>
    );
}
