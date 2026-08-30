import React, { useState } from 'react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head, useForm, usePage, router } from '@inertiajs/react';

export default function WalletIndex({ wallet_balance, paymentInfo, depositRequests, transactions }) {
    const { flash } = usePage().props;

    const [activeTab, setActiveTab] = useState('deposit'); // deposit, history
    const [chargeMode, setChargeMode] = useState('manual'); // 'manual' (Cash/InstaPay) default

    // Manual Form State
    const [manualQuickAmount, setManualQuickAmount] = useState(300);
    const [copiedVodafone, setCopiedVodafone] = useState(false);
    const [copiedInstapay, setCopiedInstapay] = useState(false);
    const [copiedCodeId, setCopiedCodeId] = useState(null);
    const [previewImage, setPreviewImage] = useState(null);
    const [viewingReceipt, setViewingReceipt] = useState(null);

    const manualForm = useForm({
        amount: 300,
        payment_method: 'vodafone_cash',
        payment_reference: '',
        receipt: null,
    });

    // Instant (Paymob) Form State
    const [instantQuickAmount, setInstantQuickAmount] = useState(300);
    const instantForm = useForm({
        amount: 300,
        method_type: 'card', // 'card' or 'wallet'
        wallet_phone: '',
    });

    const handleManualQuickAmount = (val) => {
        setManualQuickAmount(val);
        if (val !== 'custom') {
            manualForm.setData('amount', val);
        } else {
            manualForm.setData('amount', '');
        }
    };

    const handleInstantQuickAmount = (val) => {
        setInstantQuickAmount(val);
        if (val !== 'custom') {
            instantForm.setData('amount', val);
        } else {
            instantForm.setData('amount', '');
        }
    };

    const handleCopy = (text, type) => {
        navigator.clipboard.writeText(text);
        if (type === 'vodafone') {
            setCopiedVodafone(true);
            setTimeout(() => setCopiedVodafone(false), 2000);
        } else {
            setCopiedInstapay(true);
            setTimeout(() => setCopiedInstapay(false), 2000);
        }
    };

    const handleCopyRefCode = (code, id) => {
        navigator.clipboard.writeText(code);
        setCopiedCodeId(id);
        setTimeout(() => setCopiedCodeId(null), 2000);
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            manualForm.setData('receipt', file);
            manualForm.clearErrors('receipt');
            setPreviewImage(URL.createObjectURL(file));
        }
    };

    const handleManualSubmit = (e) => {
        e.preventDefault();

        if (!manualForm.data.receipt) {
            manualForm.setError('receipt', 'يرجى إرفاق صورة إيصال التحويل (إسكرين شوت) لإتمام الطلب.');
            return;
        }

        manualForm.post(route('merchant.wallet.deposit'), {
            preserveScroll: true,
            onSuccess: () => {
                manualForm.reset();
                setPreviewImage(null);
                setManualQuickAmount(300);
                setActiveTab('history');
            },
        });
    };

    const handleInstantSubmit = (e) => {
        e.preventDefault();
        instantForm.post(route('merchant.wallet.instant-deposit'), {
            preserveScroll: true,
        });
    };

    const whatsappLink = `https://wa.me/2${paymentInfo?.support_phone?.replace(/[^0-9]/g, '')}?text=${encodeURIComponent('مرحباً، أود الاستفسار عن طلب شحن محفظتي.')}`;

    const getPaymentMethodBadge = (method) => {
        if (method === 'paymob_card') {
            return (
                <span className="inline-flex items-center gap-1 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md">
                    <span>💳</span>
                    <span>فيزا / ماستركارد (لحظي)</span>
                </span>
            );
        }
        if (method === 'paymob_wallet') {
            return (
                <span className="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">
                    <span>📱</span>
                    <span>محفظة إلكترونية (لحظي)</span>
                </span>
            );
        }
        if (method === 'vodafone_cash') {
            return (
                <span className="inline-flex items-center gap-1 text-xs font-bold text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded-md">
                    <span>🔴</span>
                    <span>فودافون كاش</span>
                </span>
            );
        }
        if (method === 'instapay') {
            return (
                <span className="inline-flex items-center gap-1 text-xs font-bold text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-md">
                    <span>⚡</span>
                    <span>إنستا باي</span>
                </span>
            );
        }
        return <span className="text-xs font-bold text-gray-700">{method}</span>;
    };

    return (
        <MerchantLayout title="المحفظة والرصيد">
            <Head title="المحفظة والرصيد" />

            <div className="max-w-6xl space-y-6" dir="rtl">
                {/* Header & Balance Card */}
                <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
                    <div className="absolute left-0 top-0 translate-y-[-20%] translate-x-[-10%] w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div className="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                        <div className="space-y-1">
                            <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                👛 محفظة المتجر
                            </span>
                            <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">إدارة المحفظة والرصيد</h1>
                            <p className="text-xs text-indigo-200/80">شحن الرصيد واستعراض السجل والخصومات</p>
                        </div>
                        <div className="bg-white/10 backdrop-blur-md border border-white/15 p-5 rounded-2xl flex flex-col items-start sm:items-end min-w-[220px]">
                            <span className="text-xs text-indigo-200 font-semibold mb-1">الرصيد الحالي بالمحفظة</span>
                            <div className="flex items-baseline gap-1.5">
                                <span className="text-3xl font-black text-emerald-400 font-mono" dir="ltr">
                                    {Math.round(Number(wallet_balance)).toLocaleString('en-US')}
                                </span>
                                <span className="text-sm font-bold text-emerald-300">ج.م</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border-r-4 border-emerald-500 rounded-2xl text-emerald-900 text-sm font-bold flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm animate-fade-in">
                        <div className="flex items-center gap-2">
                            <span className="text-base">✓</span>
                            <span>{flash.success}</span>
                        </div>
                        <a
                            href={whatsappLink}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition-colors shadow-sm self-start sm:self-auto"
                        >
                            <span>💬</span>
                            <span>التواصل مع الدعم الفني</span>
                        </a>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-rose-50 border-r-4 border-rose-500 rounded-2xl text-rose-800 text-sm font-bold flex items-center gap-2 shadow-sm animate-fade-in">
                        <span>⚠️</span>
                        <span>{flash.error}</span>
                    </div>
                )}

                {/* Navigation Tabs (Deposit vs History) */}
                <div className="flex border-b border-gray-200 bg-white rounded-2xl shadow-sm p-1.5 gap-2">
                    <button
                        onClick={() => setActiveTab('deposit')}
                        className={`flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 cursor-pointer ${
                            activeTab === 'deposit'
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'text-gray-600 hover:bg-gray-50'
                        }`}
                    >
                        <span>💳</span>
                        <span>شحن المحفظة</span>
                    </button>
                    <button
                        onClick={() => setActiveTab('history')}
                        className={`flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 cursor-pointer ${
                            activeTab === 'history'
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'text-gray-600 hover:bg-gray-50'
                        }`}
                    >
                        <span>📋</span>
                        <span>السجل ({depositRequests.length})</span>
                    </button>
                </div>

                {/* Tab 1: Charge Wallet (Manual) */}
                {activeTab === 'deposit' && (
                    <div className="space-y-6">
                        {/* MANUAL TOP-UP (VODAFONE CASH / INSTAPAY) */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Left Column: Transfer Payment Accounts & Info */}
                            <div className="lg:col-span-1 space-y-6">
                                    <div className="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 space-y-5">
                                        <h3 className="font-bold text-gray-900 text-base border-b border-gray-100 pb-3 flex items-center gap-2">
                                            <span>📲</span>
                                            <span>أرقام استقبال التحويلات</span>
                                        </h3>

                                        <p className="text-xs text-gray-500 leading-relaxed">
                                            قم بتحويل المبلغ المطلوب لأحد الأرقام التابعة لنا، ثم املأ نموذج الشحن:
                                        </p>

                                        {/* Vodafone Cash Card */}
                                        <div className="p-4 bg-red-50/70 border border-red-200 rounded-2xl space-y-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs font-bold text-red-800 flex items-center gap-1.5">
                                                    <span>🔴</span> رقم فودافون كاش (Vodafone Cash)
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between bg-white p-2.5 rounded-xl border border-red-100">
                                                <span className="font-mono font-bold text-gray-900 text-base tracking-wide" dir="ltr">
                                                    {paymentInfo?.vodafone_cash || '010xxxxxxxx'}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => handleCopy(paymentInfo?.vodafone_cash, 'vodafone')}
                                                    className="px-3 py-1 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 transition-colors shadow-sm cursor-pointer"
                                                >
                                                    {copiedVodafone ? 'تم النسخ ✓' : 'نسخ 📋'}
                                                </button>
                                            </div>
                                        </div>

                                        {/* InstaPay Phone Number Card */}
                                        <div className="p-4 bg-purple-50/70 border border-purple-200 rounded-2xl space-y-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs font-bold text-purple-800 flex items-center gap-1.5">
                                                    <span>⚡</span> رقم إنستا باي (InstaPay)
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between bg-white p-2.5 rounded-xl border border-purple-100">
                                                <span className="font-mono font-bold text-gray-900 text-base tracking-wide" dir="ltr">
                                                    {paymentInfo?.instapay || 'username@instapay'}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => handleCopy(paymentInfo?.instapay, 'instapay')}
                                                    className="px-3 py-1 bg-purple-600 text-white rounded-lg text-xs font-bold hover:bg-purple-700 transition-colors shadow-sm flex-shrink-0 cursor-pointer"
                                                >
                                                    {copiedInstapay ? 'تم النسخ ✓' : 'نسخ 📋'}
                                                </button>
                                            </div>
                                        </div>

                                        <div className="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl text-amber-900 text-xs space-y-1">
                                            <p className="font-bold flex items-center gap-1">
                                                <span>⏰</span> مواعيد عمل المراجعة اليدوية:
                                            </p>
                                            <p className="text-amber-800 leading-relaxed font-semibold">
                                                {paymentInfo?.work_hours || 'من 10 صباحاً حتى 2 بعد منتصف الليل'}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Right Column: Manual Deposit Form */}
                                <div className="lg:col-span-2 space-y-6">
                                    <form onSubmit={handleManualSubmit} className="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 sm:p-7 space-y-6">
                                        <h3 className="font-bold text-gray-900 text-base border-b border-gray-100 pb-3 flex items-center justify-between">
                                            <span className="flex items-center gap-2">
                                                <span>📝</span>
                                                <span>تقديم طلب شحن يدوي</span>
                                            </span>
                                            <span className="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full">
                                                الحد الأدنى: 300 ج.م
                                            </span>
                                        </h3>

                                        {/* Quick Amounts Selection */}
                                        <div className="space-y-2">
                                            <label className="block text-xs font-bold text-gray-700">اختر مبلغ الشحن السريع:</label>
                                            <div className="grid grid-cols-3 sm:grid-cols-5 gap-2">
                                                {[300, 600, 1000, 2000].map((amt) => (
                                                    <button
                                                        key={amt}
                                                        type="button"
                                                        onClick={() => handleManualQuickAmount(amt)}
                                                        className={`py-2.5 px-3 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                                                            manualQuickAmount === amt
                                                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                                                : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                                                        }`}
                                                    >
                                                        {amt} ج.م
                                                    </button>
                                                ))}
                                                <button
                                                    type="button"
                                                    onClick={() => handleManualQuickAmount('custom')}
                                                    className={`py-2.5 px-3 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                                                        manualQuickAmount === 'custom'
                                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                                            : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                                                    }`}
                                                >
                                                    مبلغ آخر ✏️
                                                </button>
                                            </div>
                                        </div>

                                        {/* Custom Amount Input */}
                                        <div>
                                            <label className="block text-xs font-bold text-gray-700 mb-1">
                                                مبلغ الشحن (جنيه مصري):
                                            </label>
                                            <input
                                                type="number"
                                                min="300"
                                                step="1"
                                                required
                                                value={manualForm.data.amount}
                                                onChange={(e) => manualForm.setData('amount', e.target.value)}
                                                placeholder="أدخل مبلغ الشحن (300 كحد أدنى)"
                                                className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                            />
                                            {manualForm.errors.amount && (
                                                <span className="text-xs text-rose-600 mt-1 block font-medium">{manualForm.errors.amount}</span>
                                            )}
                                        </div>

                                        {/* Transfer Method Selection */}
                                        <div className="space-y-2">
                                            <label className="block text-xs font-bold text-gray-700">وسيلة التحويل المستخدمة:</label>
                                            <div className="grid grid-cols-2 gap-3">
                                                <label
                                                    className={`flex items-center gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all ${
                                                        manualForm.data.payment_method === 'vodafone_cash'
                                                            ? 'border-red-500 bg-red-50/50 ring-2 ring-red-500/20'
                                                            : 'border-gray-200 hover:bg-gray-50'
                                                    }`}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="payment_method"
                                                        value="vodafone_cash"
                                                        checked={manualForm.data.payment_method === 'vodafone_cash'}
                                                        onChange={(e) => manualForm.setData('payment_method', e.target.value)}
                                                        className="text-red-600 focus:ring-red-500"
                                                    />
                                                    <span className="text-xs font-bold text-gray-800">فودافون كاش 🔴</span>
                                                </label>

                                                <label
                                                    className={`flex items-center gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all ${
                                                        manualForm.data.payment_method === 'instapay'
                                                            ? 'border-purple-500 bg-purple-50/50 ring-2 ring-purple-500/20'
                                                            : 'border-gray-200 hover:bg-gray-50'
                                                    }`}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="payment_method"
                                                        value="instapay"
                                                        checked={manualForm.data.payment_method === 'instapay'}
                                                        onChange={(e) => manualForm.setData('payment_method', e.target.value)}
                                                        className="text-purple-600 focus:ring-purple-500"
                                                    />
                                                    <span className="text-xs font-bold text-gray-800">إنستا باي ⚡</span>
                                                </label>
                                            </div>
                                        </div>

                                        {/* Sender Number Field */}
                                        <div>
                                            <label className="block text-xs font-bold text-gray-700 mb-1">
                                                الرقم المُنقَل منه (الرقم المحوّل منه):
                                            </label>
                                            <input
                                                type="text"
                                                required
                                                value={manualForm.data.payment_reference}
                                                onChange={(e) => manualForm.setData('payment_reference', e.target.value)}
                                                placeholder="أدخل رقم الهاتف المحول منه"
                                                className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                            />
                                            {manualForm.errors.payment_reference && (
                                                <span className="text-xs text-rose-600 mt-1 block font-medium">{manualForm.errors.payment_reference}</span>
                                            )}
                                        </div>

                                        {/* Receipt Image Upload Field */}
                                        <div className="space-y-2">
                                            <label className="block text-xs font-bold text-gray-700">
                                                صورة إيصال التحويل (إسكرين شوت): <span className="text-rose-500">*</span>
                                            </label>
                                            <div className="flex flex-col sm:flex-row items-center gap-4">
                                                <label className={`flex-1 w-full flex flex-col items-center justify-center p-4 border-2 border-dashed rounded-2xl cursor-pointer transition-all text-center ${
                                                    manualForm.errors.receipt 
                                                        ? 'border-rose-400 bg-rose-50/40 hover:bg-rose-50/60 ring-2 ring-rose-100' 
                                                        : 'border-gray-300 hover:border-indigo-500 hover:bg-indigo-50/20'
                                                }`}>
                                                    <svg className={`w-8 h-8 mb-1 ${manualForm.errors.receipt ? 'text-rose-400' : 'text-gray-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span className={`text-xs font-bold ${manualForm.errors.receipt ? 'text-rose-600' : 'text-indigo-600'}`}>
                                                        اختر صورة الإيصال للرفع
                                                    </span>
                                                    <span className="text-[11px] text-gray-400 mt-0.5">PNG, JPG حتى 3 ميجابايت</span>
                                                    <input
                                                        type="file"
                                                        accept="image/*"
                                                        onChange={handleFileChange}
                                                        className="hidden"
                                                    />
                                                </label>
                                                {previewImage && (
                                                    <div className="w-24 h-24 rounded-2xl border border-gray-200 overflow-hidden bg-gray-50 flex-shrink-0 shadow-sm relative group">
                                                        <img src={previewImage} alt="معاينة الإيصال" className="w-full h-full object-cover" />
                                                        <span className="absolute inset-0 bg-black/40 text-white text-[10px] font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                            معاينة
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                            {manualForm.errors.receipt && (
                                                <div className="p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-600 font-bold flex items-center gap-1.5 mt-1">
                                                    <svg className="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>{manualForm.errors.receipt}</span>
                                                </div>
                                            )}
                                        </div>

                                        {/* Submit Button */}
                                        <button
                                            type="submit"
                                            disabled={manualForm.processing}
                                            className="w-full py-3.5 bg-indigo-600 text-white font-extrabold text-sm rounded-2xl hover:bg-indigo-700 transition-all shadow-md hover:shadow-lg disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                                        >
                                            {manualForm.processing ? 'جاري إرسال الطلب...' : 'إرسال طلب الشحن 🚀'}
                                        </button>
                                    </form>
                                </div>
                            </div>
                    </div>
                )}

                {/* Tab 2: Combined Requests & Transactions History */}
                {activeTab === 'history' && (
                    <div className="space-y-6">
                        {/* Section 1: Deposit Requests */}
                        <div className="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 space-y-4">
                            <h3 className="font-bold text-gray-900 text-base border-b border-gray-100 pb-3 flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <span>📋</span>
                                    <span>طلبات الشحن</span>
                                </span>
                                <span className="text-xs font-normal text-gray-500">إجمالي الطلبات: {depositRequests.length}</span>
                            </h3>

                            {depositRequests.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-right border-collapse">
                                        <thead>
                                            <tr className="bg-gray-50 text-gray-500 text-xs font-bold border-b border-gray-100">
                                                <th className="px-4 py-3 whitespace-nowrap">الرقم المرجعي</th>
                                                <th className="px-4 py-3 whitespace-nowrap">المبلغ</th>
                                                <th className="px-4 py-3 whitespace-nowrap">طريقة الشحن</th>
                                                <th className="px-4 py-3 whitespace-nowrap">المرجع / الرقم</th>
                                                <th className="px-4 py-3 whitespace-nowrap">الإيصال</th>
                                                <th className="px-4 py-3 whitespace-nowrap">التاريخ والوقت</th>
                                                <th className="px-4 py-3 whitespace-nowrap">الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 text-xs">
                                            {depositRequests.map((req) => (
                                                <tr key={req.id} className="hover:bg-gray-50/50 transition-colors">
                                                    <td className="px-4 py-3.5 whitespace-nowrap">
                                                        <div className="flex items-center gap-1.5" dir="ltr">
                                                            <span className="font-mono font-black text-indigo-900 bg-indigo-50 border border-indigo-200/80 px-2.5 py-1 rounded-lg text-sm select-all">
                                                                {req.reference_code}
                                                            </span>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleCopyRefCode(req.reference_code, req.id)}
                                                                className="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[11px] font-bold transition-all shadow-sm cursor-pointer"
                                                                title="نسخ الرقم المرجعي"
                                                            >
                                                                {copiedCodeId === req.id ? 'تم ✓' : 'نسخ'}
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3.5 font-extrabold text-gray-900 text-sm whitespace-nowrap">
                                                        {Math.round(req.amount).toLocaleString('en-US')} ج.م
                                                    </td>
                                                    <td className="px-4 py-3.5 whitespace-nowrap">
                                                        {getPaymentMethodBadge(req.payment_method)}
                                                    </td>
                                                    <td className="px-4 py-3.5 font-mono font-bold text-gray-800 whitespace-nowrap" dir="ltr">
                                                        {req.payment_reference || '-'}
                                                    </td>
                                                    <td className="px-4 py-3.5 whitespace-nowrap">
                                                        {req.receipt_url ? (
                                                            <button
                                                                type="button"
                                                                onClick={() => setViewingReceipt(req.receipt_url)}
                                                                className="text-xs font-bold text-indigo-600 hover:underline inline-flex items-center gap-1 whitespace-nowrap cursor-pointer"
                                                            >
                                                                عرض الإيصال 🖼️
                                                            </button>
                                                        ) : (
                                                            <span className="text-gray-400">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3.5 text-gray-600 whitespace-nowrap">
                                                        <div className="flex items-center gap-2" dir="ltr">
                                                            <span className="font-bold text-gray-800 text-xs">{req.date_formatted}</span>
                                                            <span className="text-[11px] text-gray-400 font-mono">{req.time_formatted}</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3.5 whitespace-nowrap">
                                                        {req.status === 'pending' && (
                                                            <span className="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full font-bold inline-block whitespace-nowrap">
                                                                ⏳ قيد المعالجة
                                                            </span>
                                                        )}
                                                        {req.status === 'approved' && (
                                                            <span className="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full font-bold inline-block whitespace-nowrap">
                                                                ✅ مقبول ومُضاف
                                                            </span>
                                                        )}
                                                        {req.status === 'rejected' && (
                                                            <div className="space-y-1">
                                                                <span className="px-2.5 py-1 bg-rose-50 text-rose-800 border border-rose-200 rounded-full font-bold inline-block whitespace-nowrap">
                                                                    ❌ فشل / مرفوض
                                                                </span>
                                                                {req.rejection_reason && (
                                                                    <p className="text-[11px] text-rose-600 font-semibold max-w-[200px] truncate" title={req.rejection_reason}>
                                                                        {req.rejection_reason}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="py-10 text-center text-gray-400 space-y-2">
                                    <span className="text-3xl block">📭</span>
                                    <p className="text-sm font-medium">لا توجد طلبات شحن سابقة حتى الآن.</p>
                                </div>
                            )}
                        </div>

                        {/* Section 2: Wallet Transactions Log */}
                        <div className="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 space-y-4">
                            <h3 className="font-bold text-gray-900 text-base border-b border-gray-100 pb-3 flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <span>📊</span>
                                    <span>السجل</span>
                                </span>
                                <span className="text-xs font-normal text-gray-500">إجمالي المعاملات: {transactions.length}</span>
                            </h3>

                            {transactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-right border-collapse">
                                        <thead>
                                            <tr className="bg-gray-50 text-gray-500 text-xs font-bold border-b border-gray-100">
                                                <th className="px-4 py-3 whitespace-nowrap">نوع العملية</th>
                                                <th className="px-4 py-3 whitespace-nowrap">المبلغ</th>
                                                <th className="px-4 py-3 whitespace-nowrap">التفاصيل والوصف</th>
                                                <th className="px-4 py-3 whitespace-nowrap">التاريخ والوقت</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 text-xs">
                                            {transactions.map((tx) => (
                                                <tr key={tx.id} className="hover:bg-gray-50/50 transition-colors">
                                                    <td className="px-4 py-3.5 whitespace-nowrap">
                                                        {tx.type === 'credit' ? (
                                                            <span className="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold whitespace-nowrap">
                                                                ➕ إيداع
                                                            </span>
                                                        ) : (
                                                            <span className="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full font-bold whitespace-nowrap">
                                                                ➖ خصم
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className={`px-4 py-3.5 font-mono font-extrabold text-sm whitespace-nowrap ${tx.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                        {tx.type === 'credit' ? '+' : '-'}{Math.round(tx.amount).toLocaleString('en-US')} ج.م
                                                    </td>
                                                    <td className="px-4 py-3.5 font-semibold text-gray-800">{tx.description || '-'}</td>
                                                    <td className="px-4 py-3.5 text-gray-600 whitespace-nowrap">
                                                        <div className="flex items-center gap-2" dir="ltr">
                                                            <span className="font-bold text-gray-800 text-xs">{tx.date_formatted}</span>
                                                            <span className="text-[11px] text-gray-400 font-mono">{tx.time_formatted}</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="py-10 text-center text-gray-400 space-y-2">
                                    <span className="text-3xl block">📋</span>
                                    <p className="text-sm font-medium">لا توجد حركات سابقة مؤكدة في المحفظة حتى الآن.</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* Receipt Modal */}
            {viewingReceipt && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
                    <div className="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden p-4 space-y-4">
                        <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                            <h4 className="font-bold text-gray-900 text-sm">صورة إيصال التحويل المرفقة</h4>
                            <button
                                onClick={() => setViewingReceipt(null)}
                                className="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 font-bold cursor-pointer"
                            >
                                ✕
                            </button>
                        </div>
                        <div className="max-h-[70vh] overflow-y-auto rounded-xl border border-gray-200">
                            <img src={viewingReceipt} alt="الإيصال" className="w-full h-auto object-contain" />
                        </div>
                    </div>
                </div>
            )}
        </MerchantLayout>
    );
}
