import { useState, useEffect } from 'react';
import { useForm, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head } from '@inertiajs/react';

export default function ApiKeysIndex({ apiKeys }) {
    const { flash } = usePage().props;
    const [showNewKey, setShowNewKey] = useState(flash?.new_key || null);
    const [copiedKeyId, setCopiedKeyId] = useState(null);
    const [copiedNewKey, setCopiedNewKey] = useState(false);
    const [copiedUrl, setCopiedUrl] = useState(false);
    const [copiedHeader, setCopiedHeader] = useState(false);
    const [visibleKeys, setVisibleKeys] = useState({});

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
    });

    useEffect(() => {
        if (flash?.new_key) {
            setShowNewKey(flash.new_key);
        }
    }, [flash?.new_key]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('merchant.api-keys.store'), {
            onSuccess: (page) => {
                reset('name');
                if (page.props?.flash?.new_key) {
                    setShowNewKey(page.props.flash.new_key);
                }
            },
        });
    };

    const handleDeleteKey = (id) => {
        if (confirm('هل أنت متأكد من حذف هذا المفتاح نهائياً؟')) {
            router.delete(route('merchant.api-keys.destroy', id));
        }
    };

    const copyText = (text, callback) => {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                callback();
            }).catch(() => {
                fallbackCopy(text, callback);
            });
        } else {
            fallbackCopy(text, callback);
        }
    };

    const fallbackCopy = (text, callback) => {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            callback();
        } catch (err) {
            alert('فشل النسخ التلقائي، يرجى تحديده ونسخه يدوياً.');
        }
        textArea.remove();
    };

    const handleCopyKey = (keyObj) => {
        copyText(keyObj.key, () => {
            setCopiedKeyId(keyObj.id);
            setTimeout(() => setCopiedKeyId(null), 2500);
        });
    };

    const handleCopyNewKey = () => {
        if (!showNewKey) return;
        copyText(showNewKey, () => {
            setCopiedNewKey(true);
            setTimeout(() => setCopiedNewKey(false), 2500);
        });
    };

    const toggleKeyVisibility = (id) => {
        setVisibleKeys(prev => ({ ...prev, [id]: !prev[id] }));
    };

    const ordersApiUrl = typeof window !== 'undefined'
        ? `${window.location.origin}/api/v1/orders`
        : 'https://fast-order-eg.tech/api/v1/orders';

    return (
        <MerchantLayout title="مفاتيح الربط البرمجي (Orders API)">
            <Head title="مفاتيح الربط البرمجي (Orders API)" />

            <div className="max-w-5xl mx-auto px-4 py-6 space-y-6 text-right" dir="rtl">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 pb-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">مفاتيح الربط البرمجي (Orders API)</h1>
                        <p className="text-sm text-gray-500 mt-1">
                            أنشئ مفتاح API خاص بمتجرك لربطه بتطبيقك الخارجي وسحب الأوردرات وتفاصيلها لحظياً
                        </p>
                    </div>
                </div>

                {/* مفتاح جديد تم إنشاؤه بنجاح */}
                {showNewKey && (
                    <div className="p-5 bg-gradient-to-r from-emerald-50 to-green-50 border-2 border-emerald-300 rounded-2xl shadow-md animate-in fade-in zoom-in-95 duration-200">
                        <div className="flex items-center gap-2 mb-2">
                            <span className="text-2xl">🎉</span>
                            <h3 className="font-bold text-emerald-900 text-base">
                                تم إنشاء مفتاح الـ API بنجاح! انسخه الآن:
                            </h3>
                        </div>
                        <p className="text-xs text-emerald-800 mb-3">
                            احتفظ بهذا المفتاح وأعطه للمبرمج الخاص بتطبيقك. يمكنك أيضاً نسخه في أي وقت من قائمة المفاتيح بالأسفل.
                        </p>
                        <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <input
                                type="text"
                                readOnly
                                value={showNewKey}
                                className="flex-1 bg-white border border-emerald-300 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-800 text-left select-all focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                            <button
                                type="button"
                                onClick={handleCopyNewKey}
                                className={`px-6 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-sm ${
                                    copiedNewKey
                                        ? 'bg-emerald-700 text-white'
                                        : 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                }`}
                            >
                                <span>{copiedNewKey ? '✓' : '📋'}</span>
                                <span>{copiedNewKey ? 'تم نسخ المفتاح!' : 'نسخ المفتاح'}</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowNewKey(null)}
                                className="px-4 py-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl text-sm font-medium transition-colors cursor-pointer"
                            >
                                إغلاق
                            </button>
                        </div>
                    </div>
                )}

                {/* فورم إنشاء مفتاح جديد */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h2 className="text-base font-bold text-gray-900 mb-1 flex items-center gap-2">
                        <span>🔑</span>
                        <span>إنشاء مفتاح ربط جديد</span>
                    </h2>
                    <p className="text-xs text-gray-500 mb-4">
                        اكتب اسم توضيحي للمفتاح لمعرفة أي تطبيق أو سيستم يستخدمه (مثل: تطبيق الموبايل، نظام الكاشير).
                    </p>

                    <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1">
                            <input
                                type="text"
                                placeholder="اسم المفتاح (مثال: تطبيق الموبايل الخاص بي)"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                            />
                            {errors.name && (
                                <p className="text-red-500 text-xs mt-1">{errors.name}</p>
                            )}
                        </div>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-6 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50 font-bold text-sm transition-all shadow-sm hover:shadow flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>🔑</span>
                            <span>{processing ? 'جاري الإنشاء...' : 'إنشاء المفتاح'}</span>
                        </button>
                    </form>
                </div>

                {/* قائمة المفاتيح */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="p-5 border-b border-gray-100 flex items-center justify-between">
                        <h2 className="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>📋</span>
                            <span>مفاتيح الـ API المتاحة ({apiKeys.length})</span>
                        </h2>
                    </div>

                    {apiKeys.length === 0 ? (
                        <div className="p-10 text-center text-gray-400">
                            <div className="text-4xl mb-2">🔑</div>
                            <p className="text-sm font-medium">لا توجد أي مفاتيح API حتى الآن.</p>
                            <p className="text-xs text-gray-400 mt-1">اكتب اسم المفتاح بالأعلى واضغط "إنشاء المفتاح" للبدء.</p>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {apiKeys.map((item) => (
                                <div
                                    key={item.id}
                                    className="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/70 transition-colors"
                                >
                                    <div className="flex-1 space-y-1.5">
                                        <div className="flex items-center gap-2.5">
                                            <span className="font-bold text-gray-900 text-sm">
                                                {item.name}
                                            </span>
                                            <span
                                                className={`px-2.5 py-0.5 text-xs rounded-full font-semibold ${
                                                    item.is_active
                                                        ? 'bg-emerald-100 text-emerald-800'
                                                        : 'bg-red-100 text-red-800'
                                                }`}
                                            >
                                                {item.is_active ? 'نشط ومفعل ✓' : 'ملغى ✗'}
                                            </span>
                                        </div>

                                        {/* Key Display & Show/Hide */}
                                        <div className="flex items-center gap-2">
                                            <code className="bg-gray-100 px-3 py-1.5 rounded-lg text-xs font-mono text-gray-800 select-all text-left">
                                                {visibleKeys[item.id] ? item.key : item.key_preview}
                                            </code>
                                            <button
                                                type="button"
                                                onClick={() => toggleKeyVisibility(item.id)}
                                                className="text-xs text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer"
                                            >
                                                {visibleKeys[item.id] ? 'إخفاء 🙈' : 'إظهار كامل 👁️'}
                                            </button>
                                        </div>

                                        <p className="text-xs text-gray-400">
                                            أُنشئ في: {item.created_at}
                                            {item.last_used_at && ` · آخر استخدام: ${item.last_used_at}`}
                                        </p>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="flex items-center gap-2 flex-wrap">
                                        {/* زرار النسخ الفوري */}
                                        <button
                                            type="button"
                                            onClick={() => handleCopyKey(item)}
                                            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-xs ${
                                                copiedKeyId === item.id
                                                    ? 'bg-emerald-600 text-white'
                                                    : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200'
                                            }`}
                                            title="نسخ المفتاح بالكامل لاستخدامه في تطبيقك"
                                        >
                                            <span>{copiedKeyId === item.id ? '✓' : '📋'}</span>
                                            <span>{copiedKeyId === item.id ? 'تم نسخ المفتاح!' : 'نسخ المفتاح'}</span>
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => handleDeleteKey(item.id)}
                                            className="px-3.5 py-2 text-red-600 border border-red-200 rounded-xl hover:bg-red-50 text-xs font-bold transition-colors cursor-pointer flex items-center gap-1"
                                            title="حذف هذا المفتاح نهائياً"
                                        >
                                            <span>🗑️</span>
                                            <span>حذف المفتاح</span>
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* رابط سحب الطلبات المباشر */}
                <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm space-y-2">
                    <h3 className="font-bold text-gray-900 text-sm flex items-center gap-1.5">
                        <span>🔗</span>
                        <span>رابط سحب الأوردرات للتطبيق:</span>
                    </h3>
                    <p className="text-xs text-gray-500">
                        المبرمج بيستخدم الرابط ده مع المفتاح اللي نسخته من فوق عشان كل الأوردرات تنزله في تطبيقه مباشرة:
                    </p>

                    <div className="flex items-center gap-2 pt-1">
                        <code className="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-mono text-gray-800 text-left truncate select-all">
                            {ordersApiUrl}
                        </code>
                        <button
                            type="button"
                            onClick={() => copyText(ordersApiUrl, () => {
                                setCopiedUrl(true);
                                setTimeout(() => setCopiedUrl(false), 2000);
                            })}
                            className="px-4 py-2.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-xs font-bold transition-colors flex-shrink-0 cursor-pointer"
                        >
                            {copiedUrl ? '✓ تم النسخ' : 'نسخ الرابط 📋'}
                        </button>
                    </div>
                </div>
            </div>
        </MerchantLayout>
    );
}
