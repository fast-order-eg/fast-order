import React, { useState, useEffect } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import axios from 'axios';

export default function DomainEdit({ currentSlug, baseDomain, currentUrl, scheme }) {
    const { flash } = usePage().props;

    const [slugInput, setSlugInput] = useState(currentSlug || '');
    const [checkStatus, setCheckStatus] = useState({ loading: false, available: null, message: '' });
    const [showConfirmModal, setShowConfirmModal] = useState(false);
    const [copied, setCopied] = useState(false);

    // Default protocol to https for display if preferred, or matching scheme
    const displayScheme = scheme === 'https' ? 'https' : 'https';

    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, setData, put, processing, errors, clearErrors } = useForm({
        slug: currentSlug || '',
    });

    // Check slug availability on change with debounce
    useEffect(() => {
        const cleanSlug = slugInput.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');

        if (!cleanSlug || cleanSlug.length < 3) {
            setCheckStatus({
                loading: false,
                available: false,
                message: 'الرابط يجب أن يحتوي على 3 حروف أو أرقام إنجليزية على الأقل.'
            });
            return;
        }

        if (cleanSlug === currentSlug) {
            setCheckStatus({
                loading: false,
                available: true,
                is_current: true,
                message: 'هذا هو رابط متجرك الحالي بالفعل.'
            });
            return;
        }

        setCheckStatus({ loading: true, available: null, message: 'جاري فحص توفر الرابط...' });

        const timer = setTimeout(() => {
            axios.post('/admin/domain/check', { slug: cleanSlug })
                .then((res) => {
                    setCheckStatus({
                        loading: false,
                        available: res.data.available,
                        is_current: res.data.is_current || false,
                        message: res.data.message
                    });
                })
                .catch(() => {
                    setCheckStatus({
                        loading: false,
                        available: false,
                        message: 'تعذر الاتصال بالسيرفر لفحص الرابط.'
                    });
                });
        }, 400);

        return () => clearTimeout(timer);
    }, [slugInput, currentSlug]);

    const handleInputChange = (e) => {
        const val = e.target.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
        setSlugInput(val);
        setData('slug', val);
        clearErrors('slug');
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();
        if (slugInput === currentSlug) return;
        if (!checkStatus.available) return;
        
        setShowConfirmModal(true);
    };

    const confirmDomainChange = () => {
        setShowConfirmModal(false);
        setIsSubmitting(true);
        put('/admin/domain', {
            preserveScroll: true,
            onError: () => {
                setIsSubmitting(false);
            }
        });
    };

    const liveStoreUrl = `${displayScheme}://${slugInput || 'your-store'}.${baseDomain}/shop/index.html`;

    const copyToClipboard = () => {
        navigator.clipboard.writeText(liveStoreUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <MerchantLayout title="تغيير رابط المتجر">
            <Head title="تغيير رابط المتجر" />

            <div className="max-w-4xl space-y-6">
                {/* Flash Success Message */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border-r-4 border-emerald-500 rounded-2xl text-emerald-900 text-sm font-bold flex items-start gap-3 shadow-sm animate-fade-in">
                        <span className="text-xl">✅</span>
                        <div className="flex-1">{flash.success}</div>
                    </div>
                )}

                {/* Header Info Card */}
                <div className="bg-white rounded-3xl p-6 border border-gray-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <div className="flex flex-wrap items-center gap-2 mb-1.5">
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">
                                اسم المتجر الفرعي (Subdomain)
                            </span>
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                🔒 مشفر بمواصفات SSL (HTTPS)
                            </span>
                        </div>
                        <h2 className="text-xl font-extrabold text-gray-900">رابط متجرك الحالي</h2>
                        <p className="text-sm font-mono text-indigo-600 font-extrabold mt-1 dir-ltr text-left break-all" dir="ltr">
                            {displayScheme}://{currentSlug}.{baseDomain}
                        </p>
                    </div>

                    <a
                        href={`${scheme}://${currentSlug}.${baseDomain}/shop/index.html`}
                        target="_blank"
                        rel="noreferrer"
                        className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl transition-all flex items-center gap-2 shadow-md hover:shadow-indigo-200 flex-shrink-0"
                    >
                        {/* Eye Icon */}
                        <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>معاينة المتجر الحالي 👁️</span>
                    </a>
                </div>

                {/* Important Warning Banner */}
                <div className="bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-amber-500/10 border-2 border-rose-300 rounded-3xl p-6 shadow-sm">
                    <div className="flex items-start gap-4">
                        <div className="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-2xl font-bold flex-shrink-0 border border-rose-200">
                            ⚠️
                        </div>
                        <div className="space-y-1.5">
                            <h3 className="text-base font-extrabold text-rose-900">
                                تنبيه هام جداً قبل تغيير رابط المتجر:
                            </h3>
                            <ul className="text-xs text-rose-800 space-y-1 leading-relaxed list-disc list-inside font-semibold">
                                <li>
                                    <b>سيتوقف الرابط القديم تماماً وفوراً عن العمل</b> بمجرد حفظ الرابط الجديد.
                                </li>
                                <li>
                                    أي رابط قديم قمت بمشاركته مع زبائنك على وسائل التواصل الاجتماعي لن يصل إلى متجرك مجدداً.
                                </li>
                                <li>
                                    سيتعين عليك تزويد عملائك بالرابط الجديد واستبدال الروابط القديمة في حملاتك الإعلانية.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {/* Change Domain Form */}
                <div className="bg-white rounded-3xl p-6 border border-gray-200 shadow-sm space-y-6">
                    <div className="border-b border-gray-100 pb-4">
                        <h3 className="text-lg font-bold text-gray-900">أدخل الرابط الجديد المطلوب</h3>
                        <p className="text-xs text-gray-500 mt-1">اكتب الاسم باللغة الإنجليزية بدون مسافات أو رموز خاصة (يمكن استخدام الشرطة - بين الكلمات).</p>
                    </div>

                    <form onSubmit={handleFormSubmit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">
                                اسم رابط المتجر الجديد
                            </label>
                            
                            {/* Input container with proper spacing and clean borders */}
                            <div className="flex items-center gap-1 sm:gap-2 dir-ltr w-full max-w-full overflow-hidden" dir="ltr">
                                <span className="bg-slate-100 border border-slate-200 text-slate-700 font-mono text-xs sm:text-sm px-2 py-2.5 sm:px-3.5 sm:py-3 rounded-xl select-none font-bold flex items-center gap-0.5 sm:gap-1 shadow-sm flex-shrink-0">
                                    <span>🔒</span>
                                    <span>{displayScheme}://</span>
                                </span>

                                <input
                                    type="text"
                                    value={slugInput}
                                    onChange={handleInputChange}
                                    placeholder="my-store-name"
                                    className={`flex-1 min-w-0 px-2.5 py-2.5 sm:px-4 sm:py-3 rounded-xl border font-mono text-xs sm:text-base font-bold focus:outline-none transition-all shadow-sm ${
                                        checkStatus.available === true && !checkStatus.is_current
                                            ? 'border-emerald-500 bg-emerald-50/20 text-emerald-900 ring-2 ring-emerald-100'
                                            : (checkStatus.available === false
                                                ? 'border-rose-400 bg-rose-50/20 text-rose-900 ring-2 ring-rose-100'
                                                : 'border-gray-300 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100')
                                    }`}
                                    required
                                    minLength={3}
                                    maxLength={50}
                                />

                                <span className="bg-slate-100 border border-slate-200 text-slate-700 font-mono text-xs sm:text-sm px-2 py-2.5 sm:px-3.5 sm:py-3 rounded-xl select-none font-bold shadow-sm flex-shrink-0 max-w-[130px] sm:max-w-none truncate">
                                    .{baseDomain}
                                </span>
                            </div>

                            {/* Status and Errors Feedback */}
                            <div className="mt-3 text-xs font-bold">
                                {checkStatus.loading && (
                                    <div className="text-indigo-600 flex items-center gap-1.5 animate-pulse">
                                        <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        <span>جاري فحص توفر الرابط...</span>
                                    </div>
                                )}

                                {!checkStatus.loading && checkStatus.message && (
                                    <div className={`flex items-center gap-1.5 ${
                                        checkStatus.available === true && !checkStatus.is_current
                                            ? 'text-emerald-600'
                                            : (checkStatus.is_current ? 'text-indigo-600' : 'text-rose-600')
                                    }`}>
                                        <span>{checkStatus.available === true ? '✅' : '❌'}</span>
                                        <span>{checkStatus.message}</span>
                                    </div>
                                )}

                                {errors.slug && (
                                    <p className="text-rose-600 mt-1">{errors.slug}</p>
                                )}
                            </div>
                        </div>

                        {/* Live URL Preview Box with Eye Button & Copy */}
                        <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-xs text-gray-500 font-extrabold flex items-center gap-1">
                                    <span>👁️</span>
                                    <span>معاينة الرابط الكامل للعملاء بعد التغيير:</span>
                                </span>

                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={copyToClipboard}
                                        className="text-[11px] px-2.5 py-1 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded-lg transition-all shadow-sm"
                                    >
                                        {copied ? 'تم النسخ! 📋' : 'نسخ الرابط 📋'}
                                    </button>
                                </div>
                            </div>

                            <div className="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                <p className="text-sm font-mono font-extrabold text-indigo-700 dir-ltr text-left break-all flex-1" dir="ltr">
                                    {liveStoreUrl}
                                </p>
                                
                                <a
                                    href={liveStoreUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="p-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg transition-colors flex-shrink-0"
                                    title="معاينة رابط المتجر الجديد 👁️"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            </div>

                            <p className="text-[11px] text-emerald-700 font-semibold pt-0.5">
                                🔒 التشفير الآمن SSL مفعل تلقائياً على النطاق الرئيسي ودومينات العملاء الفرعية.
                            </p>
                        </div>

                        {/* Submit Button */}
                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={processing || checkStatus.loading || !checkStatus.available || slugInput === currentSlug}
                                className={`w-full sm:w-auto px-8 py-3.5 rounded-2xl font-extrabold text-sm transition-all shadow-md flex items-center justify-center gap-2 ${
                                    processing || checkStatus.loading || !checkStatus.available || slugInput === currentSlug
                                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed border border-gray-300'
                                        : 'bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white shadow-indigo-200 hover:shadow-lg'
                                }`}
                            >
                                <span>تأكيد وتغيير رابط المتجر الآن 🚀</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {/* ====== Modal: تأكيد تغيير الرابط ====== */}
            {showConfirmModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div className="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full text-center shadow-2xl border border-gray-100 animate-fade-in">
                        <div className="w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-3xl font-bold mx-auto mb-4 border border-amber-200">
                            🚨
                        </div>

                        <h3 className="text-xl font-extrabold text-gray-900 mb-2">
                            تأكيد تغيير رابط المتجر النهائي
                        </h3>

                        <div className="text-xs text-gray-600 leading-relaxed mb-4 space-y-2">
                            <p>هل أنت متأكد تماماً من تغيير رابط المتجر من:</p>
                            <span className="font-mono font-bold text-rose-600 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200 block dir-ltr" dir="ltr">
                                ({currentSlug})
                            </span>
                            <p>إلى الرابط الجديد النهائي:</p>
                            <span className="font-mono font-bold text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 block dir-ltr" dir="ltr">
                                ({slugInput})
                            </span>
                            <p className="text-rose-700 font-bold pt-1">
                                ⚠️ سيتم إيقاف الرابط القديم فوراً ولن يتمكن العملاء من استخدامه مجدداً.
                            </p>
                        </div>

                        <div className="flex flex-col sm:flex-row gap-3 pt-2">
                            <button
                                type="button"
                                onClick={confirmDomainChange}
                                disabled={processing || isSubmitting}
                                className="flex-1 py-3 px-4 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md"
                            >
                                نعم، غيّر الرابط الآن 🚀
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowConfirmModal(false)}
                                className="py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition-colors"
                            >
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* ====== Fullscreen Redirect Loading Indicator ====== */}
            {isSubmitting && (
                <div className="fixed inset-0 z-[999] flex flex-col items-center justify-center p-6 bg-slate-900/85 backdrop-blur-md text-white text-center">
                    <div className="w-16 h-16 border-4 border-indigo-400 border-t-white rounded-full animate-spin mb-5 shadow-lg"></div>
                    <h3 className="text-xl font-extrabold mb-2">جاري تفعيل الرابط الجديد وتحديث لوحة التحكم... 🚀</h3>
                    <p className="text-sm text-slate-300 max-w-md">يتم الآن نقلك تلقائياً إلى رابط متجرك ولوحة التحكم الجديدة في ثوانٍ معدودة، برجاء الانتظار...</p>
                </div>
            )}
        </MerchantLayout>
    );
}
