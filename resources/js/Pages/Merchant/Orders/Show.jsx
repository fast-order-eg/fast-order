import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function OrderShow({ order, active_shipping_gateways = [] }) {
    const { flash } = usePage().props;

    const handleStatusChange = (newStatus) => {
        router.patch(`/admin/orders/${order.id}/status`, { status: newStatus }, {
            preserveScroll: true,
        });
    };

    const handleCancel = () => {
        if (!confirm('هل أنت متأكد من إلغاء هذا الطلب؟')) return;
        router.patch(`/admin/orders/${order.id}/cancel`, {}, {
            preserveScroll: true,
        });
    };

    const getStatusText = (status) => {
        const statuses = {
            pending: 'في الانتظار',
            confirmed: 'مؤكد',
            shipped: 'في التوصيل',
            delivered: 'تم التسليم',
            cancelled: 'ملغي',
        };
        return statuses[status] || status;
    };

    const getStatusBadgeClass = (status) => {
        const classes = {
            pending: 'bg-yellow-50 text-yellow-700 border-yellow-100',
            confirmed: 'bg-blue-50 text-blue-700 border-blue-100',
            shipped: 'bg-purple-50 text-purple-700 border-purple-100',
            delivered: 'bg-green-50 text-green-700 border-green-100',
            cancelled: 'bg-red-50 text-red-700 border-red-100',
        };
        return classes[status] || 'bg-gray-50 text-gray-700 border-gray-150';
    };

    const formatCurrency = (amount) => {
        return Math.round(Number(amount)).toLocaleString('en-US') + ' ج.م';
    };

    const getCleanWhatsAppPhone = (phone) => {
        if (!phone) return '';
        let cleaned = phone.replace(/[^0-9]/g, '');
        if (cleaned.startsWith('01')) {
            cleaned = '20' + cleaned.substring(1);
        }
        return cleaned;
    };

    const generateWhatsAppUrl = (orderData) => {
        const cleanPhone = getCleanWhatsAppPhone(orderData.customer_phone);
        
        const itemsList = (orderData.items || []).map((item, idx) => {
            const unitPrice = Math.round(Number(item.price));
            const itemTotal = Math.round(Number(item.total));
            const mainLine = `${idx + 1}. ${item.name} (${item.quantity}x ${unitPrice}) = ${itemTotal}`;

            let variantDetails = [];
            if (item.selectedColor) variantDetails.push(`اللون: ${item.selectedColor}`);
            if (item.selectedSize) variantDetails.push(`المقاس: ${item.selectedSize}`);
            if (item.options && typeof item.options === 'object') {
                Object.entries(item.options).forEach(([k, v]) => {
                    if (v) variantDetails.push(`${k}: ${v}`);
                });
            }

            if (variantDetails.length > 0) {
                return `${mainLine}\n${variantDetails.join(', ')}`;
            }
            return mainLine;
        }).join('\n');

        const subtotal = Math.round(Number(orderData.subtotal));
        const shipping = Math.round(Number(orderData.shipping_cost || 0));
        const total = Math.round(Number(orderData.total));
        const refNum = orderData.reference_number ? `#${orderData.reference_number}` : `#${orderData.id}`;

        let totalsBlock = `مجموع سعر المنتجات: ${subtotal}`;
        if (shipping > 0) {
            totalsBlock += `\nرسوم التوصيل: ${shipping}`;
        }
        totalsBlock += `\nالاجمالي: ${total} ج.م`;

        let shippingLines = [];
        if (orderData.governorate) {
            shippingLines.push(`المحافظة: ${orderData.governorate}`);
        }
        if (orderData.customer_address) {
            shippingLines.push(`العنوان: ${orderData.customer_address}`);
        }
        let shippingBlock = '';
        if (shippingLines.length > 0) {
            shippingBlock = `\n\nبيانات الشحن:\n\n${shippingLines.join('\n')}`;
        }

        const text = `مرحبا: ${orderData.customer_name || ''}

ملخص الطلب:

معرف الطلب: ${refNum}
عناصر السلة:
${itemsList}
${totalsBlock}${shippingBlock}`;

        const encodedText = encodeURIComponent(text);
        return `https://api.whatsapp.com/send/?phone=%2B${cleanPhone}&text=${encodedText}`;
    };

    return (
        <MerchantLayout title={`تفاصيل الطلب ${order.reference_number}`}>
            <Head title={`طلب ${order.reference_number}`} />

            <div className="w-full space-y-6">
                {/* Breadcrumb & Navigation */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <nav className="flex items-center gap-2 text-sm text-gray-500">
                        <Link href="/admin/orders" className="hover:text-orange-600 transition-colors">الطلبات</Link>
                        <svg className="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span className="text-gray-800 font-medium">تفاصيل الطلب {order.reference_number}</span>
                    </nav>
                    
                    <div className="flex items-center gap-2">
                        <a
                            href={`/admin/orders/${order.id}/invoice`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition-colors shadow-sm"
                        >
                            <svg className="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            الفاتورة pdf
                        </a>
                    </div>
                </div>

                {/* Status Bar */}
                <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <span className="text-sm font-semibold text-gray-500">حالة الطلب الحالية:</span>
                        <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border ${getStatusBadgeClass(order.status)}`}>
                            {getStatusText(order.status)}
                        </span>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <div className="flex items-center gap-2">
                            <span className="text-xs font-bold text-gray-500">تحديث حالة الطلب:</span>
                            <select
                                value={order.status}
                                onChange={(e) => handleStatusChange(e.target.value)}
                                className="bg-white border border-gray-300 rounded-lg text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-bold text-gray-800"
                            >
                                <option value="pending">في الانتظار</option>
                                <option value="confirmed">مؤكد</option>
                                <option value="shipped">في التوصيل</option>
                                <option value="delivered">تم التسليم</option>
                                <option value="cancelled">ملغي</option>
                            </select>
                        </div>

                        <a
                            href={generateWhatsAppUrl(order)}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm shadow-emerald-100 shrink-0 cursor-pointer"
                            title="إرسال ملخص التفاصيل للعميل عبر الواتساب لتأكيد الطلب"
                        >
                            <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.205 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                            </svg>
                            <span>التأكيد عبر الواتساب</span>
                        </a>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border-r-4 border-green-500 rounded-lg text-green-800 text-sm font-medium flex items-center gap-2">
                        <span>✓</span>
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border-r-4 border-red-500 rounded-lg text-red-800 text-sm font-medium flex items-center gap-2">
                        <span>⚠️</span>
                        {flash.error}
                    </div>
                )}

                <div className="flex flex-col lg:grid lg:grid-cols-5 gap-6 items-stretch lg:items-start w-full">
                    {/* العمود الرئيسي (يسار على الديسكتوب 3/5) */}
                    <div className="contents lg:block lg:col-span-3 lg:space-y-6 w-full">
                        {/* 1. منتجات الطلب */}
                        <div className="order-1 w-full bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                            <h3 className="font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    <span>منتجات الطلب</span>
                                </span>
                                <span className="text-xs font-semibold bg-gray-100 text-gray-600 px-2.5 py-0.5 rounded-full">
                                    {order.items?.length || 0} منتج
                                </span>
                            </h3>

                            <div className="divide-y divide-gray-100">
                                {order.items.map((item, idx) => (
                                    <div key={idx} className="py-4 flex gap-4 first:pt-0 last:pb-0">
                                        <a
                                            href={`/shop/product.html?id=${item.id}`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="w-16 h-16 rounded-lg overflow-hidden border border-gray-200 bg-gray-50 flex-shrink-0 hover:opacity-80 transition-opacity"
                                        >
                                            {item.image_url ? (
                                                <img
                                                    src={item.image_url}
                                                    alt={item.name}
                                                    className="w-full h-full object-cover"
                                                    onError={(e) => {
                                                        e.currentTarget.onerror = null;
                                                        e.currentTarget.src = 'https://dummyimage.com/150x150/f3f4f6/9ca3af&text=صورة+المنتج';
                                                    }}
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-xs text-gray-400 font-bold bg-gray-100">صورة</div>
                                            )}
                                        </a>

                                        <div className="flex-1 min-w-0">
                                            <a
                                                href={`/shop/product.html?id=${item.id}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="font-semibold text-gray-900 text-sm hover:text-orange-600 transition-colors block"
                                            >
                                                {item.name}
                                            </a>
                                            <div className="flex flex-wrap gap-2 mt-1">
                                                {item.selectedSize && (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">
                                                        مقاس: {item.selectedSize}
                                                    </span>
                                                )}
                                                {item.selectedColor && (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">
                                                        لون: {item.selectedColor}
                                                    </span>
                                                )}
                                                {item.options && Object.entries(item.options).map(([k, v]) => v ? (
                                                    <span key={k} className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-100">
                                                        {k}: {v}
                                                    </span>
                                                ) : null)}
                                                <span className="text-xs text-gray-500 font-medium">الكمية: {item.quantity}</span>
                                            </div>
                                        </div>

                                        <div className="text-left flex-shrink-0">
                                            <p className="font-bold text-gray-900 text-sm">{formatCurrency(item.price)}</p>
                                            <p className="text-xs text-gray-400 mt-0.5">الإجمالي: {formatCurrency(item.total)}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* 5. ملاحظات وسجل الطلب */}
                        <div className="order-5 w-full bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-3">
                            <h4 className="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span>📝</span>
                                <span>ملاحظات وسجل الطلب</span>
                            </h4>
                            {order.notes ? (
                                <p className="text-sm text-gray-700 bg-gray-50 rounded-xl p-3.5 border border-gray-100 leading-relaxed whitespace-pre-line">
                                    {order.notes}
                                </p>
                            ) : (
                                <p className="text-xs text-gray-400 bg-gray-50 rounded-xl p-3 border border-gray-100">
                                    لا توجد ملاحظات مسجلة على هذا الطلب حتى الآن.
                                </p>
                            )}
                        </div>

                        {/* 6. حالة وتأكيد الواتساب التلقائي */}
                        <div className="order-6 w-full bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl border border-emerald-200 shadow-sm p-5 space-y-3">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-emerald-200/70 pb-3">
                                <div className="flex items-center gap-2">
                                    <span className="text-xl">💬</span>
                                    <h4 className="font-bold text-gray-900 text-sm">حالة وتأكيد الواتساب التلقائي</h4>
                                </div>

                                {(!order.whatsapp_status || order.whatsapp_status === 'none' || order.whatsapp_status === 'failed') ? (
                                    <div className="flex items-center gap-2">
                                        <span className="px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                                            غير مفعل
                                        </span>
                                        <Link
                                            href="/admin/auto-confirm"
                                            className="inline-flex items-center justify-center px-3.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm cursor-pointer"
                                        >
                                            تفعيل
                                        </Link>
                                    </div>
                                ) : (
                                    <span className={`px-2.5 py-1 rounded-full text-xs font-bold ${
                                        order.whatsapp_status === 'confirmed'
                                            ? 'bg-emerald-600 text-white'
                                            : (order.whatsapp_status === 'cancelled'
                                                ? 'bg-red-600 text-white'
                                                : (order.whatsapp_status === 'pending'
                                                    ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                                    : (order.whatsapp_status === 'no_whatsapp'
                                                        ? 'bg-blue-100 text-blue-900 border border-blue-300'
                                                        : 'bg-gray-100 text-gray-600'))
                                            )
                                    }`}>
                                        {order.whatsapp_status === 'confirmed' && 'تم التأكيد عبر الواتس ✅'}
                                        {order.whatsapp_status === 'cancelled' && 'تم الإلغاء عبر الواتس ❌'}
                                        {order.whatsapp_status === 'pending' && 'بانتظار رد العميل ⏳'}
                                        {order.whatsapp_status === 'no_whatsapp' && 'لا يوجد واتساب ⚠️'}
                                    </span>
                                )}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div className="bg-white/80 p-2.5 rounded-xl border border-emerald-100 space-y-1">
                                    <span className="text-gray-500 font-medium block">وقت وتاريخ الإرسال:</span>
                                    <span className="font-bold text-gray-800">
                                        {order.whatsapp_sent_at ? order.whatsapp_sent_at : 'لم ترسل بعد'}
                                    </span>
                                </div>

                                <div className="bg-white/80 p-2.5 rounded-xl border border-emerald-100 space-y-1">
                                    <span className="text-gray-500 font-medium block">وقت وتاريخ استجابة العميل:</span>
                                    <span className="font-bold text-gray-800">
                                        {order.whatsapp_response_at ? order.whatsapp_response_at : 'في انتظار الرد'}
                                    </span>
                                </div>

                                {order.whatsapp_message_id && (
                                    <div className="bg-white/80 p-2.5 rounded-xl border border-emerald-100 space-y-1 sm:col-span-2">
                                        <span className="text-gray-500 font-medium block">معرّف الرسالة الرسمي من واتساب:</span>
                                        <span className="font-mono text-[11px] font-bold text-indigo-700 select-all break-all dir-ltr text-left block">
                                            {order.whatsapp_message_id}
                                        </span>
                                    </div>
                                )}
                            </div>

                            {order.whatsapp_charge_amount > 0 && (
                                <div className="text-[11px] text-emerald-800 font-semibold flex items-center justify-between pt-1 border-t border-emerald-200/50">
                                    <span>رسوم الخدمة المسجلة على هذا الطلب:</span>
                                    <span className="font-bold">{order.whatsapp_charge_amount} ج.م</span>
                                </div>
                            )}
                        </div>

                        {/* 7. إرسال الشحنة لشركة الشحن وتوليد البوليسة */}
                        <div className="order-7 w-full bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                                <h3 className="font-bold text-gray-900 flex items-center gap-2 text-sm">
                                    <span>🚚</span>
                                    <span>إرسال الشحنة لشركة الشحن وتوليد البوليسة</span>
                                </h3>
                                <Link
                                    href="/admin/shipping-gateways"
                                    className="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1"
                                >
                                    <span>إعدادات شركات الشحن ⚙️</span>
                                </Link>
                            </div>

                            <div className="space-y-3">
                                <p className="text-xs text-gray-500">
                                    اختر شركة الشحن المربوطة لإرسال بيانات العميل والطلب واستخراج رقم التتبع فوراً:
                                </p>
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    {/* Bosta */}
                                    {active_shipping_gateways.includes('bosta') ? (
                                        <button
                                            type="button"
                                            onClick={() => router.post(`/admin/orders/${order.id}/shipment`, { provider: 'bosta' })}
                                            className="py-2.5 px-3 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm text-center flex flex-col items-center justify-center gap-1 cursor-pointer"
                                        >
                                            <span className="font-black">📦 بوسطة (Bosta)</span>
                                            <span className="text-[10px] text-red-100">مفعلة ✓</span>
                                        </button>
                                    ) : (
                                        <Link
                                            href="/admin/shipping-gateways"
                                            className="py-2.5 px-3 bg-gray-50 hover:bg-gray-100 text-gray-600 border border-dashed border-gray-300 rounded-xl text-xs font-bold transition-colors text-center flex flex-col items-center justify-center gap-1"
                                            title="اضغط لربط وتفعيل بوسطة"
                                        >
                                            <span>📦 بوسطة (Bosta)</span>
                                            <span className="text-[10px] text-amber-600 font-normal">غير مربوطة (ربط الآن ⚙️)</span>
                                        </Link>
                                    )}

                                    {/* J&T Express */}
                                    {active_shipping_gateways.includes('jnt') ? (
                                        <button
                                            type="button"
                                            onClick={() => router.post(`/admin/orders/${order.id}/shipment`, { provider: 'jnt' })}
                                            className="py-2.5 px-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all shadow-sm text-center flex flex-col items-center justify-center gap-1 cursor-pointer"
                                        >
                                            <span className="font-black">⚡ J&T Express</span>
                                            <span className="text-[10px] text-amber-100">مفعلة ✓</span>
                                        </button>
                                    ) : (
                                        <Link
                                            href="/admin/shipping-gateways"
                                            className="py-2.5 px-3 bg-gray-50 hover:bg-gray-100 text-gray-600 border border-dashed border-gray-300 rounded-xl text-xs font-bold transition-colors text-center flex flex-col items-center justify-center gap-1"
                                            title="اضغط لربط وتفعيل J&T"
                                        >
                                            <span>⚡ J&T Express</span>
                                            <span className="text-[10px] text-amber-600 font-normal">غير مربوطة (ربط الآن ⚙️)</span>
                                        </Link>
                                    )}

                                    {/* Aramex */}
                                    {active_shipping_gateways.includes('aramex') ? (
                                        <button
                                            type="button"
                                            onClick={() => router.post(`/admin/orders/${order.id}/shipment`, { provider: 'aramex' })}
                                            className="py-2.5 px-3 bg-red-800 hover:bg-red-900 text-white rounded-xl text-xs font-bold transition-all shadow-sm text-center flex flex-col items-center justify-center gap-1 cursor-pointer"
                                        >
                                            <span className="font-black">🔴 أرامكس (Aramex)</span>
                                            <span className="text-[10px] text-red-200">مفعلة ✓</span>
                                        </button>
                                    ) : (
                                        <Link
                                            href="/admin/shipping-gateways"
                                            className="py-2.5 px-3 bg-gray-50 hover:bg-gray-100 text-gray-600 border border-dashed border-gray-300 rounded-xl text-xs font-bold transition-colors text-center flex flex-col items-center justify-center gap-1"
                                            title="اضغط لربط وتفعيل أرامكس"
                                        >
                                            <span>🔴 أرامكس (Aramex)</span>
                                            <span className="text-[10px] text-amber-600 font-normal">غير مربوطة (ربط الآن ⚙️)</span>
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* العمود الجانبي (يمين على الديسكتوب 2/5) */}
                    <div className="contents lg:block lg:col-span-2 lg:space-y-6 w-full">
                        {/* 2. بيانات العميل */}
                        <div className="order-2 w-full bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                            <h3 className="font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                                <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>بيانات العميل</span>
                            </h3>

                            <div className="space-y-3 text-sm">
                                <div>
                                    <span className="text-xs text-gray-400 block mb-0.5">الاسم:</span>
                                    <span className="font-semibold text-gray-900">{order.customer_name}</span>
                                </div>
                                <div>
                                    <span className="text-xs text-gray-400 block mb-0.5">الهاتف:</span>
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="font-mono font-semibold text-gray-900" dir="ltr">{order.customer_phone}</span>
                                        <a
                                            href={generateWhatsAppUrl(order)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-md text-xs font-bold transition-colors cursor-pointer"
                                        >
                                            <svg className="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.205 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                            </svg>
                                            واتساب
                                        </a>
                                    </div>
                                </div>
                                <div>
                                    <span className="text-xs text-gray-400 block mb-0.5">المحافظة:</span>
                                    <span className="font-semibold text-gray-900">{order.governorate}</span>
                                </div>
                                <div>
                                    <span className="text-xs text-gray-400 block mb-0.5">العنوان:</span>
                                    <span className="text-gray-700 leading-relaxed block">{order.customer_address}</span>
                                </div>
                            </div>
                        </div>

                        {/* 3. ملخص الطلب والحساب */}
                        <div className="order-3 w-full bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                            <h3 className="font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center justify-between">
                                <span>ملخص الحساب</span>
                                <span className="text-xs text-gray-400">الإجمالي الشامل</span>
                            </h3>

                            <div className="space-y-2.5 text-sm">
                                <div className="flex justify-between text-gray-600">
                                    <span>المجموع الفرعي:</span>
                                    <span className="font-semibold">{formatCurrency(order.subtotal)}</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>تكلفة الشحن والتوصيل:</span>
                                    <span className="font-semibold">{formatCurrency(order.shipping_cost)}</span>
                                </div>
                                <div className="border-t border-gray-100 my-2 pt-2.5 flex justify-between font-extrabold text-base text-gray-900">
                                    <span>الإجمالي الكلي:</span>
                                    <span className="text-indigo-600 text-lg">{formatCurrency(order.total)}</span>
                                </div>
                            </div>
                        </div>

                        {/* 4. طريقة وحالة الدفع */}
                        <div className="order-4 w-full bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                                <h3 className="font-bold text-gray-900 flex items-center gap-1.5 text-sm">
                                    <span>💳</span>
                                    <span>طريقة وحالة الدفع</span>
                                </h3>
                                {order.payment_status === 'paid' ? (
                                    <span className="px-2.5 py-0.5 rounded-md text-[11px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                                        <span>✓</span>
                                        <span>مدفوع إلكترونياً</span>
                                    </span>
                                ) : (
                                    <span className="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-gray-100 text-gray-700">
                                        {order.payment_method === 'cod' ? 'عند الاستلام (كاش)' : 'بانتظار السداد ⏳'}
                                    </span>
                                )}
                            </div>

                            <div className="space-y-2.5 text-xs">
                                <div className="flex justify-between items-center">
                                    <span className="text-gray-500">الوسيلة المختارة:</span>
                                    <span className="font-bold text-gray-800">
                                        {order.payment_method === 'paymob' ? '⚡ باي موب (Paymob)' : (order.payment_method === 'kashier' ? '🟢 كاشير (Kashier)' : (order.payment_method === 'fawry' ? '🟡 فوري باي (Fawry)' : '💵 الدفع عند الاستلام'))}
                                    </span>
                                </div>
                                {order.transaction_id && (
                                    <div className="flex justify-between items-center pt-2 border-t border-gray-100">
                                        <span className="text-gray-500">رقم المعاملة البنكية:</span>
                                        <span className="font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded select-all text-[11px]" dir="ltr">
                                            {order.transaction_id}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </MerchantLayout>
    );
}

