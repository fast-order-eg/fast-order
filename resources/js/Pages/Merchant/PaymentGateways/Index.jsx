import React, { useState } from 'react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';

export default function PaymentGatewaysIndex({ providers, savedGateways }) {
    const { flash } = usePage().props;
    const [selectedProvider, setSelectedProvider] = useState(null);
    const [copiedWebhook, setCopiedWebhook] = useState(false);

    const { data, setData, post, processing, reset } = useForm({
        is_active: false,
        display_name: '',
        display_description: '',
        sort_order: 0,
        credentials: {},
        settings: {
            fee_enabled: false,
            fee_type: 'percent', // 'percent' or 'fixed'
            fee_direction: 'increase', // 'increase' or 'discount'
            fee_value: 0,
            mode: 'live', // 'live' or 'test'
            language: 'ar',
            expiry_hours: 48,
        },
    });

    const openEditModal = (provider, forceActive = false) => {
        const config = provider.config || {};
        const creds = config.credentials || {};
        const setts = config.settings || {};

        setData({
            is_active: forceActive ? true : (config.is_active !== undefined ? Boolean(config.is_active) : (provider.id === 'cod')),
            display_name: config.display_name || getDefaultDisplayName(provider.id),
            display_description: config.display_description || getDefaultDescription(provider.id),
            sort_order: config.sort_order || 0,
            credentials: {
                api_key: creds.api_key || '',
                public_key: creds.public_key || '',
                secret_key: creds.secret_key || '',
                card_integration_id: creds.card_integration_id || '',
                wallet_integration_id: creds.wallet_integration_id || '',
                hmac_secret: creds.hmac_secret || '',
                merchant_id: creds.merchant_id || '',
                merchant_code: creds.merchant_code || '',
                security_key: creds.security_key || '',
                api_secret: creds.api_secret || '',
            },
            settings: {
                fee_enabled: setts.fee_enabled || false,
                fee_type: setts.fee_type || 'percent',
                fee_direction: setts.fee_direction || 'increase',
                fee_value: setts.fee_value || 0,
                mode: setts.mode || 'live',
                language: setts.language || 'ar',
                expiry_hours: setts.expiry_hours || 48,
            },
        });

        setSelectedProvider(provider);
    };

    const closeModal = () => {
        setSelectedProvider(null);
        reset();
    };

    const handleCopyWebhook = (url) => {
        navigator.clipboard.writeText(url);
        setCopiedWebhook(true);
        setTimeout(() => setCopiedWebhook(false), 2000);
    };

    const handleSave = (e) => {
        e.preventDefault();
        if (!selectedProvider) return;

        post(route('merchant.payment-gateways.update', selectedProvider.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    };

    const handleToggle = (providerId) => {
        router.patch(route('merchant.payment-gateways.toggle', providerId), {}, {
            preserveScroll: true,
        });
    };

    const getDefaultDisplayName = (id) => {
        switch (id) {
            case 'cod': return 'الدفع عند الاستلام (COD)';
            case 'paymob': return 'الدفع بالبطاقات البنكية والمحافظ (Paymob)';
            case 'kashier': return 'الدفع الإلكتروني (Kashier)';
            case 'fawry': return 'الدفع عبر منافذ فوري (Fawry)';
            default: return '';
        }
    };

    const getDefaultDescription = (id) => {
        switch (id) {
            case 'cod': return 'ادفع نقداً عند استلام الشحنة من مندوب التوصيل';
            case 'paymob': return 'ادفع بأمان باستخدام فيزا، ماستركارد، ميزة أو المحافظ الإلكترونية';
            case 'kashier': return 'ادفع أونلاين عبر بطاقات الائتمان والمحافظ والمحافظ البنكية';
            case 'fawry': return 'ادفع في أي منفذ أو فرع فوري في مصر باستخدام الرقم المرجعي';
            default: return '';
        }
    };

    return (
        <MerchantLayout title="بوابات ووسائل الدفع">
            <Head title="بوابات ووسائل الدفع الإلكتروني" />

            <div className="max-w-7xl space-y-6" dir="rtl">
                {/* Header */}
                <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2.5 mb-1">
                            <span className="text-2xl">💳</span>
                            <h1 className="text-xl sm:text-2xl font-black text-gray-900">بوابات ووسائل الدفع الإلكتروني</h1>
                        </div>
                        <p className="text-xs sm:text-sm text-gray-500">
                            تحكم في وسائل الدفع المتاحة لعملائك عند إتمام الطلب (الدفع عند الاستلام، باي موب، كاشير، فوري).
                        </p>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border-r-4 border-emerald-500 rounded-2xl text-emerald-900 text-sm font-bold flex items-center gap-2 shadow-sm animate-fade-in">
                        <span>✓</span>
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-rose-50 border-r-4 border-rose-500 rounded-2xl text-rose-800 text-sm font-bold flex items-center gap-2 shadow-sm animate-fade-in">
                        <span>⚠️</span>
                        <span>{flash.error}</span>
                    </div>
                )}

                {/* Gateways Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                    {providers.map((provider) => {
                        const isSupported = provider.is_supported;
                        const isActive = provider.is_active;

                        return (
                            <div
                                key={provider.id}
                                className={`bg-white rounded-2xl border transition-all duration-200 p-5 flex flex-col justify-between h-[280px] shadow-sm hover:shadow-md relative overflow-hidden ${
                                    isActive
                                        ? 'border-emerald-200 ring-1 ring-emerald-400/30'
                                        : 'border-gray-200/80 hover:border-gray-300'
                                }`}
                            >
                                {/* Active Indicator Dot */}
                                {isActive && (
                                    <div className="absolute top-3 left-3 flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full text-[10px] font-black border border-emerald-200">
                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>مفعل</span>
                                    </div>
                                )}

                                {/* Logo Box */}
                                <div
                                    onClick={() => isSupported && openEditModal(provider)}
                                    className={`flex-1 flex flex-col items-center justify-center pt-2 ${isSupported ? 'cursor-pointer' : ''}`}
                                >
                                    <div className="w-full h-24 flex items-center justify-center p-3">
                                        <img
                                            src={provider.logo}
                                            alt={provider.title}
                                            className="max-h-16 max-w-[140px] object-contain select-none"
                                        />
                                    </div>
                                    <h3 className="text-xs font-black text-gray-800 text-center mt-1">
                                        {provider.name}
                                    </h3>
                                    <p className="text-[10px] text-gray-400 text-center line-clamp-1 mt-0.5">
                                        {provider.description}
                                    </p>
                                </div>

                                {/* Actions Footer */}
                                <div className="pt-4 border-t border-gray-100/80">
                                    {isSupported ? (
                                        <div className="flex items-center gap-2">
                                            <button
                                                type="button"
                                                onClick={() => openEditModal(provider, false)}
                                                className="flex-1 py-2 px-3 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-bold border border-gray-200 transition-colors text-center cursor-pointer"
                                            >
                                                تعديل
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => openEditModal(provider, !isActive)}
                                                className={`flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all text-center cursor-pointer ${
                                                    isActive
                                                        ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-sm'
                                                        : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200'
                                                }`}
                                            >
                                                {isActive ? 'مفعل' : 'تفعيل'}
                                            </button>
                                        </div>
                                    ) : (
                                        <div className="w-full py-2 bg-gray-50 rounded-xl text-[11px] font-bold text-gray-400 text-center border border-dashed border-gray-200">
                                            قريباً ⏳
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Modal / Settings Drawer */}
            {selectedProvider && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in" dir="rtl">
                    <div className="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 max-h-[90vh] flex flex-col">
                        {/* Modal Header */}
                        <div className="p-5 sm:p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 flex-shrink-0">
                            <div className="flex items-center gap-3">
                                <img
                                    src={selectedProvider.logo}
                                    alt={selectedProvider.title}
                                    className="h-7 w-auto object-contain"
                                />
                                <div>
                                    <h2 className="text-sm sm:text-base font-black text-gray-900">
                                        إعدادات {selectedProvider.title}
                                    </h2>
                                    <p className="text-[11px] text-gray-500">تخصيص بيانات الربط وطريقة الظهور في صفحة الدفع</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={closeModal}
                                className="w-8 h-8 rounded-full bg-gray-200/70 hover:bg-gray-300 flex items-center justify-center text-gray-600 font-bold transition-colors cursor-pointer text-xs"
                            >
                                ✕
                            </button>
                        </div>

                        {/* Modal Body Form */}
                        <form onSubmit={handleSave} className="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5">
                            {/* Webhook Callback Box (if applicable) */}
                            {selectedProvider.webhook_url && (
                                <div className="p-4 bg-slate-900 rounded-2xl text-white space-y-2">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="font-bold text-emerald-400 flex items-center gap-1.5">
                                            <span>🔗</span> رابط استجابة المعاملة (Integration Callbacks):
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => handleCopyWebhook(selectedProvider.webhook_url)}
                                            className="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[10px] font-bold transition-colors cursor-pointer"
                                        >
                                            {copiedWebhook ? 'تم النسخ ✓' : 'نسخ الرابط 📋'}
                                        </button>
                                    </div>
                                    <p className="text-[10px] text-gray-300 leading-relaxed">
                                        ضع هذا الرابط في لوحة تحكم <strong>{selectedProvider.title}</strong> في خانة (Transaction processed callback) لاستقبال تأكيد الدفع الفوري:
                                    </p>
                                    <div className="p-2 bg-slate-800 rounded-xl text-[11px] font-mono text-indigo-300 select-all break-all border border-slate-700" dir="ltr">
                                        {selectedProvider.webhook_url}
                                    </div>
                                </div>
                            )}

                            {/* Enable / Disable Switch */}
                            <div className="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-200/70">
                                <div>
                                    <span className="text-xs font-bold text-gray-800 block">حالة تفعيل البوابة:</span>
                                    <span className="text-[11px] text-gray-500">
                                        {data.is_active ? 'البوابة مفعلة وتظهر للعملاء في المتجر' : 'البوابة معطلة ومخفية عن العملاء'}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={data.is_active}
                                    onClick={() => setData('is_active', !data.is_active)}
                                    className={`w-14 h-8 flex items-center rounded-full p-1 cursor-pointer transition-colors duration-200 focus:outline-none ${
                                        data.is_active ? 'bg-emerald-600 justify-end' : 'bg-gray-300 justify-start'
                                    }`}
                                    dir="ltr"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="block w-6 h-6 rounded-full bg-white shadow-md transition-all duration-200"
                                    />
                                </button>
                            </div>

                            {/* Display Name Input */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1">
                                    اسم وسيلة الدفع الذي سيظهر للعميل:
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={data.display_name}
                                    onChange={(e) => setData('display_name', e.target.value)}
                                    className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                />
                            </div>

                            {/* Display Description Input */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1">
                                    وصف وسيلة الدفع الذي سيظهر للعميل:
                                </label>
                                <textarea
                                    rows="2"
                                    value={data.display_description}
                                    onChange={(e) => setData('display_description', e.target.value)}
                                    className="w-full px-3.5 py-2 border border-gray-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                ></textarea>
                            </div>

                            {/* Sort Order */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1">
                                    الأولوية في الظهور (الترتيب):
                                </label>
                                <input
                                    type="number"
                                    value={data.sort_order}
                                    onChange={(e) => setData('sort_order', Number(e.target.value))}
                                    className="w-24 px-3 py-2 border border-gray-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none text-center"
                                />
                            </div>

                            {/* Logos Badge Preview (If applicable) */}
                            {selectedProvider.badge_image && (
                                <div className="space-y-1.5">
                                    <label className="block text-xs font-bold text-gray-700">شعارات البطاقات المعتمدة:</label>
                                    <div className="p-3 bg-gray-50 border border-gray-200 rounded-2xl flex items-center justify-center">
                                        <img
                                            src={selectedProvider.badge_image}
                                            alt="Logos"
                                            className="h-8 object-contain"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* PROVIDER-SPECIFIC CREDENTIALS */}

                            {/* PAYMOB CREDENTIALS */}
                            {selectedProvider.id === 'paymob' && (
                                <div className="space-y-3.5 pt-3 border-t border-gray-100">
                                    <h4 className="text-xs font-black text-gray-900 flex items-center gap-1.5">
                                        <span>🔑</span> بيانات الربط مع Paymob:
                                    </h4>

                                    <div>
                                        <label className="block text-[11px] font-bold text-gray-700 mb-1">API Key (الرئيسي):</label>
                                        <input
                                            type="password"
                                            value={data.credentials.api_key || ''}
                                            onChange={(e) => setData('credentials', { ...data.credentials, api_key: e.target.value })}
                                            placeholder="ZXlKaGJHY2l..."
                                            className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-right"
                                        />
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">Public Key:</label>
                                            <input
                                                type="text"
                                                value={data.credentials.public_key || ''}
                                                onChange={(e) => setData('credentials', { ...data.credentials, public_key: e.target.value })}
                                                placeholder="egy_pk_live_..."
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-left"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">Secret Key:</label>
                                            <input
                                                type="password"
                                                value={data.credentials.secret_key || ''}
                                                onChange={(e) => setData('credentials', { ...data.credentials, secret_key: e.target.value })}
                                                placeholder="sec_..."
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-right"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">Card Integration ID (الكروت):</label>
                                            <input
                                                type="text"
                                                value={data.credentials.card_integration_id || ''}
                                                onChange={(e) => setData('credentials', { ...data.credentials, card_integration_id: e.target.value })}
                                                placeholder="مثال: 456789"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-left"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">Wallet Integration ID (المحافظ):</label>
                                            <input
                                                type="text"
                                                value={data.credentials.wallet_integration_id || ''}
                                                onChange={(e) => setData('credentials', { ...data.credentials, wallet_integration_id: e.target.value })}
                                                placeholder="مثال: 987654"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-left"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-[11px] font-bold text-gray-700 mb-1">HMAC Secret (للتحقق من العمليات):</label>
                                        <input
                                            type="password"
                                            value={data.credentials.hmac_secret || ''}
                                            onChange={(e) => setData('credentials', { ...data.credentials, hmac_secret: e.target.value })}
                                            placeholder="639A..."
                                            className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-right"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* KASHIER CREDENTIALS */}
                            {selectedProvider.id === 'kashier' && (
                                <div className="space-y-3.5 pt-3 border-t border-gray-100">
                                    <h4 className="text-xs font-black text-gray-900 flex items-center gap-1.5">
                                        <span>🔑</span> بيانات الربط مع Kashier:
                                    </h4>

                                    <div>
                                        <label className="block text-[11px] font-bold text-gray-700 mb-1">Merchant ID (معرف التاجر):</label>
                                        <input
                                            type="text"
                                            value={data.credentials.merchant_id || ''}
                                            onChange={(e) => setData('credentials', { ...data.credentials, merchant_id: e.target.value })}
                                            placeholder="MID-xxxx-xxxx"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-left"
                                        />
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">API Key:</label>
                                            <input
                                                type="password"
                                                value={data.credentials.api_key || ''}
                                                onChange={(e) => setData('credentials', { ...data.credentials, api_key: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-right"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">API Secret (Iframe Secret):</label>
                                            <input
                                                type="password"
                                                value={data.credentials.api_secret || ''}
                                                onChange={(e) => setData('credentials', { ...data.credentials, api_secret: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-right"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">وضع الدفع (Mode):</label>
                                            <select
                                                value={data.settings.mode || 'live'}
                                                onChange={(e) => setData('settings', { ...data.settings, mode: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 bg-white"
                                            >
                                                <option value="live">Live (حقيقي - بيئة الإنتاج)</option>
                                                <option value="test">Testing (تجريبي - للاختبار)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">لغة صفحة الدفع:</label>
                                            <select
                                                value={data.settings.language || 'ar'}
                                                onChange={(e) => setData('settings', { ...data.settings, language: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 bg-white"
                                            >
                                                <option value="ar">Arabic (العربية)</option>
                                                <option value="en">English (الإنجليزية)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* FAWRY CREDENTIALS */}
                            {selectedProvider.id === 'fawry' && (
                                <div className="space-y-3.5 pt-3 border-t border-gray-100">
                                    <h4 className="text-xs font-black text-gray-900 flex items-center gap-1.5">
                                        <span>🔑</span> بيانات الربط مع فوري (Fawry):
                                    </h4>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">Merchant Code (كود التاجر):</label>
                                            <input
                                                type="text"
                                                value={data.credentials.merchant_code || ''}
                                                onChange={(e) => setData('credentials', { ...data.credentials, merchant_code: e.target.value })}
                                                placeholder="مثال: 7700000xxxxx"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-left"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">Security Key (المفتاح السري):</label>
                                            <input
                                                type="password"
                                                value={data.credentials.security_key || ''}
                                                onChange={(e) => setData('credentials', { ...data.credentials, security_key: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 dir-ltr text-right"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">وضع الدفع (Mode):</label>
                                            <select
                                                value={data.settings.mode || 'live'}
                                                onChange={(e) => setData('settings', { ...data.settings, mode: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 bg-white"
                                            >
                                                <option value="live">Live (حقيقي - بيئة الإنتاج)</option>
                                                <option value="test">Testing (تجريبي - للاختبار)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-700 mb-1">صلاحية كود فوري (ساعات):</label>
                                            <input
                                                type="number"
                                                value={data.settings.expiry_hours || 48}
                                                onChange={(e) => setData('settings', { ...data.settings, expiry_hours: Number(e.target.value) })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 text-center"
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* FEE ADJUSTMENT (APPLY PRICE CHANGE) */}
                            <div className="p-4 bg-gray-50/70 border border-gray-200/80 rounded-2xl space-y-3">
                                <label className="flex items-center gap-2.5 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.settings.fee_enabled || false}
                                        onChange={(e) => setData('settings', { ...data.settings, fee_enabled: e.target.checked })}
                                        className="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500 border-gray-300 cursor-pointer"
                                    />
                                    <span className="text-xs font-bold text-gray-900">تطبيق تغير في السعر مع هذه البوابة</span>
                                </label>

                                {data.settings.fee_enabled && (
                                    <div className="space-y-3 pt-2 border-t border-gray-200/60 animate-fade-in">
                                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                            <div>
                                                <label className="block text-[11px] font-bold text-gray-600 mb-1">نوع التغير:</label>
                                                <select
                                                    value={data.settings.fee_type || 'percent'}
                                                    onChange={(e) => setData('settings', { ...data.settings, fee_type: e.target.value })}
                                                    className="w-full px-2.5 py-1.5 border border-gray-300 rounded-xl text-xs font-bold bg-white focus:ring-2 focus:ring-emerald-500"
                                                >
                                                    <option value="percent">نسبة مئوية (%)</option>
                                                    <option value="fixed">قيمة ثابتة (ج.م)</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-[11px] font-bold text-gray-600 mb-1">اتجاه التغير:</label>
                                                <select
                                                    value={data.settings.fee_direction || 'increase'}
                                                    onChange={(e) => setData('settings', { ...data.settings, fee_direction: e.target.value })}
                                                    className="w-full px-2.5 py-1.5 border border-gray-300 rounded-xl text-xs font-bold bg-white focus:ring-2 focus:ring-emerald-500"
                                                >
                                                    <option value="increase">زيادة ➕ (لتغطية العمولة)</option>
                                                    <option value="discount">خصم ➖ (لتشجيع الدفع)</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-[11px] font-bold text-gray-600 mb-1">القيمة:</label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.settings.fee_value || 0}
                                                    onChange={(e) => setData('settings', { ...data.settings, fee_value: Number(e.target.value) })}
                                                    className="w-full px-2.5 py-1.5 border border-gray-300 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 text-center"
                                                />
                                            </div>
                                        </div>
                                        <p className="text-[10px] text-gray-400">
                                            يمكنك وضع قيمة ثابتة أو نسبة مئوية بشرط أن تكون النسبة أقل من 100 وأعلى من 0.
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Form Actions */}
                            <div className="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                                <button
                                    type="button"
                                    onClick={closeModal}
                                    className="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-60"
                                >
                                    <span>💾</span>
                                    <span>{processing ? 'جاري الحفظ...' : 'حفظ الإعدادات'}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </MerchantLayout>
    );
}
