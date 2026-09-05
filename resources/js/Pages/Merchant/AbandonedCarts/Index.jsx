import React, { useState } from 'react';
import { Head, router, usePage, Link } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import Pagination from '@/Components/Pagination';

export default function AbandonedCartsIndex({ abandonedCarts, records, stats, statistics, filters, tenant }) {
    const { flash } = usePage().props;
    const cartsData = abandonedCarts || records || { data: [], links: [] };
    const currentStats = stats || statistics || {
        total_carts: 0,
        abandoned_count: 0,
        contacted_count: 0,
        converted_count: 0,
        lost_revenue: 0,
        recovered_revenue: 0,
        recovery_rate: 0,
    };

    const [search, setSearch] = useState(filters?.search || '');
    const [status, setStatus] = useState(filters?.status || '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from || '');
    const [dateTo, setDateTo] = useState(filters?.date_to || '');

    // Convert Modal State
    const [showConvertModal, setShowConvertModal] = useState(false);
    const [selectedCart, setSelectedCart] = useState(null);
    const [isConverting, setIsConverting] = useState(false);
    const [convertForm, setConvertForm] = useState({
        customer_name: '',
        customer_phone: '',
        customer_address: '',
        governorate: '',
        notes: '',
    });

    const handleSearch = (e) => {
        e?.preventDefault();
        router.get('/admin/abandoned-carts', {
            search,
            status,
            date_from: dateFrom,
            date_to: dateTo,
        }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setDateFrom('');
        setDateTo('');
        router.get('/admin/abandoned-carts', {}, { replace: true });
    };

    const handleStatusTab = (st) => {
        setStatus(st);
        router.get('/admin/abandoned-carts', {
            search,
            status: st,
            date_from: dateFrom,
            date_to: dateTo,
        }, { preserveState: true });
    };

    const handleDelete = (id) => {
        if (confirm('هل أنت متأكد من حذف هذه السلة المتروكة؟')) {
            router.delete(`/admin/abandoned-carts/${id}`, {
                preserveScroll: true,
            });
        }
    };

    // Open WhatsApp with pre-filled professional recovery message
    const handleWhatsAppRecovery = (cart) => {
        let phone = cart.phone ? cart.phone.replace(/[\s\+\-]/g, '') : '';
        if (!phone) {
            alert('لا يوجد رقم هاتف مسجل لهذه السلة.');
            return;
        }

        // Format to international format for Egypt (+20)
        if (phone.startsWith('01')) {
            phone = '2' + phone;
        } else if (phone.startsWith('00201')) {
            phone = phone.substring(2);
        } else if (!phone.startsWith('20') && phone.length === 10 && phone.startsWith('1')) {
            phone = '20' + phone;
        }

        const storeName = tenant?.name || 'متجرنا';
        const customerName = cart.customer_name ? `أستاذ/ة ${cart.customer_name}` : 'يا فندم';
        const items = cart.cart_data?.items || [];
        const itemsText = items.map(i => `${i.name} (${i.qty || i.quantity || 1} قطعة)`).join(' و ');
        const recoveryUrl = cart.recovery_token ? `${window.location.origin}/shop/cart/recover/${cart.recovery_token}` : '';

        const message = `أهلاً بك ${customerName}، مع حضرتك خدمة عملاء متجر ${storeName} 🌸\nلاحظنا إنك بدأت طلب ${itemsText ? `[${itemsText}]` : 'من متجرنا'} ووقفت قبل تأكيد الطلب.\nهل واجهتك أي مشكلة أثناء الطلب أو حابب نساعدك في تأكيد شحنتك وتوصيلها؟${recoveryUrl ? `\n\nتقدر تكمل طلبك وتأكده مباشرة بضغطة واحدة من هنا:\n${recoveryUrl}` : ''}`;

        const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
        window.open(waUrl, '_blank');

        // Automatically mark as contacted
        if (cart.status !== 'converted') {
            router.post(`/admin/abandoned-carts/${cart.id}/mark-contacted`, {}, {
                preserveScroll: true,
            });
        }
    };

    // Open Convert Modal
    const openConvertModal = (cart) => {
        setSelectedCart(cart);
        setConvertForm({
            customer_name: cart.customer_name || '',
            customer_phone: cart.phone || '',
            customer_address: cart.customer_address || cart.cart_data?.address || '',
            governorate: cart.governorate || cart.cart_data?.governorate || 'القاهرة',
            notes: '',
        });
        setShowConvertModal(true);
    };

    const handleConfirmConvert = (e) => {
        e?.preventDefault();
        if (!selectedCart) return;

        setIsConverting(true);
        router.post(`/admin/abandoned-carts/${selectedCart.id}/convert`, convertForm, {
            preserveScroll: true,
            onFinish: () => {
                setIsConverting(false);
                setShowConvertModal(false);
                setSelectedCart(null);
            }
        });
    };

    const copyRecoveryLink = (token) => {
        const url = `${window.location.origin}/shop/cart/recover/${token}`;
        navigator.clipboard.writeText(url);
        alert('تم نسخ رابط استعادة السلة السحري بنجاح!');
    };

    const formatCurrency = (val) => {
        return `${Math.round(val || 0).toLocaleString()} ج.م`;
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('ar-EG', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getStatusBadge = (cart) => {
        if (cart.status === 'converted' || cart.recovered_at) {
            return (
                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span>✅</span>
                    <span>تم التحويل لأوردر</span>
                </span>
            );
        }
        if (cart.status === 'contacted') {
            return (
                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    <span>💬</span>
                    <span>تم التواصل</span>
                </span>
            );
        }
        return (
            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                <span>⏳</span>
                <span>سلة متروكة</span>
            </span>
        );
    };

    return (
        <MerchantLayout title="السلات المتروكة واسترجاع المبيعات">
            <Head title="السلات المتروكة - لوحة التاجر" />

            <div className="space-y-6 text-right" dir="rtl">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 className="text-2xl font-black text-gray-900 flex items-center gap-2">
                            <span>🛒</span>
                            <span>السلات المتروكة (Abandoned Carts)</span>
                        </h2>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            العملاء الذين سجلوا أرقام هواتفهم أو ملأوا سلة الشراء ولم يؤكدوا الطلب. تواصل معهم واسترجع مبيعاتك بنقرة واحدة!
                        </p>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border-r-4 border-emerald-500 rounded-xl text-emerald-800 text-xs sm:text-sm font-bold flex items-center gap-2 shadow-sm animate-fade-in">
                        <span>✓</span>
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-rose-50 border-r-4 border-rose-500 rounded-xl text-rose-800 text-xs sm:text-sm font-bold flex items-center gap-2 shadow-sm animate-fade-in">
                        <span>⚠️</span>
                        <span>{flash.error}</span>
                    </div>
                )}

                {/* Statistics Cards */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                        <span className="text-[11px] text-gray-500 font-bold block">إجمالي السلات</span>
                        <span className="text-xl font-black text-gray-900 mt-1 block">{currentStats.total_carts ?? 0}</span>
                        <span className="text-[10px] text-gray-400 mt-1 block">كل المسجلات</span>
                    </div>

                    <div className="bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/30 shadow-sm">
                        <span className="text-[11px] text-amber-700 font-bold block">متروكة بانتظار التواصل</span>
                        <span className="text-xl font-black text-amber-700 mt-1 block">{currentStats.abandoned_count ?? 0}</span>
                        <span className="text-[10px] text-amber-600 mt-1 block">لم يتم التواصل بعد</span>
                    </div>

                    <div className="bg-white p-4 rounded-xl border border-blue-200 bg-blue-50/30 shadow-sm">
                        <span className="text-[11px] text-blue-700 font-bold block">تم التواصل معهم</span>
                        <span className="text-xl font-black text-blue-700 mt-1 block">{currentStats.contacted_count ?? 0}</span>
                        <span className="text-[10px] text-blue-600 mt-1 block">عبر الواتساب / الهاتف</span>
                    </div>

                    <div className="bg-white p-4 rounded-xl border border-emerald-200 bg-emerald-50/30 shadow-sm">
                        <span className="text-[11px] text-emerald-700 font-bold block">سلات تم استرجاعها</span>
                        <span className="text-xl font-black text-emerald-700 mt-1 block">{currentStats.converted_count ?? 0}</span>
                        <span className="text-[10px] text-emerald-600 font-bold mt-1 block">نسبة التحويل: {currentStats.recovery_rate ?? 0}%</span>
                    </div>

                    <div className="bg-white p-4 rounded-xl border border-rose-200 bg-rose-50/30 shadow-sm">
                        <span className="text-[11px] text-rose-700 font-bold block">مبيعات مفقودة حالياً</span>
                        <span className="text-lg font-black text-rose-700 mt-1 block">{formatCurrency(currentStats.lost_revenue)}</span>
                        <span className="text-[10px] text-rose-500 mt-1 block">فرصة للإنقاذ</span>
                    </div>

                    <div className="bg-white p-4 rounded-xl border border-teal-200 bg-teal-50/30 shadow-sm">
                        <span className="text-[11px] text-teal-700 font-bold block">مبيعات مستردة فعلية</span>
                        <span className="text-lg font-black text-teal-700 mt-1 block">{formatCurrency(currentStats.recovered_revenue)}</span>
                        <span className="text-[10px] text-teal-600 font-bold mt-1 block">أرباح تم إنقاذها 🎉</span>
                    </div>
                </div>

                {/* Quick Filter Tabs */}
                <div className="flex items-center gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        onClick={() => handleStatusTab('')}
                        className={`px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all border ${
                            status === '' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                        }`}
                    >
                        كل السلات ({currentStats.total_carts ?? 0})
                    </button>
                    <button
                        type="button"
                        onClick={() => handleStatusTab('abandoned')}
                        className={`px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all border ${
                            status === 'abandoned' ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                        }`}
                    >
                        ⏳ لم يتم التواصل ({currentStats.abandoned_count ?? 0})
                    </button>
                    <button
                        type="button"
                        onClick={() => handleStatusTab('contacted')}
                        className={`px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all border ${
                            status === 'contacted' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                        }`}
                    >
                        💬 تم التواصل ({currentStats.contacted_count ?? 0})
                    </button>
                    <button
                        type="button"
                        onClick={() => handleStatusTab('converted')}
                        className={`px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all border ${
                            status === 'converted' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                        }`}
                    >
                        ✅ تم الاسترجاع والتحويل ({currentStats.converted_count ?? 0})
                    </button>
                </div>

                {/* Search & Date Filters */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div className="sm:col-span-2">
                            <input
                                type="text"
                                placeholder="بحث برقم الهاتف، اسم العميل، المحافظة..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="من تاريخ"
                            />
                        </div>
                        <div className="flex gap-2">
                            <input
                                type="date"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="إلى تاريخ"
                            />
                            <button
                                type="submit"
                                className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1"
                            >
                                <span>🔍</span>
                                <span>بحث</span>
                            </button>
                            <button
                                type="button"
                                onClick={handleReset}
                                className="px-3 py-2 border border-gray-300 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-50 transition-colors"
                            >
                                إعادة تعيين
                            </button>
                        </div>
                    </form>
                </div>

                {/* Table list */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    {/* Desktop View */}
                    <div className="hidden md:block overflow-x-auto">
                        <table className="w-full text-right border-collapse">
                            <thead>
                                <tr className="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500">
                                    <th className="px-5 py-3.5">#</th>
                                    <th className="px-5 py-3.5">العميل والهاتف</th>
                                    <th className="px-5 py-3.5">المحافظة والعنوان</th>
                                    <th className="px-5 py-3.5">محتويات السلة</th>
                                    <th className="px-5 py-3.5">الإجمالي</th>
                                    <th className="px-5 py-3.5">الحالة</th>
                                    <th className="px-5 py-3.5">التاريخ والوقت</th>
                                    <th className="px-5 py-3.5 text-left">إجراءات الاسترجاع السريعة</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 text-sm text-gray-700">
                                {cartsData.data.length > 0 ? (
                                    cartsData.data.map((cart, idx) => {
                                        const items = cart.cart_data?.items || [];
                                        const isConverted = cart.status === 'converted' || !!cart.recovered_at;

                                        return (
                                            <tr key={cart.id} className="hover:bg-gray-50/80 transition-colors">
                                                <td className="px-5 py-4 font-semibold text-gray-400">
                                                    {(cartsData.current_page - 1) * cartsData.per_page + idx + 1}
                                                </td>

                                                {/* Customer */}
                                                <td className="px-5 py-4">
                                                    <div className="font-bold text-gray-900">
                                                        {cart.customer_name || 'عميل (بدون اسم)'}
                                                    </div>
                                                    <div className="text-xs text-indigo-600 font-mono font-bold mt-0.5" dir="ltr">
                                                        {cart.phone || '—'}
                                                    </div>
                                                    {cart.email && (
                                                        <div className="text-[11px] text-gray-400 font-mono">
                                                            {cart.email}
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Governorate & Address */}
                                                <td className="px-5 py-4">
                                                    <div className="font-semibold text-gray-800 text-xs">
                                                        {cart.governorate || cart.cart_data?.governorate || 'غير محددة'}
                                                    </div>
                                                    <div className="text-[11px] text-gray-500 max-w-[180px] truncate" title={cart.customer_address || cart.cart_data?.address}>
                                                        {cart.customer_address || cart.cart_data?.address || '—'}
                                                    </div>
                                                </td>

                                                {/* Cart Items */}
                                                <td className="px-5 py-4">
                                                    {items.length > 0 ? (
                                                        <div className="space-y-1 max-w-[220px]">
                                                            {items.slice(0, 2).map((item, iIdx) => (
                                                                <div key={iIdx} className="text-xs text-gray-800 flex items-center gap-1.5">
                                                                    <span className="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                                                                    <span className="font-medium truncate">{item.name}</span>
                                                                    <span className="text-[10px] text-gray-500 font-bold shrink-0">×{item.qty || item.quantity || 1}</span>
                                                                </div>
                                                            ))}
                                                            {items.length > 2 && (
                                                                <div className="text-[10px] text-indigo-600 font-bold">
                                                                    +{items.length - 2} منتجات أخرى
                                                                </div>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-gray-400">سلة فارغة</span>
                                                    )}
                                                </td>

                                                {/* Total */}
                                                <td className="px-5 py-4 font-black text-indigo-600 whitespace-nowrap">
                                                    {formatCurrency(cart.total || cart.subtotal)}
                                                </td>

                                                {/* Status */}
                                                <td className="px-5 py-4">
                                                    {getStatusBadge(cart)}
                                                    {isConverted && cart.order && (
                                                        <div className="mt-1">
                                                            <Link
                                                                href={`/admin/orders/${cart.order.id}`}
                                                                className="text-[11px] text-indigo-600 hover:text-indigo-900 font-mono font-bold underline"
                                                            >
                                                                #{cart.order.reference_number}
                                                            </Link>
                                                        </div>
                                                    )}
                                                    {cart.last_contacted_at && !isConverted && (
                                                        <div className="text-[9px] text-gray-400 mt-0.5">
                                                            تواصل: {new Date(cart.last_contacted_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' })}
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Date */}
                                                <td className="px-5 py-4 text-xs text-gray-500 whitespace-nowrap">
                                                    {formatDate(cart.updated_at || cart.created_at)}
                                                </td>

                                                {/* Actions */}
                                                <td className="px-5 py-4 text-left">
                                                    <div className="flex items-center justify-end gap-1.5 flex-wrap">
                                                        {/* WhatsApp Button */}
                                                        {cart.phone && (
                                                            <button
                                                                type="button"
                                                                onClick={() => handleWhatsAppRecovery(cart)}
                                                                className="p-2 bg-[#25D366] hover:bg-[#1ebd59] text-white rounded-lg transition-all shadow-sm flex items-center justify-center hover:scale-105"
                                                                title="مراسلة واتساب"
                                                            >
                                                                <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                                                </svg>
                                                            </button>
                                                        )}

                                                        {/* Phone Call Button */}
                                                        {cart.phone && (
                                                            <a
                                                                href={`tel:${cart.phone}`}
                                                                className="p-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-bold transition-colors border border-blue-200 flex items-center"
                                                                title="اتصال هاتفي"
                                                            >
                                                                <span>📞</span>
                                                            </a>
                                                        )}

                                                        {/* Convert to Order Button */}
                                                        {!isConverted && items.length > 0 && (
                                                            <button
                                                                type="button"
                                                                onClick={() => openConvertModal(cart)}
                                                                className="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1"
                                                                title="تحويل السلة لطلب رسمي مؤكد"
                                                            >
                                                                <span>🔄</span>
                                                                <span>تحويل لطلب</span>
                                                            </button>
                                                        )}

                                                        {/* Copy Recovery Link */}
                                                        {cart.recovery_token && (
                                                            <button
                                                                type="button"
                                                                onClick={() => copyRecoveryLink(cart.recovery_token)}
                                                                className="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs transition-colors"
                                                                title="نسخ رابط استعادة السلة السحري"
                                                            >
                                                                <span>🔗</span>
                                                            </button>
                                                        )}

                                                        {/* Delete */}
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDelete(cart.id)}
                                                            className="p-2 text-rose-500 hover:bg-rose-50 rounded-lg text-xs transition-colors"
                                                            title="حذف السلة"
                                                        >
                                                            <span>🗑️</span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td colSpan="8" className="px-6 py-12 text-center text-gray-400">
                                            <div className="text-4xl mb-2">🛒</div>
                                            <p className="font-bold text-gray-600">لا توجد سلات متروكة مطابقة للبحث أو الفلترة.</p>
                                            <p className="text-xs text-gray-400 mt-1">عندما يبدأ أي عميل في كتابة بياناته في متجرك، ستظهر هنا فوراً!</p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile View: Cards */}
                    <div className="md:hidden divide-y divide-gray-100">
                        {cartsData.data.length > 0 ? (
                            cartsData.data.map((cart) => {
                                const items = cart.cart_data?.items || [];
                                const isConverted = cart.status === 'converted' || !!cart.recovered_at;

                                return (
                                    <div key={cart.id} className="p-4 bg-white space-y-3">
                                        <div className="flex justify-between items-center gap-2">
                                            <div className="font-bold text-gray-900 text-sm">
                                                {cart.customer_name || 'عميل (بدون اسم)'}
                                            </div>
                                            <div>{getStatusBadge(cart)}</div>
                                        </div>

                                        <div className="flex justify-between items-center text-xs text-gray-600">
                                            <span className="font-mono font-bold text-indigo-600" dir="ltr">{cart.phone || '—'}</span>
                                            <span>{cart.governorate || cart.cart_data?.governorate || 'غير محددة'}</span>
                                        </div>

                                        {items.length > 0 && (
                                            <div className="p-2.5 bg-gray-50 rounded-lg text-xs space-y-1">
                                                {items.map((it, idx) => (
                                                    <div key={idx} className="flex justify-between text-gray-700">
                                                        <span className="truncate max-w-[200px]">{it.name}</span>
                                                        <span className="font-bold shrink-0">×{it.qty || it.quantity || 1}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        )}

                                        <div className="flex justify-between items-center pt-1 border-t border-gray-100 text-xs">
                                            <span className="text-gray-400 font-mono">{formatDate(cart.updated_at || cart.created_at)}</span>
                                            <span className="text-sm font-black text-indigo-600">{formatCurrency(cart.total || cart.subtotal)}</span>
                                        </div>

                                        {isConverted && cart.order && (
                                            <div className="p-2 bg-emerald-50 rounded-lg text-xs font-bold text-emerald-800 flex justify-between items-center">
                                                <span>تم تحويلها لطلب رقم:</span>
                                                <Link href={`/admin/orders/${cart.order.id}`} className="font-mono underline">
                                                    #{cart.order.reference_number}
                                                </Link>
                                            </div>
                                        )}

                                        {/* Mobile Action Buttons */}
                                        <div className="flex items-center gap-2 pt-2">
                                            {cart.phone && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleWhatsAppRecovery(cart)}
                                                    className="py-2 px-3 bg-[#25D366] hover:bg-[#1ebd59] text-white rounded-lg shadow-sm flex items-center justify-center transition-all"
                                                    title="مراسلة واتساب"
                                                >
                                                    <svg className="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                                    </svg>
                                                </button>
                                            )}
                                            {cart.phone && (
                                                <a
                                                    href={`tel:${cart.phone}`}
                                                    className="py-2 px-3 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-xs font-bold flex items-center justify-center"
                                                >
                                                    📞
                                                </a>
                                            )}
                                            {!isConverted && items.length > 0 && (
                                                <button
                                                    type="button"
                                                    onClick={() => openConvertModal(cart)}
                                                    className="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-center font-bold text-xs rounded-lg shadow-sm flex items-center justify-center gap-1"
                                                >
                                                    <span>🔄</span>
                                                    <span>تحويل لطلب</span>
                                                </button>
                                            )}
                                            <button
                                                type="button"
                                                onClick={() => handleDelete(cart.id)}
                                                className="py-2 px-3 text-rose-500 hover:bg-rose-50 rounded-lg text-xs"
                                                title="حذف"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </div>
                                );
                            })
                        ) : (
                            <div className="p-8 text-center text-gray-400 text-sm">
                                لا توجد سلات متروكة مطابقة للبحث أو الفلترة.
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    <Pagination links={cartsData.links} />
                </div>
            </div>

            {/* Modal: تحويل السلة المتروكة إلى طلب رسمي */}
            {showConvertModal && selectedCart && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in text-right" dir="rtl">
                    <div className="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden p-6 space-y-5">
                        <div className="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div className="flex items-center gap-2.5 text-indigo-600">
                                <span className="text-2xl">🔄</span>
                                <div>
                                    <h3 className="font-extrabold text-base text-gray-900">تحويل السلة إلى طلب مؤكد</h3>
                                    <span className="text-xs text-gray-500">سيتم إنشاء طلب رسمي في جدول الطلبات وخصم المخزون</span>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={() => setShowConvertModal(false)}
                                className="text-gray-400 hover:text-gray-600 text-lg p-1"
                            >
                                ✕
                            </button>
                        </div>

                        {/* Order Items Preview */}
                        <div className="p-3 bg-gray-50 rounded-xl border border-gray-200 space-y-2">
                            <span className="text-xs font-bold text-gray-700 block">محتويات السلة:</span>
                            {(selectedCart.cart_data?.items || []).map((it, idx) => (
                                <div key={idx} className="flex justify-between items-center text-xs text-gray-800">
                                    <div className="flex items-center gap-2">
                                        <span className="font-semibold">{it.name}</span>
                                        {it.selectedSize && <span className="text-[10px] bg-gray-200 px-1.5 py-0.5 rounded">مقاس: {it.selectedSize}</span>}
                                        {it.selectedColor && <span className="text-[10px] bg-gray-200 px-1.5 py-0.5 rounded">لون: {it.selectedColor}</span>}
                                    </div>
                                    <span className="font-bold text-indigo-600">
                                        {it.qty || it.quantity || 1} × {formatCurrency(it.price)}
                                    </span>
                                </div>
                            ))}
                            <div className="pt-2 border-t border-gray-200 flex justify-between items-center text-xs font-black text-gray-900">
                                <span>إجمالي المنتجات:</span>
                                <span>{formatCurrency(selectedCart.subtotal || selectedCart.total)}</span>
                            </div>
                        </div>

                        {/* Customer Form */}
                        <form onSubmit={handleConfirmConvert} className="space-y-3 text-xs">
                            <div>
                                <label className="block font-bold text-gray-700 mb-1">اسم العميل:</label>
                                <input
                                    type="text"
                                    value={convertForm.customer_name}
                                    onChange={(e) => setConvertForm({ ...convertForm, customer_name: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    placeholder="اسم العميل"
                                    required
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block font-bold text-gray-700 mb-1">رقم الهاتف:</label>
                                    <input
                                        type="tel"
                                        value={convertForm.customer_phone}
                                        onChange={(e) => setConvertForm({ ...convertForm, customer_phone: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono"
                                        placeholder="01000000000"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block font-bold text-gray-700 mb-1">المحافظة:</label>
                                    <input
                                        type="text"
                                        value={convertForm.governorate}
                                        onChange={(e) => setConvertForm({ ...convertForm, governorate: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        placeholder="القاهرة، الجيزة..."
                                        required
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block font-bold text-gray-700 mb-1">العنوان التفصيلي:</label>
                                <input
                                    type="text"
                                    value={convertForm.customer_address}
                                    onChange={(e) => setConvertForm({ ...convertForm, customer_address: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    placeholder="الشارع، رقم العمارة، الشقة..."
                                    required
                                />
                            </div>

                            <div>
                                <label className="block font-bold text-gray-700 mb-1">ملاحظات إضافية على الطلب:</label>
                                <textarea
                                    value={convertForm.notes}
                                    onChange={(e) => setConvertForm({ ...convertForm, notes: e.target.value })}
                                    rows="2"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    placeholder="أي اتفاق خاص مع العميل أو تفاصيل التوصيل..."
                                ></textarea>
                            </div>

                            <div className="flex items-center gap-2 pt-3">
                                <button
                                    type="submit"
                                    disabled={isConverting}
                                    className="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-center font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5"
                                >
                                    {isConverting ? (
                                        <>
                                            <span className="animate-spin text-sm">⏳</span>
                                            <span>جاري إنشاء الطلب...</span>
                                        </>
                                    ) : (
                                        <>
                                            <span>🚀</span>
                                            <span>تأكيد وإنشاء الأوردر الرسمي</span>
                                        </>
                                    )}
                                </button>
                                <button
                                    type="button"
                                    disabled={isConverting}
                                    onClick={() => setShowConvertModal(false)}
                                    className="py-2.5 px-4 bg-gray-100 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-200 transition-colors"
                                >
                                    إلغاء
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </MerchantLayout>
    );
}
