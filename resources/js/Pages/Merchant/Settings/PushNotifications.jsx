import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import axios from 'axios';

export default function PushNotifications({ vapidPublicKey, deviceCount, settings }) {
    const [permission, setPermission] = useState('default');
    const [isSubscribed, setIsSubscribed] = useState(false);
    const [currentEndpoint, setCurrentEndpoint] = useState(null);
    const [loading, setLoading] = useState(false);
    const [testLoading, setTestLoading] = useState(false);
    const [message, setMessage] = useState(null);
    const [supported, setSupported] = useState(true);

    useEffect(() => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            setSupported(false);
            return;
        }
        setPermission(Notification.permission);
        checkSubscription();
    }, []);

    const checkSubscription = async () => {
        try {
            const reg = await navigator.serviceWorker.getRegistration('/sw.js');
            if (!reg) return;
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                setIsSubscribed(true);
                setCurrentEndpoint(sub.endpoint);
            }
        } catch (e) {
            console.error('checkSubscription error:', e);
        }
    };

    const registerServiceWorker = async () => {
        try {
            const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
            await navigator.serviceWorker.ready;
            return reg;
        } catch (e) {
            throw new Error('فشل تسجيل ملف الخدمة Service Worker: ' + e.message);
        }
    };

    const urlBase64ToUint8Array = (base64String) => {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    };

    const handleToggleSubscription = async () => {
        if (loading) return;

        if (isSubscribed) {
            // Unsubscribe
            setLoading(true);
            try {
                const reg = await navigator.serviceWorker.getRegistration('/sw.js');
                if (reg) {
                    const sub = await reg.pushManager.getSubscription();
                    if (sub) {
                        await sub.unsubscribe();
                        await axios.post('/admin/push-notifications/unsubscribe', {
                            endpoint: sub.endpoint,
                        });
                    }
                }
                setIsSubscribed(false);
                setCurrentEndpoint(null);
                showMessage('success', 'تم إلغاء تفعيل الإشعارات لهذا الجهاز');
                router.reload({ only: ['deviceCount'] });
            } catch (e) {
                showMessage('error', 'حدث خطأ أثناء إلغاء التفعيل: ' + (e.response?.data?.message || e.message));
            } finally {
                setLoading(false);
            }
        } else {
            // Subscribe
            if (!vapidPublicKey) {
                showMessage('error', 'مفاتيح VAPID غير مضبوطة على السيرفر.');
                return;
            }
            setLoading(true);
            try {
                const perm = await Notification.requestPermission();
                setPermission(perm);
                if (perm !== 'granted') {
                    showMessage('error', 'لم يتم السماح بالإشعارات من المتصفح.');
                    return;
                }
                const reg = await registerServiceWorker();
                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                });
                const subJson = sub.toJSON();
                const deviceName = getDeviceName();

                const res = await axios.post('/admin/push-notifications/subscribe', {
                    ...subJson,
                    device_name: deviceName,
                });

                if (res.data.success) {
                    setIsSubscribed(true);
                    setCurrentEndpoint(sub.endpoint);
                    showMessage('success', res.data.message || 'تم تفعيل الإشعارات بنجاح!');
                    router.reload({ only: ['deviceCount'] });
                } else {
                    showMessage('error', res.data.message || 'فشل التفعيل');
                }
            } catch (e) {
                showMessage('error', 'حدث خطأ: ' + (e.response?.data?.message || e.message));
            } finally {
                setLoading(false);
            }
        }
    };

    const handleSendTest = async () => {
        setTestLoading(true);
        try {
            const res = await axios.post('/admin/push-notifications/test');
            showMessage(res.data.success ? 'success' : 'error', res.data.message);
        } catch (e) {
            showMessage('error', 'فشل الإرسال: ' + (e.response?.data?.message || e.message));
        } finally {
            setTestLoading(false);
        }
    };

    const showMessage = (type, text) => {
        setMessage({ type, text });
        setTimeout(() => setMessage(null), 5000);
    };

    const getDeviceName = () => {
        const ua = navigator.userAgent;
        if (/iPhone/.test(ua)) return 'iPhone (Safari)';
        if (/Android/.test(ua)) return 'Android (Chrome)';
        if (/iPad/.test(ua)) return 'iPad';
        if (/Windows/.test(ua)) return 'Windows PC';
        if (/Mac/.test(ua)) return 'Mac';
        return 'متصفح ويب';
    };

    const getPermissionInfo = () => {
        if (permission === 'granted') {
            return {
                badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                dotBg: 'bg-emerald-500',
                text: 'مسموح بالإشعارات في المتصفح',
                desc: 'متصفحك جاهز لاستقبال التنبيهات الفورية',
            };
        }
        if (permission === 'denied') {
            return {
                badgeBg: 'bg-rose-50 text-rose-700 border-rose-200',
                dotBg: 'bg-rose-500',
                text: 'الإشعارات محظورة في هذا المتصفح',
                desc: 'اضغط على علامة القفل 🔒 بجانب الرابط وفعل إذن الإشعارات',
            };
        }
        return {
            badgeBg: 'bg-amber-50 text-amber-700 border-amber-200',
            dotBg: 'bg-amber-500',
            text: 'بانتظار طلب الإذن',
            desc: 'سيطلب المتصفح موافقتك عند تشغيل الإشعارات بالأسفل',
        };
    };

    const isIOS = typeof navigator !== 'undefined' && (/iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1));
    const isStandalone = typeof window !== 'undefined' && (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true);

    if (!supported) {
        return (
            <MerchantLayout>
                <Head title="إشعارات الطلبات" />
                <div className="p-4 md:p-6 max-w-xl mx-auto space-y-5" dir="rtl">
                    {/* رأس الصفحة */}
                    <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-2xl shadow-sm">
                            🔔
                        </div>
                        <div>
                            <h1 className="text-lg md:text-xl font-bold text-gray-900">إشعارات الطلبات الفورية</h1>
                            <p className="text-xs md:text-sm text-gray-500 mt-0.5">تنبيهات صوتية فورية على جهازك لحظة وصول أي طلب جديد</p>
                        </div>
                    </div>

                    {isIOS ? (
                        <div className="bg-gradient-to-br from-indigo-50 via-white to-purple-50 border-2 border-indigo-200 rounded-3xl p-6 shadow-sm space-y-4">
                            <div className="flex items-center gap-3">
                                <div className="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-2xl shadow-md">
                                    🍏
                                </div>
                                <div>
                                    <h3 className="font-extrabold text-base md:text-lg text-indigo-950">تفعيل الإشعارات على أجهزة iPhone</h3>
                                    <p className="text-xs text-indigo-700 font-medium">خطوات سريعة وبسيطة لتشغيل التنبيهات</p>
                                </div>
                            </div>

                            <p className="text-xs sm:text-sm text-gray-700 leading-relaxed font-medium bg-white/80 p-3.5 rounded-2xl border border-indigo-100">
                                تشترط شركة <strong className="text-indigo-900">Apple (iOS)</strong> إضافة لوحة التحكم إلى الشاشة الرئيسية لموبايلك أولاً لتفعيل الإشعارات الفورية:
                            </p>

                            <div className="space-y-3 pt-1">
                                <div className="flex items-start gap-3 bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs">
                                    <span className="w-7 h-7 rounded-xl bg-indigo-600 text-white font-extrabold text-xs flex items-center justify-center shrink-0">1</span>
                                    <p className="text-xs sm:text-sm text-gray-800 font-medium leading-normal">
                                        افتح هذا الرابط من متصفح <strong className="text-indigo-700">Safari</strong> الرئيسي (وليس من داخل تطبيق واتساب أو فيسبوك).
                                    </p>
                                </div>

                                <div className="flex items-start gap-3 bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs">
                                    <span className="w-7 h-7 rounded-xl bg-indigo-600 text-white font-extrabold text-xs flex items-center justify-center shrink-0">2</span>
                                    <p className="text-xs sm:text-sm text-gray-800 font-medium leading-normal">
                                        اضغط على زر المشاركة <span className="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 font-mono text-indigo-600 border border-gray-200 text-xs">📤 Share</span> أسفل المتصفح.
                                    </p>
                                </div>

                                <div className="flex items-start gap-3 bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs">
                                    <span className="w-7 h-7 rounded-xl bg-indigo-600 text-white font-extrabold text-xs flex items-center justify-center shrink-0">3</span>
                                    <p className="text-xs sm:text-sm text-gray-800 font-medium leading-normal">
                                        مرر للأسفل واختر <strong className="text-indigo-700">«إضافة إلى الشاشة الرئيسية» (Add to Home Screen 📲)</strong> ثم اضغط <strong className="text-emerald-700">«إضافة / Add»</strong>.
                                    </p>
                                </div>

                                <div className="flex items-start gap-3 bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs">
                                    <span className="w-7 h-7 rounded-xl bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center shrink-0">4</span>
                                    <p className="text-xs sm:text-sm text-gray-800 font-medium leading-normal">
                                        افتح أيقونة <strong className="text-indigo-700">FastOrder</strong> من شاشة هاتفك الرئيسية، وادخل على هذه الصفحة واضغط <strong>«تفعيل الإشعارات»</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="bg-rose-50 border border-rose-200 rounded-2xl p-6 text-rose-700 text-center shadow-sm">
                            <div className="text-4xl mb-3">⚠️</div>
                            <h3 className="font-bold text-lg mb-1">متصفحك لا يدعم الإشعارات الفورية</h3>
                            <p className="text-sm text-rose-600">يرجى فتح لوحة التحكم من متصفح حديث مثل Google Chrome أو Microsoft Edge أو Safari 16.4+.</p>
                        </div>
                    )}
                </div>
            </MerchantLayout>
        );
    }

    const permInfo = getPermissionInfo();

    return (
        <MerchantLayout>
            <Head title="إشعارات الطلبات" />
            <div className="p-4 md:p-6 max-w-xl mx-auto space-y-5" dir="rtl">
                {/* رأس الصفحة */}
                <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-2xl shadow-sm">
                        🔔
                    </div>
                    <div>
                        <h1 className="text-lg md:text-xl font-bold text-gray-900">إشعارات الطلبات الفورية</h1>
                        <p className="text-xs md:text-sm text-gray-500 mt-0.5">تنبيهات صوتية فورية على جهازك لحظة وصول أي طلب جديد</p>
                    </div>
                </div>

                {/* رسالة التنبيهات */}
                {message && (
                    <div className={`rounded-xl p-4 text-sm font-medium flex items-center gap-3 border transition-all ${
                        message.type === 'success'
                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200 shadow-sm'
                            : 'bg-rose-50 text-rose-800 border-rose-200 shadow-sm'
                    }`}>
                        <span className="text-lg">{message.type === 'success' ? '✅' : '❌'}</span>
                        <span className="flex-1">{message.text}</span>
                    </div>
                )}

                {/* فحص حالة إذن المتصفح */}
                <div className={`rounded-2xl p-4 border flex items-center justify-between gap-3 ${permInfo.badgeBg} shadow-sm`}>
                    <div className="flex items-center gap-3">
                        <div className="relative flex h-3 w-3">
                            <span className={`animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 ${permInfo.dotBg}`}></span>
                            <span className={`relative inline-flex rounded-full h-3 w-3 ${permInfo.dotBg}`}></span>
                        </div>
                        <div>
                            <div className="font-bold text-sm">{permInfo.text}</div>
                            <div className="text-xs opacity-80 mt-0.5">{permInfo.desc}</div>
                        </div>
                    </div>
                </div>

                {/* بطاقة السويتش ON/OFF للجهاز الحالي */}
                <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm space-y-4">
                    <div className="flex items-center justify-between">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <span className="font-bold text-base text-gray-900">إشعارات هذا الجهاز</span>
                                <span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium">
                                    {getDeviceName()}
                                </span>
                            </div>
                            <p className="text-xs text-gray-500">
                                {isSubscribed
                                    ? 'الإشعارات مفعلة حالياً — ستصلك فوراً عند وصول أي طلب'
                                    : 'الإشعارات متوقفة على هذا الجهاز'}
                            </p>
                        </div>

                        {/* Switch ON/OFF */}
                        <button
                            type="button"
                            role="switch"
                            aria-checked={isSubscribed}
                            disabled={loading || permission === 'denied'}
                            onClick={handleToggleSubscription}
                            className={`relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed ${
                                isSubscribed ? 'bg-indigo-600' : 'bg-gray-200'
                            }`}
                        >
                            <span className="sr-only">تفعيل الإشعارات</span>
                            <span
                                className={`pointer-events-none inline-block h-7 w-7 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out flex items-center justify-center ${
                                    isSubscribed ? '-translate-x-6' : 'translate-x-0'
                                }`}
                            >
                                {loading ? (
                                    <svg className="animate-spin h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                ) : isSubscribed ? (
                                    <span className="text-[10px] text-indigo-600 font-bold">ON</span>
                                ) : (
                                    <span className="text-[10px] text-gray-400 font-bold">OFF</span>
                                )}
                            </span>
                        </button>
                    </div>

                    {/* زر إرسال إشعار تجريبي يظهر فقط إذا كان مفعل */}
                    {isSubscribed && (
                        <div className="pt-3 border-t border-gray-100 flex items-center justify-between">
                            <span className="text-xs text-gray-500">جرب وصول الإشعار والصوت الآن:</span>
                            <button
                                type="button"
                                onClick={handleSendTest}
                                disabled={testLoading}
                                className="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-semibold transition shadow-sm disabled:opacity-50"
                            >
                                {testLoading ? (
                                    <span className="animate-spin">⟳</span>
                                ) : (
                                    <span>🧪</span>
                                )}
                                <span>إرسال إشعار تجريبي</span>
                            </button>
                        </div>
                    )}
                </div>

                {/* كارت الأجهزة المشتركة */}
                <div className="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs text-gray-500 font-medium">الأجهزة المفعلة بالمتجر</div>
                        <div className="text-xs text-gray-400 mt-0.5">تصل الإشعارات لجميع أجهزتك المفتوحة تلقائياً</div>
                    </div>
                    <div className="flex items-baseline gap-1.5 bg-indigo-50 border border-indigo-100 px-3 py-1.5 rounded-xl">
                        <span className="text-xl font-extrabold text-indigo-600">{deviceCount}</span>
                        <span className="text-xs text-indigo-700 font-medium">أجهزة</span>
                    </div>
                </div>
            </div>
        </MerchantLayout>
    );
}