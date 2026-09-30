import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

export default function OrderShow({ order, active_shipping_gateways = [], is_auto_confirm_enabled = false, wallet_balance = 0 }) {
    const { flash } = usePage().props;
    const [isSendingWa, setIsSendingWa] = React.useState(false);
    const [previewImage, setPreviewImage] = React.useState(null);

    React.useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') setPreviewImage(null);
        };
        if (previewImage) {
            window.addEventListener('keydown', handleKeyDown);
            document.body.style.overflow = 'hidden';
        }
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
            document.body.style.overflow = '';
        };
    }, [previewImage]);

    const handleSendAutoConfirm = () => {
        if (!confirm('هل تريد إرسال رسالة التأكيد التلقائي للعميل عبر الواتساب الآن؟ (تكلفة الرسالة 1 ج.م تُخصم من رصيد المحفظة)')) return;
        setIsSendingWa(true);
        router.post(`/admin/orders/${order.id}/send-whatsapp-confirm`, {}, {
            preserveScroll: true,
            onFinish: () => setIsSendingWa(false),
        });
    };

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
            shipped: 'مع شركة الشحن',
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
            if (item.piecesSelections && Array.isArray(item.piecesSelections) && item.piecesSelections.length > 0) {
                const piecesStr = item.piecesSelections.map((p, i) => `قطعة ${p.piece || (i + 1)}: ${[p.color ? `اللون: ${p.color}` : '', p.size ? `المقاس: ${p.size}` : ''].filter(Boolean).join(' - ')}`).join(' | ');
                variantDetails.push(`القطع: [ ${piecesStr} ]`);
            } else {
                if (item.selectedColor) variantDetails.push(`اللون: ${item.selectedColor}`);
                if (item.selectedSize) variantDetails.push(`المقاس: ${item.selectedSize}`);
                if (item.options && typeof item.options === 'object') {
                    Object.entries(item.options).forEach(([k, v]) => {
                        if (v) variantDetails.push(`${k}: ${v}`);
                    });
                }
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
                                <option value="shipped">مع شركة الشحن</option>
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
                                {order.items.map((item, idx) => {
                                    const hasPieces = item.piecesSelections && Array.isArray(item.piecesSelections) && item.piecesSelections.length > 0;
                                    const mainImg = item.selectedColorImage || item.image_url;

                                    return (
                                        <div key={idx} className="py-4 first:pt-0 last:pb-0 space-y-3">
                                            {/* Header row: Thumbnail & Title/Price */}
                                            <div className="flex items-start gap-3 sm:gap-4">
                                                {/* Thumbnail with Lightbox zoom */}
                                                <div className="relative group shrink-0">
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            if (mainImg) setPreviewImage({ url: mainImg, title: item.name });
                                                        }}
                                                        className={`w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center relative transition-all ${mainImg ? 'cursor-pointer hover:ring-2 hover:ring-indigo-400 hover:shadow-md' : ''}`}
                                                        title={mainImg ? "اضغط لتكبير الصورة" : ""}
                                                    >
                                                        {mainImg ? (
                                                            <>
                                                                <img
                                                                    src={mainImg}
                                                                    alt={item.name}
                                                                    className="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
                                                                    onError={(e) => {
                                                                        e.currentTarget.onerror = null;
                                                                        e.currentTarget.src = 'https://dummyimage.com/150x150/f3f4f6/9ca3af&text=صورة+المنتج';
                                                                    }}
                                                                />
                                                                <span className="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity rounded-xl">
                                                                    <svg className="w-5 h-5 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                                                    </svg>
                                                                </span>
                                                            </>
                                                        ) : (
                                                            <div className="w-full h-full flex items-center justify-center text-xs text-gray-400 font-bold bg-gray-100">صورة</div>
                                                        )}
                                                    </button>
                                                </div>

                                                {/* Title + Price */}
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-start justify-between gap-2 sm:gap-4">
                                                        <div className="min-w-0">
                                                            <a
                                                                href={`/shop/product.html?id=${item.id}`}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="font-bold text-gray-900 text-sm sm:text-base hover:text-orange-600 transition-colors block leading-snug line-clamp-2"
                                                            >
                                                                {item.name}
                                                            </a>
                                                            <div className="flex items-center gap-2 mt-1">
                                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-700">
                                                                    الكمية: {item.quantity}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div className="text-left shrink-0">
                                                            <p className="font-bold text-gray-900 text-sm sm:text-base whitespace-nowrap">{formatCurrency(item.price)}</p>
                                                            {Number(item.quantity) > 1 && (
                                                                <p className="text-[11px] sm:text-xs text-gray-400 mt-0.5 whitespace-nowrap">
                                                                    الإجمالي: {formatCurrency(item.total || (item.price * item.quantity))}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Standard Variant Badges (Only shown if NOT multi-pieces to avoid duplicates) */}
                                                    {!hasPieces && (
                                                        <div className="flex flex-wrap items-center gap-1.5 mt-2">
                                                            {item.selectedSize && (
                                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                                    مقاس: {item.selectedSize}
                                                                </span>
                                                            )}
                                                            {item.selectedColor && (
                                                                <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200">
                                                                    {item.selectedColorImage && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => setPreviewImage({ url: item.selectedColorImage, title: `${item.name} - لون: ${item.selectedColor}` })}
                                                                            className="cursor-pointer hover:opacity-80 transition shrink-0"
                                                                            title="اضغط لتكبير صورة اللون"
                                                                        >
                                                                            <img src={item.selectedColorImage} alt={item.selectedColor} className="w-4 h-4 rounded object-cover border border-gray-300" />
                                                                        </button>
                                                                    )}
                                                                    <span>لون: {item.selectedColor}</span>
                                                                </span>
                                                            )}
                                                            {item.options && Object.entries(item.options).map(([k, v]) => v ? (
                                                                <span key={k} className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-100">
                                                                    {k}: {v}
                                                                </span>
                                                            ) : null)}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Piece Selections Breakdown (اختيارات كل قطعة) - Spans FULL width */}
                                            {hasPieces && (
                                                <div className="w-full bg-slate-50/90 p-3 rounded-xl border border-slate-200/80 space-y-2 mt-2">
                                                    <div className="flex items-center justify-between text-xs">
                                                        <span className="font-bold text-gray-800 flex items-center gap-1.5">
                                                            <span className="w-2 h-2 rounded-full bg-orange-500"></span>
                                                            تفاصيل اختيار كل قطعة ({item.piecesSelections.length} قطع):
                                                        </span>
                                                        <span className="text-[11px] text-gray-400">اضغط على صورة اللون للتكبير</span>
                                                    </div>
                                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        {item.piecesSelections.map((pc, pIdx) => (
                                                            <div key={pIdx} className="flex items-center gap-2.5 bg-white p-2.5 rounded-lg border border-gray-200/90 shadow-2xs hover:border-gray-300 transition">
                                                                <span className="font-bold text-orange-600 text-xs shrink-0 bg-orange-50 px-2 py-1 rounded border border-orange-100">
                                                                    قطعة {pc.piece || (pIdx + 1)}
                                                                </span>
                                                                {pc.color_image ? (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => setPreviewImage({ url: pc.color_image, title: `${item.name} - قطعة ${pc.piece || (pIdx + 1)} (${pc.color || ''})` })}
                                                                        className="relative group shrink-0 rounded-lg overflow-hidden border border-gray-300 hover:ring-2 hover:ring-orange-400 transition"
                                                                        title="اضغط لتكبير صورة اللون"
                                                                    >
                                                                        <img src={pc.color_image} alt={pc.color || ''} className="w-8 h-8 object-cover" />
                                                                        <div className="absolute inset-0 bg-black/25 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                                                            <svg className="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                                                            </svg>
                                                                        </div>
                                                                    </button>
                                                                ) : null}
                                                                {pc.color && (
                                                                    <span className="font-bold text-gray-800 text-xs">{pc.color}</span>
                                                                )}
                                                                {pc.size && (
                                                                    <span className="text-gray-600 font-semibold text-xs bg-gray-100 px-2 py-0.5 rounded mr-auto border border-gray-200">
                                                                        مقاس {pc.size}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
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
                                    {!is_auto_confirm_enabled && (
                                        <span className="text-[11px] font-medium text-amber-700 bg-amber-100 px-2 py-0.5 rounded-md border border-amber-200">
                                            متوقفة بالمتجر
                                        </span>
                                    )}
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    {/* شارة الحالة */}
                                    <span className={`px-2.5 py-1 rounded-full text-xs font-bold ${
                                        order.whatsapp_status === 'confirmed'
                                            ? 'bg-emerald-600 text-white'
                                            : (order.whatsapp_status === 'cancelled'
                                                ? 'bg-red-600 text-white'
                                                : (order.whatsapp_status === 'pending'
                                                    ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                                    : (order.whatsapp_status === 'no_whatsapp'
                                                        ? 'bg-blue-100 text-blue-900 border border-blue-300'
                                                        : (order.whatsapp_status === 'failed'
                                                            ? 'bg-red-100 text-red-800 border border-red-200'
                                                            : 'bg-gray-100 text-gray-700'))))
                                    }`}>
                                        {order.whatsapp_status === 'confirmed' && 'تم التأكيد عبر الواتس ✅'}
                                        {order.whatsapp_status === 'cancelled' && 'تم الإلغاء عبر الواتس ❌'}
                                        {order.whatsapp_status === 'pending' && 'تم الإرسال (بانتظار رد العميل) ⏳'}
                                        {order.whatsapp_status === 'no_whatsapp' && 'الرقم غير مسجل بالواتساب ⚠️'}
                                        {order.whatsapp_status === 'failed' && 'فشل الإرسال ❌'}
                                        {(!order.whatsapp_status || order.whatsapp_status === 'none') && 'لم تُرسل بعد'}
                                    </span>

                                    {/* أزرار الإجراءات */}
                                    {(!order.whatsapp_status || order.whatsapp_status === 'none' || order.whatsapp_status === 'failed') && (
                                        <button
                                            type="button"
                                            onClick={handleSendAutoConfirm}
                                            disabled={isSendingWa}
                                            className="inline-flex items-center justify-center gap-1 px-3 py-1 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-sm cursor-pointer"
                                            title="إرسال رسالة التأكيد عبر بوابة Meta WhatsApp وخصم 1 ج.م من المحفظة"
                                        >
                                            {isSendingWa ? (
                                                <span>جاري الإرسال...</span>
                                            ) : (
                                                <span>{order.whatsapp_status === 'failed' ? 'إعادة المحاولة 🔄' : 'إرسال الآن 💬'}</span>
                                            )}
                                        </button>
                                    )}

                                    {order.whatsapp_status === 'pending' && (
                                        <button
                                            type="button"
                                            onClick={handleSendAutoConfirm}
                                            disabled={isSendingWa}
                                            className="inline-flex items-center justify-center gap-1 px-2.5 py-1 bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-300 disabled:opacity-50 rounded-lg text-xs font-bold transition-all shadow-sm cursor-pointer"
                                            title="إعادة إرسال رسالة التأكيد للعميل"
                                        >
                                            {isSendingWa ? 'جاري الإرسال...' : 'إعادة الإرسال 🔄'}
                                        </button>
                                    )}

                                    {!is_auto_confirm_enabled && (
                                        <Link
                                            href="/admin/auto-confirm"
                                            className="inline-flex items-center justify-center px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition-all shadow-sm"
                                        >
                                            إعدادات الخدمة ⚙️
                                        </Link>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div className="bg-white/80 p-2.5 rounded-xl border border-emerald-100 space-y-1">
                                    <span className="text-gray-500 font-medium block">وقت وتاريخ الإرسال:</span>
                                    <span className="font-bold text-gray-800">
                                        {order.whatsapp_sent_at ? order.whatsapp_sent_at : 'لم تُرسل بعد'}
                                    </span>
                                </div>

                                <div className="bg-white/80 p-2.5 rounded-xl border border-emerald-100 space-y-1">
                                    <span className="text-gray-500 font-medium block">استجابة العميل:</span>
                                    <span className="font-bold text-gray-800">
                                        {order.whatsapp_response_at 
                                            ? order.whatsapp_response_at 
                                            : (order.whatsapp_status === 'pending' ? 'في انتظار رد العميل' : '—')}
                                    </span>
                                </div>

                                {order.whatsapp_message_id && (
                                    <div className="bg-white/80 p-2.5 rounded-xl border border-emerald-100 space-y-1 sm:col-span-2">
                                        <span className="text-gray-500 font-medium block">معرّف الرسالة الرسمي من واتساب (WAMID):</span>
                                        <span className="font-mono text-[11px] font-bold text-indigo-700 select-all break-all dir-ltr text-left block">
                                            {order.whatsapp_message_id}
                                        </span>
                                    </div>
                                )}
                            </div>

                            <div className="text-[11px] text-emerald-800 font-semibold flex items-center justify-between pt-1 border-t border-emerald-200/50">
                                <span>رسوم خدمة التأكيد التلقائي:</span>
                                <span className="font-bold">
                                    {order.whatsapp_charge_amount > 0 
                                        ? `${order.whatsapp_charge_amount} ج.م (تم الخصم من المحفظة ✓)` 
                                        : '1 ج.م (تُخصم عند إرسال الرسالة بنجاح)'}
                                </span>
                            </div>
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

            {/* Image Preview Modal (Lightbox) */}
            {previewImage && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 animate-in fade-in duration-200"
                    onClick={() => setPreviewImage(null)}
                >
                    <div
                        className="relative max-w-2xl w-full bg-white rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between px-4 py-3 bg-gray-900 text-white">
                            <span className="font-bold text-sm truncate">{previewImage.title || 'معاينة الصورة'}</span>
                            <button
                                type="button"
                                onClick={() => setPreviewImage(null)}
                                className="p-1 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors cursor-pointer"
                                title="إغلاق"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div className="p-4 bg-gray-100 flex items-center justify-center overflow-auto min-h-[220px]">
                            <img
                                src={previewImage.url}
                                alt={previewImage.title || 'معاينة الصورة'}
                                className="max-w-full max-h-[65vh] object-contain rounded-lg shadow-sm"
                            />
                        </div>
                        <div className="px-4 py-3 bg-white border-t border-gray-150 flex items-center justify-between text-xs text-gray-500">
                            <a
                                href={previewImage.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-indigo-600 hover:text-indigo-700 font-bold inline-flex items-center gap-1 cursor-pointer"
                            >
                                <span>فتح بالحجم الأصلي</span>
                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                            <button
                                type="button"
                                onClick={() => setPreviewImage(null)}
                                className="px-4 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-bold transition-colors cursor-pointer"
                            >
                                إغلاق
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </MerchantLayout>
    );
}

