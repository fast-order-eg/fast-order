import React, { useState } from 'react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

export default function Receipts({ receipts, tenants, plans, paymentSettings, filters }) {
    const safeFilters = filters || {};
    const [searchQuery, setSearchQuery] = useState(safeFilters.search || '');
    const [dateFilter, setDateFilter] = useState(safeFilters.date || '');
    const [statusFilter, setStatusFilter] = useState(safeFilters.status || '');
    const [typeFilter, setTypeFilter] = useState(safeFilters.type || '');
    const [selectedReceipt, setSelectedReceipt] = useState(null);
    const [rejectionReason, setRejectionReason] = useState('');
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [showSettingsModal, setShowSettingsModal] = useState(false);
    const [processing, setProcessing] = useState(false);

    // Form for editing payment settings
    const { data: settingsData, setData: setSettingsData, post: postSettings, processing: savingSettings } = useForm({
        vodafone_cash_number: paymentSettings?.vodafone_cash_number || '',
        instapay_number: paymentSettings?.instapay_number || '',
    });

    // Modal state for attaching receipt
    const [showAttachModal, setShowAttachModal] = useState(false);
    const [attachData, setAttachData] = useState({
        tenant_id: '',
        plan_id: '',
        amount: '',
        payment_method: '',
        payment_reference: '',
        receipt_file: null,
    });
    const [attachErrors, setAttachErrors] = useState({});
    const [attaching, setAttaching] = useState(false);

    const handleApplyFilters = (newParams = {}) => {
        const params = {
            search: searchQuery,
            date: dateFilter,
            status: statusFilter,
            type: typeFilter,
            ...newParams,
        };
        router.get(
            route('superadmin.subscriptions.receipts'),
            params,
            { preserveState: true, replace: true }
        );
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        handleApplyFilters();
    };

    const handleApprove = (receipt) => {
        const isWallet = receipt.type === 'wallet' || receipt.plan?.slug === 'commission' || receipt.plan?.name?.includes('عمولة');
        const msg = isWallet
            ? `هل أنت متأكد من الموافقة وتأكيد إضافة مبلغ (${Math.round(receipt.amount)} ج.م) في محفظة المتجر؟`
            : 'هل أنت متأكد من الموافقة على هذا الإيصال وتفعيل الاشتراك؟';

        if (confirm(msg)) {
            setProcessing(true);
            router.post(
                route('superadmin.subscriptions.receipts.approve', receipt.id),
                {},
                {
                    preserveScroll: true,
                    onFinish: () => setProcessing(false),
                }
            );
        }
    };

    const handleDeleteReceipt = (receipt) => {
        if (confirm('هل أنت متأكد من حذف هذا الإيصال نهائياً؟')) {
            setProcessing(true);
            router.delete(route('superadmin.subscriptions.receipts.destroy', receipt.id), {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            });
        }
    };

    const openRejectModal = (receipt) => {
        setSelectedReceipt(receipt);
        setRejectionReason('');
        setShowRejectModal(true);
    };

    const closeRejectModal = () => {
        setShowRejectModal(false);
        setSelectedReceipt(null);
        setRejectionReason('');
    };

    const handleReject = (e) => {
        e.preventDefault();
        if (!rejectionReason.trim()) {
            alert('يرجى كتابة سبب الرفض');
            return;
        }

        setProcessing(true);
        router.post(
            route('superadmin.subscriptions.receipts.reject', selectedReceipt.id),
            { rejection_reason: rejectionReason },
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeRejectModal();
                },
                onFinish: () => setProcessing(false),
            }
        );
    };

    const handleSaveSettings = (e) => {
        e.preventDefault();
        postSettings(route('superadmin.subscriptions.update-payment-settings'), {
            preserveScroll: true,
            onSuccess: () => setShowSettingsModal(false),
        });
    };

    const handleFileChange = (e) => {
        setAttachData({
            ...attachData,
            receipt_file: e.target.files[0]
        });
    };

    const handleAttachSubmit = (e) => {
        e.preventDefault();
        setAttaching(true);
        router.post(
            route('superadmin.subscriptions.receipts.store'),
            attachData,
            {
                forceFormData: true,
                onError: (errs) => {
                    setAttachErrors(errs);
                    setAttaching(false);
                },
                onSuccess: () => {
                    setShowAttachModal(false);
                    setAttachData({
                        tenant_id: '',
                        plan_id: '',
                        amount: '',
                        payment_method: '',
                        payment_reference: '',
                        receipt_file: null,
                    });
                    setAttachErrors({});
                    setAttaching(false);
                },
            }
        );
    };

    const getPaymentMethodLabel = (method) => {
        const methods = {
            instapay: 'إنستاباي (InstaPay)',
            vodafone_cash: 'فودافون كاش (Vodafone Cash)',
            bank_transfer: 'تحويل بنكي',
            cash: 'نقدي',
        };
        return methods[method] || method;
    };

    const formatDateStr = (dateString) => {
        if (!dateString) return { date: '-', time: '' };
        const d = new Date(dateString);
        const yearStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        let hours = d.getHours();
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // 12-hour format
        const timeStr = String(hours).padStart(2, '0') + ':' + minutes + ' ' + ampm;
        return { date: yearStr, time: timeStr };
    };

    return (
        <SuperAdminLayout>
            <Head title="إيصالات الدفع ومحافظ المتاجر - لوحة التحكم" />

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden space-y-0">
                {/* Header Section */}
                <div className="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-gray-800">إيصالات الدفع ومحافظ المتاجر</h2>
                        <p className="text-sm text-gray-500 mt-1">
                            مراجعة طلبات شحن المحفظة واشتراكات الباقات وتأكيد المبالغ المحولة.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setShowSettingsModal(true)}
                            className="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm font-semibold shadow-sm transition-all flex items-center gap-2"
                        >
                            <span>⚙️</span>
                            <span>أرقام استقبال التحويلات</span>
                        </button>
                        <button
                            onClick={() => setShowAttachModal(true)}
                            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-all flex items-center justify-center gap-2"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                            </svg>
                            إرفاق إيصال جديد
                        </button>
                    </div>
                </div>

                {/* Comprehensive Search & Filter Section */}
                <div className="p-6 bg-gray-50/50 border-b border-gray-100 space-y-4">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div className="sm:col-span-2">
                            <label className="block text-xs font-bold text-gray-500 mb-1">البحث (الرقم المرجعي / الرقم المحول منه / اسم المتجر / المبلغ):</label>
                            <div className="relative">
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder="ابحث بالرقم المرجعي (WAL-...) أو رقم الهاتف..."
                                    className="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                />
                                <button type="submit" className="absolute left-2 top-2 text-gray-400 hover:text-indigo-600">
                                    🔍
                                </button>
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-gray-500 mb-1">الفلترة باليوم/التاريخ:</label>
                            <input
                                type="date"
                                value={dateFilter}
                                onChange={(e) => {
                                    setDateFilter(e.target.value);
                                    handleApplyFilters({ date: e.target.value });
                                }}
                                className="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            />
                        </div>

                        <div className="flex items-end gap-2">
                            <button
                                type="submit"
                                className="flex-1 py-2 px-3 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition-colors shadow-sm"
                            >
                                تصفية نتائج البحث
                            </button>
                            {(searchQuery || dateFilter || statusFilter || typeFilter) && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSearchQuery('');
                                        setDateFilter('');
                                        setStatusFilter('');
                                        setTypeFilter('');
                                        router.get(route('superadmin.subscriptions.receipts'), {}, { preserveState: true, replace: true });
                                    }}
                                    className="py-2 px-3 bg-gray-200 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-300 transition-colors"
                                >
                                    إلغاء
                                </button>
                            )}
                        </div>
                    </form>

                    {/* Status & Type Quick Filters */}
                    <div className="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-gray-200/60">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-xs font-bold text-gray-400 ml-1">الحالة:</span>
                            {[
                                { id: '', label: 'الكل' },
                                { id: 'pending', label: 'قيد المراجعة ⏳', color: 'bg-amber-500' },
                                { id: 'approved', label: 'المقبولة ✅', color: 'bg-emerald-500' },
                                { id: 'rejected', label: 'المرفوضة ❌', color: 'bg-rose-500' },
                            ].map((st) => (
                                <button
                                    key={st.id}
                                    onClick={() => {
                                        setStatusFilter(st.id);
                                        handleApplyFilters({ status: st.id });
                                    }}
                                    className={`px-3 py-1 text-xs font-bold rounded-lg border transition-all ${
                                        statusFilter === st.id
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                    }`}
                                >
                                    {st.label}
                                </button>
                            ))}
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-xs font-bold text-gray-400 ml-1">النوع:</span>
                            {[
                                { id: '', label: 'الكل' },
                                { id: 'wallet', label: '👛 شحن المحفظة' },
                                { id: 'subscription', label: '📦 اشتراك باقة' },
                            ].map((tp) => (
                                <button
                                    key={tp.id}
                                    onClick={() => {
                                        setTypeFilter(tp.id);
                                        handleApplyFilters({ type: tp.id });
                                    }}
                                    className={`px-3 py-1 text-xs font-bold rounded-lg border transition-all ${
                                        typeFilter === tp.id
                                            ? 'bg-slate-800 text-white border-slate-800 shadow-sm'
                                            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                    }`}
                                >
                                    {tp.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Table Section */}
                <div className="overflow-x-auto">
                    <table className="w-full text-right border-collapse">
                        <thead>
                            <tr className="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-100">
                                <th className="px-6 py-4">الرقم المرجعي</th>
                                <th className="px-6 py-4">المتجر</th>
                                <th className="px-6 py-4">نوع الطلب</th>
                                <th className="px-6 py-4">الباقة / التفاصيل</th>
                                <th className="px-6 py-4">المبلغ</th>
                                <th className="px-6 py-4">طريقة الدفع / المرجع</th>
                                <th className="px-6 py-4">تاريخ التقديم</th>
                                <th className="px-6 py-4">الإشعار</th>
                                <th className="px-6 py-4">الحالة</th>
                                <th className="px-6 py-4 text-left">العمليات</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-sm">
                            {receipts.data && receipts.data.length > 0 ? (
                                receipts.data.map((receipt) => {
                                    const dt = formatDateStr(receipt.created_at);
                                    const refCode = receipt.reference_code || String(100000 + receipt.id);

                                    return (
                                        <tr key={receipt.id} className="hover:bg-gray-50/50 transition-colors">
                                            <td className="px-6 py-4">
                                                <span className="font-mono font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 text-xs select-all" dir="ltr">
                                                    {refCode}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div>
                                                    <Link 
                                                        href={route('superadmin.tenants.show', receipt.tenant?.id || 0)}
                                                        className="font-bold text-gray-800 hover:text-indigo-600 transition-colors"
                                                    >
                                                        {receipt.tenant?.name || 'متجر غير معروف'}
                                                    </Link>
                                                    <span className="block text-xs text-gray-400 mt-0.5" dir="ltr">
                                                        {receipt.tenant?.email}
                                                    </span>
                                                </div>
                                            </td>
                                            {(() => {
                                                const isCommissionReceipt = receipt.type === 'wallet' || receipt.plan?.slug === 'commission' || receipt.plan?.name?.includes('عمولة');
                                                const isMonthlyReceipt = receipt.plan?.slug === 'monthly' || receipt.plan?.name?.includes('شهري');
                                                const isYearlyReceipt = receipt.plan?.slug === 'yearly' || receipt.plan?.name?.includes('سنوي');

                                                return (
                                                    <>
                                                        <td className="px-6 py-4">
                                                            {isCommissionReceipt ? (
                                                                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                                                                    👛 شحن محفظة
                                                                </span>
                                                            ) : (
                                                                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                                                    📦 اشتراك باقة
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <span className="font-semibold text-gray-800">
                                                                {isCommissionReceipt 
                                                                    ? '👛 شحن محفظة' 
                                                                    : isMonthlyReceipt 
                                                                        ? 'اشتراك شهري' 
                                                                        : isYearlyReceipt 
                                                                            ? 'اشتراك سنوي' 
                                                                            : (receipt.plan ? (
                                                                                receipt.plan.price_yearly > receipt.plan.price_monthly && receipt.amount >= receipt.plan.price_yearly 
                                                                                    ? 'اشتراك سنوي' 
                                                                                    : 'اشتراك شهري'
                                                                              ) : 'خطة غير معروفة'
                                                                            )
                                                                }
                                                            </span>
                                                        </td>
                                                    </>
                                                );
                                            })()}
                                            <td className="px-6 py-4 font-bold text-indigo-600">
                                                {Math.round(parseFloat(receipt.amount)).toLocaleString('en-US')} ج.م
                                            </td>
                                            <td className="px-6 py-4">
                                                <div>
                                                    <span className="font-semibold text-gray-700 block text-xs">
                                                        {getPaymentMethodLabel(receipt.payment_method)}
                                                    </span>
                                                    {receipt.payment_reference && (
                                                        <span className="font-mono text-xs text-gray-500 select-all block mt-0.5" dir="ltr">
                                                            الرقم: {receipt.payment_reference}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-xs text-gray-600">
                                                <div className="space-y-0.5">
                                                    <span className="font-bold text-gray-800 block">{dt.date}</span>
                                                    <span className="text-[11px] text-gray-400 font-mono block" dir="ltr">{dt.time}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {receipt.receipt_path ? (
                                                    <a
                                                        href={`/storage/${receipt.receipt_path}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 underline transition-colors"
                                                    >
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        عرض الملف
                                                    </a>
                                                ) : (
                                                    <span className="text-xs text-gray-400">لا يوجد ملف</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                {receipt.status === 'pending' && (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                                        قيد المراجعة
                                                    </span>
                                                )}
                                                {receipt.status === 'approved' && (
                                                    <div>
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                            مقبول ومؤكد
                                                        </span>
                                                        {receipt.approved_by && (
                                                            <span className="block text-[10px] text-gray-400 mt-1">
                                                                بواسطة: {receipt.approved_by?.name || 'المدير'}
                                                            </span>
                                                        )}
                                                    </div>
                                                )}
                                                {receipt.status === 'rejected' && (
                                                    <div>
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-100">
                                                            مرفوض
                                                        </span>
                                                        {receipt.rejection_reason && (
                                                            <span className="block text-[10px] text-rose-500 font-medium max-w-[150px] truncate mt-1" title={receipt.rejection_reason}>
                                                                السبب: {receipt.rejection_reason}
                                                            </span>
                                                        )}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-left">
                                                {receipt.status === 'pending' ? (
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            onClick={() => handleApprove(receipt)}
                                                            disabled={processing}
                                                            className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-xs font-bold transition-colors disabled:opacity-50 shadow-sm"
                                                        >
                                                            موافقة وتأكيد
                                                        </button>
                                                        <button
                                                            onClick={() => openRejectModal(receipt)}
                                                            disabled={processing}
                                                            className="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-md text-xs font-bold transition-colors disabled:opacity-50 shadow-sm"
                                                        >
                                                            رفض
                                                        </button>
                                                        <button
                                                            onClick={() => handleDeleteReceipt(receipt)}
                                                            disabled={processing}
                                                            className="px-2.5 py-1.5 bg-gray-100 hover:bg-red-50 text-gray-500 hover:text-red-600 rounded-md text-xs font-bold transition-colors"
                                                            title="حذف الإيصال"
                                                        >
                                                            🗑️
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <button
                                                        onClick={() => handleDeleteReceipt(receipt)}
                                                        disabled={processing}
                                                        className="px-2.5 py-1 bg-gray-100 hover:bg-red-50 text-gray-500 hover:text-red-600 border border-gray-200 hover:border-red-200 rounded-md text-xs font-bold transition-colors"
                                                        title="حذف الإيصال"
                                                    >
                                                        🗑️ حذف
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td colSpan="10" className="px-6 py-12 text-center text-gray-400">
                                        لا توجد إيصالات أو طلبات شحن مطابقة للتصفية حالياً.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Section */}
                {receipts.links && receipts.links.length > 3 && (
                    <div className="p-6 border-t border-gray-100 flex items-center justify-between">
                        <div className="text-xs text-gray-500">
                            عرض {receipts.from || 0} إلى {receipts.to || 0} من إجمالي {receipts.total || 0} طلب
                        </div>
                        <div className="flex items-center gap-1">
                            {receipts.links.map((link, idx) => (
                                <Link
                                    key={idx}
                                    href={link.url || '#'}
                                    disabled={!link.url}
                                    className={`px-3 py-1.5 border text-xs font-medium rounded-md transition-all ${
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label
                                            .replace('Previous', 'السابق')
                                            .replace('Next', 'التالي')
                                    }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Payment Settings Modal */}
            {showSettingsModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-fade-in">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                        <form onSubmit={handleSaveSettings}>
                            <div className="p-6 border-b border-gray-100 flex items-center justify-between">
                                <h3 className="text-lg font-bold text-gray-800">أرقام استقبال التحويلات</h3>
                                <button type="button" onClick={() => setShowSettingsModal(false)} className="text-gray-400 hover:text-gray-600">✕</button>
                            </div>
                            <div className="p-6 space-y-4">
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">رقم فودافون كاش (Vodafone Cash)</label>
                                    <input
                                        type="text"
                                        required
                                        value={settingsData.vodafone_cash_number}
                                        onChange={(e) => setSettingsData('vodafone_cash_number', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold"
                                        dir="ltr"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">رقم إنستا باي (InstaPay)</label>
                                    <input
                                        type="text"
                                        required
                                        value={settingsData.instapay_number}
                                        onChange={(e) => setSettingsData('instapay_number', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold"
                                        dir="ltr"
                                    />
                                </div>
                            </div>
                            <div className="p-6 bg-gray-50 flex justify-end gap-2 border-t border-gray-100">
                                <button type="button" onClick={() => setShowSettingsModal(false)} className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-xs font-bold">إلغاء</button>
                                <button type="submit" disabled={savingSettings} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold shadow-md hover:bg-indigo-700">
                                    {savingSettings ? 'جاري الحفظ...' : 'حفظ التعديلات'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Rejection Modal */}
            {showRejectModal && selectedReceipt && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={closeRejectModal}></div>
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <div>
                                <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 text-rose-600">
                                    <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div className="mt-3 text-center sm:mt-5">
                                    <h3 className="text-lg leading-6 font-bold text-gray-900">
                                        رفض طلب الإيداع / الإيصال
                                    </h3>
                                    <div className="mt-2">
                                        <p className="text-sm text-gray-500">
                                            المتجر: <span className="font-bold text-gray-700">{selectedReceipt.tenant?.name}</span> | المبلغ: <span className="font-bold text-gray-700">{Math.round(selectedReceipt.amount)} ج.م</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={handleReject} className="mt-5">
                                <div>
                                    <label htmlFor="rejection_reason" className="block text-sm font-semibold text-gray-700 mb-2">
                                        سبب الرفض:
                                    </label>
                                    <textarea
                                        id="rejection_reason"
                                        rows="3"
                                        required
                                        value={rejectionReason}
                                        onChange={(e) => setRejectionReason(e.target.value)}
                                        placeholder="مثال: صورة الإيصال غير واضحة، أو لم يصل المبلغ لإنستا باي..."
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all"
                                    ></textarea>
                                </div>

                                <div className="mt-6 flex flex-row-reverse gap-2">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-rose-600 text-base font-bold text-white hover:bg-rose-700 sm:text-sm disabled:opacity-50"
                                    >
                                        تأكيد الرفض
                                    </button>
                                    <button
                                        type="button"
                                        onClick={closeRejectModal}
                                        className="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:text-sm"
                                    >
                                        إلغاء
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Attach Receipt Modal */}
            {showAttachModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={() => setShowAttachModal(false)}></div>
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <div>
                                <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 text-indigo-600">
                                    <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div className="mt-3 text-center sm:mt-5">
                                    <h3 className="text-lg leading-6 font-bold text-gray-900">
                                        إرفاق إيصال دفع جديد
                                    </h3>
                                    <p className="text-xs text-gray-400 mt-1">
                                        تسجيل عملية دفع يدوية لمتجر وتحديد الباقة والمبلغ المحول.
                                    </p>
                                </div>
                            </div>

                            <form onSubmit={handleAttachSubmit} className="mt-5 space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        المتجر
                                    </label>
                                    <select
                                        required
                                        value={attachData.tenant_id}
                                        onChange={(e) => setAttachData({ ...attachData, tenant_id: e.target.value })}
                                        className="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-right"
                                    >
                                        <option value="">اختر المتجر...</option>
                                        {(tenants || []).map((tenant) => (
                                            <option key={tenant.id} value={tenant.id}>
                                                {tenant.name} ({tenant.slug}.{typeof window !== 'undefined' ? window.location.host.replace('app.', '') : 'fastorder.localhost'})
                                            </option>
                                        ))}
                                    </select>
                                    {attachErrors.tenant_id && (
                                        <span className="text-xs text-rose-500 mt-1 block">{attachErrors.tenant_id}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        خطة الاشتراك (الباقة)
                                    </label>
                                    <select
                                        required
                                        value={attachData.plan_id}
                                        onChange={(e) => {
                                            const selectedPlan = (plans || []).find(p => String(p.id) === String(e.target.value));
                                            const isComm = selectedPlan && (
                                                selectedPlan.slug === 'commission' || 
                                                selectedPlan.name?.includes('عمولة') || 
                                                (parseFloat(selectedPlan.price_monthly) === 0 && selectedPlan.slug !== 'free')
                                            );
                                            setAttachData({
                                                ...attachData,
                                                plan_id: e.target.value,
                                                amount: isComm ? '' : (selectedPlan ? selectedPlan.price_monthly : '')
                                            });
                                        }}
                                        className="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-right"
                                    >
                                        <option value="">اختر الباقة...</option>
                                        {(plans || []).map((plan) => {
                                            const isComm = plan.slug === 'commission' || plan.name?.includes('عمولة') || (parseFloat(plan.price_monthly) === 0 && plan.slug !== 'free');
                                            let labelPrice = '';
                                            if (isComm) {
                                                labelPrice = 'عمولة 2ج';
                                            } else if (plan.slug === 'monthly' || plan.name?.includes('شهري')) {
                                                labelPrice = `${Math.round(parseFloat(plan.price_monthly)).toLocaleString('en-US')} ج.م / شهر`;
                                            } else if (plan.slug === 'yearly' || plan.name?.includes('سنوي')) {
                                                labelPrice = `${Math.round(parseFloat(plan.price_yearly || plan.price_monthly)).toLocaleString('en-US')} ج.م / سنة`;
                                            } else if (parseFloat(plan.price_monthly) > 0) {
                                                labelPrice = `${Math.round(parseFloat(plan.price_monthly)).toLocaleString('en-US')} ج.م / شهر`;
                                            } else {
                                                labelPrice = 'مجاني';
                                            }

                                            return (
                                                <option key={plan.id} value={plan.id}>
                                                    {plan.name} ({labelPrice})
                                                </option>
                                            );
                                        })}
                                    </select>
                                    {attachErrors.plan_id && (
                                        <span className="text-xs text-rose-500 mt-1 block">{attachErrors.plan_id}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        المبلغ المدفوع (ج.م)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={attachData.amount}
                                        onChange={(e) => setAttachData({ ...attachData, amount: e.target.value })}
                                        placeholder="مثال: 300"
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-right"
                                    />
                                    {attachErrors.amount && (
                                        <span className="text-xs text-rose-500 mt-1 block">{attachErrors.amount}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        طريقة الدفع
                                    </label>
                                    <select
                                        required
                                        value={attachData.payment_method}
                                        onChange={(e) => setAttachData({ ...attachData, payment_method: e.target.value })}
                                        className="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-right"
                                    >
                                        <option value="">اختر طريقة الدفع...</option>
                                        <option value="instapay">إنستاباي (InstaPay)</option>
                                        <option value="vodafone_cash">فودافون كاش (Vodafone Cash)</option>
                                        <option value="bank_transfer">تحويل بنكي</option>
                                        <option value="cash">نقدي</option>
                                    </select>
                                    {attachErrors.payment_method && (
                                        <span className="text-xs text-rose-500 mt-1 block">{attachErrors.payment_method}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        الرقم المرجعي للمعاملة (اختياري)
                                    </label>
                                    <input
                                        type="text"
                                        value={attachData.payment_reference}
                                        onChange={(e) => setAttachData({ ...attachData, payment_reference: e.target.value })}
                                        placeholder="رقم العملية أو المرجع البنكي..."
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-right"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        صورة إيصال الدفع (jpg, png, jpeg)
                                    </label>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={handleFileChange}
                                        className="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm file:ml-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all text-right"
                                    />
                                    {attachErrors.receipt_file && (
                                        <span className="text-xs text-rose-500 mt-1 block">{attachErrors.receipt_file}</span>
                                    )}
                                </div>

                                <div className="mt-6 flex flex-row-reverse gap-2">
                                    <button
                                        type="submit"
                                        disabled={attaching}
                                        className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-bold text-white hover:bg-indigo-700 sm:text-sm disabled:opacity-50"
                                    >
                                        {attaching ? 'جاري الإرسال...' : 'إرفاق الإيصال'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowAttachModal(false);
                                            setAttachErrors({});
                                        }}
                                        className="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:text-sm"
                                    >
                                        إلغاء
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </SuperAdminLayout>
    );
}
