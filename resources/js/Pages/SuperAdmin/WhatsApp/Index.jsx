import React, { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';

export default function WhatsAppIndex({ settings = {}, summary = {}, merchants = [] }) {
    const { flash, errors } = usePage().props;

    const [form, setForm] = useState({
        meta_phone_number_id: settings.meta_phone_number_id || '',
        meta_waba_id: settings.meta_waba_id || '',
        meta_access_token: settings.meta_access_token || '',
        meta_template_name: settings.meta_template_name || 'order_confirmation',
        meta_template_language: settings.meta_template_language || 'ar',
        meta_webhook_verify_token: settings.meta_webhook_verify_token || 'fastorder_wa_secret_2026',
        meta_cost_per_order: settings.meta_cost_per_order || 1.00,
    });

    const [testPhone, setTestPhone] = useState('');
    const [testLoading, setTestLoading] = useState(false);
    const [testResult, setTestResult] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [savingSettings, setSavingSettings] = useState(false);

    const handleSaveSettings = (e) => {
        e.preventDefault();
        setSavingSettings(true);
        router.post('/whatsapp-gateway', form, {
            preserveScroll: true,
            onFinish: () => setSavingSettings(false),
        });
    };

    const handleSendTestMessage = async (e) => {
        e.preventDefault();
        if (!testPhone) return;

        setTestLoading(true);
        setTestResult(null);

        try {
            const response = await fetch('/whatsapp-gateway/test-message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ phone: testPhone }),
            });

            const data = await response.json();
            setTestResult(data);
        } catch (err) {
            setTestResult({ success: false, message: 'حدث خطأ أثناء إرسال الرسالة التجريبية.' });
        } finally {
            setTestLoading(false);
        }
    };

    const handleToggleMerchant = (tenantId, tenantName, currentStatus) => {
        const actionText = currentStatus ? 'إيقاف' : 'تفعيل';
        if (!confirm(`هل أنت متأكد من ${actionText} خدمة التأكيد التلقائي لمتجر (${tenantName})؟`)) return;
        router.post(`/whatsapp-gateway/merchants/${tenantId}/toggle`, {}, {
            preserveScroll: true,
        });
    };

    const filteredMerchants = merchants.filter((m) =>
        m.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        m.subdomain.toLowerCase().includes(searchQuery.toLowerCase()) ||
        m.owner_name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    return (
        <SuperAdminLayout>
            <Head title="بوابة الواتساب والتأكيد التلقائي" />

            <div className="space-y-6 pb-12" dir="rtl">
                {/* Header Banner */}
                <div className="bg-gradient-to-r from-emerald-950 via-teal-900 to-indigo-950 rounded-3xl p-6 md:p-8 text-white shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden">
                    <div className="relative z-10 space-y-2 max-w-2xl">
                        <div className="inline-flex items-center gap-2 px-3 py-1 bg-emerald-500/20 rounded-full text-xs font-semibold text-emerald-300 border border-emerald-400/30">
                            <span>⚡</span>
                            <span>البوابة المركزية الرسمية (Meta WhatsApp Cloud API)</span>
                        </div>
                        <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight">
                            إدارة بوابة الواتساب والتأكيد التلقائي
                        </h1>
                        <p className="text-emerald-100/80 text-xs md:text-sm leading-relaxed">
                            التحكم الكامل في ربط Meta API المركزي، وتتبع استخدام التجار لخدمة التأكيد التلقائي، ومتابعة الرسوم المحصلة والأرباح.
                        </p>
                    </div>

                    <div className="relative z-10 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center shrink-0 min-w-[220px]">
                        <div className="text-xs text-emerald-200 font-medium">حالة الاتصال المركزي بميتا</div>
                        <div className="text-base font-black mt-1 text-emerald-300 flex items-center justify-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>{settings.is_configured ? 'متصل وجاهز للإرسال' : 'في وضع الاختبار (Sandbox)'}</span>
                        </div>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-sm font-medium flex items-center gap-2 shadow-sm">
                        {flash.success}
                    </div>
                )}

                {/* Summary Metrics Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm space-y-2">
                        <div className="text-xs font-semibold text-gray-500">المتاجر المفعلة للخدمة</div>
                        <div className="text-2xl font-black text-gray-900 flex items-baseline gap-2">
                            <span>{summary.total_merchants_enabled || 0}</span>
                            <span className="text-xs font-medium text-gray-400">من إجمالي {summary.total_merchants || 0} متجر</span>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm space-y-2">
                        <div className="text-xs font-semibold text-gray-500">إجمالي رسائل التأكيد المرسلة</div>
                        <div className="text-2xl font-black text-indigo-600">
                            {summary.total_messages_all_time || 0}
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm space-y-2">
                        <div className="text-xs font-semibold text-gray-500">الأوردرات المؤكدة عبر الواتس</div>
                        <div className="text-2xl font-black text-emerald-600 flex items-baseline gap-2">
                            <span>{summary.total_confirmed_all_time || 0}</span>
                            <span className="text-xs font-bold text-emerald-700">({summary.confirmation_rate || 0}%)</span>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50 p-5 shadow-sm space-y-2">
                        <div className="text-xs font-bold text-emerald-800">إجمالي الرسوم المحصلة</div>
                        <div className="text-2xl font-black text-emerald-900">
                            {(summary.total_revenue_egp || 0).toLocaleString()} ج.م
                        </div>
                    </div>
                </div>

                {/* Main Settings & Test Sandbox Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    {/* Left 7/12: Meta API Settings Form */}
                    <div className="lg:col-span-7 bg-white rounded-3xl border border-gray-200 shadow-sm p-6 space-y-5">
                        <div className="border-b border-gray-100 pb-3 flex items-center justify-between">
                            <div>
                                <h2 className="text-base font-bold text-gray-900">بيانات ومفاتيح Meta WhatsApp Cloud API</h2>
                                <p className="text-xs text-gray-500 mt-0.5">أدخل بيانات التطبيق من لوحة Meta for Developers</p>
                            </div>
                            <span className="text-xl">🔑</span>
                        </div>

                        <form onSubmit={handleSaveSettings} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">
                                        Phone Number ID (معرف رقم الهاتف):
                                    </label>
                                    <input
                                        type="text"
                                        value={form.meta_phone_number_id}
                                        onChange={(e) => setForm({ ...form, meta_phone_number_id: e.target.value })}
                                        placeholder="مثال: 102938475610293"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">
                                        WhatsApp Business Account ID (WABA ID):
                                    </label>
                                    <input
                                        type="text"
                                        value={form.meta_waba_id}
                                        onChange={(e) => setForm({ ...form, meta_waba_id: e.target.value })}
                                        placeholder="مثال: 987654321098765"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1">
                                    Permanent Access Token (التوكن الدائم - System User):
                                </label>
                                <input
                                    type="password"
                                    value={form.meta_access_token}
                                    onChange={(e) => setForm({ ...form, meta_access_token: e.target.value })}
                                    placeholder="EAAB..."
                                    className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-right"
                                />
                                <span className="text-[10px] text-gray-400 mt-1 block">
                                    اترك الحقل كما هو إذا لم ترغب بتغيير التوكن الحالي المخزن.
                                </span>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">
                                        Template Name (اسم القالب):
                                    </label>
                                    <input
                                        type="text"
                                        value={form.meta_template_name}
                                        onChange={(e) => setForm({ ...form, meta_template_name: e.target.value })}
                                        placeholder="order_confirmation"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">
                                        اللغة (Language Code):
                                    </label>
                                    <input
                                        type="text"
                                        value={form.meta_template_language}
                                        onChange={(e) => setForm({ ...form, meta_template_language: e.target.value })}
                                        placeholder="ar"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">
                                        التعريفة على التاجر (ج.م):
                                    </label>
                                    <input
                                        type="number"
                                        step="0.25"
                                        min="0"
                                        value={form.meta_cost_per_order}
                                        onChange={(e) => setForm({ ...form, meta_cost_per_order: Number(e.target.value) })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500"
                                        required
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1">
                                    Webhook Verify Token (كلمة سر الويب هوك):
                                </label>
                                <input
                                    type="text"
                                    value={form.meta_webhook_verify_token}
                                    onChange={(e) => setForm({ ...form, meta_webhook_verify_token: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500"
                                    required
                                />
                            </div>

                            <button
                                type="submit"
                                disabled={savingSettings}
                                className="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-bold shadow-md transition-colors disabled:opacity-60 flex items-center justify-center gap-2"
                            >
                                <span>💾</span>
                                <span>{savingSettings ? 'جاري الحفظ...' : 'حفظ إعدادات بوابة الواتساب'}</span>
                            </button>
                        </form>
                    </div>

                    {/* Right 5/12: Webhook Endpoint & Live Test Message */}
                    <div className="lg:col-span-5 space-y-6">
                        {/* Webhook Info Card */}
                        <div className="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 space-y-4">
                            <div className="flex items-center gap-2 text-gray-900 font-bold text-sm">
                                <span>🌐</span>
                                <span>رابط الـ Webhook الخاص بالسيرفر</span>
                            </div>
                            <p className="text-xs text-gray-500 leading-relaxed">
                                انسخ هذا الرابط وضعه في إعدادات Webhook داخل Meta App لاستقبال تأكيدات وإلغاءات العملاء فورياً:
                            </p>

                            <div className="p-3 bg-gray-50 border border-gray-200 rounded-2xl space-y-2">
                                <div className="text-[11px] font-semibold text-gray-500">Callback URL:</div>
                                <div className="font-mono text-xs text-indigo-700 select-all bg-white p-2 rounded-xl border border-gray-200 break-all dir-ltr text-left">
                                    {settings.webhook_url}
                                </div>

                                <div className="text-[11px] font-semibold text-gray-500 pt-1">Verify Token:</div>
                                <div className="font-mono text-xs text-gray-800 select-all bg-white p-2 rounded-xl border border-gray-200 break-all dir-ltr text-left">
                                    {form.meta_webhook_verify_token}
                                </div>
                            </div>
                        </div>

                        {/* Test Message Card */}
                        <div className="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-3xl border border-emerald-200 shadow-sm p-6 space-y-4">
                            <div className="flex items-center gap-2 text-emerald-950 font-bold text-sm">
                                <span>📲</span>
                                <span>إرسال رسالة تجريبية للاختبار (Test Message)</span>
                            </div>
                            <p className="text-xs text-emerald-900/80 leading-relaxed">
                                اختبر الربط الآن بإرسال رسالة تجريبية لرقمك للتأكد من وصول الرسائل بنجاح:
                            </p>

                            <form onSubmit={handleSendTestMessage} className="space-y-3">
                                <div>
                                    <input
                                        type="tel"
                                        value={testPhone}
                                        onChange={(e) => setTestPhone(e.target.value)}
                                        placeholder="رقم الهاتف (مثال: 01012345678)"
                                        className="w-full px-3.5 py-2.5 bg-white border border-emerald-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500"
                                        required
                                    />
                                </div>

                                <button
                                    type="submit"
                                    disabled={testLoading}
                                    className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors disabled:opacity-60 flex items-center justify-center gap-1.5"
                                >
                                    <span>🚀</span>
                                    <span>{testLoading ? 'جاري الإرسال...' : 'إرسال رسالة تجريبية الآن'}</span>
                                </button>
                            </form>

                            {testResult && (
                                <div className={`p-3 rounded-xl text-xs font-medium border ${
                                    testResult.success 
                                        ? 'bg-emerald-100 border-emerald-300 text-emerald-900' 
                                        : 'bg-red-100 border-red-300 text-red-900'
                                }`}>
                                    {testResult.message}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Merchants WhatsApp Billing & Usage Table */}
                <div className="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden space-y-4 p-6">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                        <div>
                            <h2 className="text-base font-bold text-gray-900 flex items-center gap-2">
                                <span>📊</span>
                                <span>تقرير استهلاك المتاجر والمستحقات المالية</span>
                            </h2>
                            <p className="text-xs text-gray-500 mt-0.5">
                                مراقبة عدد رسائل كل متجر، الأوردرات المؤكدة والملغية، وإجمالي الرسوم المستحقة (1ج/أوردر)
                            </p>
                        </div>

                        <div className="w-full md:w-72">
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="بحث باسم المتجر أو التاجر..."
                                className="w-full px-3.5 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="w-full text-right text-xs">
                            <thead className="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                                <tr>
                                    <th className="py-3 px-4">المتجر / التاجر</th>
                                    <th className="py-3 px-4">حالة الخدمة</th>
                                    <th className="py-3 px-4 text-center">الرسائل المرسلة</th>
                                    <th className="py-3 px-4 text-center">مؤكد عبر الواتس</th>
                                    <th className="py-3 px-4 text-center">ملغي عبر الواتس</th>
                                    <th className="py-3 px-4 text-left">إجمالي الرسوم (ج.م)</th>
                                    <th className="py-3 px-4 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {filteredMerchants.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="py-8 text-center text-gray-400 font-medium">
                                            لا توجد متاجر مطابقة للبحث.
                                        </td>
                                    </tr>
                                ) : (
                                    filteredMerchants.map((merchant) => (
                                        <tr key={merchant.id} className="hover:bg-gray-50/80 transition-colors">
                                            <td className="py-3.5 px-4">
                                                <div className="font-bold text-gray-900">{merchant.name}</div>
                                                <div className="text-[11px] text-gray-500 flex items-center gap-2 mt-0.5">
                                                    <span>👤 {merchant.owner_name}</span>
                                                    <span>•</span>
                                                    <span className="font-mono text-indigo-600">{merchant.subdomain}.fast-order-eg.tech</span>
                                                </div>
                                            </td>

                                            <td className="py-3.5 px-4">
                                                <span className={`px-2.5 py-1 rounded-full text-[11px] font-bold ${
                                                    merchant.is_auto_confirm_enabled 
                                                        ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' 
                                                        : 'bg-gray-100 text-gray-500'
                                                }`}>
                                                    {merchant.is_auto_confirm_enabled ? 'مفعلة' : 'غير مفعلة'}
                                                </span>
                                            </td>

                                            <td className="py-3.5 px-4 text-center font-bold text-gray-800">
                                                {merchant.total_messages}
                                            </td>

                                            <td className="py-3.5 px-4 text-center font-bold text-emerald-600">
                                                {merchant.confirmed_count}
                                            </td>

                                            <td className="py-3.5 px-4 text-center font-bold text-red-500">
                                                {merchant.cancelled_count}
                                            </td>

                                            <td className="py-3.5 px-4 text-left font-black text-indigo-900">
                                                {merchant.total_charges_egp.toLocaleString()} ج.م
                                            </td>

                                            <td className="py-3.5 px-4 text-center">
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleMerchant(merchant.id, merchant.name, merchant.is_auto_confirm_enabled)}
                                                    className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-colors ${
                                                        merchant.is_auto_confirm_enabled
                                                            ? 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200'
                                                            : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200'
                                                    }`}
                                                >
                                                    {merchant.is_auto_confirm_enabled ? 'إيقاف للتاجر' : 'تفعيل للتاجر'}
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </SuperAdminLayout>
    );
}
