import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function EditOrderModal({
    isOpen,
    onClose,
    order,
    products = [],
    governorates = [],
}) {
    if (!isOpen || !order) return null;

    // بيانات العميل
    const [customerName, setCustomerName] = useState(order.customer_name || '');
    const [customerPhone, setCustomerPhone] = useState(order.customer_phone || '');
    const [customerEmail, setCustomerEmail] = useState(order.customer_email || '');
    const [notes, setNotes] = useState(order.notes || '');

    // بيانات الشحن
    const [governorate, setGovernorate] = useState(order.governorate || '');
    const [customerAddress, setCustomerAddress] = useState(order.customer_address || '');
    const [shippingCost, setShippingCost] = useState(Number(order.shipping_cost || 0));

    // عناصر السلة
    const [items, setItems] = useState(() => {
        const initialItems = Array.isArray(order.items) ? order.items : [];
        return initialItems.map((it) => ({
            id: it.id || null,
            name: it.name || 'منتج',
            price: Number(it.price || 0),
            quantity: Number(it.quantity || 1),
            image: it.image || it.image_url || null,
            image_url: it.image_url || it.image || null,
            selectedSize: it.selectedSize || null,
            selectedColor: it.selectedColor || null,
            piecesSelections: it.piecesSelections || null,
            options: it.options || null,
        }));
    });

    const [isProductPickerOpen, setIsProductPickerOpen] = useState(false);
    const [productSearch, setProductSearch] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');

    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                if (isProductPickerOpen) setIsProductPickerOpen(false);
                else onClose();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        document.body.style.overflow = 'hidden';
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
            document.body.style.overflow = '';
        };
    }, [isProductPickerOpen, onClose]);

    // حساب المجاميع
    const subtotal = items.reduce((acc, it) => acc + (Number(it.price || 0) * Number(it.quantity || 1)), 0);
    const discount = Number(order.discount || 0);
    const total = Math.max(0, subtotal + Number(shippingCost || 0) - discount);

    const updateItemQuantity = (index, val) => {
        const qty = Math.max(1, parseInt(val) || 1);
        setItems(prev => {
            const next = [...prev];
            next[index] = { ...next[index], quantity: qty };
            return next;
        });
    };

    const updateItemPrice = (index, val) => {
        const price = Math.max(0, parseFloat(val) || 0);
        setItems(prev => {
            const next = [...prev];
            next[index] = { ...next[index], price: price };
            return next;
        });
    };

    const removeItem = (index) => {
        if (items.length <= 1) {
            alert('يجب أن يحتوي الطلب على منتج واحد على الأقل.');
            return;
        }
        setItems(prev => prev.filter((_, idx) => idx !== index));
    };

    const handleAddProduct = (prod) => {
        const newItem = {
            id: prod.id,
            name: prod.name,
            price: Number(prod.price || 0),
            quantity: 1,
            image: prod.image,
            image_url: prod.image,
            selectedSize: null,
            selectedColor: null,
            piecesSelections: null,
            options: null,
        };
        setItems(prev => [...prev, newItem]);
        setIsProductPickerOpen(false);
        setProductSearch('');
    };

    const handleGovernorateChange = (govName) => {
        setGovernorate(govName);
        const match = governorates.find(g => g.name === govName);
        if (match && Number(match.price) > 0) {
            setShippingCost(Number(match.price));
        }
    };

    const handleSubmit = (e) => {
        if (e) e.preventDefault();
        setErrorMessage('');

        if (!customerName.trim()) {
            setErrorMessage('يرجى إدخال اسم العميل.');
            return;
        }
        if (!customerPhone.trim()) {
            setErrorMessage('يرجى إدخال رقم هاتف العميل.');
            return;
        }
        if (!customerAddress.trim()) {
            setErrorMessage('يرجى إدخال العنوان بالتفصيل.');
            return;
        }
        if (items.length === 0) {
            setErrorMessage('يجب أن يحتوي الطلب على منتج واحد على الأقل.');
            return;
        }

        setIsSubmitting(true);

        router.put(`/admin/orders/${order.id}`, {
            customer_name: customerName,
            customer_phone: customerPhone,
            customer_email: customerEmail || null,
            customer_address: customerAddress,
            governorate: governorate || null,
            shipping_cost: Number(shippingCost || 0),
            notes: notes || null,
            items: items.map(it => ({
                id: it.id,
                name: it.name,
                price: Number(it.price || 0),
                quantity: Number(it.quantity || 1),
                total: Number(it.price || 0) * Number(it.quantity || 1),
                image: it.image,
                image_url: it.image_url,
                selectedSize: it.selectedSize,
                selectedColor: it.selectedColor,
                piecesSelections: it.piecesSelections,
                options: it.options,
            })),
        }, {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
            },
            onError: (errs) => {
                const first = Object.values(errs)[0];
                setErrorMessage(first || 'حدث خطأ أثناء حفظ التعديلات.');
            },
            onFinish: () => setIsSubmitting(false),
        });
    };

    const filteredProducts = products.filter(p => 
        !productSearch || p.name.toLowerCase().includes(productSearch.toLowerCase())
    );

    return (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto animate-in fade-in duration-200">
            <div className="bg-white rounded-2xl shadow-2xl max-w-5xl w-full my-auto flex flex-col max-h-[92vh] overflow-hidden border border-gray-200">
                {/* Modal Header */}
                <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white sticky top-0 z-20">
                    <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-lg bg-orange-50 border border-orange-200 text-orange-600 flex items-center justify-center">
                            <svg className="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h2 className="text-lg font-bold text-gray-900">
                            تعديل الطلب #{order.reference_number}
                        </h2>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition"
                        title="إغلاق"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Error Banner */}
                {errorMessage && (
                    <div className="bg-red-50 border-r-4 border-red-500 px-6 py-3 text-red-700 text-xs font-bold flex items-center gap-2">
                        <span>⚠️</span>
                        <span>{errorMessage}</span>
                    </div>
                )}

                {/* Modal Body */}
                <div className="p-5 sm:p-6 overflow-y-auto space-y-6 flex-1 bg-slate-50/50">
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-5">
                        
                        {/* Right / Second Column in RTL: عناصر السلة وملخص الطلب (lg:col-span-7) */}
                        <div className="lg:col-span-7 space-y-5 order-1 lg:order-2">
                            {/* Card: عناصر السلة */}
                            <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/90 shadow-2xs space-y-4">
                                <div className="flex items-center justify-between pb-1 border-b border-gray-100">
                                    <h3 className="font-bold text-gray-900 text-sm flex items-center gap-2">
                                        <span className="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        <span>عناصر السلة</span>
                                        <span className="text-xs text-gray-400 font-normal">({items.length} منتجات)</span>
                                    </h3>

                                    <div className="relative">
                                        <button
                                            type="button"
                                            onClick={() => setIsProductPickerOpen(!isProductPickerOpen)}
                                            className="inline-flex items-center gap-1.5 px-3 py-1.5 border border-emerald-500 text-emerald-700 bg-emerald-50/70 hover:bg-emerald-100 rounded-xl text-xs font-bold transition cursor-pointer"
                                        >
                                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span>اختر المنتجات</span>
                                        </button>

                                        {/* Product Picker Dropdown */}
                                        {isProductPickerOpen && (
                                            <div className="absolute left-0 sm:right-auto sm:left-0 mt-2 w-72 sm:w-80 bg-white border border-gray-200 rounded-xl shadow-xl z-30 p-3 space-y-2">
                                                <div className="flex items-center justify-between pb-1 border-b border-gray-100">
                                                    <span className="text-xs font-bold text-gray-700">إضافة منتج من المتجر</span>
                                                    <button
                                                        type="button"
                                                        onClick={() => setIsProductPickerOpen(false)}
                                                        className="text-gray-400 hover:text-gray-600 text-xs"
                                                    >
                                                        ✕
                                                    </button>
                                                </div>
                                                <input
                                                    type="text"
                                                    placeholder="ابحث بالاسم..."
                                                    value={productSearch}
                                                    onChange={(e) => setProductSearch(e.target.value)}
                                                    className="w-full text-xs px-2.5 py-1.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                                                    autoFocus
                                                />
                                                <div className="max-h-48 overflow-y-auto space-y-1">
                                                    {filteredProducts.length > 0 ? (
                                                        filteredProducts.map((prod) => (
                                                            <button
                                                                key={prod.id}
                                                                type="button"
                                                                onClick={() => handleAddProduct(prod)}
                                                                className="w-full flex items-center gap-2 p-1.5 hover:bg-emerald-50 rounded-lg text-right transition cursor-pointer"
                                                            >
                                                                <img
                                                                    src={prod.image || 'https://dummyimage.com/60x60/f3f4f6/9ca3af&text=صورة'}
                                                                    alt={prod.name}
                                                                    className="w-8 h-8 rounded object-cover border border-gray-200 shrink-0"
                                                                />
                                                                <div className="min-w-0 flex-1">
                                                                    <p className="text-xs font-bold text-gray-800 truncate">{prod.name}</p>
                                                                    <p className="text-[11px] text-emerald-600 font-semibold">{prod.price} ج.م</p>
                                                                </div>
                                                                <span className="text-emerald-600 font-bold text-xs shrink-0">+ إضافة</span>
                                                            </button>
                                                        ))
                                                    ) : (
                                                        <div className="text-center py-4 text-xs text-gray-400">لا توجد منتجات مطابقة.</div>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Items List */}
                                <div className="space-y-3">
                                    {items.map((item, idx) => (
                                        <div
                                            key={idx}
                                            className="p-3 bg-slate-50/70 rounded-xl border border-gray-200/90 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 hover:border-gray-300 transition"
                                        >
                                            {/* Thumbnail & Info */}
                                            <div className="flex items-center gap-3 min-w-0 flex-1">
                                                <img
                                                    src={item.image_url || item.image || 'https://dummyimage.com/100x100/f3f4f6/9ca3af&text=صورة'}
                                                    alt={item.name}
                                                    className="w-12 h-12 rounded-lg object-cover border border-gray-200 shrink-0 bg-white shadow-2xs"
                                                />
                                                <div className="min-w-0">
                                                    <h4 className="font-bold text-gray-900 text-xs sm:text-sm line-clamp-1">{item.name}</h4>
                                                    {(item.selectedColor || item.selectedSize) && (
                                                        <div className="text-[11px] text-gray-500 flex flex-wrap gap-2 mt-0.5">
                                                            {item.selectedColor && <span>اللون: <strong className="text-gray-700">{item.selectedColor}</strong></span>}
                                                            {item.selectedSize && <span>| المقاس: <strong className="text-gray-700">{item.selectedSize}</strong></span>}
                                                        </div>
                                                    )}
                                                    {item.piecesSelections && item.piecesSelections.length > 0 && (
                                                        <div className="text-[10px] text-orange-700 bg-orange-50 px-2 py-0.5 rounded border border-orange-100 mt-1 inline-block font-bold">
                                                            باقة {item.piecesSelections.length} قطع
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Quantity × Price = Total + Trash */}
                                            <div className="flex items-center gap-2.5 self-end sm:self-center shrink-0">
                                                {/* Quantity */}
                                                <div className="flex flex-col items-center">
                                                    <span className="text-[10px] text-gray-400 font-bold mb-0.5">الكمية *</span>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        value={item.quantity}
                                                        onChange={(e) => updateItemQuantity(idx, e.target.value)}
                                                        className="w-14 text-center text-xs py-1.5 px-1 border border-gray-300 rounded-lg font-bold text-gray-800 focus:ring-1 focus:ring-orange-400 focus:outline-none bg-white"
                                                    />
                                                </div>

                                                <span className="text-gray-400 text-xs pt-3 font-bold">×</span>

                                                {/* Price */}
                                                <div className="flex flex-col items-center">
                                                    <span className="text-[10px] text-gray-400 font-bold mb-0.5">السعر *</span>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="any"
                                                        value={item.price}
                                                        onChange={(e) => updateItemPrice(idx, e.target.value)}
                                                        className="w-20 text-center text-xs py-1.5 px-1 border border-gray-300 rounded-lg font-bold text-gray-800 focus:ring-1 focus:ring-orange-400 focus:outline-none bg-white"
                                                    />
                                                </div>

                                                {/* Item Total */}
                                                <div className="text-left min-w-[70px] pt-3">
                                                    <span className="font-extrabold text-gray-900 text-xs sm:text-sm">
                                                        {Math.round(item.price * item.quantity)} ج.م
                                                    </span>
                                                </div>

                                                {/* Delete Button */}
                                                <button
                                                    type="button"
                                                    onClick={() => removeItem(idx)}
                                                    className="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition mt-3 cursor-pointer shrink-0"
                                                    title="حذف هذا المنتج"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Card: ملخص الطلب */}
                            <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/90 shadow-2xs space-y-3">
                                <h3 className="font-bold text-gray-900 text-sm flex items-center gap-2 pb-1 border-b border-gray-100">
                                    <span className="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                    <span>ملخص الطلب</span>
                                </h3>

                                <div className="space-y-2 text-xs">
                                    <div className="flex items-center justify-between text-gray-600">
                                        <span>وسيلة الدفع</span>
                                        <span className="font-bold text-gray-800 uppercase">{order.payment_method || 'cod'}</span>
                                    </div>

                                    <div className="flex items-center justify-between text-gray-600">
                                        <span>مجموع سعر المنتجات</span>
                                        <span className="font-bold text-gray-800">{Math.round(subtotal)} ج.م</span>
                                    </div>

                                    {discount > 0 && (
                                        <div className="flex items-center justify-between text-emerald-600 font-semibold">
                                            <span>الخصم / الكوبون</span>
                                            <span>-{Math.round(discount)} ج.م</span>
                                        </div>
                                    )}

                                    {/* Shipping Fee - Editable Input Box */}
                                    <div className="flex items-center justify-between py-2 border-y border-gray-100">
                                        <span className="font-semibold text-gray-700">رسوم التوصيل:</span>
                                        <div className="flex items-center gap-1.5">
                                            <input
                                                type="number"
                                                min="0"
                                                step="any"
                                                value={shippingCost}
                                                onChange={(e) => setShippingCost(e.target.value)}
                                                className="w-20 text-center text-xs py-1.5 px-2 border border-gray-300 rounded-lg font-bold text-gray-800 focus:ring-1 focus:ring-orange-400 focus:outline-none bg-white"
                                            />
                                            <span className="text-gray-500 font-bold">ج.م</span>
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between pt-1">
                                        <span className="text-sm font-extrabold text-gray-900">الإجمالي:</span>
                                        <span className="text-base sm:text-lg font-black text-indigo-700 font-mono">
                                            {Math.round(total)} ج.م
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Left / First Column in RTL: بيانات العميل والشحن (lg:col-span-5) */}
                        <div className="lg:col-span-5 space-y-5 order-2 lg:order-1">
                            {/* Card: بيانات العميل */}
                            <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/90 shadow-2xs space-y-3.5">
                                <h3 className="font-bold text-gray-900 text-sm flex items-center gap-2 pb-1 border-b border-gray-100">
                                    <span className="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                    <span>بيانات العميل</span>
                                </h3>

                                <div className="space-y-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 mb-1">اسم العميل *</label>
                                        <input
                                            type="text"
                                            value={customerName}
                                            onChange={(e) => setCustomerName(e.target.value)}
                                            className="w-full text-xs sm:text-sm px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-400 focus:outline-none font-medium"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 mb-1">بريد العميل</label>
                                        <input
                                            type="email"
                                            value={customerEmail}
                                            onChange={(e) => setCustomerEmail(e.target.value)}
                                            className="w-full text-xs sm:text-sm px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-400 focus:outline-none dir-ltr text-right font-medium"
                                            placeholder="example@mail.com"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 mb-1">رقم هاتف العميل *</label>
                                        <input
                                            type="tel"
                                            value={customerPhone}
                                            onChange={(e) => setCustomerPhone(e.target.value)}
                                            className="w-full text-xs sm:text-sm px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-400 focus:outline-none font-mono dir-ltr text-right font-bold"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 mb-1">ملاحظات الطلب</label>
                                        <textarea
                                            rows={2}
                                            value={notes}
                                            onChange={(e) => setNotes(e.target.value)}
                                            className="w-full text-xs sm:text-sm px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-400 focus:outline-none resize-none font-medium"
                                            placeholder="أي ملاحظات أو رقم بديل..."
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Card: بيانات الشحن */}
                            <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/90 shadow-2xs space-y-3.5">
                                <h3 className="font-bold text-gray-900 text-sm flex items-center gap-2 pb-1 border-b border-gray-100">
                                    <span className="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                    <span>بيانات الشحن</span>
                                </h3>

                                <div className="space-y-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 mb-1">الدولة</label>
                                        <input
                                            type="text"
                                            value="مصر"
                                            disabled
                                            className="w-full text-xs sm:text-sm px-3 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded-xl cursor-not-allowed font-medium"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 mb-1">المحافظة</label>
                                        <select
                                            value={governorate}
                                            onChange={(e) => handleGovernorateChange(e.target.value)}
                                            className="w-full text-xs sm:text-sm px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-400 focus:outline-none bg-white font-medium"
                                        >
                                            <option value="">اختر المحافظة</option>
                                            {governorates.map((gov) => (
                                                <option key={gov.id} value={gov.name}>
                                                    {gov.name} ({gov.price} ج.م)
                                                </option>
                                            ))}
                                            {governorate && !governorates.some(g => g.name === governorate) && (
                                                <option value={governorate}>{governorate}</option>
                                            )}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 mb-1">العنوان بالتفصيل *</label>
                                        <textarea
                                            rows={3}
                                            value={customerAddress}
                                            onChange={(e) => setCustomerAddress(e.target.value)}
                                            className="w-full text-xs sm:text-sm px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-400 focus:outline-none resize-none font-medium"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {/* Modal Footer */}
                <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-white sticky bottom-0 z-20">
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={isSubmitting}
                        className="px-5 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 transition cursor-pointer"
                    >
                        إلغاء
                    </button>

                    <button
                        type="button"
                        onClick={handleSubmit}
                        disabled={isSubmitting}
                        className="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm shadow-emerald-200 flex items-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        {isSubmitting ? (
                            <>
                                <span className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>جاري الحفظ...</span>
                            </>
                        ) : (
                            <span>حفظ</span>
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
}
