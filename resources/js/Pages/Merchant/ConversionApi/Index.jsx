import React, { useState } from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function ConversionApiIndex({ pixels = [] }) {
    const { flash } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isGuideOpen, setIsGuideOpen] = useState(false);
    const [editingPixel, setEditingPixel] = useState(null);
    const [testingId, setTestingId] = useState(null);
    const [testResult, setTestResult] = useState(null);
    const [visibleTokens, setVisibleTokens] = useState({});

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        platform: 'facebook',
        pixel_id: '',
        access_token: '',
        test_event_code: '',
        note: '',
        is_active: true,
    });

    const openAddModal = () => {
        setEditingPixel(null);
        clearErrors();
        reset();
        setData({
            platform: 'facebook',
            pixel_id: '',
            access_token: '',
            test_event_code: '',
            note: '',
            is_active: true,
        });
        setIsModalOpen(true);
    };

    const openEditModal = (pixel) => {
        setEditingPixel(pixel);
        clearErrors();
        setData({
            platform: pixel.platform,
            pixel_id: pixel.pixel_id,
            access_token: pixel.access_token,
            test_event_code: pixel.test_event_code || '',
            note: pixel.note || '',
            is_active: !!pixel.is_active,
        });
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingPixel(null);
        reset();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingPixel) {
            put(`/admin/conversion-api/${editingPixel.id}`, {
                onSuccess: () => closeModal(),
            });
        } else {
            post('/admin/conversion-api', {
                onSuccess: () => closeModal(),
            });
        }
    };

    const handleDelete = (pixel) => {
        if (confirm(`هل أنت متأكد من حذف بيكسل (${pixel.platform} - ${pixel.pixel_id})؟`)) {
            router.delete(`/admin/conversion-api/${pixel.id}`);
        }
    };

    const handleToggle = (pixel) => {
        router.patch(`/admin/conversion-api/${pixel.id}/toggle`, {}, { preserveScroll: true });
    };

    const handleTestEvent = async (pixel) => {
        setTestingId(pixel.id);
        setTestResult(null);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/admin/conversion-api/${pixel.id}/test`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            const result = await res.json();
            setTestResult({ pixelId: pixel.id, ...result });
        } catch (err) {
            setTestResult({
                pixelId: pixel.id,
                success: false,
                message: 'حدث خطأ أثناء الاتصال بالخادم: ' + err.message,
            });
        } finally {
            setTestingId(null);
        }
    };

    const toggleTokenVisibility = (id) => {
        setVisibleTokens((prev) => ({ ...prev, [id]: !prev[id] }));
    };

    const getPlatformBadge = (platform) => {
        switch (platform?.toLowerCase()) {
            case 'facebook':
            case 'meta':
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Meta (Facebook)
                    </span>
                );
            case 'tiktok':
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-900 text-white border border-gray-700">
                        <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                        </svg>
                        TikTok
                    </span>
                );
            case 'snapchat':
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 3.2c3.08 0 5.2 2.2 5.2 5.3 0 1.2-.4 2.5-.9 3.4.8.4 1.8.8 2.2 1.5.4.7.1 1.6-.6 2.1-.6.4-1.5.5-2.2.3-.5 1-1.5 1.7-2.7 1.9-.3.6-.9 1.1-1.6 1.1h-.8c-.7 0-1.3-.5-1.6-1.1-1.2-.2-2.2-.9-2.7-1.9-.7.2-1.6.1-2.2-.3-.7-.5-1-1.4-.6-2.1.4-.7 1.4-1.1 2.2-1.5-.5-.9-.9-2.2-.9-3.4 0-3.1 2.1-5.3 5.2-5.3z" />
                        </svg>
                        Snapchat
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                        {platform}
                    </span>
                );
        }
    };

    return (
        <MerchantLayout title="Conversion API">
            <Head title="Conversion API - التتبع المباشر من السيرفر" />

            <div className="space-y-6 text-right" dir="rtl">
                {/* Top Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
                    <div>
                        <div className="flex items-center gap-3">
                            <span className="text-3xl">🎯</span>
                            <div>
                                <h1 className="text-2xl font-black text-gray-900">Conversion API (CAPI)</h1>
                                <p className="text-sm text-gray-500 mt-1">
                                    أرسل أحداث الشراء (Purchase) مباشرة من السيرفر لسيرفرات Meta و TikTok و Snapchat لتفادي AdBlockers و iOS 14+ ورفع دقة النتائج لـ 100%.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3 flex-wrap">
                        <button
                            type="button"
                            onClick={() => setIsGuideOpen(true)}
                            className="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-xl transition-all shadow-sm"
                        >
                            <span>📖</span> كيفية استخدام الـ Conversion API
                        </button>
                        <button
                            type="button"
                            onClick={openAddModal}
                            className="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-black rounded-xl transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            إضافة بيكسل جديد
                        </button>
                    </div>
                </div>

                {/* Info Notice Banners */}
                <div className="space-y-3">
                    <div className="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3 text-amber-900 text-sm leading-relaxed">
                        <span className="text-xl">⚠️</span>
                        <div>
                            <strong>تنبيه هام للميديا باير:</strong> يعمل نظام الـ Conversions API لدينا بالتكامل مع البيكسل العادي عبر تقنية <strong>Event Deduplication الموحدة</strong> بمعرف (<code className="bg-amber-100 px-1 py-0.5 rounded font-mono text-xs">event_id</code>)، مما يضمن عدم تكرار الأوردر في الإحصائيات ورفع جودة المطابقة (Match Quality) لأعلى تصنيف.
                        </div>
                    </div>
                    <div className="p-4 bg-blue-50 border border-blue-200 rounded-2xl flex items-start gap-3 text-blue-900 text-sm leading-relaxed">
                        <span className="text-xl">🧪</span>
                        <div>
                            <strong>اختبار لايف:</strong> يمكنك وضع كود الاختبار (<code className="bg-blue-100 px-1 py-0.5 rounded font-mono text-xs">Test Event Code</code>) المستخرج من تبويب Test Events في مدير الأحداث، والضغط على زر <strong>"تجربة إرسال حدث"</strong> للتأكد من وصول البيانات مباشرة.
                        </div>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm font-bold flex items-center gap-3 shadow-sm">
                        <span className="flex items-center justify-center w-6 h-6 bg-emerald-100 rounded-full text-emerald-600 text-xs">✓</span>
                        {flash.success}
                    </div>
                )}

                {/* Test Result Live Banner */}
                {testResult && (
                    <div className={`p-5 rounded-2xl border ${testResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-red-50 border-red-200 text-red-900'} shadow-sm transition-all animate-fadeIn`}>
                        <div className="flex items-center justify-between gap-4 mb-2">
                            <div className="flex items-center gap-2 font-black text-base">
                                <span>{testResult.success ? '✅' : '❌'}</span>
                                <span>{testResult.message}</span>
                            </div>
                            <button
                                onClick={() => setTestResult(null)}
                                className="text-xs text-gray-500 hover:text-gray-700 bg-white/60 px-2 py-1 rounded"
                            >
                                إغلاق ✕
                            </button>
                        </div>
                        {testResult.response && (
                            <pre className="mt-2 p-3 bg-white/80 rounded-xl text-xs font-mono text-gray-800 overflow-x-auto text-left" dir="ltr">
                                {JSON.stringify(testResult.response, null, 2)}
                            </pre>
                        )}
                    </div>
                )}

                {/* Pixels Grid / Table */}
                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div className="p-6 border-b border-gray-100 flex items-center justify-between">
                        <h2 className="text-lg font-black text-gray-900 flex items-center gap-2">
                            <span>📡</span> البيكسلات المربوطة ({pixels.length})
                        </h2>
                    </div>

                    {pixels.length === 0 ? (
                        <div className="text-center py-16 px-4">
                            <div className="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                                🎯
                            </div>
                            <h3 className="text-lg font-bold text-gray-800 mb-1">لا يوجد بيكسل مضاف حتى الآن</h3>
                            <p className="text-sm text-gray-500 max-w-md mx-auto mb-6">
                                ابدأ بإضافة أول بيكسل Conversion API لتتبع أوردرات متجرك من السيرفر مباشرة.
                            </p>
                            <button
                                type="button"
                                onClick={openAddModal}
                                className="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-black rounded-xl transition-all shadow"
                            >
                                + إضافة بيكسل جديد
                            </button>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {pixels.map((pixel) => {
                                const isVisible = !!visibleTokens[pixel.id];
                                const isTesting = testingId === pixel.id;

                                return (
                                    <div key={pixel.id} className="p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-6 hover:bg-gray-50/70 transition-colors">
                                        <div className="space-y-2 flex-1">
                                            <div className="flex items-center gap-3 flex-wrap">
                                                {getPlatformBadge(pixel.platform)}
                                                <span className="font-mono text-base font-bold text-gray-900 bg-gray-100 px-2.5 py-1 rounded-lg">
                                                    ID: {pixel.pixel_id}
                                                </span>
                                                {pixel.note && (
                                                    <span className="text-xs text-gray-600 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md">
                                                        🏷️ {pixel.note}
                                                    </span>
                                                )}
                                                <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-bold ${pixel.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600'}`}>
                                                    {pixel.is_active ? '🟢 نشط' : '⚪ معطل'}
                                                </span>
                                            </div>

                                            {/* Access Token display */}
                                            <div className="flex items-center gap-2 text-xs text-gray-500 mt-2">
                                                <span className="font-semibold text-gray-700">Access Token:</span>
                                                <code className="font-mono bg-gray-100 px-2 py-1 rounded max-w-xs md:max-w-md truncate" dir="ltr">
                                                    {isVisible ? pixel.access_token : '••••••••••••••••••••••••••••••••••••••••••••••'}
                                                </code>
                                                <button
                                                    type="button"
                                                    onClick={() => toggleTokenVisibility(pixel.id)}
                                                    className="text-gray-500 hover:text-gray-800 text-xs font-semibold px-1.5 py-0.5 rounded bg-gray-200"
                                                >
                                                    {isVisible ? 'إخفاء 👁️' : 'إظهار 👁️'}
                                                </button>
                                            </div>

                                            {/* Test Code */}
                                            {pixel.test_event_code && (
                                                <div className="text-xs text-blue-700 font-medium">
                                                    كود التيست: <code className="bg-blue-50 border border-blue-200 px-2 py-0.5 rounded font-mono font-bold" dir="ltr">{pixel.test_event_code}</code>
                                                </div>
                                            )}
                                        </div>

                                        {/* Action buttons */}
                                        <div className="flex items-center gap-2.5 flex-wrap">
                                            <button
                                                type="button"
                                                onClick={() => handleTestEvent(pixel)}
                                                disabled={isTesting}
                                                className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200 transition-all disabled:opacity-50 shadow-sm"
                                            >
                                                {isTesting ? (
                                                    <>
                                                        <svg className="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                            <circle cx="12" cy="12" r="10" strokeWidth="4" className="opacity-25" />
                                                            <path fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" className="opacity-75" />
                                                        </svg>
                                                        جاري الإرسال...
                                                    </>
                                                ) : (
                                                    <>
                                                        <span>🧪</span> تجربة إرسال حدث تيست
                                                    </>
                                                )}
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => handleToggle(pixel)}
                                                className={`px-3 py-2 text-xs font-bold rounded-xl border transition-all ${pixel.is_active ? 'bg-gray-100 hover:bg-gray-200 text-gray-700 border-gray-300' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200'}`}
                                            >
                                                {pixel.is_active ? 'تعطيل' : 'تفعيل'}
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => openEditModal(pixel)}
                                                className="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all"
                                            >
                                                ✏️ تعديل
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => handleDelete(pixel)}
                                                className="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold rounded-xl border border-red-200 transition-all"
                                            >
                                                🗑️ حذف
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            {/* Add / Edit Modal */}
            {isModalOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm p-3 sm:p-6 flex justify-center items-center" dir="rtl">
                    <div className="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-gray-100 animate-scaleUp my-auto max-h-[92vh] flex flex-col">
                        <div className="flex items-center justify-between pb-3 sm:pb-4 border-b border-gray-100 mb-3 sm:mb-4 shrink-0">
                            <h3 className="text-lg sm:text-xl font-black text-gray-900 flex items-center gap-2">
                                <span>🎯</span> {editingPixel ? 'تعديل بيكسل CAPI' : 'إضافة بيكسل CAPI جديد'}
                            </h3>
                            <button
                                type="button"
                                onClick={closeModal}
                                className="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center text-sm font-bold transition-colors"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="flex flex-col flex-1 overflow-hidden min-h-0">
                            <div className="space-y-3.5 sm:space-y-4 overflow-y-auto pr-1 pl-1 flex-1 py-1">
                                {/* Platform */}
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1.5">
                                        نوع المنصة <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={data.platform}
                                        onChange={(e) => setData('platform', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all outline-none"
                                    >
                                        <option value="facebook">Meta (Facebook)</option>
                                        <option value="tiktok">TikTok</option>
                                        <option value="snapchat">Snapchat</option>
                                    </select>
                                    {errors.platform && <p className="text-red-500 text-xs mt-1">{errors.platform}</p>}
                                </div>

                                {/* Pixel ID */}
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1.5">
                                        معرّف البيكسل (Pixel ID) <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.pixel_id}
                                        onChange={(e) => setData('pixel_id', e.target.value.trim())}
                                        placeholder="مثال: 123456789012345"
                                        className="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all outline-none"
                                        dir="ltr"
                                    />
                                    {errors.pixel_id && <p className="text-red-500 text-xs mt-1">{errors.pixel_id}</p>}
                                </div>

                                {/* Access Token */}
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1.5">
                                        رمز الوصول (Access Token) <span className="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        value={data.access_token}
                                        onChange={(e) => setData('access_token', e.target.value.trim())}
                                        rows="3"
                                        placeholder="EAAG..."
                                        className="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all outline-none"
                                        dir="ltr"
                                    />
                                    <p className="text-[11px] text-gray-500 mt-1">
                                        يتم توليده من تبويب Settings في Events Manager تحت قسم Conversions API.
                                    </p>
                                    {errors.access_token && <p className="text-red-500 text-xs mt-1">{errors.access_token}</p>}
                                </div>

                                {/* Test Event Code */}
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1.5">
                                        كود الأحداث التجريبية (Test Event Code) <span className="text-gray-400 font-normal">(اختياري للتيست)</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.test_event_code}
                                        onChange={(e) => setData('test_event_code', e.target.value.trim())}
                                        placeholder="مثال: TEST12345"
                                        className="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all outline-none"
                                        dir="ltr"
                                    />
                                    <p className="text-[11px] text-gray-500 mt-1">
                                        إذا كنت تقوم باختبار الأحداث في تبويب Test Events انسخ الكود وضعه هنا. قم بحذفه عند بدء الحملات الفعلية.
                                    </p>
                                </div>

                                {/* Note */}
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1.5">
                                        ملاحظة لتمييز البيكسل <span className="text-gray-400 font-normal">(اختياري)</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.note}
                                        onChange={(e) => setData('note', e.target.value)}
                                        placeholder="مثال: بيكسل الملابس / بيكسل العطور"
                                        className="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all outline-none"
                                    />
                                </div>

                                {/* Active checkbox */}
                                <div className="flex items-center gap-2 pt-1">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500 border-gray-300 cursor-pointer"
                                    />
                                    <label htmlFor="is_active" className="text-sm font-bold text-gray-800 cursor-pointer">
                                        تفعيل إرسال الأحداث لهذا البيكسل فوراً
                                    </label>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex items-center justify-end gap-3 pt-3 sm:pt-4 border-t border-gray-100 shrink-0 mt-2">
                                <button
                                    type="button"
                                    onClick={closeModal}
                                    className="px-4 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-all"
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-black rounded-xl transition-all shadow-md disabled:opacity-50"
                                >
                                    {processing ? 'جاري الحفظ...' : editingPixel ? 'حفظ التعديلات' : 'إضافة البيكسل'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Guide Modal */}
            {isGuideOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm p-3 sm:p-6 flex justify-center items-center" dir="rtl">
                    <div className="bg-white rounded-3xl max-w-2xl w-full p-5 sm:p-6 shadow-2xl border border-gray-100 my-auto max-h-[92vh] flex flex-col">
                        <div className="flex items-center justify-between pb-3 sm:pb-4 border-b border-gray-100 mb-3 sm:mb-4 shrink-0">
                            <h3 className="text-lg sm:text-xl font-black text-gray-900 flex items-center gap-2">
                                <span>📖</span> دليل استخراج الـ Access Token للـ Conversions API
                            </h3>
                            <button
                                type="button"
                                onClick={() => setIsGuideOpen(false)}
                                className="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center text-sm font-bold transition-colors"
                            >
                                ✕
                            </button>
                        </div>

                        <div className="space-y-4 sm:space-y-6 text-sm text-gray-700 leading-relaxed overflow-y-auto pl-1 pr-1 flex-1 py-1">
                            {/* Meta Guide */}
                            <div className="p-4 bg-blue-50/60 rounded-2xl border border-blue-100 space-y-2">
                                <h4 className="text-base font-black text-blue-900 flex items-center gap-2">
                                    <span>🔵</span> 1. خطوات فيسبوك Meta (Facebook CAPI):
                                </h4>
                                <ol className="list-decimal list-inside space-y-1.5 text-xs text-gray-800 pr-2">
                                    <li>افتح <strong>Meta Events Manager</strong> (مدير الأحداث) في حسابك الإعلاني.</li>
                                    <li>اختر البيكسل (Pixel / Dataset) الخاص بمتجرك من القائمة.</li>
                                    <li>ادخل على تبويب <strong>Settings (الإعدادات)</strong>.</li>
                                    <li>انزل للأسفل حتى قسم <strong>Conversions API</strong>.</li>
                                    <li>اضغط على رابط <strong>Generate access token (إنشاء رمز وصول)</strong>.</li>
                                    <li>انسخ التوكن الناتج والصقه في خانة <strong>Access Token</strong> في لوحة تحكمك.</li>
                                </ol>
                            </div>

                            {/* TikTok Guide */}
                            <div className="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                                <h4 className="text-base font-black text-gray-900 flex items-center gap-2">
                                    <span>⚫</span> 2. خطوات تيك توك (TikTok Events API):
                                </h4>
                                <ol className="list-decimal list-inside space-y-1.5 text-xs text-gray-800 pr-2">
                                    <li>افتح <strong>TikTok Ads Manager</strong> ثم توجه إلى <strong>Tools ➔ Events</strong>.</li>
                                    <li>اختر <strong>Web Events</strong> وافتح البيكسل الخاص بك.</li>
                                    <li>توجه إلى تبويب <strong>Settings</strong> ثم قسم <strong>Events API</strong>.</li>
                                    <li>اضغط على <strong>Generate Access Token</strong> وانسخه.</li>
                                </ol>
                            </div>

                            {/* Snapchat Guide */}
                            <div className="p-4 bg-amber-50/60 rounded-2xl border border-amber-200 space-y-2">
                                <h4 className="text-base font-black text-amber-900 flex items-center gap-2">
                                    <span>🟡</span> 3. خطوات سناب شات (Snapchat Conversions API):
                                </h4>
                                <ol className="list-decimal list-inside space-y-1.5 text-xs text-gray-800 pr-2">
                                    <li>من <strong>Snapchat Ads Manager</strong> توجه إلى <strong>Events Manager</strong>.</li>
                                    <li>اختر البيكسل ثم توجه إلى <strong>Conversions API Setup</strong> لتوليد الـ OAuth Token.</li>
                                </ol>
                            </div>
                        </div>

                        <div className="pt-4 border-t border-gray-100 flex justify-end">
                            <button
                                type="button"
                                onClick={() => setIsGuideOpen(false)}
                                className="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white text-sm font-bold rounded-xl transition-all"
                            >
                                فهمت، إغلاق الدليل
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </MerchantLayout>
    );
}
