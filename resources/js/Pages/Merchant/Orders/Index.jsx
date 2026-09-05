import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import Pagination from '@/Components/Pagination';

export default function OrdersIndex({ orders, totalAmount, statusCounts, productsList, activeShippingGateways = [], wallet_balance, isSubscriptionExpired, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search || '');
    const [status, setStatus] = useState(filters?.status || '');
    const [quickFilter, setQuickFilter] = useState(filters?.quick_filter || '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from || '');
    const [dateTo, setDateTo] = useState(filters?.date_to || '');
    const [productId, setProductId] = useState(filters?.product_id || '');
    const [showInsufficientModal, setShowInsufficientModal] = useState(false);

    // Selection & Bulk Action States
    const [selectedOrders, setSelectedOrders] = useState([]);
    const [showShippingModal, setShowShippingModal] = useState(false);
    const [selectedShippingProvider, setSelectedShippingProvider] = useState(
        activeShippingGateways?.[0]?.provider || ''
    );
    const [showStatusModal, setShowStatusModal] = useState(false);
    const [selectedBulkStatus, setSelectedBulkStatus] = useState('confirmed');
    const [isProcessingBulk, setIsProcessingBulk] = useState(false);

    useEffect(() => {
        if (flash?.insufficient_balance) {
            setShowInsufficientModal(true);
        }
    }, [flash]);

    useEffect(() => {
        if (!selectedShippingProvider && activeShippingGateways?.length > 0) {
            setSelectedShippingProvider(activeShippingGateways[0].provider);
        }
    }, [activeShippingGateways]);

    const handleSearch = (e) => {
        e?.preventDefault();
        router.get('/admin/orders', {
            search,
            status,
            quick_filter: quickFilter,
            date_from: dateFrom,
            date_to: dateTo,
            product_id: productId
        }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setQuickFilter('');
        setDateFrom('');
        setDateTo('');
        setProductId('');
        router.get('/admin/orders', {}, { replace: true });
    };

    const handleQuickFilter = (qfKey) => {
        const nextQf = quickFilter === qfKey ? '' : qfKey;
        setQuickFilter(nextQf);
        setStatus('');
        router.get('/admin/orders', {
            search,
            quick_filter: nextQf,
            date_from: dateFrom,
            date_to: dateTo,
            product_id: productId
        }, { preserveState: true });
    };

    const handleStatusFilter = (stKey) => {
        setStatus(stKey);
        setQuickFilter('');
        router.get('/admin/orders', {
            search,
            status: stKey,
            date_from: dateFrom,
            date_to: dateTo,
            product_id: productId
        }, { preserveState: true });
    };

    // Selection Logic
    const isAllVisibleSelected = orders.data.length > 0 && orders.data.every(o => selectedOrders.includes(o.id));

    const toggleSelectAll = () => {
        if (isAllVisibleSelected) {
            const visibleIds = orders.data.map(o => o.id);
            setSelectedOrders(selectedOrders.filter(id => !visibleIds.includes(id)));
        } else {
            const visibleIds = orders.data.map(o => o.id);
            setSelectedOrders(Array.from(new Set([...selectedOrders, ...visibleIds])));
        }
    };

    const toggleSelectOrder = (id) => {
        if (selectedOrders.includes(id)) {
            setSelectedOrders(selectedOrders.filter(item => item !== id));
        } else {
            setSelectedOrders([...selectedOrders, id]);
        }
    };

    // Bulk Handlers
    const handleBulkPrint = () => {
        if (selectedOrders.length === 0) return;
        window.open(`/admin/orders/bulk-invoice?ids=${selectedOrders.join(',')}`, '_blank');
        router.post('/admin/orders/bulk-print', { order_ids: selectedOrders }, {
            preserveScroll: true,
        });
    };

    const handleBulkExport = () => {
        if (selectedOrders.length === 0) return;
        window.location.href = `/admin/orders/export?format=excel&ids=${selectedOrders.join(',')}`;
    };

    const handleConfirmBulkShip = () => {
        if (selectedOrders.length === 0) return;
        setIsProcessingBulk(true);
        router.post('/admin/orders/bulk-ship', {
            order_ids: selectedOrders,
            provider: selectedShippingProvider,
        }, {
            preserveScroll: true,
            onFinish: () => {
                setIsProcessingBulk(false);
                setShowShippingModal(false);
                setSelectedOrders([]);
            }
        });
    };

    const handleConfirmBulkStatus = () => {
        if (selectedOrders.length === 0) return;
        setIsProcessingBulk(true);
        router.post('/admin/orders/bulk-status', {
            order_ids: selectedOrders,
            status: selectedBulkStatus,
        }, {
            preserveScroll: true,
            onFinish: () => {
                setIsProcessingBulk(false);
                setShowStatusModal(false);
                setSelectedOrders([]);
            }
        });
    };

    const handleOrderView = (order) => {
        if (isSubscriptionExpired) {
            return;
        }
        if (order.is_unlocked || wallet_balance >= 2) {
            router.get(`/admin/orders/${order.id}`);
        } else {
            setShowInsufficientModal(true);
        }
    };

    const statusConfig = {
        pending:   { text: 'في الانتظار', color: 'bg-yellow-50 text-yellow-700 border-yellow-100' },
        confirmed: { text: 'مؤكد', color: 'bg-blue-50 text-blue-700 border-blue-100' },
        shipped:   { text: 'مع شركة الشحن', color: 'bg-purple-50 text-purple-700 border-purple-100' },
        delivered: { text: 'تم التسليم', color: 'bg-green-50 text-green-700 border-green-100' },
        cancelled: { text: 'ملغي', color: 'bg-red-50 text-red-700 border-red-100' },
    };

    const getStatusBadge = (statusKey) => {
        const conf = statusConfig[statusKey] || { text: statusKey, color: 'bg-gray-50 text-gray-700 border-gray-100' };
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ${conf.color}`}>
                {conf.text}
            </span>
        );
    };

    const getPrintBadge = (order) => {
        if (order.is_printed) {
            return (
                <div className="flex flex-col items-start gap-0.5">
                    <span
                        className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                        title={order.printed_at ? `وقت الطباعة: ${formatDate(order.printed_at)}` : 'تمت الطباعة'}
                    >
                        <span>🖨️</span>
                        <span>تمت الطباعة</span>
                    </span>
                    {order.printed_at && (
                        <span className="text-[9px] text-gray-400 font-mono" dir="ltr">
                            {new Date(order.printed_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                        </span>
                    )}
                </div>
            );
        }
        return (
            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-gray-100 text-gray-500 border border-gray-200">
                <span>⚪</span>
                <span>غير مطبوع</span>
            </span>
        );
    };

    const getShippingBadge = (order) => {
        if (order.shipment) {
            const providerTitle = order.shipment.provider === 'jnt'
                ? 'J&T'
                : (order.shipment.provider === 'bosta' ? 'بوسطة' : (order.shipment.provider === 'aramex' ? 'أرامكس' : order.shipment.provider));

            return (
                <div className="flex flex-col items-start gap-0.5">
                    <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                        <span>🚚</span>
                        <span>{providerTitle}:</span>
                        {order.shipment.airway_bill_url ? (
                            <a
                                href={order.shipment.airway_bill_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="font-mono text-indigo-600 hover:text-indigo-900 underline font-extrabold"
                                title="عرض بوليصة وتتبع الشحنة"
                            >
                                {order.shipment.tracking_number}
                            </a>
                        ) : (
                            <span className="font-mono">{order.shipment.tracking_number}</span>
                        )}
                    </div>
                </div>
            );
        }

        return (
            <span className="text-[10px] text-gray-400 font-medium">لم يُشحن</span>
        );
    };

    const getPaymentBadge = (order) => {
        const method = order.payment_method || 'cod';
        const isPaid = order.payment_status === 'paid';

        if (isPaid) {
            return (
                <span
                    className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200"
                    title={order.transaction_id ? `رقم المعاملة: ${order.transaction_id}` : 'مدفوع إلكترونياً'}
                >
                    <span>💳</span>
                    <span>
                        {method === 'paymob' ? 'مدفوع Paymob' : (method === 'kashier' ? 'مدفوع Kashier' : (method === 'fawry' ? 'مدفوع Fawry' : 'مدفوع أونلاين'))}
                    </span>
                    <span className="text-[9px] bg-emerald-600 text-white px-1 rounded">✓</span>
                </span>
            );
        }

        if (method === 'paymob' || method === 'kashier' || method === 'fawry') {
            return (
                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    <span>💳</span>
                    <span>بانتظار السداد ({method})</span>
                </span>
            );
        }

        return (
            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 text-gray-700">
                <span>💵</span>
                <span>عند الاستلام</span>
            </span>
        );
    };

    const formatCurrency = (amount) => {
        return Math.round(Number(amount)).toLocaleString('en-US') + ' ج.م';
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '';
        const cleanStr = dateStr.includes(' ') && !dateStr.includes('T')
            ? dateStr.replace(' ', 'T')
            : dateStr;
        return new Date(cleanStr).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    };

    return (
        <MerchantLayout title="إدارة الطلبات">
            <Head title="الطلبات" />

            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border-r-4 border-emerald-500 rounded-xl text-emerald-900 text-sm font-bold animate-fade-in flex items-center justify-between">
                        <span>{flash.success}</span>
                        <span className="text-xs text-emerald-700 font-mono">رصيدك الحالي: {Math.round(wallet_balance)} ج.م</span>
                    </div>
                )}

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">الطلبات</h2>
                        <p className="text-sm text-gray-500 mt-0.5">
                            تجاوز عدد الطلبات: <span className="font-bold text-indigo-600">{statusCounts.total} طلب</span> |
                            رسوم الطلب (2 ج.م) تُخصم تلقائياً مع كل طلب جديد.
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="hidden md:flex flex-wrap items-center gap-2">
                            <a
                                href={`/admin/orders/export?format=excel&search=${search}&status=${status}&date_from=${dateFrom}&date_to=${dateTo}&product_id=${productId}`}
                                className="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-bold hover:bg-emerald-700 transition-colors shadow-sm"
                                title="تصدير الأوردرات كـ Excel"
                            >
                                📥 excel الأوردرات
                            </a>
                            <a
                                href={`/admin/orders/export?format=pdf&search=${search}&status=${status}&date_from=${dateFrom}&date_to=${dateTo}&product_id=${productId}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition-colors shadow-sm"
                                title="طباعة الأوردرات"
                            >
                                🖨️ pdf / طباعة
                            </a>
                        </div>
                    </div>
                </div>

                {/* Quick Filter Tabs */}
                <div className="bg-white p-3.5 rounded-2xl border border-gray-200 shadow-sm">
                    <div className="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
                        <span className="font-bold text-gray-400 pl-2 shrink-0">فلاتر سريعة:</span>
                        {[
                            { key: 'confirmed_unprinted', label: 'مؤكد وغير مطبوع', count: statusCounts.confirmed_unprinted ?? 0, icon: '🖨️', color: 'border-amber-400 text-amber-800 bg-amber-50' },
                            { key: 'ready_to_ship', label: 'جاهز للشحن (مؤكد)', count: statusCounts.ready_to_ship ?? 0, icon: '🚀', color: 'border-blue-400 text-blue-800 bg-blue-50' },
                            { key: 'sent_to_shipping', label: 'تم إرسالها للشحن', count: statusCounts.sent_to_shipping ?? 0, icon: '🚚', color: 'border-purple-400 text-purple-800 bg-purple-50' },
                            { key: 'printed', label: 'تمت طباعتها', count: statusCounts.printed ?? 0, icon: '✅', color: 'border-emerald-400 text-emerald-800 bg-emerald-50' },
                        ].map(tab => {
                            const isActive = quickFilter === tab.key;
                            return (
                                <button
                                    key={tab.key}
                                    type="button"
                                    onClick={() => handleQuickFilter(tab.key)}
                                    className={`shrink-0 inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border font-bold transition-all shadow-sm ${
                                        isActive
                                            ? `${tab.color} border-current ring-2 ring-indigo-400 shadow`
                                            : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200'
                                    }`}
                                >
                                    <span>{tab.icon}</span>
                                    <span>{tab.label}</span>
                                    <span className={`px-1.5 py-0.5 rounded-md text-[10px] font-black ${
                                        isActive ? 'bg-white/80' : 'bg-gray-200 text-gray-800'
                                    }`}>
                                        {tab.count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <button
                        onClick={() => handleStatusFilter('')}
                        className={`p-4 rounded-xl border text-right transition-all ${status === '' && !quickFilter ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}`}
                    >
                        <p className="text-2xl font-bold">{statusCounts.total}</p>
                        <p className="text-xs font-medium opacity-80 mt-1">كل الطلبات</p>
                    </button>
                    <button
                        onClick={() => handleStatusFilter('pending')}
                        className={`p-4 rounded-xl border text-right transition-all ${status === 'pending' ? 'bg-yellow-500 text-white border-yellow-500 shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}`}
                    >
                        <p className="text-2xl font-bold">{statusCounts.pending}</p>
                        <p className="text-xs font-medium opacity-80 mt-1">في الانتظار</p>
                    </button>
                    <button
                        onClick={() => handleStatusFilter('confirmed')}
                        className={`p-4 rounded-xl border text-right transition-all ${status === 'confirmed' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}`}
                    >
                        <p className="text-2xl font-bold">{statusCounts.confirmed}</p>
                        <p className="text-xs font-medium opacity-80 mt-1">مؤكد</p>
                    </button>
                    <button
                        onClick={() => handleStatusFilter('shipped')}
                        className={`p-4 rounded-xl border text-right transition-all ${status === 'shipped' ? 'bg-purple-600 text-white border-purple-600 shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}`}
                    >
                        <p className="text-2xl font-bold">{statusCounts.shipped ?? 0}</p>
                        <p className="text-xs font-medium opacity-80 mt-1">مع شركة الشحن</p>
                    </button>
                    <button
                        onClick={() => handleStatusFilter('delivered')}
                        className={`p-4 rounded-xl border text-right transition-all ${status === 'delivered' ? 'bg-emerald-600 text-white border-emerald-600 shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}`}
                    >
                        <p className="text-2xl font-bold">{statusCounts.delivered ?? 0}</p>
                        <p className="text-xs font-medium opacity-80 mt-1">تم التسليم</p>
                    </button>
                    <button
                        onClick={() => handleStatusFilter('cancelled')}
                        className={`p-4 rounded-xl border text-right transition-all ${status === 'cancelled' ? 'bg-red-600 text-white border-red-600 shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}`}
                    >
                        <p className="text-2xl font-bold">{statusCounts.cancelled}</p>
                        <p className="text-xs font-medium opacity-80 mt-1">ملغي</p>
                    </button>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-5 gap-3">
                        <div className="relative">
                            <input
                                type="text"
                                placeholder="الاسم، الهاتف، الرقم المرجعي..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pr-3 pl-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
                            />
                        </div>
                        <div>
                            <select
                                value={productId}
                                onChange={(e) => setProductId(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none"
                            >
                                <option value="">كل المنتجات</option>
                                {productsList.map(prod => (
                                    <option key={prod.id} value={prod.id}>{prod.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none"
                            />
                        </div>
                        <div>
                            <input
                                type="date"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none"
                            />
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="submit"
                                className="flex-1 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors"
                            >
                                تصفية
                            </button>
                            <button
                                type="button"
                                onClick={handleReset}
                                className="px-3 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition-colors"
                            >
                                إعادة تعيين
                            </button>
                        </div>
                    </form>
                </div>

                {/* Mobile Export Buttons */}
                <div className="md:hidden flex gap-2">
                    <a
                        href={`/admin/orders/export?format=excel&search=${search}&status=${status}&date_from=${dateFrom}&date_to=${dateTo}&product_id=${productId}`}
                        className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 transition-colors shadow-sm"
                    >
                        excel الأوردرات المفتوحة
                    </a>
                    <a
                        href={`/admin/orders/export?format=pdf&search=${search}&status=${search}&date_from=${dateFrom}&date_to=${dateTo}&product_id=${productId}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 transition-colors shadow-sm"
                    >
                        pdf / طباعة المفتوح
                    </a>
                </div>

                {/* Table Container */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-1">
                    {/* Desktop View: Table */}
                    <div className="hidden md:block overflow-x-auto">
                        <table className="w-full text-right border-collapse">
                            <thead>
                                <tr className="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500">
                                    <th className="px-4 py-3.5 w-10 text-center">
                                        <input
                                            type="checkbox"
                                            checked={isAllVisibleSelected}
                                            onChange={toggleSelectAll}
                                            className="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer"
                                            title="تحديد الكل في هذه الصفحة"
                                        />
                                    </th>
                                    <th className="px-6 py-3.5">#</th>
                                    <th className="px-6 py-3.5">الرقم المرجعي</th>
                                    <th className="px-6 py-3.5">العميل والهاتف</th>
                                    <th className="px-6 py-3.5">المحافظة</th>
                                    <th className="px-6 py-3.5">الحالة</th>
                                    <th className="px-6 py-3.5">الطباعة والشحن</th>
                                    <th className="px-6 py-3.5">الإجمالي</th>
                                    <th className="px-6 py-3.5">التاريخ</th>
                                    <th className="px-6 py-3.5 text-left">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 text-sm text-gray-700">
                                {orders.data.length > 0 ? (
                                    orders.data.map((order, idx) => (
                                        <tr key={order.id} className={`hover:bg-gray-50/80 transition-colors ${selectedOrders.includes(order.id) ? 'bg-indigo-50/50' : ''}`}>
                                            <td className="px-4 py-4 text-center">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedOrders.includes(order.id)}
                                                    onChange={() => toggleSelectOrder(order.id)}
                                                    className="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer"
                                                />
                                            </td>
                                            <td className="px-6 py-4 font-semibold text-gray-400">
                                                {(orders.current_page - 1) * orders.per_page + idx + 1}
                                            </td>
                                            <td className="px-6 py-4 font-mono font-bold text-gray-900">
                                                {order.reference_number}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="font-semibold text-gray-900">
                                                    {order.is_unlocked || wallet_balance >= 2 ? order.customer_name : `${order.customer_name?.substring(0, 4)}***`}
                                                </div>
                                                <div className="text-xs text-gray-500 font-mono mt-0.5" dir="ltr">
                                                    {order.is_unlocked || wallet_balance >= 2 ? order.customer_phone : `${order.customer_phone?.substring(0, 4)}******`}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-gray-600 font-medium">
                                                {order.governorate}
                                            </td>
                                            <td className="px-6 py-4 space-y-1">
                                                <div>{getStatusBadge(order.status)}</div>
                                                <div>{getPaymentBadge(order)}</div>
                                                {order.whatsapp_status && order.whatsapp_status !== 'none' && (
                                                    <div>
                                                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold ${
                                                            order.whatsapp_status === 'confirmed'
                                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                                : (order.whatsapp_status === 'cancelled'
                                                                    ? 'bg-red-50 text-red-700 border border-red-200'
                                                                    : (order.whatsapp_status === 'pending'
                                                                        ? 'bg-amber-50 text-amber-800 border border-amber-200'
                                                                        : 'bg-gray-100 text-gray-600'))
                                                        }`}>
                                                            <span>💬</span>
                                                            <span>
                                                                {order.whatsapp_status === 'confirmed' && 'مؤكد واتس'}
                                                                {order.whatsapp_status === 'cancelled' && 'ملغي واتس'}
                                                                {order.whatsapp_status === 'pending' && 'بانتظار الواتس'}
                                                                {order.whatsapp_status === 'no_whatsapp' && 'بدون واتس'}
                                                            </span>
                                                        </span>
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 space-y-1.5">
                                                <div>{getPrintBadge(order)}</div>
                                                <div>{getShippingBadge(order)}</div>
                                            </td>
                                            <td className="px-6 py-4 font-extrabold text-indigo-600">
                                                {formatCurrency(order.total)}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">
                                                {formatDate(order.created_at)}
                                            </td>
                                            <td className="px-6 py-4 text-left">
                                                <div className="flex items-center justify-end gap-2">
                                                    {order.is_unlocked || wallet_balance >= 2 ? (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleOrderView(order)}
                                                            className="px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-sm flex items-center gap-1"
                                                        >
                                                            <span>👁️</span>
                                                            <span>عرض التفاصيل</span>
                                                        </button>
                                                    ) : (
                                                        <Link
                                                            href={route('merchant.wallet.index')}
                                                            className="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 text-white hover:bg-amber-600 transition-all shadow-sm flex items-center gap-1 whitespace-nowrap"
                                                        >
                                                            <span>💳</span>
                                                            <span>شحن لعرض التفاصيل</span>
                                                        </Link>
                                                    )}
                                                    {(order.is_unlocked || wallet_balance >= 2) && (
                                                        <a
                                                            href={`/admin/orders/${order.id}/invoice`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200 transition-colors"
                                                        >
                                                            الفاتورة
                                                        </a>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="10" className="px-6 py-10 text-center text-gray-400">
                                            لا توجد طلبات مطابقة للبحث أو الفلترة.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile View: Cards */}
                    <div className="md:hidden divide-y divide-gray-100">
                        {orders.data.length > 0 && (
                            <div className="p-3 bg-gray-50 flex items-center justify-between border-b border-gray-200">
                                <label className="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={isAllVisibleSelected}
                                        onChange={toggleSelectAll}
                                        className="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
                                    />
                                    <span>تحديد الكل في هذه الصفحة</span>
                                </label>
                                <span className="text-xs text-gray-500">
                                    {selectedOrders.length > 0 ? `${selectedOrders.length} محدد` : ''}
                                </span>
                            </div>
                        )}
                        {orders.data.length > 0 ? (
                            orders.data.map((order, idx) => (
                                <div key={order.id} className={`p-4 space-y-3 transition-colors ${selectedOrders.includes(order.id) ? 'bg-indigo-50/40' : 'bg-white'}`}>
                                    <div className="flex justify-between items-center gap-2">
                                        <div className="flex items-center gap-2 flex-nowrap shrink-0">
                                            <input
                                                type="checkbox"
                                                checked={selectedOrders.includes(order.id)}
                                                onChange={() => toggleSelectOrder(order.id)}
                                                className="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer"
                                            />
                                            <span className="font-mono font-extrabold text-gray-900 text-xs sm:text-sm bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-md whitespace-nowrap inline-flex items-center" dir="ltr">
                                                #{order.reference_number}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-1.5 flex-wrap justify-end">
                                            {getPaymentBadge(order)}
                                            {getStatusBadge(order.status)}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 flex-wrap text-xs pt-0.5">
                                        {getPrintBadge(order)}
                                        {getShippingBadge(order)}
                                    </div>
                                    <div className="text-sm space-y-1.5 text-gray-600">
                                        <div className="flex justify-between">
                                            <span className="font-semibold text-gray-900">
                                                {order.is_unlocked || wallet_balance >= 2 ? order.customer_name : `${order.customer_name?.substring(0, 4)}***`}
                                            </span>
                                            <span className="font-bold text-indigo-600">{formatCurrency(order.total)}</span>
                                        </div>
                                        <div className="flex justify-between text-xs">
                                            <span className="font-mono" dir="ltr">
                                                {order.is_unlocked || wallet_balance >= 2 ? order.customer_phone : `${order.customer_phone?.substring(0, 4)}******`}
                                            </span>
                                            <span>{order.governorate}</span>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 pt-1">
                                        {order.is_unlocked || wallet_balance >= 2 ? (
                                            <button
                                                type="button"
                                                onClick={() => handleOrderView(order)}
                                                className="flex-1 py-2 text-center rounded-lg text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-sm"
                                            >
                                                عرض التفاصيل 👁️
                                            </button>
                                        ) : (
                                            <Link
                                                href={route('merchant.wallet.index')}
                                                className="flex-1 py-2 text-center rounded-lg text-xs font-bold bg-amber-500 text-white hover:bg-amber-600 transition-all shadow-sm"
                                            >
                                                💳 شحن لعرض التفاصيل
                                            </Link>
                                        )}
                                        {(order.is_unlocked || wallet_balance >= 2) && (
                                            <a
                                                href={`/admin/orders/${order.id}/invoice`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="flex-1 py-2 text-center bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200 transition-colors"
                                            >
                                                الفاتورة
                                            </a>
                                        )}
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="p-8 text-center text-gray-400 text-sm">
                                لا توجد طلبات مطابقة للبحث أو الفلترة.
                            </div>
                        )}
                    </div>

                    {/* Total Amount Box */}
                    <div className="px-6 py-4 bg-gradient-to-r from-emerald-50 to-teal-50 border-t border-emerald-200">
                        <div className="flex flex-col sm:flex-row justify-between items-center gap-3">
                            <span className="text-base sm:text-lg font-bold text-emerald-800">إجمالي المبالغ المعروضة:</span>
                            <div className="text-xl sm:text-2xl font-bold text-emerald-700 bg-white px-5 py-2 rounded-xl shadow-sm border border-emerald-200 flex items-center gap-2">
                                <span>{formatCurrency(totalAmount)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Pagination */}
                    <Pagination links={orders.links} />
                </div>
            </div>

            {/* مودال عدم كفاية الرصيد */}
            {showInsufficientModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
                    <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden p-6 space-y-5 text-right">
                        <div className="flex items-center gap-3 text-amber-600 bg-amber-50 p-3 rounded-xl border border-amber-100">
                            <span className="text-2xl">⚠️</span>
                            <div>
                                <h3 className="font-extrabold text-base text-gray-900">برجاء الشحن لرؤية تفاصيل الأوردر</h3>
                                <span className="text-xs text-amber-700 font-medium">رسوم كل طلب: 2 ج.م</span>
                            </div>
                        </div>

                        <p className="text-sm text-gray-600 leading-relaxed">
                            رصيدك في المحفظة غير كافٍ لفتح تفاصيل الطلب. رسوم كل طلب (2 ج.م) تُخصم تلقائياً عند الإنشاء. يرجى شحن المحفظة للمتابعة.
                        </p>

                        <div className="p-3 bg-gray-50 rounded-xl border border-gray-200 flex justify-between items-center text-xs font-bold">
                            <span className="text-gray-500">رصيدك الحالي:</span>
                            <span className="text-rose-600 font-mono text-sm" dir="ltr">{Math.round(wallet_balance)} ج.م</span>
                        </div>

                        <div className="flex items-center gap-2 pt-2">
                            <Link
                                href={route('merchant.wallet.index')}
                                className="flex-1 py-3 bg-indigo-600 text-white text-center font-extrabold text-xs rounded-xl hover:bg-indigo-700 transition-all shadow-md flex items-center justify-center gap-1.5"
                            >
                                <span>💳</span>
                                <span>الانتقال لصفحة شحن المحفظة</span>
                            </Link>
                            <button
                                type="button"
                                onClick={() => setShowInsufficientModal(false)}
                                className="py-3 px-4 bg-gray-100 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-200 transition-colors"
                            >
                                إغلاق
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* ====== Modal: قفل الطلبات لانتهاء مدة الاشتراك ====== */}
            {isSubscriptionExpired && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div className="bg-white rounded-3xl p-8 max-w-lg w-full text-center shadow-2xl border border-gray-100 animate-fade-in">
                        <div className="w-16 h-16 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-3xl font-bold mx-auto mb-4 border border-rose-200">
                            🔒
                        </div>
                        <h3 className="text-xl font-extrabold text-gray-900 mb-2">
                            تم قفل استعراض الطلبات لانتهاء مدة الاشتراك
                        </h3>
                        <p className="text-sm text-gray-600 leading-relaxed mb-6">
                            سيظل متجرك مفتوحاً وعاملاً أمام العملاء لاستقبال الطلبات دائماً، ولكن لمشاهدة تفاصيل الطلبات الواردة وإدارتها يرجى تجديد الاشتراك أو شحن المحفظة والتحويل لباقة العمولة.
                        </p>
                        <div className="flex flex-col gap-3">
                            <Link
                                href={route('merchant.subscription.index')}
                                className="w-full py-3.5 px-6 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-extrabold text-sm rounded-xl transition-all shadow-md hover:shadow-indigo-200"
                            >
                                تجديد الاشتراك أو التحويل للباقة 🚀
                            </Link>
                            <Link
                                href={route('merchant.dashboard')}
                                className="w-full py-2.5 px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition-all"
                            >
                                العودة للرئيسية
                            </Link>
                        </div>
                    </div>
                </div>
            )}

            {/* Floating Action Bar for Bulk Actions */}
            {selectedOrders.length > 0 && (
                <div className="fixed bottom-4 inset-x-4 md:inset-x-auto md:right-1/2 md:translate-x-1/2 z-40 bg-gray-900/95 text-white backdrop-blur-md px-5 py-3.5 rounded-2xl shadow-2xl border border-gray-800 flex flex-wrap items-center justify-between gap-3 animate-fade-in">
                    <div className="flex items-center gap-2">
                        <span className="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center font-black text-xs">
                            {selectedOrders.length}
                        </span>
                        <span className="text-xs sm:text-sm font-bold">
                            طلب تم تحديده
                        </span>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        {/* Send to Shipping */}
                        <button
                            type="button"
                            onClick={() => setShowShippingModal(true)}
                            className="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5"
                        >
                            <span>🚚</span>
                            <span>إرسال للشحن</span>
                        </button>

                        {/* Bulk Print Invoices */}
                        <button
                            type="button"
                            onClick={handleBulkPrint}
                            className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5"
                        >
                            <span>🖨️</span>
                            <span>طباعة الفواتير</span>
                        </button>

                        {/* Bulk Change Status */}
                        <button
                            type="button"
                            onClick={() => setShowStatusModal(true)}
                            className="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5"
                        >
                            <span>🔄</span>
                            <span>تغيير الحالة</span>
                        </button>

                        {/* Bulk Export Excel */}
                        <button
                            type="button"
                            onClick={handleBulkExport}
                            className="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5"
                        >
                            <span>📊</span>
                            <span>تصدير إكسيل</span>
                        </button>

                        {/* Cancel Selection */}
                        <button
                            type="button"
                            onClick={() => setSelectedOrders([])}
                            className="px-2.5 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-xs font-bold transition-all"
                            title="إلغاء التحديد"
                        >
                            ✕ إلغاء
                        </button>
                    </div>
                </div>
            )}

            {/* Modal: إرسال الطلبات المحددة للشحن */}
            {showShippingModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
                    <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden p-6 space-y-5 text-right">
                        <div className="flex items-center gap-3 text-purple-700 bg-purple-50 p-3 rounded-xl border border-purple-100">
                            <span className="text-2xl">🚚</span>
                            <div>
                                <h3 className="font-extrabold text-base text-gray-900">إرسال الطلبات لشركة الشحن</h3>
                                <span className="text-xs text-purple-700 font-medium">تم تحديد {selectedOrders.length} طلب</span>
                            </div>
                        </div>

                        {activeShippingGateways && activeShippingGateways.length > 0 ? (
                            <div className="space-y-4">
                                <p className="text-sm text-gray-600">
                                    اختر شركة الشحن التي تريد إنشاء بوالص الشحن عليها للطلبات المحددة:
                                </p>
                                <div className="space-y-2">
                                    {activeShippingGateways.map((gw) => (
                                        <label
                                            key={gw.id}
                                            className={`flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all ${
                                                selectedShippingProvider === gw.provider
                                                    ? 'border-purple-600 bg-purple-50/50 text-purple-900 shadow-sm'
                                                    : 'border-gray-200 hover:bg-gray-50 text-gray-700'
                                            }`}
                                        >
                                            <div className="flex items-center gap-2.5">
                                                <input
                                                    type="radio"
                                                    name="shipping_provider"
                                                    value={gw.provider}
                                                    checked={selectedShippingProvider === gw.provider}
                                                    onChange={(e) => setSelectedShippingProvider(e.target.value)}
                                                    className="w-4 h-4 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span className="font-bold text-sm">
                                                    {gw.name || (gw.provider === 'jnt' ? 'J&T Express' : (gw.provider === 'bosta' ? 'بوسطة' : (gw.provider === 'aramex' ? 'أرامكس' : gw.provider)))}
                                                </span>
                                            </div>
                                            <span className="text-xs text-gray-400 font-mono uppercase">{gw.provider}</span>
                                        </label>
                                    ))}
                                </div>
                                <div className="flex items-center gap-2 pt-3">
                                    <button
                                        type="button"
                                        disabled={isProcessingBulk || !selectedShippingProvider}
                                        onClick={handleConfirmBulkShip}
                                        className="flex-1 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-center font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5"
                                    >
                                        {isProcessingBulk ? (
                                            <>
                                                <span className="animate-spin text-sm">⏳</span>
                                                <span>جاري الإرسال...</span>
                                            </>
                                        ) : (
                                            <>
                                                <span>🚀</span>
                                                <span>تأكيد وإنشاء البوالص</span>
                                            </>
                                        )}
                                    </button>
                                    <button
                                        type="button"
                                        disabled={isProcessingBulk}
                                        onClick={() => setShowShippingModal(false)}
                                        className="py-2.5 px-4 bg-gray-100 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-200 transition-colors"
                                    >
                                        إلغاء
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs leading-relaxed">
                                    ⚠️ لا توجد شركات شحن مفعلة حالياً في متجرك. يرجى تفعيل شركة شحن أولاً من صفحة الربط والتكامل (مثل J&T Express أو بوسطة).
                                </div>
                                <div className="flex items-center gap-2 pt-2">
                                    <Link
                                        href="/admin/shipping/integrations"
                                        className="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-center font-bold text-xs rounded-xl transition-all shadow-sm"
                                    >
                                        إعدادات شركات الشحن ⚙️
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => setShowShippingModal(false)}
                                        className="py-2.5 px-4 bg-gray-100 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-200 transition-colors"
                                    >
                                        إغلاق
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Modal: تغيير حالة الطلبات المحددة */}
            {showStatusModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
                    <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden p-6 space-y-5 text-right">
                        <div className="flex items-center gap-3 text-blue-700 bg-blue-50 p-3 rounded-xl border border-blue-100">
                            <span className="text-2xl">🔄</span>
                            <div>
                                <h3 className="font-extrabold text-base text-gray-900">تغيير حالة الطلبات المحددة</h3>
                                <span className="text-xs text-blue-700 font-medium">تم تحديد {selectedOrders.length} طلب</span>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 mb-1.5">الحالة الجديدة:</label>
                                <select
                                    value={selectedBulkStatus}
                                    onChange={(e) => setSelectedBulkStatus(e.target.value)}
                                    className="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm font-semibold text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="pending">في الانتظار</option>
                                    <option value="confirmed">مؤكد</option>
                                    <option value="shipped">مع شركة الشحن</option>
                                    <option value="delivered">تم التسليم</option>
                                    <option value="cancelled">ملغي</option>
                                </select>
                            </div>

                            {selectedBulkStatus === 'cancelled' && (
                                <div className="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 leading-relaxed">
                                    ⚠️ <strong>تنبيه:</strong> اختيار "ملغي" سيقوم تلقائياً بإلغاء بوالص الشحن المرتبطة بهذه الطلبات لدى شركة الشحن (إن وُجدت).
                                </div>
                            )}

                            <div className="flex items-center gap-2 pt-3">
                                <button
                                    type="button"
                                    disabled={isProcessingBulk}
                                    onClick={handleConfirmBulkStatus}
                                    className="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-center font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5"
                                >
                                    {isProcessingBulk ? (
                                        <>
                                            <span className="animate-spin text-sm">⏳</span>
                                            <span>جاري التحديث...</span>
                                        </>
                                    ) : (
                                        <>
                                            <span>✓</span>
                                            <span>تأكيد التحديث</span>
                                        </>
                                    )}
                                </button>
                                <button
                                    type="button"
                                    disabled={isProcessingBulk}
                                    onClick={() => setShowStatusModal(false)}
                                    className="py-2.5 px-4 bg-gray-100 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-200 transition-colors"
                                >
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </MerchantLayout>
    );
}
