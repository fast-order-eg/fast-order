import React, { useState } from 'react';
import { Head, useForm, router, usePage, Link } from '@inertiajs/react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';

export default function AdReportsIndex({ reports, filters = {} }) {
    const { flash } = usePage().props;
    const [editingReport, setEditingReport] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [copiedToken, setCopiedToken] = useState(null);
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        name: '',
        campaign_ids: '',
        notes: '',
    });

    const openCreateModal = () => {
        reset();
        clearErrors();
        setEditingReport(null);
        setData({
            name: '',
            campaign_ids: '',
            notes: '',
        });
        setShowModal(true);
    };

    const openEditModal = (report) => {
        clearErrors();
        setEditingReport(report);
        const cleanIds = (report.campaign_ids_raw || (report.campaign_ids || []).join("\n")).trim();
        setData({
            name: report.name,
            campaign_ids: cleanIds,
            notes: report.notes || '',
        });
        setShowModal(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingReport) {
            put(route('superadmin.ad-reports.update', editingReport.id), {
                onSuccess: () => {
                    setShowModal(false);
                    reset();
                },
            });
        } else {
            post(route('superadmin.ad-reports.store'), {
                onSuccess: () => {
                    setShowModal(false);
                    reset();
                },
            });
        }
    };

    const handleToggle = (report) => {
        router.patch(route('superadmin.ad-reports.toggle-status', report.id), {}, { preserveScroll: true });
    };

    const handleDelete = (report) => {
        if (confirm(`هل أنت متأكد من حذف تقرير الإعلانات للعميل "${report.name}"؟ سيتم تعطيل الرابط نهائياً.`)) {
            router.delete(route('superadmin.ad-reports.destroy', report.id), { preserveScroll: true });
        }
    };

    const handleCopyLink = (report) => {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(report.public_url);
            setCopiedToken(report.token);
            setTimeout(() => setCopiedToken(null), 2500);
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('superadmin.ad-reports.index'), { search: searchTerm }, { preserveState: true, replace: true });
    };

    return (
        <SuperAdminLayout>
            <Head title="تقارير الإعلانات الخارجية - لوحة التحكم" />

            <div className="p-4 sm:p-6 space-y-6">
                {/* Header Banner */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-gray-100">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="text-xl">📢</span>
                            <h1 className="text-xl font-bold text-gray-900">تقارير الإعلانات المخصصة للعملاء</h1>
                            <span className="px-2.5 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full border border-indigo-100">
                                {reports.total || 0} تقرير
                            </span>
                        </div>
                        <p className="text-xs text-gray-500 max-w-2xl leading-relaxed">
                            إنشاء ومشاركة روابط مخصصة للعملاء والمستثمرين لعرض ومتابعة نتائج حملاتهم الإعلانية (Meta Ads) مباشرة وبدون الحاجة لامتلاك متجر أو تسجيل دخول، وبدون عرض أوردرات المتجر.
                        </p>
                    </div>
                    <button
                        onClick={openCreateModal}
                        className="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-2 shrink-0 cursor-pointer"
                    >
                        <span>➕</span>
                        <span>إضافة تقرير إعلان جديد</span>
                    </button>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border-r-4 border-emerald-500 rounded-xl text-emerald-800 text-xs font-bold flex items-center justify-between shadow-2xs">
                        <span>{flash.success}</span>
                    </div>
                )}

                {/* Search Bar */}
                <div className="bg-white p-4 rounded-xl shadow-xs border border-gray-100 flex items-center justify-between gap-3">
                    <form onSubmit={handleSearch} className="flex-1 max-w-md flex items-center gap-2">
                        <div className="relative flex-1">
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="بحث باسم العميل أو الحملة..."
                                className="w-full pl-3 pr-9 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <span className="absolute right-3 top-2.5 text-gray-400 text-xs">🔍</span>
                        </div>
                        <button
                            type="submit"
                            className="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition-colors"
                        >
                            بحث
                        </button>
                        {searchTerm && (
                            <button
                                type="button"
                                onClick={() => {
                                    setSearchTerm('');
                                    router.get(route('superadmin.ad-reports.index'), {}, { preserveState: true, replace: true });
                                }}
                                className="px-2.5 py-2 text-gray-400 hover:text-gray-600 text-xs"
                            >
                                إلغاء
                            </button>
                        )}
                    </form>
                </div>

                {/* Reports Table */}
                <div className="bg-white rounded-xl shadow-xs border border-gray-100 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-right border-collapse">
                            <thead>
                                <tr className="bg-gray-50 text-gray-500 text-xs font-semibold uppercase border-b border-gray-100">
                                    <th className="px-6 py-4">اسم العميل / التقرير</th>
                                    <th className="px-6 py-4">أرقام الحملات الإعلانية</th>
                                    <th className="px-6 py-4">الرابط المخصص للمعاينة</th>
                                    <th className="px-6 py-4 text-left">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 text-xs">
                                {reports.data && reports.data.length > 0 ? (
                                    reports.data.map((report) => (
                                        <tr key={report.id} className="hover:bg-gray-50/50 transition-colors">
                                            {/* Report Name & Notes */}
                                            <td className="px-6 py-4">
                                                <div className="font-bold text-gray-900 text-sm">{report.name}</div>
                                                {report.notes && (
                                                    <div className="text-[11px] text-gray-500 mt-1 flex items-center gap-1.5 line-clamp-1 max-w-[280px]" title={report.notes}>
                                                        <span>📝</span>
                                                        <span className="truncate">{report.notes}</span>
                                                    </div>
                                                )}
                                            </td>

                                            {/* Campaign IDs */}
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap items-center gap-1.5 max-w-xs">
                                                    <span className="px-2 py-0.5 bg-purple-50 text-purple-700 rounded-full font-bold text-[10px] border border-purple-100">
                                                        {report.campaigns_count} حملات
                                                    </span>
                                                    {report.campaign_ids.slice(0, 2).map((cId) => (
                                                        <span key={cId} className="px-2 py-0.5 bg-gray-100 text-gray-700 rounded font-mono text-[10px]">
                                                            {cId}
                                                        </span>
                                                    ))}
                                                    {report.campaign_ids.length > 2 && (
                                                        <span className="text-[10px] text-gray-400">
                                                            +{report.campaign_ids.length - 2} المزيد
                                                        </span>
                                                    )}
                                                </div>
                                            </td>

                                            {/* Dedicated Share Link */}
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleCopyLink(report)}
                                                        className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs ${
                                                            copiedToken === report.token
                                                                ? 'bg-emerald-600 text-white'
                                                                : 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200'
                                                        }`}
                                                        title="نسخ رابط المشاركة للعميل"
                                                    >
                                                        <span>{copiedToken === report.token ? '✅ تم النسخ' : '📋 نسخ الرابط'}</span>
                                                    </button>
                                                    <a
                                                        href={report.public_url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="p-1.5 text-gray-500 hover:text-indigo-600 bg-gray-100 hover:bg-indigo-50 rounded-lg transition-colors"
                                                        title="فتح ومعاينة التقرير"
                                                    >
                                                        <span className="text-xs font-bold">معاينة ↗</span>
                                                    </a>
                                                </div>
                                            </td>

                                            {/* Actions */}
                                            <td className="px-6 py-4 text-left">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => openEditModal(report)}
                                                        className="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition-colors cursor-pointer"
                                                    >
                                                        تعديل
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(report)}
                                                        className="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-semibold transition-colors cursor-pointer"
                                                    >
                                                        حذف
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="4" className="px-6 py-12 text-center text-gray-400">
                                            <div className="text-3xl mb-2">📊</div>
                                            <p className="font-bold text-gray-700 text-sm">لا توجد تقارير إعلانات حتى الآن</p>
                                            <p className="text-xs text-gray-400 mt-1">اضغط على زر "إضافة تقرير إعلان جديد" بالأعلى لإنشاء أول رابط تقرير مخصص.</p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {reports.links && reports.links.length > 3 && (
                        <div className="p-4 border-t border-gray-100 flex items-center justify-between">
                            <span className="text-xs text-gray-500">
                                عرض {reports.from || 0} إلى {reports.to || 0} من أصل {reports.total || 0} تقرير
                            </span>
                            <div className="flex items-center gap-1">
                                {reports.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url || '#'}
                                        preserveScroll
                                        className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
                                            link.active
                                                ? 'bg-indigo-600 text-white'
                                                : link.url
                                                ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                : 'text-gray-300 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Create/Edit Modal */}
            {showModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
                        <div className="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                            <h3 className="text-base font-bold text-gray-900 flex items-center gap-2">
                                <span>{editingReport ? '✏️ تعديل تقرير الإعلانات' : '➕ إضافة تقرير إعلانات جديد'}</span>
                            </h3>
                            <button
                                type="button"
                                onClick={() => setShowModal(false)}
                                className="w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center font-bold text-sm cursor-pointer"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4 text-right" dir="rtl">
                            {/* Client Name */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1">
                                    اسم العميل أو اسم التقرير <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="مثال: العميل أحمد، أو شركة النور للتسويق"
                                    className="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                {errors.name && <p className="text-rose-600 text-[11px] mt-1 font-semibold">{errors.name}</p>}
                            </div>

                            {/* Campaign IDs */}
                            <div>
                                <div className="flex items-center justify-between mb-1">
                                    <label className="block text-xs font-bold text-gray-700">
                                        معرفات الحملات الإعلانية (Campaign IDs) <span className="text-rose-500">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            const current = (data.campaign_ids || '').trim();
                                            setData('campaign_ids', current ? current + "\n" : '');
                                        }}
                                        className="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 cursor-pointer bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg border border-indigo-200 transition-colors"
                                    >
                                        <span>➕ إضافة سطر لمعرف آخر</span>
                                    </button>
                                </div>
                                <textarea
                                    required
                                    rows="4"
                                    dir="ltr"
                                    style={{ direction: 'ltr', textAlign: 'left' }}
                                    value={data.campaign_ids}
                                    onChange={(e) => setData('campaign_ids', e.target.value)}
                                    placeholder="120252902821480334&#10;120252902821480335"
                                    className="w-full px-3.5 py-2.5 text-xs border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-left tracking-wider"
                                />
                                <p className="text-[11px] text-gray-400 mt-1">
                                    💡 يمكنك إضافة أكثر من معرف حملة، اكتب كل معرف في سطر مستقل أو افصل بينهم بفاصلة.
                                </p>
                                {errors.campaign_ids && <p className="text-rose-600 text-[11px] mt-1 font-semibold">{errors.campaign_ids}</p>}
                            </div>

                            {/* Notes / Evaluation for Client */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1">
                                    ملاحظات وتقييم الحملة للعميل (اختياري)
                                </label>
                                <textarea
                                    rows="3"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="اكتب تقييمك للحملات، ملحوظاتك، أو توصياتك لتظهر للعميل أعلى صفحة التقرير..."
                                    className="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 leading-relaxed"
                                />
                                <p className="text-[11px] text-gray-400 mt-1">
                                    💡 ستظهر هذه الملاحظات بشكل بارز أعلى الحملات في رابط التقرير للعميل، وكذلك في نص التقرير المنسوخ للواتساب.
                                </p>
                            </div>

                            {/* Modal Actions */}
                            <div className="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer shadow-sm disabled:opacity-50"
                                >
                                    {processing ? 'جاري الحفظ...' : (editingReport ? 'تحديث التقرير' : 'حفظ وإنشاء الرابط 🚀')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </SuperAdminLayout>
    );
}
