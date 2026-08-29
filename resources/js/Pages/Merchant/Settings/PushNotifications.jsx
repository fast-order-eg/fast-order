import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function PushNotifications({ vapidPublicKey, deviceCount, settings }) {
    const [permission, setPermission] = useState('default');
    const [isSubscribed, setIsSubscribed] = useState(false);
    const [currentEndpoint, setCurrentEndpoint] = useState(null);
    const [loading, setLoading] = useState(false);
    const [testLoading, setTestLoading] = useState(false);
    const [message, setMessage] = useState(null);
    const [enabled, setEnabled] = useState(settings?.enabled ?? true);
    const [newOrders, setNewOrders] = useState(settings?.new_orders ?? true);
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
            throw new Error('فشل تسجيل Service Worker: ' + e.message);
        }
    };

    const urlBase64ToUint8Array = (base64String) => {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    };

    const handleSubscribe = async () => {
        if (!vapidPublicKey) {
            showMessage('error', 'مفاتيح VAPID غير مضبوطة على السيرفر. تواصل مع الدعم الفني.');
            return;
        }
        setLoading(true);
        try {
            const perm = await Notification.requestPermission();
            setPermission(perm);
            if (perm !== 'granted') {
                showMessage('error', 'لم يتم السماح بالاشعارات. غيّر الاعداد من المتصفح وحاول مجدداً.');
                return;
            }
            const reg = await registerServiceWorker();
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });
            const subJson = sub.toJSON();
            const deviceName = getDeviceName();
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(window.location.pathname.replace('/push-notifications', '') + '/push-notifications/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ ...subJson, device_name: deviceName }),
            });
            const data = await res.json();
            if (data.success) {
                setIsSubscribed(true);
                setCurrentEndpoint(sub.endpoint);
                showMessage('success', data.message);
                router.reload({ only: ['deviceCount'] });
            } else {
                showMessage('error', data.message);
            }
        } catch (e) {
            showMessage('error', 'حدث خطأ: ' + e.message);
        } finally {
            setLoading(false);
        }
    };

    const handleUnsubscribe = async () => {
        setLoading(true);
        try {
            const reg = await navigator.serviceWorker.getRegistration('/sw.js');
            if (reg) {
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    await sub.unsubscribe();
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    await fetch(window.location.pathname.replace('/push-notifications', '') + '/push-notifications/unsubscribe', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ endpoint: sub.endpoint }),
                    });
                }
            }
            setIsSubscribed(false);
            setCurrentEndpoint(null);
            showMessage('success', 'تم الغاء الاشتراك في الاشعارات لهذا الجهاز.');
            router.reload({ only: ['deviceCount'] });
        } catch (e) {
            showMessage('error', 'حدث خطأ: ' + e.message);
        } finally {
            setLoading(false);
        }
    };

    const handleSendTest = async () => {
        setTestLoading(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(window.location.pathname.replace('/push-notifications', '') + '/push-notifications/test', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            const data = await res.json();
            showMessage(data.success ? 'success' : 'error', data.message);
        } catch (e) {
            showMessage('error', 'فشل الارسال: ' + e.message);
        } finally {
            setTestLoading(false);
        }
    };

    const handleSaveSettings = async () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch(window.location.pathname.replace('/push-notifications', '') + '/push-notifications/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ enabled, new_orders: newOrders }),
        });
        const data = await res.json();
        showMessage(data.success ? 'success' : 'error', data.message);
    };

    const showMessage = (type, text) => {
        setMessage({ type, text });
        setTimeout(() => setMessage(null), 4000);
    };

    const getDeviceName = () => {
        const ua = navigator.userAgent;
        if (/iPhone/.test(ua)) return 'iPhone';
        if (/Android/.test(ua)) return 'Android';
        if (/iPad/.test(ua)) return 'iPad';
        if (/Windows/.test(ua)) return 'Windows PC';
        if (/Mac/.test(ua)) return 'Mac';
        return 'Unknown Device';
    };

    const getPermissionInfo = () => {
        if (permission === 'granted') return { color: 'text-green-600 bg-green-50', icon: '✅', text: 'مسموح بالاشعارات' };
        if (permission === 'denied') return { color: 'text-red-600 bg-red-50', icon: '🚫', text: 'محظور - غيّر الاعدادات يدوياً من المتصفح' };
        return { color: 'text-yellow-600 bg-yellow-50', icon: '⚠️', text: 'لم يتم الطلب بعد' };
    };

    if (!supported) {
        return (
            <MerchantLayout>
                <Head title="اشعارات الطلبات" />
                <div className="p-6 max-w-2xl mx-auto">
                    <div className="bg-red-50 border border-red-200 rounded-xl p-5 text-red-700 text-center">
                        <div className="text-4xl mb-3">😞</div>
                        <h3 className="font-bold text-lg">متصفحك لا يدعم الاشعارات</h3>
                        <p className="text-sm mt-1">جرّب Chrome او Edge او Safari 16.4+</p>
                    </div>
                </div>
            </MerchantLayout>
        );
    }

    const permInfo = getPermissionInfo();

    return (
        <MerchantLayout>
            <Head title="اشعارات الطلبات" />
            <div className="p-4 md:p-6 max-w-2xl mx-auto space-y-5" dir="rtl">
                <div className="flex items-center gap-3 mb-2">
                    <div className="text-3xl">🔔</div>
                    <div>
                        <h1 className="text-xl font-bold text-gray-800">اشعارات الطلبات الفورية</h1>
                        <p className="text-sm text-gray-500">استقبل اشعاراً فورياً على جهازك لحظة وصول اي طلب جديد</p>
                    </div>
                </div>

                {message && (
                    <div className={`rounded-xl p-4 text-sm font-medium flex items-center gap-2 ${
                        message.type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'
                    }`}>
                        <span>{message.type === 'success' ? '✅' : '❌'}</span>
                        {message.text}
                    </div>
                )}

                {/* حالة الاذن */}
                <div className={`rounded-xl p-4 flex items-center gap-3 ${permInfo.color} border`}>
                    <span className="text-xl">{permInfo.icon}</span>
                    <div>
                        <div className="font-semibold text-sm">حالة الاشعارات في هذا المتصفح</div>
                        <div className="text-xs mt-0.5">{permInfo.text}</div>
                    </div>
                </div>

                {/* تفعيل/الغاء الجهاز */}
                <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <h2 className="font-semibold text-gray-700 mb-1">هذا الجهاز</h2>
                    <p className="text-xs text-gray-400 mb-4">
                        {isSubscribed ? `✅ مفعّل — سيصلك إشعار فوري على هذا الجهاز` : 'غير مفعّل على هذا الجهاز بعد'}
                    </p>
                    <div className="flex flex-wrap gap-3">
                        {!isSubscribed ? (
                            <button
                                onClick={handleSubscribe}
                                disabled={loading || permission === 'denied'}
                                className="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition disabled:opacity-50"
                            >
                                {loading ? <span className="animate-spin">⟳</span> : '🔔'}
                                {loading ? 'جاري التفعيل...' : 'فعّل الاشعارات على هذا الجهاز'}
                            </button>
                        ) : (
                            <button
                                onClick={handleUnsubscribe}
                                disabled={loading}
                                className="flex items-center gap-2 px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold transition disabled:opacity-50"
                            >
                                {loading ? <span className="animate-spin">⟳</span> : '🔕'}
                                {loading ? 'جاري الالغاء...' : 'الغاء الاشعارات لهذا الجهاز'}
                            </button>
                        )}
                        {isSubscribed && (
                            <button
                                onClick={handleSendTest}
                                disabled={testLoading}
                                className="flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-semibold transition disabled:opacity-50"
                            >
                                {testLoading ? <span className="animate-spin">⟳</span> : '🧪'}
                                {testLoading ? 'جاري الارسال...' : 'ارسال اشعار تجريبي'}
                            </button>
                        )}
                    </div>
                    {permission === 'denied' && (
                        <div className="mt-3 p-3 bg-red-50 rounded-lg text-xs text-red-600">
                            <strong>كيف تفتح الاشعارات يدوياً:</strong><br />
                            Chrome: اضغط على أيقونة القفل 🔒 قدام رابط الصفحة &rarr; الاشعارات &rarr; سماح<br />
                            Mobile: الاعدادات &rarr; تطبيقات &rarr; Chrome &rarr; الاشعارات &rarr; تفعيل
                        </div>
                    )}
                </div>

                {/* احصائيات */}
                <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <h2 className="font-semibold text-gray-700 mb-3">الاجهزة المشتركة</h2>
                    <div className="flex items-center gap-3">
                        <div className="text-3xl font-bold text-indigo-600">{deviceCount}</div>
                        <div className="text-sm text-gray-500">جهاز مفعّل حالياً<br /><span className="text-xs">(موبايل + كمبيوتر + تابلت)</span></div>
                    </div>
                    <p className="text-xs text-gray-400 mt-2">كل جهاز تسجّل فيه سيستقبل الاشعار بشكل مستقل</p>
                </div>

                {/* اعدادات الاشعارات */}
                <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <h2 className="font-semibold text-gray-700 mb-4">اعدادات الاشعارات</h2>
                    <div className="space-y-3">
                        <label className="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer">
                            <div>
                                <div className="font-medium text-sm text-gray-700">تفعيل الاشعارات</div>
                                <div className="text-xs text-gray-400">تفعيل او تعطيل كل الاشعارات</div>
                            </div>
                            <input
                                type="checkbox"
                                checked={enabled}
                                onChange={e => setEnabled(e.target.checked)}
                                className="w-5 h-5 accent-indigo-600"
                            />
                        </label>
                        <label className="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer">
                            <div>
                                <div className="font-medium text-sm text-gray-700">🛍️ الطلبات الجديدة</div>
                                <div className="text-xs text-gray-400">اشعار فوري عند كل طلب جديد</div>
                            </div>
                            <input
                                type="checkbox"
                                checked={newOrders}
                                onChange={e => setNewOrders(e.target.checked)}
                                className="w-5 h-5 accent-indigo-600"
                            />
                        </label>
                    </div>
                    <button
                        onClick={handleSaveSettings}
                        className="mt-4 px-5 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-xl text-sm font-semibold transition"
                    >
                        حفظ الاعدادات
                    </button>
                </div>

                {/* دعم الاجهزة */}
                <div className="bg-gray-50 rounded-xl border border-gray-200 p-4">
                    <h3 className="font-semibold text-gray-600 text-sm mb-2">الاجهزة المدعومة</h3>
                    <div className="grid grid-cols-2 gap-2 text-xs text-gray-500">
                        <div>✅ Android Chrome/Firefox</div>
                        <div>✅ Windows Chrome/Edge/Firefox</div>
                        <div>✅ Mac Safari/Chrome</div>
                        <div>✅ iPhone iOS 16.4+ Safari</div>
                    </div>
                    <p className="text-xs text-gray-400 mt-2">* على الايفون يجب اضافة الموقع للهوم سكرين اولاً</p>
                </div>
            </div>
        </MerchantLayout>
    );
}