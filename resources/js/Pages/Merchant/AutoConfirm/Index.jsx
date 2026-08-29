import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function AutoConfirmIndex({ settings = {}, stats = {}, wallet_balance = 0 }) {
    const { flash } = usePage().props;
    const [enabled, setEnabled] = useState(settings.enabled ?? false);
    const [saving, setSaving] = useState(false);

    const hasSufficientBalance = wallet_balance >= 3;

    const handleToggle = () => {
        if (!hasSufficientBalance && !enabled) {
            alert(`رصيد محفظتك الحالي (${Math.round(wallet_balance)} ج.م) أقل من الحد الأدنى المطلوب (3 ج.م). يرجى شحن المحفظة أولاً لتتمكن من تفعيل الخدمة.`);
            return;
        }

        const nextState = !enabled;
        setEnabled(nextState);
        setSaving(true);
        router.post('/admin/auto-confirm', {
            enabled: nextState,
        }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    return (
        <MerchantLayout title="التأكيد التلقائي للطلبات">
            <Head title="التأكيد التلقائي للطلبات" />

            <div className="max-w-5xl mx-auto space-y-6" dir="rtl">
                {/* Balance Notice If Low */}
                {!hasSufficientBalance && (
                    <div className="p-4 bg-amber-50 border border-amber-300 rounded-2xl text-amber-900 text-sm font-medium flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                        <div className="flex items-center gap-2.5">
                            <span className="text-xl">⚠️</span>
                            <span>
                                رصيد محفظتك الحالي (<strong>{Math.round(wallet_balance)} ج.م</strong>) أقل من الحد الأدنى المطلوب (<strong>3 ج.م</strong>). تم إيقاف الخدمة تلقائياً للحفاظ على حسابك.
                            </span>
                        </div>
                        <Link
                            href="/admin/wallet"
                            className="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition-colors shrink-0 text-center"
                        >
                            شحن المحفظة الآن 💳
                        </Link>
                    </div>
                )}

                {/* Header Banner */}
                <div className="bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 rounded-3xl p-6 md:p-8 text-white shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden">
                    <div className="relative z-10 space-y-2 max-w-2xl">
                        <div className="inline-flex items-center gap-2 px-3 py-1 bg-emerald-500/20 rounded-full text-xs font-semibold text-emerald-300 border border-emerald-400/30">
                            <span>💬</span>
                            <span>خدمة التأكيد الذكي عبر الواتساب (WhatsApp Bot)</span>
                        </div>
                        <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight">
                            التأكيد التلقائي للطلبات عبر الواتساب
                        </h1>
                        <p className="text-emerald-100/90 text-sm leading-relaxed">
                            أرسل رسالة تفاعلية فورية ومفصلة على واتساب العميل بمجرد تسجيل الطلب، لتمكينه من مراجعة تفاصيل المنتجات والشحن وتأكيد أو إلغاء الأوردر بضغطة زر واحدة.
                        </p>
                    </div>

                    <div className="relative z-10 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center shrink-0 min-w-[200px]">
                        <div className={`text-base font-black mt-0.5 ${enabled && hasSufficientBalance ? 'text-emerald-300' : 'text-gray-300'}`}>
                            {enabled && hasSufficientBalance ? 'الحالة مفعلة ✓' : 'الحالة غير مفعلة'}
                        </div>
                        <div className="text-[11px] text-emerald-200/80 mt-0.5">
                            رصيد المحفظة: {Math.round(wallet_balance)} ج.م
                        </div>
                        <button
                            type="button"
                            disabled={saving}
                            onClick={handleToggle}
                            className={`mt-3 w-full py-2.5 px-4 rounded-xl text-xs font-bold transition-all shadow-md cursor-pointer ${
                                enabled && hasSufficientBalance
                                    ? 'bg-red-500/80 hover:bg-red-600 text-white' 
                                    : 'bg-emerald-500 hover:bg-emerald-600 text-white'
                            }`}
                        >
                            {saving ? 'جاري التحديث...' : (enabled && hasSufficientBalance ? 'إيقاف الخدمة' : 'تفعيل الخدمة الآن')}
                        </button>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-sm font-medium flex items-center gap-2 shadow-sm">
                        <span>✓</span>
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-2xl text-red-800 text-sm font-medium flex items-center gap-2 shadow-sm">
                        <span>⚠️</span>
                        {flash.error}
                    </div>
                )}

                {/* Pricing & Cost Notice Card (Important User Directive) */}
                <div className="bg-gradient-to-br from-amber-50 to-orange-50 border-2 border-amber-300/80 rounded-2xl p-5 md:p-6 shadow-sm flex items-start gap-4">
                    <div className="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl shrink-0 shadow-md">
                        💰
                    </div>
                    <div className="space-y-1">
                        <h2 className="text-base font-bold text-amber-950 flex items-center gap-2">
                            <span>تنبيه هام بشأن رسوم وتكلفة الخدمة</span>
                            <span className="bg-amber-200 text-amber-900 text-xs px-2.5 py-0.5 rounded-full font-extrabold">
                                1 جنيه إضافي / لكل أوردر
                            </span>
                        </h2>
                        <p className="text-xs md:text-sm text-amber-900/90 leading-relaxed">
                            عند تفعيل خدمة التأكيد التلقائي، يتم خصم <strong>1 جنيه مصري زيادة على كل أوردر</strong> يتم إرسال رسالة تأكيد له، وذلك <strong>أياً كان نوع باقتك أو اشتراكك الحالي</strong> (سواء اشتراك مجاني، باقة شهرية، أو باقة العمولة).
                        </p>
                    </div>
                </div>

                {/* Live Merchant WhatsApp Stats Grid */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
                    <div className="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm space-y-1">
                        <div className="text-[11px] font-semibold text-gray-500">رسائل الواتساب المرسلة</div>
                        <div className="text-xl font-black text-indigo-600">
                            {stats.total_messages_sent || 0}
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm space-y-1">
                        <div className="text-[11px] font-semibold text-emerald-800">أوردرات مؤكدة واتس</div>
                        <div className="text-xl font-black text-emerald-600">
                            {stats.confirmed_via_wa || 0}
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-red-200 bg-red-50/50 p-4 shadow-sm space-y-1">
                        <div className="text-[11px] font-semibold text-red-800">أوردرات ملغية واتس</div>
                        <div className="text-xl font-black text-red-600">
                            {stats.cancelled_via_wa || 0}
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm space-y-1">
                        <div className="text-[11px] font-semibold text-amber-800">إجمالي الرسوم المستحقة</div>
                        <div className="text-xl font-black text-amber-900">
                            {(stats.total_charges_egp || 0).toLocaleString()} ج.م
                        </div>
                    </div>
                </div>

                {/* 4 Outcome Scenarios Grid */}
                <div>
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span>⚙️</span>
                            <span>كيف تعمل الخدمة وحالات التعامل مع الردود</span>
                        </h2>
                        <span className="text-xs text-gray-500">معالجة آلية 100% بدون تدخل بشري</span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {/* Scenario 1: Confirmed */}
                        <div className="bg-white rounded-2xl border border-emerald-200 p-5 shadow-sm space-y-3 relative overflow-hidden">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg font-bold">
                                    ✅
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-gray-900">1. في حالة التأكيد من العميل</h3>
                                    <span className="text-[11px] text-emerald-600 font-semibold">تحويل فوري إلى "مؤكد"</span>
                                </div>
                            </div>
                            <p className="text-xs text-gray-600 leading-relaxed">
                                يتحول الطلب تلقائياً إلى حالة <strong>مؤكد (Confirmed)</strong> في جدول الطلبات، ويظهر إشعار ووسم بجانب الأوردر:
                            </p>
                            <div className="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-bold flex items-center gap-2">
                                <span>🏷️</span>
                                <span>تم التأكيد بواسطة الواتس من العميل</span>
                            </div>
                        </div>

                        {/* Scenario 2: Cancelled */}
                        <div className="bg-white rounded-2xl border border-red-200 p-5 shadow-sm space-y-3 relative overflow-hidden">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-xl bg-red-100 text-red-700 flex items-center justify-center text-lg font-bold">
                                    ❌
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-gray-900">2. في حالة الإلغاء من العميل</h3>
                                    <span className="text-[11px] text-red-600 font-semibold">تحويل فوري إلى "ملغي" واسترجاع المخزون</span>
                                </div>
                            </div>
                            <p className="text-xs text-gray-600 leading-relaxed">
                                يتحول الطلب مباشرة إلى <strong>ملغي (Cancelled)</strong> ويتم إرجاع كميات المنتجات إلى المخزون تلقائياً، مع ظهور وسم:
                            </p>
                            <div className="p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 font-bold flex items-center gap-2">
                                <span>🏷️</span>
                                <span>تم الإلغاء بواسطة الواتس من العميل</span>
                            </div>
                        </div>

                        {/* Scenario 3: No Response */}
                        <div className="bg-white rounded-2xl border border-amber-200 p-5 shadow-sm space-y-3 relative overflow-hidden">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg font-bold">
                                    ⏳
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-gray-900">3. في حالة عدم الرد</h3>
                                    <span className="text-[11px] text-amber-700 font-semibold">تسجيل الوقت والتاريخ لمتابعة الأوردر</span>
                                </div>
                            </div>
                            <p className="text-xs text-gray-600 leading-relaxed">
                                يظل الطلب معلقاً، ويتم توثيق محاولة المراسلة مع توضيح الوقت بدقة داخل تفاصيل الطلب:
                            </p>
                            <div className="p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 font-bold flex items-center gap-2">
                                <span>🏷️</span>
                                <span>تم إرسال رسالة واتساب إلى العميل في وقت وتاريخ (....) ولم يتم الرد</span>
                            </div>
                        </div>

                        {/* Scenario 4: No WhatsApp on Number */}
                        <div className="bg-white rounded-2xl border border-blue-200 p-5 shadow-sm space-y-3 relative overflow-hidden">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg font-bold">
                                    📞
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-gray-900">4. في حالة عدم وجود واتساب لرقم العميل</h3>
                                    <span className="text-[11px] text-blue-700 font-semibold">تنبيه فوري للاتصال الهاتفي</span>
                                </div>
                            </div>
                            <p className="text-xs text-gray-600 leading-relaxed">
                                يكتشف النظام فوراً عدم وجود حساب واتساب مفعل لرقم الهاتف، ويظهر تنبيه مباشر للتاجر:
                            </p>
                            <div className="p-2.5 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-800 font-bold flex items-center gap-2">
                                <span>🏷️</span>
                                <span>لا يوجد واتساب للعميل، برجاء الاتصال هاتفياً للتأكيد</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* WhatsApp Full Message Preview Showcase */}
                <div className="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 md:p-8 space-y-6">
                    <div className="border-b border-gray-100 pb-4 text-center max-w-xl mx-auto space-y-1">
                        <div className="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 rounded-full text-xs font-bold text-emerald-700">
                            <span>📱</span>
                            <span>معاينة حية لشكل رسالة التأكيد الكاملة</span>
                        </div>
                        <h3 className="text-lg font-bold text-gray-900">
                            هكذا تصل الرسالة التفاعلية للعميل على هاتفه
                        </h3>
                        <p className="text-xs text-gray-500">
                            الرسالة موحدة ومنظمة تلقائياً وتحتوي على ملخص شامل للمنتجات وأسعارها ومصاريف الشحن وأزرار الرد السريع:
                        </p>
                    </div>

                    {/* Phone Mockup Frame */}
                    <div className="max-w-md mx-auto bg-[#0b141a] rounded-[32px] overflow-hidden shadow-2xl border-4 border-gray-800 text-white font-sans">
                        {/* WhatsApp Top Status Bar */}
                        <div className="bg-[#1f2c34] px-4 py-3 flex items-center justify-between border-b border-gray-700/60">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-full bg-emerald-600 flex items-center justify-center text-sm font-bold shadow-inner">
                                    🛍️
                                </div>
                                <div>
                                    <div className="font-bold text-xs text-gray-100 flex items-center gap-1.5">
                                        <span>خدمة عملاء المتجر</span>
                                        <span className="text-[10px] text-emerald-400">✓</span>
                                    </div>
                                    <div className="text-[10px] text-emerald-400">حساب تجاري رسمي (Official Bot)</div>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 text-gray-300 text-xs">
                                <span>📞</span>
                                <span>⋮</span>
                            </div>
                        </div>

                        {/* WhatsApp Chat Area */}
                        <div className="p-4 space-y-3 bg-[#0b141a] bg-opacity-95 min-h-[380px] flex flex-col justify-end">
                            {/* Message Bubble */}
                            <div className="bg-[#005c4b] text-white p-4 rounded-2xl rounded-br-none max-w-[95%] self-end space-y-3 text-right shadow-md border border-emerald-600/30">
                                <div>
                                    <p className="text-xs font-bold text-emerald-100">
                                        مرحباً أحمد محمد 👋
                                    </p>
                                    <p className="text-[11px] text-gray-100 mt-0.5">
                                        شكراً لطلبك من متجرنا! تم تسجيل طلبك بنجاح برقم: <strong>#48921</strong>
                                    </p>
                                </div>

                                {/* Order Items Summary */}
                                <div className="bg-black/20 p-2.5 rounded-xl text-[11px] space-y-1.5 border border-white/10">
                                    <div className="font-bold text-emerald-200 text-xs border-b border-white/10 pb-1 flex items-center gap-1">
                                        <span>📦</span>
                                        <span>المنتجات المطلوبة:</span>
                                    </div>
                                    <div className="flex justify-between items-center text-gray-200">
                                        <span>• تيشيرت صيفي كاجوال (العدد: 2)</span>
                                        <span className="font-bold text-white">400 ج.م</span>
                                    </div>
                                    <div className="flex justify-between items-center text-gray-200">
                                        <span>• كوتشي كاجوال أسود (العدد: 1)</span>
                                        <span className="font-bold text-white">350 ج.م</span>
                                    </div>
                                </div>

                                {/* Pricing Breakdown */}
                                <div className="bg-black/20 p-2.5 rounded-xl text-[11px] space-y-1 border border-white/10">
                                    <div className="flex justify-between text-gray-300">
                                        <span>💵 إجمالي المنتجات:</span>
                                        <span>750 ج.م</span>
                                    </div>
                                    <div className="flex justify-between text-gray-300">
                                        <span>🚚 مصاريف الشحن والتوصيل:</span>
                                        <span className="text-emerald-300 font-semibold">50 ج.م (الجيزة)</span>
                                    </div>
                                    <div className="flex justify-between text-white font-extrabold pt-1 border-t border-white/10 text-xs">
                                        <span>💰 المبلغ الإجمالي عند الاستلام:</span>
                                        <span className="text-emerald-300">800 ج.م</span>
                                    </div>
                                </div>

                                {/* Delivery Info */}
                                <div className="text-[11px] text-gray-200 space-y-0.5">
                                    <div>📍 <strong>عنوان التوصيل:</strong> شارع التحرير، الدقي، الجيزة</div>
                                    <div>📞 <strong>رقم الهاتف:</strong> 01012345678</div>
                                </div>

                                <p className="text-[11px] text-emerald-100 font-medium pt-1">
                                    👇 يرجى الضغط على زر التأكيد أدناه للموافقة وبدء تجهيز الشحنة فوراً:
                                </p>

                                <div className="text-[9px] text-emerald-200 text-left pt-1">12:30 م ✓✓</div>
                            </div>

                            {/* WhatsApp Quick Reply Buttons */}
                            <div className="space-y-2 pt-1">
                                <div className="bg-[#1f2c34] hover:bg-[#2a3942] text-emerald-400 py-2.5 px-4 rounded-xl text-center font-extrabold text-xs flex items-center justify-center gap-2 border border-emerald-500/40 cursor-default shadow-md">
                                    <span>✅</span>
                                    <span>تأكيد الطلب وشحنه الآن</span>
                                </div>
                                <div className="bg-[#1f2c34] hover:bg-[#2a3942] text-red-400 py-2.5 px-4 rounded-xl text-center font-extrabold text-xs flex items-center justify-center gap-2 border border-red-500/40 cursor-default shadow-md">
                                    <span>❌</span>
                                    <span>إلغاء الطلب</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </MerchantLayout>
    );
}

