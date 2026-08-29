import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function ShippingGatewaysIndex({ providers = [], autoDispatch = {} }) {
    const { flash, errors } = usePage().props;
    const [selectedProvider, setSelectedProvider] = useState(null);
    const [loading, setLoading] = useState(false);
    const [savingAutoDispatch, setSavingAutoDispatch] = useState(false);
    const [imageErrors, setImageErrors] = useState({});

    // Auto-dispatch state
    const [autoDispatchForm, setAutoDispatchForm] = useState({
        enabled: autoDispatch.enabled ?? false,
        provider: autoDispatch.provider ?? 'bosta',
        trigger: autoDispatch.trigger ?? 'on_confirm',
    });

    // Form inputs state
    const [apiKeyInput, setApiKeyInput] = useState('');
    
    // Aramex specific state
    const [aramexForm, setAramexForm] = useState({
        account_number: '',
        user_name: '',
        password: '',
        account_pin: '',
        account_entity: 'CAI',
    });

    // J&T specific state
    const [jntForm, setJntForm] = useState({
        customer_code: '',
        api_password: '',
        private_key: '',
    });

    const handleOpenModal = (provider) => {
        setSelectedProvider(provider);
        setApiKeyInput('');
        setAramexForm({
            account_number: '',
            user_name: '',
            password: '',
            account_pin: '',
            account_entity: 'CAI',
        });
        setJntForm({
            customer_code: '',
            api_password: '',
            private_key: '',
        });
    };

    const hasActiveGateways = providers.some((p) => p.is_active);

    const handleToggleAutoDispatch = () => {
        if (!hasActiveGateways && !autoDispatchForm.enabled) {
            alert('يرجى ربط وتفعيل شركة شحن واحدة على الأقل أولاً (بوسطة / J&T / أرامكس) لتتمكن من تفعيل التحويل التلقائي.');
            return;
        }

        const nextState = !autoDispatchForm.enabled;
        setAutoDispatchForm((prev) => ({ ...prev, enabled: nextState }));
        setSavingAutoDispatch(true);
        router.post('/admin/shipping-gateways/auto-dispatch', {
            ...autoDispatchForm,
            enabled: nextState,
        }, {
            preserveScroll: true,
            onFinish: () => setSavingAutoDispatch(false),
        });
    };

    const handleAutoDispatchSubmit = (e) => {
        e.preventDefault();
        if (!hasActiveGateways) {
            alert('يرجى ربط وتفعيل شركة شحن أولاً.');
            return;
        }
        setSavingAutoDispatch(true);
        router.post('/admin/shipping-gateways/auto-dispatch', autoDispatchForm, {
            preserveScroll: true,
            onFinish: () => setSavingAutoDispatch(false),
        });
    };

    const handleConnectSubmit = (e) => {
        e.preventDefault();
        if (!selectedProvider) return;

        setLoading(true);

        if (selectedProvider.connect_type === 'bosta_api' || selectedProvider.connect_type === 'api_key') {
            router.post('/admin/shipping-gateways/connect-api-key', {
                provider: selectedProvider.id,
                api_key: apiKeyInput,
            }, {
                onFinish: () => setLoading(false),
                onSuccess: () => setSelectedProvider(null),
            });
        } else if (selectedProvider.connect_type === 'aramex_api') {
            router.post('/admin/shipping-gateways/connect-aramex', aramexForm, {
                onFinish: () => setLoading(false),
                onSuccess: () => setSelectedProvider(null),
            });
        } else if (selectedProvider.connect_type === 'jnt_api') {
            router.post('/admin/shipping-gateways/connect-jnt', jntForm, {
                onFinish: () => setLoading(false),
                onSuccess: () => setSelectedProvider(null),
            });
        }
    };

    const handleDisconnect = (providerId) => {
        if (!confirm('هل أنت متأكد من إلغاء ربط شركة الشحن؟')) return;
        router.patch(`/admin/shipping-gateways/${providerId}/toggle`);
    };

    const handleImageError = (providerId) => {
        setImageErrors((prev) => ({ ...prev, [providerId]: true }));
    };

    return (
        <MerchantLayout title="ربط شركات الشحن">
            <Head title="ربط شركات الشحن" />

            <div className="max-w-6xl mx-auto space-y-6" dir="rtl">
                {/* Header Banner */}
                <div className="bg-gradient-to-r from-indigo-900 via-indigo-800 to-blue-900 rounded-2xl p-6 md:p-8 text-white shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden">
                    <div className="relative z-10 space-y-2 max-w-2xl">
                        <div className="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-xs font-medium text-amber-300 border border-white/10">
                            🚚 شركاء الشحن المعتمدون
                        </div>
                        <h1 className="text-2xl md:text-3xl font-bold tracking-tight flex items-center gap-3 flex-wrap">
                            <span>إدارة وربط شركات الشحن التلقائي</span>
                            <span className="px-3 py-1 bg-emerald-500 text-white text-xs font-extrabold rounded-full shadow-sm">
                                متاح ومفعّل
                            </span>
                        </h1>
                        <p className="text-indigo-200 text-sm leading-relaxed">
                            اربط متجرك مباشرة مع كبرى شركات الشحن (بوسطة، أرامكس، J&T Express) لإنشاء بوليصات الشحن وتتبع الشحنات وإرسال الطلبات تلقائياً بضغطة زر.
                        </p>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm font-medium flex items-center gap-2 shadow-sm">
                        {flash.success}
                    </div>
                )}

                {/* Auto-Dispatch Settings Card */}
                <div className="bg-white rounded-2xl border border-indigo-100 shadow-sm p-5 md:p-6 overflow-hidden relative">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                        <div className="flex items-start gap-3">
                            <div className="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-xl shrink-0">
                                🚀
                            </div>
                            <div>
                                <h2 className="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <span>التحويل التلقائي للطلبات لشركة الشحن</span>
                                    {autoDispatchForm.enabled && (
                                        <span className="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[11px] font-bold rounded-full">
                                            مفعّل تلقائياً
                                        </span>
                                    )}
                                </h2>
                                <p className="text-xs text-gray-500 mt-0.5">
                                    تفعيل إرسال بيانات الشحنة وتوليد البوليصة تلقائياً فوراً دون الحاجة للدخول على كل طلب يدوياً.
                                </p>
                            </div>
                        </div>

                        {/* Toggle switch */}
                        <div className="flex items-center gap-3">
                            <span className="text-xs font-semibold text-gray-700">
                                {savingAutoDispatch 
                                    ? 'جاري التحديث...' 
                                    : (autoDispatchForm.enabled && hasActiveGateways ? 'التحويل التلقائي مفعّل' : 'التحويل التلقائي معطّل')}
                            </span>
                            <button
                                type="button"
                                dir="ltr"
                                disabled={savingAutoDispatch}
                                onClick={handleToggleAutoDispatch}
                                className={`relative inline-flex h-6 w-12 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-50 ${
                                    autoDispatchForm.enabled && hasActiveGateways
                                        ? 'bg-emerald-600 justify-end'
                                        : 'bg-gray-300 justify-start'
                                } flex items-center`}
                                title={!hasActiveGateways ? 'يرجى ربط شركة شحن أولاً' : ''}
                            >
                                <span className="h-5 w-5 rounded-full bg-white shadow-md transition-transform duration-200" />
                            </button>
                        </div>
                    </div>

                    {!hasActiveGateways && (
                        <div className="mt-3 p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-800 text-xs flex items-center gap-2">
                            <span>⚠️</span>
                            <span>التحويل التلقائي معطل حالياً لأنه لا توجد أي شركة شحن مربوطة. قم بالضغط على <strong>"إدخال بيانات الـ API والربط"</strong> لأي شركة بالأسفل لتفعيلها.</span>
                        </div>
                    )}

                    {autoDispatchForm.enabled && hasActiveGateways && (
                        <form onSubmit={handleAutoDispatchSubmit} className="mt-4 pt-2 grid grid-cols-1 md:grid-cols-3 gap-4 items-end animate-in fade-in duration-200">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                                    شركة الشحن الافتراضية للتحويل التلقائي:
                                </label>
                                <select
                                    value={autoDispatchForm.provider}
                                    onChange={(e) => setAutoDispatchForm({ ...autoDispatchForm, provider: e.target.value })}
                                    className="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500"
                                >
                                    {providers.filter((p) => p.is_active).map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} (مفعلة ✓)
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                                    موعد إرسال وتوليد الشحنة تلقائياً:
                                </label>
                                <select
                                    value={autoDispatchForm.trigger}
                                    onChange={(e) => setAutoDispatchForm({ ...autoDispatchForm, trigger: e.target.value })}
                                    className="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="on_confirm">عند تأكيد الطلب (موصى به للمراجعة)</option>
                                    <option value="on_create">فور استلام الطلب الجديد من المتجر مباشرة ⚡</option>
                                </select>
                            </div>

                            <div>
                                <button
                                    type="submit"
                                    disabled={savingAutoDispatch}
                                    className="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm disabled:opacity-60 flex items-center justify-center gap-1.5"
                                >
                                    <span>💾</span>
                                    <span>{savingAutoDispatch ? 'جاري الحفظ...' : 'حفظ إعدادات التحويل التلقائي'}</span>
                                </button>
                            </div>
                        </form>
                    )}
                </div>

                {/* Gateway Cards Grid */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {providers.map((p) => (
                        <div key={p.id} className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden">
                            <div className="space-y-4">
                                {/* Header: Logo & Status Badge */}
                                <div className="flex items-center justify-between gap-3 h-14 border-b border-gray-100 pb-3">
                                    <div className="flex items-center gap-3">
                                        {!imageErrors[p.id] && p.logo ? (
                                            <img
                                                src={p.logo}
                                                alt={p.name}
                                                onError={() => handleImageError(p.id)}
                                                className="h-10 max-w-[130px] object-contain"
                                            />
                                        ) : (
                                            <div className="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center font-bold text-indigo-700 text-lg shadow-inner">
                                                {p.id === 'bosta' ? '📦' : (p.id === 'jnt' ? '⚡' : '🔴')}
                                            </div>
                                        )}
                                    </div>

                                    <span className={`px-2.5 py-1 text-xs font-semibold rounded-full shrink-0 ${
                                        p.is_active ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600'
                                    }`}>
                                        {p.is_active ? 'مفعلة' : 'غير مفعلة'}
                                    </span>
                                </div>

                                {/* Title & Description */}
                                <div>
                                    <h3 className="text-lg font-bold text-gray-900">{p.name}</h3>
                                    <p className="text-xs text-gray-500 mt-1 leading-relaxed">{p.description}</p>

                                    {/* Connected Account Display */}
                                    {p.is_active && p.connected_account && (
                                        <div className="mt-3 p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-semibold flex items-center gap-1.5 shadow-sm">
                                            <span>🔗 الاتصال المفعّل:</span>
                                            <span className="font-mono text-[11px] truncate dir-ltr max-w-[180px]">
                                                {p.connected_account.length > 25 
                                                    ? p.connected_account.substring(0, 10) + '...' + p.connected_account.substring(p.connected_account.length - 8)
                                                    : p.connected_account}
                                            </span>
                                        </div>
                                    )}
                                </div>

                                {/* Useful External Links: Direct Official Website */}
                                <div className="flex flex-col gap-2 pt-2">
                                    {p.website_url && (
                                        <a
                                            href={p.website_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-between px-3.5 py-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl text-xs text-gray-800 font-bold transition-colors shadow-2xs"
                                        >
                                            <span className="flex items-center gap-2">
                                                <span>🌐</span>
                                                <span>الموقع الرسمي لـ {p.name}</span>
                                            </span>
                                            <span className="text-indigo-600 font-semibold text-xs">زيارة الموقع ↗</span>
                                        </a>
                                    )}

                                    {p.pricing_url && (
                                        <a
                                            href={p.pricing_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-between px-3 py-2 bg-amber-50 hover:bg-amber-100/80 border border-amber-200 rounded-xl text-xs text-amber-800 font-medium transition-colors"
                                        >
                                            <span className="flex items-center gap-1.5">
                                                <span>💰</span>
                                                <span>عرض الأسعار والتتبع</span>
                                            </span>
                                            <svg className="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    )}
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div className="pt-4 border-t border-gray-100 mt-5">
                                {!p.is_active ? (
                                    <button
                                        onClick={() => handleOpenModal(p)}
                                        className="w-full py-3 px-4 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm flex items-center justify-center gap-2"
                                    >
                                        <span>🔑</span>
                                        <span>إدخال بيانات الـ API والربط</span>
                                    </button>
                                ) : (
                                    <div className="flex gap-2">
                                        <button
                                            onClick={() => handleOpenModal(p)}
                                            className="flex-1 py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition-colors"
                                        >
                                            تعديل الربط ⚙️
                                        </button>
                                        <button
                                            onClick={() => handleDisconnect(p.id)}
                                            className="py-2 px-3 rounded-xl text-xs font-semibold border border-red-200 text-red-600 hover:bg-red-50 transition-colors flex items-center justify-center"
                                        >
                                            إلغاء الربط ✕
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Modal for Connect Flow (API Credentials for each Carrier) */}
            {selectedProvider && (
                <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
                    <div className="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl relative animate-in fade-in zoom-in duration-150">
                        <div className="flex items-center justify-between border-b pb-3">
                            <div className="flex items-center gap-2">
                                {selectedProvider.logo && !imageErrors[selectedProvider.id] && (
                                    <img src={selectedProvider.logo} alt="" className="h-6 object-contain" />
                                )}
                                <h3 className="text-lg font-bold text-gray-900">ربط وتفعيل {selectedProvider.name}</h3>
                            </div>
                            <button onClick={() => setSelectedProvider(null)} className="text-gray-400 hover:text-gray-600 text-lg font-bold">✕</button>
                        </div>

                        <form onSubmit={handleConnectSubmit} className="space-y-4">
                            {/* BOSTA API MODAL */}
                            {selectedProvider.id === 'bosta' && (
                                <div className="space-y-3">
                                    <div className="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900 leading-relaxed font-medium">
                                        ℹ️ {selectedProvider.api_key_note || 'برجاء إضافة رقم API لربط تطبيق بوسطة بالمتجر الخاص بكم'}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-1">
                                            مفتاح API الخاص بحسابك (API Key) <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={apiKeyInput}
                                            onChange={(e) => setApiKeyInput(e.target.value)}
                                            placeholder="أدخل رمز الـ API Key الخاص بك هنا..."
                                            className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 dir-ltr text-left"
                                        />
                                        {errors?.api_key && <p className="text-xs text-red-600 mt-1">{errors.api_key}</p>}
                                    </div>

                                    <p className="text-xs text-gray-500 leading-relaxed">
                                        💡 يمكنك الحصول على مفتاح الـ API من داخل حسابك في لوحة تحكم بوسطة عبر: <strong>الإعدادات &gt; الربط البرمجي (Integrations / API Keys)</strong>.
                                    </p>
                                </div>
                            )}

                            {/* ARAMEX API MODAL */}
                            {selectedProvider.id === 'aramex' && (
                                <div className="space-y-3">
                                    <div className="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 leading-relaxed font-medium">
                                        ℹ️ يتم الحصول على بيانات الـ API الرسمية من مدير حسابك في أرامكس (Account Manager).
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-700 mb-1">
                                                رقم الحساب (Account Number) <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                required
                                                value={aramexForm.account_number}
                                                onChange={(e) => setAramexForm({ ...aramexForm, account_number: e.target.value })}
                                                placeholder="مثال: 123456"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 dir-ltr text-left"
                                            />
                                            {errors?.account_number && <p className="text-xs text-red-600 mt-1">{errors.account_number}</p>}
                                        </div>

                                        <div>
                                            <label className="block text-xs font-semibold text-gray-700 mb-1">
                                                رمز الأمان (Account PIN) <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                required
                                                value={aramexForm.account_pin}
                                                onChange={(e) => setAramexForm({ ...aramexForm, account_pin: e.target.value })}
                                                placeholder="مثال: 331421"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 dir-ltr text-left"
                                            />
                                            {errors?.account_pin && <p className="text-xs text-red-600 mt-1">{errors.account_pin}</p>}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-700 mb-1">
                                                اسم المستخدم (User Name) <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                required
                                                value={aramexForm.user_name}
                                                onChange={(e) => setAramexForm({ ...aramexForm, user_name: e.target.value })}
                                                placeholder="اسم المستخدم في أرامكس..."
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 dir-ltr text-left"
                                            />
                                            {errors?.user_name && <p className="text-xs text-red-600 mt-1">{errors.user_name}</p>}
                                        </div>

                                        <div>
                                            <label className="block text-xs font-semibold text-gray-700 mb-1">
                                                كلمة المرور (Password) <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="password"
                                                required
                                                value={aramexForm.password}
                                                onChange={(e) => setAramexForm({ ...aramexForm, password: e.target.value })}
                                                placeholder="كلمة مرور API..."
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 dir-ltr text-left"
                                            />
                                            {errors?.password && <p className="text-xs text-red-600 mt-1">{errors.password}</p>}
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-700 mb-1">
                                            كود المحطة (Account Entity)
                                        </label>
                                        <input
                                            type="text"
                                            value={aramexForm.account_entity}
                                            onChange={(e) => setAramexForm({ ...aramexForm, account_entity: e.target.value })}
                                            placeholder="CAI (القاهرة)"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 dir-ltr text-left"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* J&T EXPRESS API MODAL */}
                            {selectedProvider.id === 'jnt' && (
                                <div className="space-y-3">
                                    <div className="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-900 leading-relaxed font-medium">
                                        ℹ️ يتم تسليم مفاتيح الربط البرمجي من خدمة عملاء J&T Express مصر (15885 / sales@jtexpress-eg.com) بعد توقيع العقد التجاري.
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-700 mb-1">
                                            كود العميل (Customer Code) <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={jntForm.customer_code}
                                            onChange={(e) => setJntForm({ ...jntForm, customer_code: e.target.value })}
                                            placeholder="أدخل كود العميل لدى J&T..."
                                            className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 dir-ltr text-left"
                                        />
                                        {errors?.customer_code && <p className="text-xs text-red-600 mt-1">{errors.customer_code}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-700 mb-1">
                                            كلمة سر الربط (API Password) <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="password"
                                            required
                                            value={jntForm.api_password}
                                            onChange={(e) => setJntForm({ ...jntForm, api_password: e.target.value })}
                                            placeholder="أدخل كلمة سر الـ API..."
                                            className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 dir-ltr text-left"
                                        />
                                        {errors?.api_password && <p className="text-xs text-red-600 mt-1">{errors.api_password}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-700 mb-1">
                                            المفتاح السري (Private Key) <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={jntForm.private_key}
                                            onChange={(e) => setJntForm({ ...jntForm, private_key: e.target.value })}
                                            placeholder="أدخل الـ Private Key..."
                                            className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 dir-ltr text-left"
                                        />
                                        {errors?.private_key && <p className="text-xs text-red-600 mt-1">{errors.private_key}</p>}
                                    </div>
                                </div>
                            )}

                            <div className="flex items-center justify-between text-xs pt-1">
                                {selectedProvider.website_url && (
                                    <a
                                        href={selectedProvider.website_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-indigo-600 hover:underline font-medium"
                                    >
                                        زيارة الموقع الرسمي لـ {selectedProvider.name} ↗
                                    </a>
                                )}
                                {selectedProvider.pricing_url && (
                                    <a
                                        href={selectedProvider.pricing_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-amber-700 hover:underline font-medium"
                                    >
                                        حاسبة الأسعار والتتبع ↗
                                    </a>
                                )}
                            </div>

                            <div className="flex items-center justify-end gap-2 pt-3 border-t">
                                <button
                                    type="button"
                                    onClick={() => setSelectedProvider(null)}
                                    className="px-4 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50"
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 shadow-sm disabled:opacity-60 flex items-center gap-2"
                                >
                                    {loading ? 'جاري التحقق والربط...' : 'حفظ وتفعيل الربط تلقائياً ⚡'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </MerchantLayout>
    );
}
