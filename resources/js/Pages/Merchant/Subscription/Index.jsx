import React, { useState } from 'react';
import { Head, Link, usePage, useForm } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function SubscriptionIndex({ subscription, plans, receipts, usage, tenant, paymentSettings }) {
    const { flash } = usePage().props;
    const [selectedPlan, setSelectedPlan] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [receiptPreview, setReceiptPreview] = useState(null);
    const [copiedField, setCopiedField] = useState(null);

    const vodaNumber = paymentSettings?.vodafone_cash_number || '';
    const instaNumber = paymentSettings?.instapay_number || '';

    const handleCopy = (text, field) => {
        if (!text) return;
        navigator.clipboard.writeText(text);
        setCopiedField(field);
        setTimeout(() => setCopiedField(null), 2000);
    };

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        plan_id: '',
        payment_method: 'vodafone_cash',
        payment_reference: '',
        amount: '',
        receipt: null,
    });

    const openSubscribeModal = (plan) => {
        setSelectedPlan(plan);
        const price = plan.price_monthly > 0 ? plan.price_monthly : (plan.price_yearly > 0 ? plan.price_yearly : 0);
        setData({
            plan_id: plan.id,
            payment_method: 'vodafone_cash',
            payment_reference: '',
            amount: price,
            receipt: null,
        });
        setReceiptPreview(null);
        clearErrors();
        setShowModal(true);
    };

    const closeSubscribeModal = () => {
        setShowModal(false);
        setSelectedPlan(null);
        reset();
    };

    const handleReceiptChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('receipt', file);
            const reader = new FileReader();
            reader.onload = (ev) => setReceiptPreview(ev.target.result);
            reader.readAsDataURL(file);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/subscription/subscribe', {
            forceFormData: true,
            onSuccess: () => {
                closeSubscribeModal();
            },
        });
    };

    // Formatter helpers
    const formatCurrency = (amount) => {
        if (!amount || amount == 0) return 'مجاناً';
        return Math.round(Number(amount)).toLocaleString('en-US') + ' ج.م';
    };

    const getLimitText = (max) => {
        return max >= 9999 ? 'غير محدود' : max.toLocaleString('en-US');
    };

    const getPercentage = (current, max) => {
        if (max >= 9999) return 0;
        if (max === 0) return 100;
        return Math.min(Math.round((current / max) * 100), 100);
    };

    const getStatusBadge = (status) => {
        const statuses = {
            trial: { text: 'فترة تجريبية', color: 'bg-blue-50 text-blue-700 border-blue-200' },
            active: { text: 'نشط (الباقة الافتراضية)', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
            expired: { text: 'غير نشط', color: 'bg-slate-50 text-slate-700 border-slate-200' },
            suspended: { text: 'موقوف مؤقتاً', color: 'bg-amber-50 text-amber-700 border-amber-200' },
        };
        const s = statuses[status] || { text: 'نشط', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        return (
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border ${s.color}`}>
                {s.text}
            </span>
        );
    };

    const getReceiptStatusBadge = (status) => {
        const statuses = {
            pending: { text: 'قيد المراجعة', color: 'bg-yellow-50 text-yellow-700 border-yellow-200' },
            approved: { text: 'تم التفعيل', color: 'bg-green-50 text-green-700 border-green-200' },
            rejected: { text: 'مرفوض', color: 'bg-red-50 text-red-700 border-red-200' },
        };
        const s = statuses[status] || { text: status, color: 'bg-gray-50 text-gray-700 border-gray-200' };
        return (
            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border ${s.color}`}>
                {s.text}
            </span>
        );
    };

    const isCommissionSub = subscription?.plan?.slug === 'commission' || subscription?.plan?.name?.includes('عمولة');
    const hasPaidPlan = subscription?.plan && subscription.plan.slug !== 'free' && (isCommissionSub || subscription.plan.slug === 'monthly' || subscription.plan.slug === 'yearly' || subscription.plan.price_monthly > 0 || subscription.plan.price_yearly > 0);
    const subEndsAtDate = tenant?.subscription_ends_at || subscription?.ends_at;
    const isExpiredSub = !isCommissionSub && ((tenant?.subscription_status === 'expired') || (subEndsAtDate && new Date(subEndsAtDate) < new Date()));

    // Filter plans: If user is on a paid plan (commission, monthly, yearly), hide the free plan completely
    const displayedPlans = plans.filter(plan => {
        const isFreePlan = plan.slug === 'free' || plan.name?.includes('مجانية');
        if (isFreePlan && hasPaidPlan) {
            return false;
        }
        return true;
    });

    return (
        <MerchantLayout title="الاشتراك والفوترة">
            <Head title="الاشتراك والفوترة" />

            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border-r-4 border-emerald-500 rounded-lg text-emerald-800 text-sm flex items-center gap-2 shadow-sm animate-pulse">
                        <svg className="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                        </svg>
                        <span className="font-semibold">{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border-r-4 border-red-500 rounded-lg text-red-800 text-sm flex items-center gap-2 shadow-sm">
                        <svg className="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                        </svg>
                        <span className="font-semibold">{flash.error}</span>
                    </div>
                )}

                {/* Top Card: Active Subscription Info */}
                <div className="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 pb-4 border-b border-gray-100">
                        <div>
                            <div className="flex items-center gap-2 mb-1">
                                <h3 className="text-gray-500 text-xs font-bold uppercase tracking-wider">اشتراكك الحالي</h3>
                                {getStatusBadge(isExpiredSub ? 'expired' : (subscription?.status || 'active'))}
                            </div>
                            <h2 className="text-2xl font-bold text-gray-900">
                                {subscription?.plan?.name || 'الباقة المجانية'}
                            </h2>
                            <p className="text-sm text-gray-500 mt-0.5">
                                {subscription?.plan?.description || 'باقة مجانية أساسية مفعلة تلقائياً لجميع المتاجر'}
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div className="bg-gray-50 p-3.5 rounded-xl border border-gray-100 flex items-center justify-between sm:flex-col sm:items-start sm:justify-center">
                            <span className="text-gray-500 text-xs font-semibold">{isCommissionSub ? 'نظام الباقة' : 'تاريخ انتهاء الاشتراك'}</span>
                            <span className="font-bold text-gray-900 text-base mt-0.5">
                                {isCommissionSub
                                    ? 'خصم عمولة على الطلبات ⚡'
                                    : (tenant?.subscription_ends_at || subscription?.ends_at || 'ينتهي بعد 7 أيام')}
                            </span>
                        </div>
                        <div className="bg-gray-50 p-3.5 rounded-xl border border-gray-100 flex items-center justify-between sm:flex-col sm:items-start sm:justify-center">
                            <span className="text-gray-500 text-xs font-semibold">التكلفة</span>
                            <span className="font-bold text-emerald-700 text-base mt-0.5">
                                {isCommissionSub
                                    ? 'عمولة 2ج / أوردر'
                                    : (subscription?.plan?.price_monthly > 0 ? formatCurrency(subscription.price) : 'مجاناً (0 ج.م)')}
                            </span>
                        </div>
                        <div className="bg-gray-50 p-3.5 rounded-xl border border-gray-100 flex items-center justify-between sm:flex-col sm:items-start sm:justify-center">
                            <span className="text-gray-500 text-xs font-semibold">حالة الباقة</span>
                            <span className={`font-bold text-base mt-0.5 ${isExpiredSub ? 'text-rose-600' : 'text-emerald-600'}`}>
                                {isExpiredSub ? 'منتهية / غير نشطة' : 'نشطة'}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Compare pricing plans */}
                <div className="space-y-6">
                    <div className="text-center max-w-xl mx-auto space-y-2 mt-6">
                        <h2 className="text-2xl font-bold text-gray-900">باقات الاشتراك المتاحة</h2>
                        <p className="text-sm text-gray-500">اختر الباقة الأنسب لحجم تجارتك. يمكنك الترقية أو تجديد الاشتراك في أي وقت.</p>
                    </div>

                    {/* Pricing Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {displayedPlans.map((plan) => {
                            const isCommissionPlan = plan.slug === 'commission' || plan.name?.includes('عمولة') || plan.name?.includes('محفظة');
                            const isFreePlan = plan.slug === 'free' || plan.name?.includes('مجانية');
                            const isMonthlyPlan = plan.slug === 'monthly' || plan.name?.includes('شهرية');
                            const isYearlyPlan = plan.slug === 'yearly' || plan.name?.includes('سنوية');
                            
                            const activePlanId = subscription?.plan?.id;
                            let isCurrent = false;
                            if (activePlanId) {
                                isCurrent = String(activePlanId) === String(plan.id);
                            } else {
                                isCurrent = isFreePlan;
                            }

                            const parsedLimits = typeof plan.limits === 'string' ? JSON.parse(plan.limits) : plan.limits;
                            const features = parsedLimits?.features || [];

                            return (
                                <div
                                    key={plan.id}
                                    className={`bg-white rounded-3xl p-6 border-2 flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-xl ${
                                        isCurrent
                                            ? 'border-emerald-500 ring-4 ring-emerald-50'
                                            : 'border-gray-200 hover:border-indigo-400'
                                    }`}
                                >
                                    <div>
                                        <div className="flex items-center justify-between mb-2">
                                            <h3 className="text-xl font-bold text-gray-900">{plan.name}</h3>
                                            {isCurrent && (
                                                <span className={`text-xs px-2.5 py-1 rounded-full font-bold ${
                                                    isFreePlan && isExpiredSub
                                                        ? 'bg-rose-100 text-rose-700 border border-rose-200'
                                                        : 'bg-emerald-600 text-white'
                                                }`}>
                                                    {isFreePlan && isExpiredSub ? 'باقة منتهية' : 'باقتك الحالية'}
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-sm text-gray-500 min-h-[40px] mb-4">
                                            {isCommissionPlan
                                                ? 'باقة الخصم بالعمولة على كل طلب ناجح عبر شحن محفظة التاجر. يظل متجرك مفتوحاً دائماً أمام العملاء.'
                                                : (isFreePlan 
                                                    ? 'الباقة المجانية مدتها 7 أيام فقط وتنتهي بانتهاء المدة (7 أيام) أو عند الوصول لعدد 50 طلب.' 
                                                    : (isMonthlyPlan || isYearlyPlan
                                                        ? 'سيظل متجرك مفتوحاً دائماً أمام العملاء، وعند انتهاء الاشتراك يلزم التجديد أو التحويل لباقة العمولة لاستعراض الطلبات.'
                                                        : plan.description))}
                                        </p>
                                        
                                        <div className="mb-6">
                                            {isMonthlyPlan && (
                                                <div className="flex flex-col mb-1 items-start">
                                                    <span className="text-sm text-red-500 font-bold line-through">1,000 ج.م</span>
                                                    <span className="text-xs font-extrabold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full mt-1 border border-emerald-200">
                                                        السعر الحالي لفترة محدودة جداً! (500ج بدلاً من 1000ج - وفر 50%)
                                                    </span>
                                                </div>
                                            )}

                                            {isYearlyPlan && (
                                                <div className="flex flex-col mb-1 items-start">
                                                    <span className="text-sm text-red-500 font-bold line-through">10,000 ج.م</span>
                                                    <span className="text-xs font-extrabold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full mt-1 border border-emerald-200">
                                                        خصم 50% بمناسبة الإطلاق! (5,000ج بدلاً من 10,000ج سنوياً)
                                                    </span>
                                                </div>
                                            )}

                                            {isFreePlan && (
                                                <div className="flex flex-col mb-1 items-start">
                                                    <span className="text-xs font-bold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full mt-1 border border-indigo-200">
                                                        تُفعل لمدة 7 أيام أو 50 طلب فقط
                                                    </span>
                                                </div>
                                            )}

                                            {isCommissionPlan && (
                                                <div className="flex flex-col mb-1 items-start">
                                                    <span className="text-xs font-bold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full mt-1 border border-amber-200">
                                                        عمولة 2 ج.م فقط تخصم تلقائياً لكل أوردر
                                                    </span>
                                                </div>
                                            )}

                                            {isCommissionPlan ? (
                                                <div className="mt-2">
                                                    <span className="text-3xl font-extrabold text-emerald-700">2 ج.م</span>
                                                    <span className="text-gray-500 text-sm font-semibold mr-1.5">/ لكل أوردر ناجح</span>
                                                </div>
                                            ) : (
                                                <div className="mt-2">
                                                    <span className="text-4xl font-extrabold text-gray-900">
                                                        {isYearlyPlan ? '5,000 ج.م' : formatCurrency(plan.price_monthly || plan.price_yearly)}
                                                    </span>
                                                    {plan.price_monthly > 0 && (
                                                        <span className="text-gray-400 text-sm font-semibold mr-1">
                                                            / {isYearlyPlan ? 'سنوياً' : 'شهرياً'}
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                        </div>

                                        {/* Limits & Features list */}
                                        <ul className="space-y-3 text-sm text-gray-600 mb-6 border-t border-gray-100 pt-4">
                                            {isFreePlan ? (
                                                <li className="flex items-start gap-2.5 bg-rose-50 p-2.5 rounded-xl border border-rose-200 text-rose-900 font-bold text-xs">
                                                    <span className="text-base leading-none">🔴</span>
                                                    <span>سيتوقف المتجر تلقائياً بعد انتهاء المدة (7 أيام أو 50 طلب)</span>
                                                </li>
                                            ) : (
                                                <li className="flex items-start gap-2.5 bg-emerald-50 p-2.5 rounded-xl border border-emerald-200 text-emerald-900 font-bold text-xs">
                                                    <span className="text-base leading-none">🟢</span>
                                                    <span>سيظل المتجر مفتوحاً أمام العملاء دائماً لاستقبال الطلبات</span>
                                                </li>
                                            )}
                                            <li className="flex items-center gap-2.5">
                                                <svg className="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>أقصى عدد منتجات: <b>{isMonthlyPlan || isYearlyPlan || isCommissionPlan ? 'غير محدود' : getLimitText(parsedLimits?.max_products ?? 50)}</b></span>
                                            </li>
                                            <li className="flex items-center gap-2.5">
                                                <svg className="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>أقصى عدد طلبات: <b>{isMonthlyPlan || isYearlyPlan || isCommissionPlan ? 'غير محدود' : '50 طلب (أو 7 أيام)'}</b></span>
                                            </li>
                                            {features.map((feature, i) => (
                                                <li key={i} className="flex items-center gap-2.5">
                                                    <svg className="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>{feature}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>

                                    {isFreePlan ? (
                                        <button
                                            type="button"
                                            disabled
                                            className="w-full py-3 rounded-2xl font-bold text-center bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200"
                                        >
                                            {isExpiredSub ? 'باقة تجريبية منتهية' : 'باقتك الحالية (تجريبية)'}
                                        </button>
                                    ) : isCommissionPlan ? (
                                        isCurrent ? (
                                            <button
                                                type="button"
                                                disabled
                                                className="w-full py-3 rounded-2xl font-bold text-center bg-emerald-50 text-emerald-700 cursor-not-allowed border border-emerald-200"
                                            >
                                                باقتك الحالية (نشطة)
                                            </button>
                                        ) : (
                                            <Link
                                                href={route('merchant.wallet.index')}
                                                className="w-full py-3 rounded-2xl font-extrabold text-center transition-all bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg hover:shadow-emerald-200 block"
                                            >
                                                اشحن المحفظة 💳
                                            </Link>
                                        )
                                    ) : (
                                        <button
                                            type="button"
                                            disabled={isCurrent}
                                            onClick={() => openSubscribeModal(plan)}
                                            className={`w-full py-3 rounded-2xl font-bold text-center transition-all ${
                                                isCurrent
                                                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
                                                    : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg hover:shadow-indigo-200 cursor-pointer'
                                            }`}
                                        >
                                            {isCurrent ? 'باقتك الحالية' : 'اشترك الآن'}
                                        </button>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Receipts History */}
                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mt-8">
                    <div className="p-5 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-bold text-gray-900">سجل طلبات الاشتراك اليدوية</h3>
                            <p className="text-xs text-gray-500 mt-0.5">تابع حالة طلبات الترقية والتحويلات اليدوية التي قمت برفعها.</p>
                        </div>
                    </div>

                    {receipts.length === 0 ? (
                        <div className="text-center py-12">
                            <svg className="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <p className="text-gray-500 font-medium">لا توجد طلبات اشتراك سابقة</p>
                            <p className="text-gray-400 text-xs mt-1">تظهر هنا طلبات الدفع عبر فودافون كاش أو انستا باي بعد رفع إيصال الدفع.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-right">
                                <thead>
                                    <tr className="bg-gray-50 border-b border-gray-200">
                                        <th className="px-5 py-3.5 font-bold text-gray-700">الباقة المطلوبة</th>
                                        <th className="px-5 py-3.5 font-bold text-gray-700">تاريخ الطلب</th>
                                        <th className="px-5 py-3.5 font-bold text-gray-700">المبلغ المدفوع</th>
                                        <th className="px-5 py-3.5 font-bold text-gray-700">طريقة التحويل</th>
                                        <th className="px-5 py-3.5 font-bold text-gray-700">رقم المرجع/التحويل</th>
                                        <th className="px-5 py-3.5 font-bold text-gray-700">صورة الإيصال</th>
                                        <th className="px-5 py-3.5 font-bold text-gray-700">حالة الطلب</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {receipts.map((receipt) => (
                                        <tr key={receipt.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-5 py-4 font-semibold text-gray-900">
                                                {receipt.plan_name}
                                            </td>
                                            <td className="px-5 py-4 text-gray-500">{receipt.created_at}</td>
                                            <td className="px-5 py-4 font-bold text-gray-900">{formatCurrency(receipt.amount)}</td>
                                            <td className="px-5 py-4 text-gray-600 font-bold">
                                                {receipt.payment_method === 'instapay' ? 'إنستا باي' : 'فودافون كاش'}
                                            </td>
                                            <td className="px-5 py-4 text-gray-600 font-mono">{receipt.payment_reference}</td>
                                            <td className="px-5 py-4">
                                                <a
                                                    href={receipt.receipt_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors"
                                                >
                                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    عرض الإيصال
                                                </a>
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="flex flex-col gap-1">
                                                    {getReceiptStatusBadge(receipt.status)}
                                                    {receipt.status === 'rejected' && receipt.rejection_reason && (
                                                        <span className="text-xs text-red-600 font-medium max-w-[150px] leading-tight">
                                                            السبب: {receipt.rejection_reason}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Manual Payment Receipt Form Modal */}
            {showModal && selectedPlan && (
                <div className="fixed inset-0 bg-black/60 z-50 flex items-start sm:items-center justify-center p-2 sm:p-4 overflow-y-auto" dir="rtl">
                    <div className="bg-white rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl relative border border-gray-100 my-auto transform transition-all duration-300 max-h-[92vh] flex flex-col">
                        
                        {/* Sticky Header */}
                        <div className="flex items-center justify-between pb-3 border-b border-gray-100 mb-3 flex-shrink-0 bg-white z-10">
                            <div>
                                <h3 className="text-base sm:text-lg font-bold text-gray-900">رفع إيصال الدفع للباقة</h3>
                                <p className="text-xs text-gray-500 mt-0.5">الباقة المطلوبة: <span className="font-bold text-indigo-600">{selectedPlan.name}</span></p>
                            </div>
                            <button
                                onClick={closeSubscribeModal}
                                className="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 transition-colors"
                                title="إغلاق"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {/* Scrollable Body */}
                        <div className="flex-1 overflow-y-auto pr-1 pl-1 space-y-4">
                            {/* Approved wallets/payment details from Super Admin */}
                            <div className="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-3.5 text-sm text-indigo-900 space-y-2.5">
                                <h4 className="font-bold flex items-center gap-1.5 text-indigo-950 text-xs sm:text-sm">
                                    <svg className="w-4 h-4 sm:w-5 sm:h-5 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    بيانات التحويل المعتمدة للمنصة:
                                </h4>
                                
                                <div className="grid grid-cols-1 gap-2 pt-1 border-t border-indigo-100">
                                    {/* Vodafone Cash Card */}
                                    <div className="bg-white p-2.5 sm:p-3 rounded-xl border border-indigo-100 flex items-center justify-between shadow-2xs">
                                        <div>
                                            <p className="text-[11px] text-indigo-600 font-bold">رقم فودافون كاش</p>
                                            <p className="text-sm sm:text-base font-extrabold font-mono text-gray-900 mt-0.5">{vodaNumber}</p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => handleCopy(vodaNumber, 'voda')}
                                            className={`px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 border ${
                                                copiedField === 'voda'
                                                    ? 'bg-emerald-600 text-white border-emerald-600'
                                                    : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border-indigo-200'
                                            }`}
                                        >
                                            {copiedField === 'voda' ? 'تم النسخ ✓' : 'نسخ الرقم 📋'}
                                        </button>
                                    </div>

                                    {/* InstaPay Card */}
                                    <div className="bg-white p-2.5 sm:p-3 rounded-xl border border-indigo-100 flex items-center justify-between shadow-2xs">
                                        <div>
                                            <p className="text-[11px] text-indigo-600 font-bold">رقم إنستا باي (InstaPay)</p>
                                            <p className="text-sm sm:text-base font-extrabold font-mono text-gray-900 mt-0.5">{instaNumber}</p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => handleCopy(instaNumber, 'insta')}
                                            className={`px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 border ${
                                                copiedField === 'insta'
                                                    ? 'bg-emerald-600 text-white border-emerald-600'
                                                    : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border-indigo-200'
                                            }`}
                                        >
                                            {copiedField === 'insta' ? 'تم النسخ ✓' : 'نسخ الرقم 📋'}
                                        </button>
                                    </div>
                                </div>

                                <p className="text-[11px] sm:text-xs text-indigo-700 leading-normal pt-1">
                                    * يرجى تحويل القيمة الإجمالية للباقة <b>({formatCurrency(selectedPlan.price_monthly || selectedPlan.price_yearly)})</b> ثم كتابة رقم التحويل ورفع صورة الإيصال أدناه.
                                </p>
                            </div>

                            {/* Form */}
                            <form onSubmit={handleSubmit} className="space-y-3.5">
                                {/* Payment Method */}
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">طريقة الدفع المستخدمة:</label>
                                    <div className="grid grid-cols-2 gap-3">
                                        <label className={`border rounded-2xl p-2.5 sm:p-3 flex items-center justify-center gap-2 cursor-pointer transition-all ${
                                            data.payment_method === 'vodafone_cash'
                                                ? 'border-indigo-600 bg-indigo-50/50 text-indigo-950 font-bold shadow-xs'
                                                : 'border-gray-200 hover:bg-gray-50 text-gray-600'
                                        }`}>
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value="vodafone_cash"
                                                checked={data.payment_method === 'vodafone_cash'}
                                                onChange={(e) => setData('payment_method', e.target.value)}
                                                className="hidden"
                                            />
                                            <span className="text-xs sm:text-sm">فودافون كاش</span>
                                        </label>
                                        <label className={`border rounded-2xl p-2.5 sm:p-3 flex items-center justify-center gap-2 cursor-pointer transition-all ${
                                            data.payment_method === 'instapay'
                                                ? 'border-indigo-600 bg-indigo-50/50 text-indigo-950 font-bold shadow-xs'
                                                : 'border-gray-200 hover:bg-gray-50 text-gray-600'
                                        }`}>
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value="instapay"
                                                checked={data.payment_method === 'instapay'}
                                                onChange={(e) => setData('payment_method', e.target.value)}
                                                className="hidden"
                                            />
                                            <span className="text-xs sm:text-sm">إنستا باي</span>
                                        </label>
                                    </div>
                                    {errors.payment_method && <p className="text-xs text-red-500 mt-1">{errors.payment_method}</p>}
                                </div>

                                {/* Amount & Reference */}
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-700 mb-1">المبلغ المحول (ج.م):</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={data.amount}
                                            onChange={(e) => setData('amount', e.target.value)}
                                            className="w-full px-3 py-2 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors font-bold text-gray-900"
                                        />
                                        {errors.amount && <p className="text-xs text-red-500 mt-1">{errors.amount}</p>}
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-700 mb-1">
                                            {data.payment_method === 'vodafone_cash' ? 'رقم المحفظة المحول منها:' : 'رقم إنستا باي المحول منه:'}
                                        </label>
                                        <input
                                            type="text"
                                            placeholder="مثال: 01012345678"
                                            value={data.payment_reference}
                                            onChange={(e) => setData('payment_reference', e.target.value)}
                                            className="w-full px-3 py-2 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors font-mono"
                                        />
                                        {errors.payment_reference && <p className="text-xs text-red-500 mt-1">{errors.payment_reference}</p>}
                                    </div>
                                </div>

                                {/* Receipt File */}
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">صورة إيصال التحويل البنكي:</label>
                                    <div className="border-2 border-dashed border-gray-300 rounded-2xl p-3 text-center cursor-pointer hover:border-indigo-400 transition-colors relative bg-gray-50 flex flex-col items-center justify-center min-h-[110px]">
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={handleReceiptChange}
                                            className="absolute inset-0 opacity-0 cursor-pointer"
                                        />
                                        {receiptPreview ? (
                                            <div className="relative w-full max-h-[140px] overflow-hidden rounded-xl">
                                                <img
                                                    src={receiptPreview}
                                                    alt="Receipt preview"
                                                    className="w-full h-auto max-h-[140px] object-contain rounded-xl"
                                                />
                                                <div className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                                    <p className="text-white text-xs font-bold">تغيير الصورة</p>
                                                </div>
                                            </div>
                                        ) : (
                                            <>
                                                <svg className="w-7 h-7 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span className="text-xs font-bold text-indigo-600 hover:text-indigo-800">اضغط لرفع صورة الإيصال</span>
                                                <span className="text-[10px] text-gray-400 mt-0.5">JPEG, PNG, JPG (الحد الأقصى 2 ميجابايت)</span>
                                            </>
                                        )}
                                    </div>
                                    {errors.receipt && <p className="text-xs text-red-500 mt-1">{errors.receipt}</p>}
                                </div>

                                {/* Submit & Cancel Buttons */}
                                <div className="flex gap-3 pt-3 border-t border-gray-100">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="flex-1 bg-indigo-600 text-white py-2.5 rounded-2xl font-bold text-center hover:bg-indigo-700 shadow-md hover:shadow-indigo-200 transition-all flex items-center justify-center gap-2 text-sm"
                                    >
                                        {processing ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                                </svg>
                                                جاري الإرسال...
                                            </>
                                        ) : (
                                            'إرسال'
                                        )}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={closeSubscribeModal}
                                        className="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-2xl font-bold hover:bg-gray-50 transition-colors text-sm"
                                    >
                                        إلغاء
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </MerchantLayout>
    );
}
