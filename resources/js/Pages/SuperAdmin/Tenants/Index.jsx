import React, { useState } from 'react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import { Head, Link, router } from '@inertiajs/react';

function LogoImage({ logo, name }) {
    const [imgError, setImgError] = useState(false);
    const src = logo 
        ? (logo.startsWith('http') ? logo : `/storage/${logo}`)
        : '/storage/demo/logo.png';

    if (imgError) {
        return (
            <div className="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-sm">
                {name ? name.substring(0, 2).toUpperCase() : 'ST'}
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={name}
            className="w-full h-full object-cover"
            onError={() => setImgError(true)}
        />
    );
}

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '-';
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
};

export default function Index({ tenants, filters, plans, planCounts }) {
    // Ensure filters is a valid object and not an array, null, or undefined
    const safeFilters = (typeof filters === 'object' && filters !== null && !Array.isArray(filters)) ? filters : {};
    
    const [search, setSearch] = useState(safeFilters.search || '');
    const [status, setStatus] = useState(safeFilters.status || 'all');
    const [plan, setPlan] = useState(safeFilters.plan || 'all');
    const [sortBy, setSortBy] = useState(safeFilters.sort_by || 'latest');

    // Helper to render status badge
    const renderStatusBadge = (tenant, planObj) => {
        const isCommission = planObj && (planObj.slug === 'commission' || planObj.name?.includes('عمولة'));
        const isExpired = !isCommission && (
            tenant.subscription_status === 'expired' ||
            (tenant.subscription_ends_at && new Date(tenant.subscription_ends_at) < new Date()) ||
            (tenant.trial_ends_at && new Date(tenant.trial_ends_at) < new Date() && !tenant.subscription_ends_at)
        );

        if (!tenant.is_active) {
            return (
                <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100">
                    <span className="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    موقوف
                </span>
            );
        }

        if (isExpired) {
            return (
                <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300">
                    <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    منتهي
                </span>
            );
        }

        return (
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                نشط
            </span>
        );
    };

    // Helper to render distinctive plan badges
    const renderPlanBadge = (planObj, subStatus) => {
        if (!planObj) {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                    لا يوجد اشتراك
                </span>
            );
        }
        const slug = planObj.slug || '';
        const name = planObj.name || '';

        if (slug === 'free' || name.includes('مجانية')) {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                    🎁 {name}
                </span>
            );
        }
        if (slug === 'monthly' || name.includes('شهرية')) {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    📅 {name}
                </span>
            );
        }
        if (slug === 'yearly' || name.includes('سنوية')) {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300">
                    👑 {name}
                </span>
            );
        }
        if (slug === 'commission' || name.includes('عمولة') || name.includes('محفظة')) {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    💰 {name}
                </span>
            );
        }
        return (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                ✨ {name}
            </span>
        );
    };
    
    // Modal state
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [createData, setCreateData] = useState({
        name: '',
        owner_name: '',
        email: '',
        phone: '',
        password: '',
        slug: '',
        plan_id: '',
        ends_at: '',
    });
    const [createErrors, setCreateErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    // Assign Subscription Modal state
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [assignData, setAssignData] = useState({
        tenant_id: null,
        plan_id: '',
        ends_at: '',
    });
    const [assignErrors, setAssignErrors] = useState({});
    const [assigning, setAssigning] = useState(false);

    const openAssignModal = (tenant) => {
        const activeSub = tenant.subscriptions && tenant.subscriptions[0];
        setAssignData({
            tenant_id: tenant.id,
            plan_id: activeSub?.plan_id || '',
            ends_at: tenant.subscription_ends_at ? tenant.subscription_ends_at.split('T')[0] : '',
        });
        setAssignErrors({});
        setShowAssignModal(true);
    };

    const handleAssignSubmit = (e) => {
        e.preventDefault();
        setAssigning(true);
        router.post(
            route('superadmin.tenants.assign-subscription', assignData.tenant_id),
            assignData,
            {
                onError: (errs) => {
                    setAssignErrors(errs);
                    setAssigning(false);
                },
                onSuccess: () => {
                    setShowAssignModal(false);
                    setAssignErrors({});
                    setAssigning(false);
                },
            }
        );
    };

    const handleSearch = (e) => {
        if (e) e.preventDefault();
        router.get(
            route('superadmin.tenants.index'),
            { search, status, plan, sort_by: sortBy },
            { preserveState: true, replace: true }
        );
    };

    const handleClearSearch = () => {
        setSearch('');
        router.get(
            route('superadmin.tenants.index'),
            { search: '', status, plan, sort_by: sortBy },
            { preserveState: true, replace: true }
        );
    };

    const handleStatusChange = (newStatus) => {
        setStatus(newStatus);
        router.get(
            route('superadmin.tenants.index'),
            { search, status: newStatus, plan, sort_by: sortBy },
            { preserveState: true, replace: true }
        );
    };

    const handlePlanFilterChange = (newPlan) => {
        setPlan(newPlan);
        router.get(
            route('superadmin.tenants.index'),
            { search, status, plan: newPlan, sort_by: sortBy },
            { preserveState: true, replace: true }
        );
    };

    const handleSortChange = (newSort) => {
        setSortBy(newSort);
        router.get(
            route('superadmin.tenants.index'),
            { search, status, plan, sort_by: newSort },
            { preserveState: true, replace: true }
        );
    };

    const toggleStatus = (id) => {
        if (confirm('هل أنت متأكد من تغيير حالة هذا المتجر؟')) {
            router.patch(
                route('superadmin.tenants.toggle-status', id),
                {},
                { preserveScroll: true }
            );
        }
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        
        const errors = {};
        if (!createData.password || createData.password.length < 8) {
            errors.password = 'يجب ألا تقل كلمة المرور عن 8 خانات أو أرقام.';
        }
        if (!createData.slug || !/^[a-zA-Z0-9-_]+$/.test(createData.slug)) {
            errors.slug = 'يجب أن يحتوي الرابط على أحرف إنجليزية وأرقام وشرطات فقط بدون مسافات.';
        }

        if (Object.keys(errors).length > 0) {
            setCreateErrors(errors);
            return;
        }

        setSubmitting(true);
        router.post(
            route('superadmin.tenants.store'),
            createData,
            {
                onError: (errs) => {
                    setCreateErrors(errs);
                    setSubmitting(false);
                },
                onSuccess: () => {
                    setShowCreateModal(false);
                    setCreateData({
                        name: '',
                        owner_name: '',
                        email: '',
                        phone: '',
                        password: '',
                        slug: '',
                        plan_id: '',
                        ends_at: '',
                    });
                    setCreateErrors({});
                    setShowPassword(false);
                    setSubmitting(false);
                },
            }
        );
    };

    const [showPassword, setShowPassword] = useState(false);

    return (
        <SuperAdminLayout>
            <Head title="إدارة المتاجر - لوحة التحكم" />

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                {/* Header Section */}
                <div className="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h2 className="text-xl font-bold text-gray-800">إدارة المتاجر والعملاء</h2>
                            {planCounts && (
                                <span className="px-3 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-full text-xs font-extrabold shadow-2xs flex items-center gap-1.5">
                                    <span className="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                    {plan === 'free' ? `${planCounts.free} متجر` :
                                     plan === 'monthly' ? `${planCounts.monthly} متجر` :
                                     plan === 'yearly' ? `${planCounts.yearly} متجر` :
                                     plan === 'commission' ? `${planCounts.commission} متجر` :
                                     `${planCounts.all} متجر`}
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-gray-500 mt-1">عرض وتصفية المتاجر المشتركة في المنصة والتحكم في حالاتهم.</p>
                    </div>
                    <div className="flex items-center gap-2 self-start md:self-auto">
                        <a
                            href={route('superadmin.tenants.export')}
                            className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-all flex items-center justify-center gap-2"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            تصدير Excel
                        </a>
                        <button
                            onClick={() => setShowCreateModal(true)}
                            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-center gap-2"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                            </svg>
                            إنشاء متجر جديد
                        </button>
                    </div>
                </div>

                {/* Filters Section */}
                <div className="p-6 bg-gray-50/50 border-b border-gray-100">
                    <form onSubmit={handleSearch} className="flex flex-col lg:flex-row items-center gap-3">
                        <div className="flex-1 w-full relative">
                            <span className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                placeholder="البحث باسم المتجر، المالك، الرابط، الهاتف، أو الإيميل..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pr-10 pl-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            />
                            {search && (
                                <button
                                    type="button"
                                    onClick={handleClearSearch}
                                    className="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 hover:text-rose-600 transition-colors focus:outline-none"
                                    title="إلغاء البحث"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            )}
                        </div>

                        <div className="w-full sm:w-auto min-w-[140px]">
                            <select
                                value={status}
                                onChange={(e) => handleStatusChange(e.target.value)}
                                style={{
                                    backgroundImage: `url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E")`,
                                    backgroundPosition: 'left 0.75rem center',
                                    backgroundSize: '1.25rem',
                                    backgroundRepeat: 'no-repeat',
                                }}
                                className="w-full pl-10 pr-3 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all appearance-none font-medium text-gray-700"
                            >
                                <option value="all">كل الحالات</option>
                                <option value="active">نشط</option>
                                <option value="expired">منتهي ⚠️</option>
                                <option value="suspended">موقوف</option>
                            </select>
                        </div>

                        <div className="w-full sm:w-auto min-w-[160px]">
                            <select
                                value={plan}
                                onChange={(e) => handlePlanFilterChange(e.target.value)}
                                style={{
                                    backgroundImage: `url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E")`,
                                    backgroundPosition: 'left 0.75rem center',
                                    backgroundSize: '1.25rem',
                                    backgroundRepeat: 'no-repeat',
                                }}
                                className="w-full pl-10 pr-3 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all appearance-none font-semibold text-gray-700"
                            >
                                <option value="all">جميع الباقات</option>
                                <option value="free">الباقة المجانية 🎁</option>
                                <option value="monthly">الباقة الشهرية 📅</option>
                                <option value="yearly">الباقة السنوية 👑</option>
                                <option value="commission">باقة العمولة 💰</option>
                            </select>
                        </div>

                        <div className="w-full sm:w-auto min-w-[190px]">
                            <select
                                value={sortBy}
                                onChange={(e) => handleSortChange(e.target.value)}
                                style={{
                                    backgroundImage: `url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E")`,
                                    backgroundPosition: 'left 0.75rem center',
                                    backgroundSize: '1.25rem',
                                    backgroundRepeat: 'no-repeat',
                                }}
                                className="w-full pl-10 pr-3 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all appearance-none font-semibold text-gray-700"
                            >
                                <option value="latest">ترتيب: الأحدث تسجيلاً 🕒</option>
                                <option value="most_products">ترتيب: الأكثر منتجات 🛍️</option>
                                <option value="most_orders">ترتيب: الأكثر طلبات 📦</option>
                                <option value="expiring_soon">ترتيب: أوشك على الانتهاء ⚠️</option>
                                <option value="oldest">ترتيب: الأقدم تسجيلاً 📅</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            className="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex-shrink-0"
                        >
                            بحث
                        </button>
                    </form>
                </div>

                {/* Table Section */}
                <div className="overflow-x-auto">
                    <table className="w-full text-right border-collapse">
                        <thead>
                            <tr className="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-100">
                                <th className="px-6 py-4">المتجر</th>
                                <th className="px-6 py-4">المالك</th>
                                <th className="px-6 py-4">المنتجات والطلبات</th>
                                <th className="px-6 py-4">الاشتراك الحالي</th>
                                <th className="px-6 py-4">تاريخ الانتهاء</th>
                                <th className="px-6 py-4">الحالة</th>
                                <th className="px-6 py-4 text-left">العمليات</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-sm">
                            {tenants.data && tenants.data.length > 0 ? (
                                tenants.data.map((tenant) => {
                                    const activeSub = tenant.subscriptions?.find(s => s.status === 'active') || tenant.subscriptions?.[tenant.subscriptions?.length - 1];
                                    const freePlan = plans?.find(p => p.slug === 'free' || p.name?.includes('مجانية')) || plans?.[0];
                                    const planToShow = activeSub?.plan || (tenant.subscription_status === 'trial' ? freePlan : null);
                                    const isCommission = planToShow && (planToShow.slug === 'commission' || planToShow.name?.includes('عمولة') || planToShow.name?.includes('المحفظة'));

                                    return (
                                        <tr key={tenant.id} className="hover:bg-gray-50/50 transition-colors">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-700 text-white flex items-center justify-center font-extrabold text-sm shadow-sm shrink-0">
                                                        #{tenant.id}
                                                    </div>
                                                    <div>
                                                        <Link
                                                            href={route('superadmin.tenants.show', tenant.id)}
                                                            className="font-bold text-gray-800 hover:text-indigo-600 transition-colors"
                                                        >
                                                            {tenant.name}
                                                        </Link>
                                                        <span className="block text-xs text-gray-400 mt-0.5" dir="ltr">
                                                            {tenant.slug}.{typeof window !== 'undefined' ? window.location.host.replace('app.', '') : 'fast-order-eg.tech'}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div>
                                                    <p className="font-medium text-gray-700">{tenant.owner?.name || 'غير معروف'}</p>
                                                    <p className="text-xs text-gray-400">{tenant.owner?.email || tenant.email}</p>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col gap-1.5 items-start">
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 shadow-2xs">
                                                        <span>📦</span>
                                                        <span>{tenant.orders_count !== undefined ? tenant.orders_count : 0} طلب</span>
                                                    </span>
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 shadow-2xs">
                                                        <span>🛍️</span>
                                                        <span>{tenant.products_count !== undefined ? tenant.products_count : 0} منتج</span>
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {renderPlanBadge(planToShow, tenant.subscription_status)}
                                            </td>
                                            <td className="px-6 py-4 text-gray-700 font-semibold font-mono text-xs">
                                                {isCommission ? '-' : formatDate(tenant.subscription_ends_at)}
                                            </td>
                                            <td className="px-6 py-4">
                                                {renderStatusBadge(tenant, planToShow)}
                                            </td>
                                            <td className="px-6 py-4 text-left">
                                                <div className="flex items-center justify-end gap-2">
                                                    <div className="relative group inline-block text-left">
                                                        <button type="button" className="p-2 text-gray-500 hover:text-indigo-600 bg-gray-100 hover:bg-indigo-50 rounded-md transition-colors">
                                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                        <div className="absolute left-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 z-50">
                                                            <div className="py-1">
                                                                <a href={`${window.location.protocol}//${tenant.slug}.${window.location.host.replace('app.', '')}`} target="_blank" rel="noreferrer" className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-right">
                                                                    فتح واجهة المتجر
                                                                </a>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => window.open(route('superadmin.tenants.impersonate', tenant.id), '_blank')}
                                                                    className="block w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-right"
                                                                >
                                                                    دخول للوحة تحكم التاجر
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <Link
                                                        href={route('superadmin.tenants.show', tenant.id)}
                                                        className="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-xs font-semibold transition-colors"
                                                    >
                                                        تفاصيل
                                                    </Link>
                                                    <button
                                                        onClick={() => toggleStatus(tenant.id)}
                                                        className={`px-3 py-1.5 rounded-md text-xs font-semibold transition-colors text-white ${
                                                            tenant.is_active
                                                                ? 'bg-rose-500 hover:bg-rose-600'
                                                                : 'bg-emerald-500 hover:bg-emerald-600'
                                                        }`}
                                                    >
                                                        {tenant.is_active ? 'إيقاف' : 'تفعيل'}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td colSpan="6" className="px-6 py-12 text-center text-gray-400">
                                        لا توجد متاجر تطابق معايير البحث.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Section */}
                {tenants.links && tenants.links.length > 3 && (
                    <div className="p-6 border-t border-gray-100 flex items-center justify-between">
                        <div className="text-xs text-gray-500">
                            عرض {tenants.from || 0} إلى {tenants.to || 0} من إجمالي {tenants.total || 0} متجر
                        </div>
                        <div className="flex items-center gap-1">
                            {tenants.links.map((link, idx) => (
                                <Link
                                    key={idx}
                                    href={link.url || '#'}
                                    disabled={!link.url}
                                    className={`px-3 py-1.5 border text-xs font-medium rounded-md transition-all ${
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label
                                            .replace('Previous', 'السابق')
                                            .replace('Next', 'التالي')
                                    }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Create Tenant Modal */}
            {showCreateModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        {/* Background overlay */}
                        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onClick={() => setShowCreateModal(false)}></div>

                        {/* Modal panel */}
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <div>
                                <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 text-indigo-600">
                                    <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div className="mt-3 text-center sm:mt-5">
                                    <h3 className="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                        إنشاء متجر جديد
                                    </h3>
                                    <p className="text-xs text-gray-400 mt-1">
                                        أدخل تفاصيل المتجر الجديد والمالك لإنشاء الحساب فوراً.
                                    </p>
                                </div>
                            </div>

                            <form onSubmit={handleCreateSubmit} className="mt-5 space-y-4">
                                {Object.keys(createErrors).length > 0 && (
                                    <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold flex items-start gap-2 text-right">
                                        <svg className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <div>
                                            <p>تنبيه: يرجى مراجعة البيانات المحددة باللون الأحمر أدناه:</p>
                                            <ul className="list-disc list-inside mt-1 font-normal text-[11px] space-y-0.5">
                                                {Object.entries(createErrors).map(([k, err]) => (
                                                    <li key={k}>{err}</li>
                                                ))}
                                            </ul>
                                        </div>
                                    </div>
                                )}

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        اسم المتجر
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={createData.name}
                                        onChange={(e) => setCreateData({ ...createData, name: e.target.value })}
                                        placeholder="مثال: متجر الأمل للملابس"
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    />
                                    {createErrors.name && (
                                        <span className="text-xs text-rose-500 mt-1 block">{createErrors.name}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        رابط المتجر (Subdomain / Slug)
                                    </label>
                                    <div className="flex rounded-lg shadow-sm" dir="ltr">
                                        <input
                                            type="text"
                                            required
                                            value={createData.slug}
                                            onChange={(e) => setCreateData({ ...createData, slug: e.target.value })}
                                            placeholder="el-amel"
                                            className="w-full px-3 py-2 border border-gray-200 rounded-l-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-left"
                                        />
                                        <span className="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-200 bg-gray-50 text-gray-500 text-sm font-mono">
                                            .{typeof window !== 'undefined' ? window.location.host.replace('app.', '') : 'fastorder.localhost'}
                                        </span>
                                    </div>
                                    {createErrors.slug && (
                                        <span className="text-xs text-rose-500 mt-1 block">{createErrors.slug}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        اسم مالك المتجر
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={createData.owner_name}
                                        onChange={(e) => setCreateData({ ...createData, owner_name: e.target.value })}
                                        placeholder="مثال: محمد أحمد"
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    />
                                    {createErrors.owner_name && (
                                        <span className="text-xs text-rose-500 mt-1 block">{createErrors.owner_name}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        البريد الإلكتروني للمالك
                                    </label>
                                    <input
                                        type="email"
                                        required
                                        value={createData.email}
                                        onChange={(e) => setCreateData({ ...createData, email: e.target.value })}
                                        placeholder="owner@example.com"
                                        dir="ltr"
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-left"
                                    />
                                    {createErrors.email && (
                                        <span className="text-xs text-rose-500 mt-1 block">{createErrors.email}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        رقم الهاتف <span className="text-gray-400 font-normal">(اختياري)</span>
                                    </label>
                                    <input
                                        type="tel"
                                        value={createData.phone}
                                        onChange={(e) => setCreateData({ ...createData, phone: e.target.value })}
                                        placeholder="+201000000000"
                                        dir="ltr"
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-left"
                                    />
                                    {createErrors.phone && (
                                        <span className="text-xs text-rose-500 mt-1 block">{createErrors.phone}</span>
                                    )}
                                </div>
                                
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-1">
                                            الباقة <span className="text-gray-400 font-normal">(اختياري)</span>
                                        </label>
                                        <select
                                            value={createData.plan_id}
                                            onChange={(e) => setCreateData({ ...createData, plan_id: e.target.value })}
                                            style={{
                                                backgroundImage: `url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E")`,
                                                backgroundPosition: 'left 0.75rem center',
                                                backgroundSize: '1.25rem',
                                                backgroundRepeat: 'no-repeat',
                                            }}
                                            className="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all appearance-none"
                                        >
                                            <option value="">الاشتراك المجاني</option>
                                            {plans?.map((p) => (
                                                <option key={p.id} value={p.id}>{p.name}</option>
                                            ))}
                                        </select>
                                        {createErrors.plan_id && (
                                            <span className="text-xs text-rose-500 mt-1 block">{createErrors.plan_id}</span>
                                        )}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-1">
                                            تاريخ الانتهاء
                                        </label>
                                        <input
                                            type="date"
                                            value={createData.ends_at}
                                            onChange={(e) => setCreateData({ ...createData, ends_at: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-left"
                                            dir="ltr"
                                        />
                                        {createErrors.ends_at && (
                                            <span className="text-xs text-rose-500 mt-1 block">{createErrors.ends_at}</span>
                                        )}
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        كلمة مرور المالك
                                    </label>
                                    <div className="relative">
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            required
                                            value={createData.password}
                                            onChange={(e) => setCreateData({ ...createData, password: e.target.value })}
                                            placeholder="••••••••"
                                            dir="ltr"
                                            className="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-left"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                                        >
                                            {showPassword ? (
                                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            ) : (
                                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                    {createErrors.password && (
                                        <span className="text-xs text-rose-500 mt-1 block">{createErrors.password}</span>
                                    )}
                                </div>

                                <div className="mt-6 flex flex-row-reverse gap-2">
                                    <button
                                        type="submit"
                                        disabled={submitting}
                                        className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-bold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm disabled:opacity-50"
                                    >
                                        {submitting ? 'جاري الإنشاء...' : 'إنشاء المتجر'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowCreateModal(false);
                                            setCreateErrors({});
                                        }}
                                        className="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        إلغاء
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
            {/* Assign Subscription Modal */}
            {showAssignModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={() => setShowAssignModal(false)}></div>
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
                            <h3 className="text-lg leading-6 font-bold text-gray-900 mb-4">تعديل اشتراك المتجر</h3>
                            <form onSubmit={handleAssignSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">اختر الباقة</label>
                                    <select
                                        required
                                        value={assignData.plan_id}
                                        onChange={(e) => setAssignData({ ...assignData, plan_id: e.target.value })}
                                        style={{
                                            backgroundImage: `url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E")`,
                                            backgroundPosition: 'left 0.75rem center',
                                            backgroundSize: '1.25rem',
                                            backgroundRepeat: 'no-repeat',
                                        }}
                                        className="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm appearance-none"
                                    >
                                        <option value="">-- اختر باقة --</option>
                                        {plans?.map((p) => (
                                            <option key={p.id} value={p.id}>{p.name}</option>
                                        ))}
                                    </select>
                                    {assignErrors.plan_id && <span className="text-xs text-rose-500 mt-1 block">{assignErrors.plan_id}</span>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">تاريخ الانتهاء</label>
                                    <input
                                        type="date"
                                        required
                                        value={assignData.ends_at}
                                        onChange={(e) => setAssignData({ ...assignData, ends_at: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-left"
                                        dir="ltr"
                                    />
                                    {assignErrors.ends_at && <span className="text-xs text-rose-500 mt-1 block">{assignErrors.ends_at}</span>}
                                </div>
                                <div className="mt-6 flex flex-row-reverse gap-2">
                                    <button type="submit" disabled={assigning} className="w-full justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-bold text-white hover:bg-indigo-700 sm:text-sm disabled:opacity-50">
                                        {assigning ? 'جاري الحفظ...' : 'حفظ التعديلات'}
                                    </button>
                                    <button type="button" onClick={() => setShowAssignModal(false)} className="w-full justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:text-sm">
                                        إلغاء
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </SuperAdminLayout>
    );
}

