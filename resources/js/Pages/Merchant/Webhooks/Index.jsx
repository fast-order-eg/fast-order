import React, { useState } from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function WebhooksIndex({ webhooks, stats, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.q || '');
    const [editingWebhook, setEditingWebhook] = useState(null);
    const [showFormModal, setShowFormModal] = useState(false);
    const [showLogsModal, setShowLogsModal] = useState(false);
    const [selectedWebhookForLogs, setSelectedWebhookForLogs] = useState(null);
    const [logs, setLogs] = useState([]);
    const [loadingLogs, setLoadingLogs] = useState(false);
    const [testingWebhookId, setTestingWebhookId] = useState(null);

    const handleTestWebhook = (webhook) => {
        setTestingWebhookId(webhook.id);
        router.post(`/admin/webhooks/${webhook.id}/test`, {}, {
            preserveScroll: true,
            onFinish: () => setTestingWebhookId(null),
        });
    };

    // Form hook
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        url: '',
        secret: '',
        events: [],
        is_active: true,
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/webhooks', { q: search }, { preserveState: true, replace: true });
    };

    const generateSecret = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-$@#';
        let result = '';
        for (let i = 0; i < 32; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        setData('secret', result);
    };

    const openCreateModal = () => {
        reset();
        clearErrors();
        setEditingWebhook(null);
        setShowFormModal(true);
    };

    const openEditModal = (webhook) => {
        clearErrors();
        setEditingWebhook(webhook);
        setData({
            url: webhook.url || '',
            secret: webhook.secret || '',
            events: webhook.events || [],
            is_active: webhook.is_active !== undefined ? webhook.is_active : true,
        });
        setShowFormModal(true);
    };

    const openLogsModal = async (webhook) => {
        setSelectedWebhookForLogs(webhook);
        setShowLogsModal(true);
        setLogs([]);
        setLoadingLogs(true);
        try {
            const response = await fetch(`/admin/webhooks/${webhook.id}/logs`);
            if (response.ok) {
                const data = await response.json();
                setLogs(data);
            } else {
                console.error("Failed to load logs");
            }
        } catch (error) {
            console.error("Error loading logs:", error);
        } finally {
            setLoadingLogs(false);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingWebhook) {
            put(`/admin/webhooks/${editingWebhook.id}`, {
                onSuccess: () => {
                    setShowFormModal(false);
                    reset();
                },
            });
        } else {
            post('/admin/webhooks', {
                onSuccess: () => {
                    setShowFormModal(false);
                    reset();
                },
            });
        }
    };

    const handleDelete = (webhook) => {
        if (!confirm(`هل أنت متأكد من حذف الـ Webhook الخاص بـ "${webhook.url}"؟`)) return;
        router.delete(`/admin/webhooks/${webhook.id}`);
    };

    const handleToggle = (webhook) => {
        router.patch(`/admin/webhooks/${webhook.id}/toggle`, {}, { preserveScroll: true });
    };

    const handleEventCheckboxChange = (event, checked) => {
        let updatedEvents = [...data.events];
        if (checked) {
            if (!updatedEvents.includes(event)) {
                updatedEvents.push(event);
            }
        } else {
            updatedEvents = updatedEvents.filter(e => e !== event);
        }
        setData('events', updatedEvents);
    };

    const availableEvents = [
        { id: 'order.created', label: 'إنشاء طلب جديد (order.created)', desc: 'يتم إرساله فوراً عند قيام العميل بتأكيد طلب جديد بنجاح.' },
        { id: 'order.status_updated', label: 'تحديث حالة الطلب (order.status_updated)', desc: 'يتم إرساله عند تغيير حالة الطلب (تأكيد، شحن، تسليم، إلغاء).' },
        { id: 'product.created', label: 'إضافة منتج جديد (product.created)', desc: 'يتم إرساله عند إضافة منتج جديد في لوحة التحكم.' },
        { id: 'customer.created', label: 'إنشاء حساب عميل (customer.created)', desc: 'يتم إرساله عند تسجيل عميل جديد بالمتجر.' }
    ];

    return (
        <MerchantLayout title="إدارة الـ Webhooks والتكاملات">
            <Head title="الـ Webhooks والتكاملات" />

            <div className="space-y-6 text-right" dir="rtl">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">الـ Webhooks والتكاملات</h2>
                        <p className="text-sm text-gray-500 mt-0.5">
                            اربط متجرك بالتطبيقات والأنظمة الخارجية واحصل على بيانات الطلبات فورياً في نفس الثانية
                        </p>
                    </div>
                    <button
                        onClick={openCreateModal}
                        className="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-all duration-200 shadow-lg shadow-indigo-150 hover:-translate-y-0.5 cursor-pointer"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                        </svg>
                        إضافة Webhook جديد
                    </button>
                </div>

                {/* Explain Banner */}
                <div className="bg-gradient-to-r from-indigo-50 via-blue-50 to-purple-50 border border-indigo-150 rounded-2xl p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 shadow-xs">
                    <div className="flex items-start gap-3">
                        <div className="p-2.5 bg-indigo-600 text-white rounded-xl flex-shrink-0 text-xl shadow-xs">
                            ⚡
                        </div>
                        <div>
                            <h4 className="font-bold text-indigo-950 text-sm">ربط تلقائي وفوري للأوردرات مع تطبيقات الموبايل وبرامج الكاشير</h4>
                            <p className="text-xs text-indigo-900/80 mt-1 leading-relaxed">
                                ضع رابط الـ Webhook الخاص بتطبيقك وسيقوم فاست أوردر بإرسال بيانات الطلب كاملة (العميل، المنتجات، العنوان، الإجمالي) لتطبيقك لحظياً فور حدوثه بدون أي تأخير.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    {/* Stat Card 1 */}
                    <div className="bg-gradient-to-br from-white to-gray-50/50 p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group hover:shadow-md transition-all duration-300">
                        <div className="space-y-2">
                            <span className="text-sm text-gray-500 font-medium">إجمالي الـ Webhooks</span>
                            <h3 className="text-3xl font-bold text-gray-900 group-hover:scale-105 transition-transform duration-300 origin-right">
                                {stats.total}
                            </h3>
                        </div>
                        <div className="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                    </div>

                    {/* Stat Card 2 */}
                    <div className="bg-gradient-to-br from-white to-gray-50/50 p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group hover:shadow-md transition-all duration-300">
                        <div className="space-y-2">
                            <span className="text-sm text-gray-500 font-medium">الروابط النشطة</span>
                            <h3 className="text-3xl font-bold text-green-600 group-hover:scale-105 transition-transform duration-300 origin-right">
                                {stats.active}
                            </h3>
                        </div>
                        <div className="p-3 bg-green-50 text-green-600 rounded-xl">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    {/* Stat Card 3 */}
                    <div className="bg-gradient-to-br from-white to-gray-50/50 p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group hover:shadow-md transition-all duration-300">
                        <div className="space-y-2">
                            <span className="text-sm text-gray-500 font-medium">إجمالي محاولات الإرسال</span>
                            <h3 className="text-3xl font-bold text-amber-600 group-hover:scale-105 transition-transform duration-300 origin-right">
                                {stats.logs_count}
                            </h3>
                        </div>
                        <div className="p-3 bg-amber-50 text-amber-600 rounded-xl">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                </div>

                {/* Search Bar */}
                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
                    <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1 relative">
                            <svg className="absolute right-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                placeholder="ابحث برابط الاستقبال..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pr-10 pl-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            />
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="submit"
                                className="px-6 py-2.5 bg-gray-800 text-white rounded-xl text-sm font-medium hover:bg-gray-700 transition-colors shadow-sm"
                            >
                                بحث
                            </button>
                            {filters?.q && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSearch('');
                                        router.get('/admin/webhooks', {}, { replace: true });
                                    }}
                                    className="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm hover:bg-gray-50 transition-colors"
                                >
                                    إعادة تعيين
                                </button>
                            )}
                        </div>
                    </form>
                </div>

                {/* Flash Notifications */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border-r-4 border-green-500 rounded-xl text-green-800 text-sm font-semibold flex items-center gap-2.5 shadow-sm animate-in slide-in-from-top duration-300">
                        <span className="text-lg">✓</span>
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border-r-4 border-red-500 rounded-xl text-red-800 text-sm font-semibold flex items-center gap-2.5 shadow-sm animate-in slide-in-from-top duration-300">
                        <span className="text-lg">⚠️</span>
                        <span>{flash.error}</span>
                    </div>
                )}

                {/* Webhooks Table */}
                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-right border-collapse">
                            <thead>
                                <tr className="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th className="px-6 py-4">رابط الاستقبال (URL)</th>
                                    <th className="px-6 py-4">الأحداث المفعلة</th>
                                    <th className="px-6 py-4">تاريخ الإضافة</th>
                                    <th className="px-6 py-4 text-center">الحالة</th>
                                    <th className="px-6 py-4 text-left">العمليات</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 text-sm text-gray-750">
                                {webhooks.data.length > 0 ? (
                                    webhooks.data.map((webhook) => (
                                        <tr key={webhook.id} className="hover:bg-gray-50/40 transition-colors">
                                            {/* URL */}
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col gap-1 max-w-[320px] sm:max-w-md">
                                                    <span className="font-mono text-gray-900 truncate select-all" title={webhook.url}>
                                                        {webhook.url}
                                                    </span>
                                                    <div className="flex items-center gap-1.5 mt-0.5">
                                                        <span className="text-xs text-gray-400 font-medium">Secret: </span>
                                                        <span className="text-xs font-mono text-gray-500 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100 select-all">
                                                            {webhook.secret.substring(0, 8)}...
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>

                                            {/* Events */}
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap gap-1 max-w-[300px]">
                                                    {webhook.events && webhook.events.map((ev, idx) => (
                                                        <span key={idx} className="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100 font-mono">
                                                            {ev}
                                                        </span>
                                                    ))}
                                                    {(!webhook.events || webhook.events.length === 0) && (
                                                        <span className="text-xs text-gray-400">لا يوجد أحداث</span>
                                                    )}
                                                </div>
                                            </td>

                                            {/* Date */}
                                            <td className="px-6 py-4 text-gray-500 text-xs">
                                                {new Date(webhook.created_at).toLocaleDateString('en-US', {
                                                    year: 'numeric',
                                                    month: 'long',
                                                    day: 'numeric'
                                                })}
                                            </td>

                                            {/* Status Switch */}
                                            <td className="px-6 py-4 text-center">
                                                <button
                                                    onClick={() => handleToggle(webhook)}
                                                    className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all ${
                                                        webhook.is_active
                                                            ? 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100'
                                                            : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100'
                                                    }`}
                                                >
                                                    <span className={`w-1.5 h-1.5 rounded-full ${webhook.is_active ? 'bg-green-500' : 'bg-red-500'}`} />
                                                    {webhook.is_active ? 'نشط' : 'معطل'}
                                                </button>
                                            </td>

                                            {/* Operations */}
                                            <td className="px-6 py-4 text-left">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleTestWebhook(webhook)}
                                                        disabled={testingWebhookId === webhook.id}
                                                        className="px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-semibold hover:bg-emerald-100 transition-colors flex items-center gap-1 disabled:opacity-50 cursor-pointer"
                                                        title="إرسال طلب وهمي لتجربة استجابة سيرفر/تطبيق العميل فوراً"
                                                    >
                                                        <span>🚀</span>
                                                        <span>{testingWebhookId === webhook.id ? 'جاري الإرسال...' : 'تجربة الإرسال'}</span>
                                                    </button>
                                                    <button
                                                        onClick={() => openLogsModal(webhook)}
                                                        className="px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-xs font-semibold hover:bg-amber-100 transition-colors flex items-center gap-1 cursor-pointer"
                                                    >
                                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                        </svg>
                                                        سجلات الإرسال
                                                    </button>
                                                    <button
                                                        onClick={() => openEditModal(webhook)}
                                                        className="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-semibold hover:bg-indigo-100 transition-colors cursor-pointer"
                                                    >
                                                        تعديل
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(webhook)}
                                                        className="px-3 py-1.5 bg-red-50 text-red-700 rounded-lg text-xs font-semibold hover:bg-red-100 transition-colors cursor-pointer"
                                                    >
                                                        حذف
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="5" className="px-6 py-12 text-center text-gray-400">
                                            لا توجد روابط Webhooks مضافة حالياً.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {webhooks.links && webhooks.links.length > 3 && (
                        <div className="bg-white border-t border-gray-100 px-6 py-4 flex justify-center gap-1.5">
                            {webhooks.links.map((link, idx) => (
                                <button
                                    key={idx}
                                    disabled={!link.url || link.active}
                                    onClick={() => router.get(link.url)}
                                    className={`px-3.5 py-1.5 rounded-lg text-xs font-medium border transition-colors ${
                                        link.active
                                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                                            : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
                                    } disabled:opacity-50`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Form */}
            {showFormModal && (
                <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto">
                    <div className="bg-white rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-200 text-right" dir="rtl">
                        <div className="h-16 bg-gray-50 border-b border-gray-100 px-6 flex items-center justify-between">
                            <h3 className="font-bold text-lg text-gray-900">
                                {editingWebhook ? 'تعديل الـ Webhook' : 'إضافة Webhook جديد'}
                            </h3>
                            <button
                                onClick={() => setShowFormModal(false)}
                                className="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            {/* URL */}
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-1">رابط استقبال البيانات (URL) <span className="text-red-500">*</span></label>
                                <input
                                    type="url"
                                    value={data.url}
                                    onChange={(e) => setData('url', e.target.value)}
                                    placeholder="https://example.com/webhook"
                                    className={`w-full px-3.5 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-left ${
                                        errors.url ? 'border-red-400 bg-red-50/30' : 'border-gray-300'
                                    }`}
                                />
                                {errors.url && <p className="text-xs text-red-600 mt-1">{errors.url}</p>}
                                <p className="text-xs text-gray-400 mt-1">يجب أن يكون الرابط مشفراً بـ HTTPS ومستعداً لتلقي طلبات POST من نوع JSON.</p>
                            </div>

                            {/* Secret */}
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-1">المفتاح السري (Secret Key) <span className="text-red-500">*</span></label>
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        value={data.secret}
                                        onChange={(e) => setData('secret', e.target.value)}
                                        placeholder="أدخل المفتاح أو اضغط لتوليد واحد تلقائياً"
                                        className={`flex-1 px-3.5 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-left ${
                                            errors.secret ? 'border-red-400 bg-red-50/30' : 'border-gray-300'
                                        }`}
                                    />
                                    <button
                                        type="button"
                                        onClick={generateSecret}
                                        className="px-4 py-2 bg-gray-100 text-gray-700 border border-gray-300 rounded-xl text-xs font-semibold hover:bg-gray-200 transition-colors cursor-pointer"
                                    >
                                        توليد عشوائي
                                    </button>
                                </div>
                                {errors.secret && <p className="text-xs text-red-600 mt-1">{errors.secret}</p>}
                                <p className="text-xs text-gray-400 mt-1">يُستخدم هذا المفتاح في ترويسة X-FastOrder-Signature لتوقيع البيانات والتأكد من موثوقية الطلب.</p>
                            </div>

                            {/* Events checkboxes */}
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-2">الأحداث المفعلة للاشتراك <span className="text-red-500">*</span></label>
                                {errors.events && <p className="text-xs text-red-600 mb-2">{errors.events}</p>}
                                <div className="space-y-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    {availableEvents.map((ev) => (
                                        <div key={ev.id} className="flex items-start gap-2.5">
                                            <input
                                                type="checkbox"
                                                id={`event-${ev.id}`}
                                                checked={data.events.includes(ev.id)}
                                                onChange={(e) => handleEventCheckboxChange(ev.id, e.target.checked)}
                                                className="w-4 h-4 text-indigo-600 border-gray-300 rounded-sm focus:ring-indigo-500 mt-1"
                                            />
                                            <label htmlFor={`event-${ev.id}`} className="select-none text-right cursor-pointer">
                                                <span className="block text-sm font-semibold text-gray-800 font-mono">{ev.label}</span>
                                                <span className="block text-xs text-gray-450 mt-0.5">{ev.desc}</span>
                                            </label>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Status switch */}
                            <div className="flex items-center gap-3 pt-2">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="w-4 h-4 text-indigo-600 border-gray-300 rounded-sm focus:ring-indigo-500"
                                />
                                <label htmlFor="is_active" className="text-sm font-semibold text-gray-700 select-none">
                                    تفعيل الـ Webhook فوراً لبدء إرسال البيانات
                                </label>
                            </div>

                            {/* Buttons */}
                            <div className="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button
                                    type="button"
                                    onClick={() => setShowFormModal(false)}
                                    className="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50"
                                >
                                    {processing ? 'جاري الحفظ...' : 'حفظ الـ Webhook'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Logs Modal */}
            {showLogsModal && (
                <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto">
                    <div className="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-200 text-right flex flex-col max-h-[85vh]" dir="rtl">
                        <div className="h-16 bg-gray-50 border-b border-gray-100 px-6 flex items-center justify-between shrink-0">
                            <div>
                                <h3 className="font-bold text-lg text-gray-900">
                                    سجل محاولات الإرسال
                                </h3>
                                <p className="text-xs text-gray-500 font-mono mt-0.5 truncate max-w-lg sm:max-w-xl text-left" dir="ltr">
                                    {selectedWebhookForLogs?.url}
                                </p>
                            </div>
                            <button
                                onClick={() => setShowLogsModal(false)}
                                className="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
                            >
                                ✕
                            </button>
                        </div>

                        {/* Logs body */}
                        <div className="p-6 overflow-y-auto flex-1 space-y-4">
                            {loadingLogs ? (
                                <div className="flex flex-col items-center justify-center py-12 gap-3">
                                    <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                                    <span className="text-sm text-gray-500 font-medium">جاري تحميل سجلات المكالمات...</span>
                                </div>
                            ) : logs.length > 0 ? (
                                <div className="space-y-4">
                                    {logs.map((log) => {
                                        const isSuccess = log.response_status >= 200 && log.response_status < 300;
                                        return (
                                            <div
                                                key={log.id}
                                                className={`border rounded-2xl overflow-hidden transition-all duration-200 ${
                                                    isSuccess
                                                        ? 'border-green-150 bg-green-50/20'
                                                        : 'border-red-150 bg-red-50/20'
                                                }`}
                                            >
                                                {/* Log Header */}
                                                <div className="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100/50">
                                                    <div className="flex items-center gap-3">
                                                        {/* Status code pill */}
                                                        <span className={`inline-flex items-center px-3 py-1 rounded-xl text-sm font-bold font-mono ${
                                                            isSuccess
                                                                ? 'bg-green-100 text-green-700'
                                                                : 'bg-red-100 text-red-700'
                                                        }`}>
                                                            {log.response_status || 'ERR'}
                                                        </span>
                                                        <div>
                                                            <span className="font-mono text-sm font-bold text-gray-800 bg-white px-2 py-0.5 rounded border border-gray-200">
                                                                {log.event}
                                                            </span>
                                                            <span className="text-xs text-gray-400 font-medium mr-2">
                                                                {log.created_at}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-4 text-xs font-semibold text-gray-600">
                                                        <div className="flex items-center gap-1">
                                                            <span>مدة الطلب:</span>
                                                            <span className="font-mono text-gray-900">{log.duration_ms} ms</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Log Details */}
                                                <div className="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-left font-mono text-xs">
                                                    {/* Payload sent */}
                                                    <div className="space-y-1">
                                                        <span className="text-xs font-semibold text-gray-700 block text-right font-sans">البيانات المرسلة (Payload):</span>
                                                        <div className="bg-gray-900 text-gray-100 p-3 rounded-xl overflow-x-auto max-h-48 text-left" dir="ltr">
                                                            <pre>{JSON.stringify(log.payload, null, 2)}</pre>
                                                        </div>
                                                    </div>

                                                    {/* Response received */}
                                                    <div className="space-y-1">
                                                        <span className="text-xs font-semibold text-gray-700 block text-right font-sans">استجابة السيرفر (Response):</span>
                                                        <div className={`p-3 rounded-xl overflow-x-auto max-h-48 text-left ${
                                                            isSuccess
                                                                ? 'bg-slate-900 text-slate-100'
                                                                : 'bg-red-950 text-red-100'
                                                        }`} dir="ltr">
                                                            <pre>{log.response_body || 'No response body returned.'}</pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="text-center py-16 text-gray-400">
                                    <svg className="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    لا توجد سجلات مكالمات أو إرسال سابقة لهذا الـ Webhook بعد.
                                </div>
                            )}
                        </div>

                        {/* Footer */}
                        <div className="h-16 bg-gray-50 border-t border-gray-100 px-6 flex items-center justify-end shrink-0">
                            <button
                                type="button"
                                onClick={() => setShowLogsModal(false)}
                                className="px-5 py-2.5 bg-gray-800 text-white rounded-xl text-sm font-medium hover:bg-gray-750 transition-colors shadow-sm"
                            >
                                إغلاق
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </MerchantLayout>
    );
}

