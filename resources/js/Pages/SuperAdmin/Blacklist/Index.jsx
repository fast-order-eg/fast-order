import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';

export default function BlacklistIndex({ records, stats, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');
    const [status, setStatus] = useState(filters.status || '');
    const [showAddModal, setShowAddModal] = useState(false);
    const [showUnblockModal, setShowUnblockModal] = useState(false);
    const [targetUnblockPhone, setTargetUnblockPhone] = useState('');

    // Form for adding new record (global ban or whitelist)
    const { data, setData, post, processing, reset, errors } = useForm({
        type: 'phone',
        value: '',
        reason: '',
        action_type: 'global_ban',
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/blacklist', { search, type, status }, { preserveState: true });
    };

    const handleFilterChange = (newType, newStatus) => {
        setType(newType);
        setStatus(newStatus);
        router.get('/blacklist', { search, type: newType, status: newStatus }, { preserveState: true });
    };

    const handleToggleGlobal = (id) => {
        if (confirm('هل أنت متأكد من تغيير حالة النطاق (حظر عام / حظر محلي)؟')) {
            router.patch(`/blacklist/${id}/toggle-global`, {}, { preserveScroll: true });
        }
    };

    const handleUnblockGlobally = (phoneValue) => {
        if (confirm(`هل أنت متأكد من فك الحظر عن (${phoneValue}) لجميع المتاجر واعتماده كموثوق؟`)) {
            router.post('/blacklist/unblock-globally', { value: phoneValue, type: 'phone' }, {
                preserveScroll: true,
                onSuccess: () => setShowUnblockModal(false)
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('هل أنت متأكد من حذف هذا السجل نهائياً؟')) {
            router.delete(`/blacklist/${id}`, { preserveScroll: true });
        }
    };

    const handleAddSubmit = (e) => {
        e.preventDefault();
        post('/blacklist', {
            preserveScroll: true,
            onSuccess: () => {
                setShowAddModal(false);
                reset();
            }
        });
    };

    return (
        <SuperAdminLayout>
            <Head title="درع المرتجعات والقائمة السوداء المشتركة" />

            <div className="space-y-6 text-right" dir="rtl">
                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 bg-rose-50 text-rose-600 rounded-xl">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </span>
                            <h1 className="text-2xl font-bold text-gray-900">درع المرتجعات (RTO Shield) والقائمة السوداء</h1>
                        </div>
                        <p className="text-gray-500 text-sm mt-1">
                            متابعة وإدارة الأرقام المشبوهة وحماية جميع المتاجر من المرتجعات وفك الحظر المباشر من السوبر أدمن.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            onClick={() => setShowUnblockModal(true)}
                            className="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium text-sm transition shadow-sm"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            <span>فك حظر رقم لجميع المتاجر</span>
                        </button>

                        <button
                            onClick={() => setShowAddModal(true)}
                            className="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 hover:bg-black text-white rounded-xl font-medium text-sm transition shadow-sm"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>إضافة حظر عام جديد</span>
                        </button>
                    </div>
                </div>

                {/* Metric Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span className="text-xs font-semibold text-gray-400 block mb-1">إجمالي البلاغات المسجلة</span>
                            <span className="text-2xl font-black text-gray-900">{stats.total}</span>
                        </div>
                        <div className="w-12 h-12 bg-gray-50 text-gray-700 rounded-xl flex items-center justify-center font-bold">
                            📋
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-rose-100 shadow-sm flex items-center justify-between bg-rose-50/20">
                        <div>
                            <span className="text-xs font-semibold text-rose-600 block mb-1">محظور عام على المنصة</span>
                            <span className="text-2xl font-black text-rose-700">{stats.global_blocked}</span>
                        </div>
                        <div className="w-12 h-12 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center font-bold">
                            🚫
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-emerald-100 shadow-sm flex items-center justify-between bg-emerald-50/20">
                        <div>
                            <span className="text-xs font-semibold text-emerald-600 block mb-1">أرقام مفكوك حظرها (موثوقة)</span>
                            <span className="text-2xl font-black text-emerald-700">{stats.whitelisted}</span>
                        </div>
                        <div className="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center font-bold">
                            ⭐
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-blue-100 shadow-sm flex items-center justify-between bg-blue-50/20">
                        <div>
                            <span className="text-xs font-semibold text-blue-600 block mb-1">سجلات أرقام الهواتف</span>
                            <span className="text-2xl font-black text-blue-700">{stats.phone_records}</span>
                        </div>
                        <div className="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center font-bold">
                            📱
                        </div>
                    </div>
                </div>

                {/* Filters & Search */}
                <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 items-center justify-between">
                    <form onSubmit={handleSearch} className="flex items-center gap-2 w-full md:w-96">
                        <div className="relative w-full">
                            <input
                                type="text"
                                placeholder="ابحث برقم الهاتف، IP، أو البريد..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                            />
                            <svg className="w-4 h-4 text-gray-400 absolute right-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <button type="submit" className="px-4 py-2.5 bg-gray-900 text-white rounded-xl text-sm font-medium hover:bg-black transition">
                            بحث
                        </button>
                    </form>

                    <div className="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <select
                            value={status}
                            onChange={(e) => handleFilterChange(type, e.target.value)}
                            className="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none"
                        >
                            <option value="">جميع الحالات</option>
                            <option value="global">محظور عام (Global)</option>
                            <option value="whitelisted">مفكوك الحظر (Whitelisted)</option>
                            <option value="local">محلي بالمتاجر فقط</option>
                        </select>

                        <select
                            value={type}
                            onChange={(e) => handleFilterChange(e.target.value, status)}
                            className="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none"
                        >
                            <option value="">كل الأنواع</option>
                            <option value="phone">هاتف</option>
                            <option value="ip">IP</option>
                            <option value="email">بريد</option>
                        </select>
                    </div>
                </div>

                {/* Table */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-right text-sm">
                            <thead className="bg-gray-50 text-gray-500 font-semibold text-xs border-b border-gray-100">
                                <tr>
                                    <th className="py-4 px-6">القيمة / المعرف</th>
                                    <th className="py-4 px-6">تقييم المخاطر (RTO Shield)</th>
                                    <th className="py-4 px-6">نطاق الحظر</th>
                                    <th className="py-4 px-6">المتجر المُبلّغ</th>
                                    <th className="py-4 px-6">السبب المذكور</th>
                                    <th className="py-4 px-6 text-left">إجراءات السوبر أدمن</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {records.data.length === 0 ? (
                                    <tr>
                                        <td colSpan="6" className="text-center py-12 text-gray-400">
                                            لا توجد أي سجلات مطابقة للبحث
                                        </td>
                                    </tr>
                                ) : (
                                    records.data.map((record) => (
                                        <tr key={record.id} className="hover:bg-gray-50/60 transition">
                                            <td className="py-4 px-6">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-mono font-bold text-gray-900 dir-ltr text-base">
                                                        {record.value}
                                                    </span>
                                                    <span className="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">
                                                        {record.type === 'phone' ? 'هاتف' : record.type === 'ip' ? 'IP' : 'بريد'}
                                                    </span>
                                                </div>
                                            </td>

                                            <td className="py-4 px-6">
                                                {record.rto_stats ? (
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-1.5">
                                                            <span className={`inline-block px-2.5 py-1 text-xs font-bold rounded-lg ${
                                                                record.rto_stats.risk_level === 'high' ? 'bg-red-100 text-red-700' :
                                                                record.rto_stats.risk_level === 'medium' ? 'bg-amber-100 text-amber-700' :
                                                                record.rto_stats.risk_level === 'whitelisted' ? 'bg-emerald-100 text-emerald-700' :
                                                                'bg-green-100 text-green-700'
                                                            }`}>
                                                                {record.rto_stats.badge_text}
                                                            </span>
                                                        </div>
                                                        <div className="text-[11px] text-gray-500">
                                                            طلبات: {record.rto_stats.total_orders} | مرتجع: <span className="text-red-600 font-semibold">{record.rto_stats.returned_orders}</span> | تسليم: {record.rto_stats.delivered_orders} ({record.rto_stats.delivery_rate}%)
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-gray-400">—</span>
                                                )}
                                            </td>

                                            <td className="py-4 px-6">
                                                {record.is_whitelisted ? (
                                                    <span className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        ⭐ مفكوك الحظر (موثوق)
                                                    </span>
                                                ) : record.is_global ? (
                                                    <span className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
                                                        🚫 حظر عام (لكل المتاجر)
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg bg-gray-100 text-gray-600">
                                                        حظر محلي لمتجره
                                                    </span>
                                                )}
                                            </td>

                                            <td className="py-4 px-6">
                                                {record.tenant ? (
                                                    <div>
                                                        <span className="font-medium text-gray-900 block">{record.tenant.name}</span>
                                                        <span className="text-xs text-gray-400 font-mono">@{record.tenant.slug}</span>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                                        إدارة المنصة (SuperAdmin)
                                                    </span>
                                                )}
                                            </td>

                                            <td className="py-4 px-6 max-w-xs truncate text-gray-600 text-xs">
                                                {record.reason || 'بدون سبب مذكور'}
                                            </td>

                                            <td className="py-4 px-6 text-left">
                                                <div className="flex items-center justify-end gap-2">
                                                    {/* Button to Unblock Globally */}
                                                    {!record.is_whitelisted && (
                                                        <button
                                                            onClick={() => handleUnblockGlobally(record.value)}
                                                            title="فك الحظر عن هذا الرقم لجميع المتاجر"
                                                            className="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                                        >
                                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                            </svg>
                                                        </button>
                                                    )}

                                                    {/* Toggle Global */}
                                                    <button
                                                        onClick={() => handleToggleGlobal(record.id)}
                                                        title={record.is_global ? "إلغاء الحظر العام" : "تعميم كحظر عام للمنصة"}
                                                        className={`p-1.5 rounded-lg transition ${
                                                            record.is_global ? 'text-amber-600 hover:bg-amber-50' : 'text-blue-600 hover:bg-blue-50'
                                                        }`}
                                                    >
                                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </button>

                                                    {/* Delete */}
                                                    <button
                                                        onClick={() => handleDelete(record.id)}
                                                        title="حذف السجل"
                                                        className="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition"
                                                    >
                                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {records.links && records.links.length > 3 && (
                        <div className="p-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                            <div>عرض {records.from || 0} إلى {records.to || 0} من إجمالي {records.total} سجل</div>
                            <div className="flex gap-1">
                                {records.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1.5 rounded-lg border text-xs ${
                                            link.active ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                                        } ${!link.url ? 'opacity-40 cursor-not-allowed' : ''}`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal: Quick Unblock Phone Globally */}
            {showUnblockModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" dir="rtl">
                    <div className="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
                        <div className="flex items-center justify-between pb-3 border-b border-gray-100">
                            <h3 className="text-lg font-bold text-gray-900">فك حظر رقم هاتف لجميع المتاجر</h3>
                            <button onClick={() => setShowUnblockModal(false)} className="text-gray-400 hover:text-gray-600">✕</button>
                        </div>
                        <p className="text-sm text-gray-500">
                            أدخل رقم الهاتف الذي ترغب في فك الحظر عنه لجميع المتاجر. سيتمكن هذا العميل من الطلب من أي متجر على المنصة دون قيود.
                        </p>
                        <div>
                            <label className="block text-xs font-semibold text-gray-700 mb-1">رقم الهاتف:</label>
                            <input
                                type="text"
                                placeholder="مثال: 01012345678"
                                value={targetUnblockPhone}
                                onChange={(e) => setTargetUnblockPhone(e.target.value)}
                                className="w-full px-3 py-2 border rounded-xl font-mono text-left dir-ltr"
                            />
                        </div>
                        <div className="flex justify-end gap-2 pt-3">
                            <button
                                onClick={() => setShowUnblockModal(false)}
                                className="px-4 py-2 border rounded-xl text-gray-600 hover:bg-gray-50 text-sm"
                            >
                                إلغاء
                            </button>
                            <button
                                disabled={!targetUnblockPhone}
                                onClick={() => handleUnblockGlobally(targetUnblockPhone)}
                                className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold disabled:opacity-50"
                            >
                                فك الحظر واعتماده كموثوق
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal: Add Blacklist or Global Ban */}
            {showAddModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" dir="rtl">
                    <div className="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
                        <div className="flex items-center justify-between pb-3 border-b border-gray-100">
                            <h3 className="text-lg font-bold text-gray-900">إضافة سجل جديد من السوبر أدمن</h3>
                            <button onClick={() => setShowAddModal(false)} className="text-gray-400 hover:text-gray-600">✕</button>
                        </div>

                        <form onSubmit={handleAddSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 mb-1">الإجراء المطلوب:</label>
                                <select
                                    value={data.action_type}
                                    onChange={(e) => setData('action_type', e.target.value)}
                                    className="w-full px-3 py-2 border rounded-xl text-sm"
                                >
                                    <option value="global_ban">🚫 حظر عام على جميع المتاجر (Global Ban)</option>
                                    <option value="whitelist">⭐ فك حظر واعتماد كموثوق (Whitelist)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 mb-1">نوع السجل:</label>
                                <select
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="w-full px-3 py-2 border rounded-xl text-sm"
                                >
                                    <option value="phone">هاتف (Phone)</option>
                                    <option value="ip">عنوان IP</option>
                                    <option value="email">بريد إلكتروني</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 mb-1">القيمة (الرقم أو الـ IP أو البريد):</label>
                                <input
                                    type="text"
                                    required
                                    value={data.value}
                                    onChange={(e) => setData('value', e.target.value)}
                                    placeholder={data.type === 'phone' ? '01012345678' : '192.168.1.1'}
                                    className="w-full px-3 py-2 border rounded-xl font-mono text-left dir-ltr text-sm"
                                />
                                {errors.value && <p className="text-red-500 text-xs mt-1">{errors.value}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 mb-1">السبب أو الملاحظة:</label>
                                <textarea
                                    value={data.reason}
                                    onChange={(e) => setData('reason', e.target.value)}
                                    placeholder="سبب الإضافة..."
                                    rows="3"
                                    className="w-full px-3 py-2 border rounded-xl text-sm"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-3">
                                <button
                                    type="button"
                                    onClick={() => setShowAddModal(false)}
                                    className="px-4 py-2 border rounded-xl text-gray-600 hover:bg-gray-50 text-sm"
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 bg-gray-900 hover:bg-black text-white rounded-xl text-sm font-semibold disabled:opacity-50"
                                >
                                    حفظ وتطبيق
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </SuperAdminLayout>
    );
}
