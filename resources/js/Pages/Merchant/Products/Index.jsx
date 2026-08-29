import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import Pagination from '@/Components/Pagination';

export default function ProductsIndex({ products, categories, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.q || '');
    const [categoryId, setCategoryId] = useState(filters?.category_id || '');
    const [deletingId, setDeletingId] = useState(null);
    const [copiedId, setCopiedId] = useState(null);

    const handleCopyLink = (productId) => {
        const url = `${window.location.origin}/shop/product.html?id=${productId}`;
        navigator.clipboard.writeText(url).then(() => {
            setCopiedId(productId);
            setTimeout(() => setCopiedId(null), 2000);
        });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/products', { q: search, category_id: categoryId }, { preserveState: true, replace: true });
    };

    const handleDelete = (product) => {
        if (!confirm(`هل أنت متأكد من حذف "${product.name}"؟ هذا الإجراء لا يمكن التراجع عنه.`)) return;
        setDeletingId(product.id);
        router.delete(`/admin/products/${product.id}`, {
            onFinish: () => setDeletingId(null),
        });
    };

    const getCategoryName = (category) => {
        if (!category) return '-';
        return category.name_ar || category.name || '-';
    };

    const formatPrice = (price) => {
        if (!price && price !== 0) return '-';
        return Math.round(Number(price)).toLocaleString('en-US') + ' جنيه';
    };

    return (
        <MerchantLayout title="إدارة المنتجات">
            <Head title="المنتجات" />

            <div className="space-y-5">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">المنتجات</h2>
                        <p className="text-sm text-gray-500 mt-0.5">
                            إجمالي {products.total} منتج
                        </p>
                    </div>
                    <Link
                        href="/admin/products/create"
                        className="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors shadow-sm"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                        </svg>
                        إضافة منتج جديد
                    </Link>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1 relative">
                            <svg className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                placeholder="ابحث باسم المنتج أو الوصف..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pr-9 pl-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent"
                            />
                        </div>
                        <select
                            value={categoryId}
                            onChange={(e) => setCategoryId(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent bg-white"
                        >
                            <option value="">كل التصنيفات</option>
                            {categories?.map((cat) => (
                                <option key={cat.id} value={cat.id}>
                                    {cat.name_ar || cat.name}
                                </option>
                            ))}
                        </select>
                        <button
                            type="submit"
                            className="px-5 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors"
                        >
                            بحث
                        </button>
                        {(filters?.q || filters?.category_id) && (
                            <button
                                type="button"
                                onClick={() => {
                                    setSearch('');
                                    setCategoryId('');
                                    router.get('/admin/products', {}, { replace: true });
                                }}
                                className="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition-colors"
                            >
                                إلغاء الفلتر
                            </button>
                        )}
                    </form>
                </div>

                {/* Table */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    {products.data.length === 0 ? (
                        <div className="text-center py-16">
                            <svg className="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p className="text-gray-500 font-medium">لا توجد منتجات</p>
                            <p className="text-gray-400 text-sm mt-1">ابدأ بإضافة منتجاتك الآن</p>
                            <Link
                                href="/admin/products/create"
                                className="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors"
                            >
                                إضافة أول منتج
                            </Link>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-gray-50 border-b border-gray-200">
                                        <th className="text-right px-4 py-3 font-semibold text-gray-700 w-12">#</th>
                                        <th className="text-right px-4 py-3 font-semibold text-gray-700">الصورة</th>
                                        <th className="text-right px-4 py-3 font-semibold text-gray-700">اسم المنتج</th>
                                        <th className="text-right px-4 py-3 font-semibold text-gray-700">التصنيف</th>
                                        <th className="text-right px-4 py-3 font-semibold text-gray-700">السعر</th>
                                        <th className="text-right px-4 py-3 font-semibold text-gray-700">المخزون</th>
                                        <th className="text-right px-4 py-3 font-semibold text-gray-700">الشحن</th>
                                        <th className="text-right px-4 py-3 font-semibold text-gray-700">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {products.data.map((product, idx) => (
                                        <tr key={product.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-4 py-3 text-gray-500">
                                                {(products.current_page - 1) * products.per_page + idx + 1}
                                            </td>
                                            <td className="px-4 py-3">
                                                {(() => {
                                                    const imgSrc = product.image_display_url || 
                                                        (product.image_url && (product.image_url.startsWith('/') || product.image_url.startsWith('http')) ? product.image_url : null) ||
                                                        (product.main_image_path && (product.main_image_path.startsWith('/') || product.main_image_path.startsWith('http')) ? product.main_image_path : (product.main_image_path ? `/storage/${product.main_image_path}` : null));
                                                    return imgSrc ? (
                                                        <img
                                                            src={imgSrc}
                                                            alt={product.name}
                                                            className="w-12 h-12 rounded-lg object-cover border border-gray-200"
                                                        />
                                                    ) : (
                                                        <div className="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 border border-gray-200">
                                                            📦
                                                        </div>
                                                    );
                                                })()}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-semibold text-gray-900">{product.name}</div>
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">
                                                {getCategoryName(product.category)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-bold text-gray-900">{formatPrice(product.price_after || product.price)}</div>
                                                {product.price_before && (
                                                    <div className="text-xs text-gray-400 line-through">{formatPrice(product.price_before)}</div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {product.stock !== null && product.stock !== undefined ? (
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ${
                                                        product.stock <= (product.low_stock_threshold || 5)
                                                            ? 'bg-red-50 text-red-700 border border-red-100'
                                                            : 'bg-green-50 text-green-700 border border-green-100'
                                                    }`}>
                                                        {product.stock} {product.stock <= (product.low_stock_threshold || 5) ? 'قليل!' : 'متوفر'}
                                                    </span>
                                                ) : (
                                                    <span className="text-gray-400 italic">غير محدد</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">
                                                {product.shipping_type === 'free' ? 'شحن مجاني' : 'شحن مدفوع'}
                                            </td>
                                            <td className="px-4 py-3 text-left">
                                                <div className="flex items-center justify-end gap-1.5 flex-wrap">
                                                    {/* Preview Storefront Link */}
                                                    <a
                                                        href={`/shop/product.html?id=${product.id}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        title="معاينة المنتج في المتجر"
                                                        className="text-gray-600 hover:text-orange-600 border border-gray-200 hover:border-orange-200 hover:bg-orange-50 p-1.5 rounded-lg transition-all"
                                                    >
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </a>
                                                    {/* Copy Storefront Link */}
                                                    <button
                                                        onClick={() => handleCopyLink(product.id)}
                                                        title={copiedId === product.id ? "تم نسخ الرابط!" : "نسخ رابط المنتج"}
                                                        className={`p-1.5 rounded-lg border transition-all ${
                                                            copiedId === product.id
                                                                ? 'text-green-600 border-green-200 bg-green-50'
                                                                : 'text-gray-600 hover:text-emerald-600 border-gray-200 hover:border-emerald-200 hover:bg-emerald-50'
                                                        }`}
                                                    >
                                                        {copiedId === product.id ? (
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        ) : (
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                            </svg>
                                                        )}
                                                    </button>

                                                    <Link
                                                        href={`/admin/products/${product.id}/edit`}
                                                        className="text-indigo-600 hover:text-indigo-900 font-semibold text-xs border border-indigo-100 hover:bg-indigo-50 px-2.5 py-1.5 rounded-lg transition-colors cursor-pointer"
                                                    >
                                                        تعديل
                                                    </Link>
                                                    <Link
                                                        href={`/admin/products/create?duplicate_from=${product.id}`}
                                                        className="text-amber-700 hover:text-amber-900 font-semibold text-xs border border-amber-200 hover:bg-amber-50 px-2.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1"
                                                        title="استنساخ هذا المنتج لإنشاء نسخة جديدة"
                                                    >
                                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                        </svg>
                                                        <span>استنساخ</span>
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(product)}
                                                        disabled={deletingId === product.id}
                                                        className="text-red-600 hover:text-red-900 font-semibold text-xs border border-red-100 hover:bg-red-50 px-2.5 py-1.5 rounded-lg transition-colors cursor-pointer disabled:opacity-50"
                                                    >
                                                        {deletingId === product.id ? 'جاري الحذف...' : 'حذف'}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Pagination */}
                    <Pagination links={products.links} />
                </div>
            </div>
        </MerchantLayout>
    );
}