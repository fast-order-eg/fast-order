import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import Pagination from '@/Components/Pagination';
import axios from 'axios';

export default function CustomersIndex({ customers, filters }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [selectedCustomer, setSelectedCustomer] = useState(null);
    const [orders, setOrders] = useState([]);
    const [loadingOrders, setLoadingOrders] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/customers', { search }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch('');
        router.get('/admin/customers', {}, { replace: true });
    };

    const openCustomerDetails = async (customer) => {
        setModalOpen(true);
        setLoadingOrders(true);
        setSelectedCustomer(customer);
        setOrders([]);

        try {
            const response = await axios.get(`/admin/customers/${customer.customer_phone}`);
            if (response.data && response.data.success) {
                setOrders(response.data.orders);
            }
        } catch (error) {
            console.error('خطأ أثناء جلب تفاصيل العميل والطلبات:', error);
        } finally {
            setLoadingOrders(false);
        }
    };

    const formatCurrency = (amount) => {
        return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' ج.م';
    };

    return (
        <MerchantLayout title="إدارة العملاء">
            <Head title="العملاء" />

            <div className="space-y-6" dir="rtl">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">العملاء</h2>
                        <p className="text-sm text-gray-500 mt-0.5">
                            استعراض وإدارة العملاء الذين قاموا بالطلب من متجرك
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-3">
                        <div className="relative flex-1">
                            <input
                                type="text"
                                placeholder="ابحث باسم العميل أو رقم الهاتف..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 text-right"
                            />
                            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="submit"
                                className="px-6 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors shadow-sm cursor-pointer"
                            >
                                تصفية
                            </button>
                            <button
                                type="button"
                                onClick={handleReset}
                                className="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition-colors cursor-pointer"
                            >
                                إعادة تعيين
                            </button>
                        </div>
                    </form>
                </div>

                {/* Table */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-right border-collapse">
                            <thead>
                                <tr className="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th className="px-6 py-4">العميل</th>
                                    <th className="px-6 py-4">رقم الهاتف</th>
                                    <th className="px-6 py-4">أحدث عنوان</th>
                                    <th className="px-6 py-4 text-center">عدد الطلبات</th>
                                    <th className="px-6 py-4">إجمالي المشتريات</th>
                                    <th className="px-6 py-4">آخر طلب</th>
                                    <th className="px-6 py-4 text-center">العمليات</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 text-sm text-gray-700">
                                {customers.data.length > 0 ? (
                                    customers.data.map((customer) => (
                                        <tr key={customer.customer_phone} className="hover:bg-gray-50/50 transition-colors">
                                            <td className="px-6 py-4 font-semibold text-gray-900">
                                                {customer.customer_name}
                                            </td>
                                            <td className="px-6 py-4 font-mono">{customer.customer_phone}</td>
                                            <td className="px-6 py-4 text-gray-500 max-w-xs truncate">
                                                {customer.customer_address}
                                                {customer.governorate ? ` (${customer.governorate})` : ''}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span className="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                                    {customer.orders_count}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 font-bold text-indigo-600">
                                                {formatCurrency(customer.total_spent)}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-gray-500">
                                                {customer.last_order_at ? (
                                                    new Date(customer.last_order_at).toLocaleDateString('en-US', {
                                                        year: 'numeric',
                                                        month: 'long',
                                                        day: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    })
                                                ) : (
                                                    '-'
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <button
                                                    onClick={() => openCustomerDetails(customer)}
                                                    className="px-3.5 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-semibold hover:bg-indigo-100 transition-colors cursor-pointer"
                                                >
                                                    عرض التفاصيل والطلبات
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="7" className="px-6 py-10 text-center text-gray-400">
                                            لا يوجد عملاء مطابقين للبحث.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    <Pagination links={customers.links} />
                </div>

                {/* Details Modal */}
                {modalOpen && (
                    <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            {/* Backdrop */}
                            <div 
                                className="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" 
                                aria-hidden="true"
                                onClick={() => setModalOpen(false)}
                            ></div>

                            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                            {/* Modal panel */}
                            <div className="inline-block align-bottom bg-white rounded-2xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                                {/* Header */}
                                <div className="bg-gray-50 px-6 py-4 border-b border-gray-150 flex items-center justify-between">
                                    <h3 className="text-lg font-bold text-gray-900" id="modal-title">
                                        تفاصيل العميل وسجل الطلبات
                                    </h3>
                                    <button 
                                        onClick={() => setModalOpen(false)}
                                        className="text-gray-400 hover:text-gray-500 focus:outline-none cursor-pointer"
                                    >
                                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                {/* Content */}
                                <div className="px-6 py-6 space-y-6">
                                    {/* Customer Card Info */}
                                    <div className="bg-gray-50 rounded-xl p-4 border border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <span className="block text-xs font-semibold text-gray-400">اسم العميل</span>
                                            <span className="text-sm font-bold text-gray-900">{selectedCustomer?.customer_name}</span>
                                        </div>
                                        <div>
                                            <span className="block text-xs font-semibold text-gray-400">رقم الهاتف</span>
                                            <span className="text-sm font-mono font-bold text-gray-900">{selectedCustomer?.customer_phone}</span>
                                        </div>
                                        <div className="sm:col-span-2">
                                            <span className="block text-xs font-semibold text-gray-400">أحدث عنوان مسجل</span>
                                            <span className="text-sm text-gray-700">
                                                {selectedCustomer?.customer_address}
                                                {selectedCustomer?.governorate ? ` (${selectedCustomer?.governorate})` : ''}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Orders History */}
                                    <div>
                                        <h4 className="text-sm font-bold text-gray-900 mb-3 flex items-center gap-1.5">
                                            <span>سجل الطلبات السابقة</span>
                                            <span className="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                                {orders.length}
                                            </span>
                                        </h4>

                                        {loadingOrders ? (
                                            <div className="flex flex-col items-center justify-center py-10 space-y-2">
                                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
                                                <span className="text-xs text-gray-500">جاري تحميل سجل الطلبات...</span>
                                            </div>
                                        ) : orders.length > 0 ? (
                                            <div className="border border-gray-150 rounded-xl overflow-hidden shadow-xs">
                                                <table className="w-full text-right border-collapse">
                                                    <thead>
                                                        <tr className="bg-gray-50 border-b border-gray-150 text-xs font-semibold text-gray-500">
                                                            <th className="px-4 py-3">الرقم المرجعي</th>
                                                            <th className="px-4 py-3">تاريخ الطلب</th>
                                                            <th className="px-4 py-3">الحالة</th>
                                                            <th className="px-4 py-3">الإجمالي</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-gray-150 text-xs text-gray-700">
                                                        {orders.map((order) => (
                                                            <tr key={order.id} className="hover:bg-gray-50/50">
                                                                <td className="px-4 py-3 font-mono font-bold text-gray-900">
                                                                    #{order.reference_number}
                                                                </td>
                                                                <td className="px-4 py-3 text-gray-500">
                                                                    {order.created_at ? (
                                                                        new Date(order.created_at).toLocaleDateString('en-US', {
                                                                            year: 'numeric',
                                                                            month: 'long',
                                                                            day: 'numeric',
                                                                            hour: '2-digit',
                                                                            minute: '2-digit'
                                                                        })
                                                                    ) : (
                                                                        '-'
                                                                    )}
                                                                </td>
                                                                <td className="px-4 py-3">
                                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ${order.status_color}`}>
                                                                        {order.status_text}
                                                                    </span>
                                                                </td>
                                                                <td className="px-4 py-3 font-bold text-indigo-600">
                                                                    {formatCurrency(order.total)}
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        ) : (
                                            <p className="text-center py-6 text-sm text-gray-400">لا يوجد سجل طلبات متاح.</p>
                                        )}
                                    </div>
                                </div>

                                {/* Footer */}
                                <div className="bg-gray-50 px-6 py-4 border-t border-gray-150 flex justify-end">
                                    <button
                                        type="button"
                                        onClick={() => setModalOpen(false)}
                                        className="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer"
                                    >
                                        إغلاق
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </MerchantLayout>
    );
}
