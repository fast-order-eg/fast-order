import React from 'react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ tenant, settings, plans, productsCount = 0, ordersCount = 0, walletTransactions = [] }) {
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isWalletModalOpen, setIsWalletModalOpen] = useState(false);
    const [isDeductWalletModalOpen, setIsDeductWalletModalOpen] = useState(false);
    const [isAssignModalOpen, setIsAssignModalOpen] = useState(false);

    const { data: assignData, setData: setAssignData, post: postAssign, processing: assigning, errors: assignErrors, reset: resetAssign } = useForm({
        plan_id: '',
        ends_at: '',
    });

    const { data: walletData, setData: setWalletData, post: postWallet, processing: walletProcessing, errors: walletErrors, reset: resetWallet } = useForm({
        amount: '',
        note: '',
    });

    const { data: deductWalletData, setData: setDeductWalletData, post: postDeductWallet, processing: deductWalletProcessing, errors: deductWalletErrors, reset: resetDeductWallet } = useForm({
        amount: '',
        note: '',
    });

    const handleDeductWalletSubmit = (e) => {
        e.preventDefault();
        postDeductWallet(route('superadmin.tenants.deduct-wallet-balance', tenant.id), {
            preserveScroll: true,
            onSuccess: () => {
                setIsDeductWalletModalOpen(false);
                resetDeductWallet();
            },
        });
    };

    const initialCampaignIds = Array.isArray(tenant.meta_campaign_ids)
        ? tenant.meta_campaign_ids.join(', ')
        : (tenant.meta_campaign_ids || '');

    const {
        data: metaData,
        setData: setMetaData,
        post: postMetaCampaigns,
        processing: savingMeta,
        errors: metaErrors,
    } = useForm({
        meta_campaign_ids: initialCampaignIds,
    });

    const handleMetaSubmit = (e) => {
        e.preventDefault();
        postMetaCampaigns(route('superadmin.tenants.update-meta-campaigns', tenant.id), {
            preserveScroll: true,
        });
    };

    const handleOpenAssignModal = () => {
        const activeSub = tenant.subscriptions?.find(s => s.status === 'active') || tenant.subscriptions?.[0];
        const currentPlanId = activeSub ? activeSub.plan_id : (plans?.[0]?.id || '');
        const currentEndsAt = activeSub?.ends_at 
            ? activeSub.ends_at.substring(0, 10) 
            : (tenant.subscription_ends_at ? tenant.subscription_ends_at.substring(0, 10) : new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0]);
        setAssignData({
            plan_id: currentPlanId,
            ends_at: currentEndsAt,
        });
        setIsAssignModalOpen(true);
    };

    const handlePlanChange = (planId) => {
        const selectedPlan = plans?.find(p => String(p.id) === String(planId));
        let daysToAdd = 30;
        if (selectedPlan) {
            if (selectedPlan.slug === 'free' || selectedPlan.name?.includes('مجانية')) {
                daysToAdd = 7;
            } else if (selectedPlan.slug === 'yearly' || selectedPlan.name?.includes('سنوية')) {
                daysToAdd = 365;
            } else if (selectedPlan.slug === 'monthly' || selectedPlan.name?.includes('شهرية')) {
                daysToAdd = 30;
            } else if (selectedPlan.slug === 'commission' || selectedPlan.name?.includes('عمولة')) {
                daysToAdd = 3650;
            } else {
                daysToAdd = 30;
            }
        }
        const futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + daysToAdd);
        const formattedDate = futureDate.toISOString().split('T')[0];

        setAssignData(prev => ({
            ...prev,
            plan_id: planId,
            ends_at: formattedDate,
        }));
    };

    const handleAssignSubmit = (e) => {
        e.preventDefault();
        postAssign(route('superadmin.tenants.assign-subscription', tenant.id), {
            preserveScroll: true,
            onSuccess: () => {
                setIsAssignModalOpen(false);
                resetAssign();
            },
        });
    };

    const handleWalletSubmit = (e) => {
        e.preventDefault();
        postWallet(route('superadmin.tenants.add-wallet-balance', tenant.id), {
            preserveScroll: true,
            onSuccess: () => {
                setIsWalletModalOpen(false);
                resetWallet();
            },
        });
    };

    const handleDelete = () => {
        router.delete(route('superadmin.tenants.destroy', tenant.id), {
            preserveScroll: true,
        });
    };

    const toggleStatus = () => {
        if (confirm('هل أنت متأكد من تغيير حالة هذا المتجر؟')) {
            router.patch(
                route('superadmin.tenants.toggle-status', tenant.id),
                {},
                { preserveScroll: true }
            );
        }
    };

    // Group settings by their group field or list them
    const groupedSettings = settings.reduce((acc, setting) => {
        const groupName = setting.group || 'عام';
        if (!acc[groupName]) {
            acc[groupName] = [];
        }
        acc[groupName].push(setting);
        return acc;
    }, {});

    const groupNamesAr = {
        general: 'إعدادات عامة',
        appearance: 'المظهر والتصميم',
        pixels: 'أكواد التتبع والبيكسل',
        social: 'روابط التواصل الاجتماعي',
        shipping: 'الشحن والتوصيل',
        payment: 'بوابات الدفع',
    };

    const activeSub = tenant.subscriptions?.find(s => s.status === 'active') || tenant.subscriptions?.[tenant.subscriptions?.length - 1];
    const freePlan = plans?.find(p => p.slug === 'free' || p.name?.includes('مجانية')) || plans?.[0];
    const activePlan = activeSub?.plan || plans?.find(p => p.id === activeSub?.plan_id) || (tenant.subscription_status === 'trial' ? freePlan : null);
    const isCommissionPlan = activePlan?.slug === 'commission' || activePlan?.name?.includes('عمولة') || activePlan?.name?.includes('محفظة');
    const activeEndsAt = activeSub?.ends_at || activeSub?.trial_ends_at || tenant.subscription_ends_at || tenant.trial_ends_at;

    return (
        <SuperAdminLayout>
            <Head title={`تفاصيل المتجر: ${tenant.name}`} />

            {/* Back link */}
            <div className="mb-6">
                <Link
                    href={route('superadmin.tenants.index')}
                    className="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-indigo-600 transition-colors gap-2"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                    <span>العودة لقائمة المتاجر</span>
                </Link>
            </div>

            {/* Top Store Summary Card */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div className="flex items-center gap-4">
                        <div className="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center font-bold text-2xl text-gray-700 overflow-hidden">
                            {tenant.logo ? (
                                <img src={`/storage/${tenant.logo}`} alt={tenant.name} className="w-full h-full object-cover" />
                            ) : (
                                tenant.name.substring(0, 2).toUpperCase()
                            )}
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-bold text-gray-800">{tenant.name}</h1>
                                {tenant.is_active ? (
                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        نشط
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-100">
                                        موقوف
                                    </span>
                                )}
                            </div>
                            <p className="text-sm text-gray-400 mt-1">
                                {tenant.slug}.{typeof window !== 'undefined' ? window.location.host.replace('app.', '') : 'fastorder.localhost'}
                                {tenant.custom_domain && <span className="mr-3 font-semibold text-indigo-600">({tenant.custom_domain})</span>}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 w-full md:w-auto">
                        <a
                            href={`${typeof window !== 'undefined' ? window.location.protocol : 'http:'}//${tenant.slug}.${typeof window !== 'undefined' ? window.location.host.replace('app.', '') : 'fastorder.localhost'}/shop/index.html`}
                            target="_blank"
                            rel="noreferrer"
                            className="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs sm:text-sm font-semibold transition-colors flex items-center justify-center gap-1.5 shadow-2xs"
                        >
                            <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            فتح واجهة المتجر
                        </a>

                        <button
                            type="button"
                            onClick={() => window.open(route('superadmin.tenants.impersonate', tenant.id), '_blank')}
                            className="px-3.5 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg text-xs sm:text-sm font-semibold transition-colors flex items-center justify-center gap-1.5 shadow-2xs"
                        >
                            <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                            دخول لوحة التاجر
                        </button>

                        <button
                            onClick={handleOpenAssignModal}
                            className="px-3.5 py-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg text-xs sm:text-sm font-semibold transition-colors flex items-center justify-center gap-1.5"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            تعديل الاشتراك
                        </button>
                        <button
                            onClick={() => setIsDeleteModalOpen(true)}
                            className="px-3.5 py-2 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg text-xs sm:text-sm font-semibold transition-colors flex items-center justify-center"
                        >
                            حذف المتجر
                        </button>
                        <button
                            onClick={toggleStatus}
                            className={`px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 ${
                                tenant.is_active
                                    ? 'bg-rose-500 hover:bg-rose-600 text-white focus:ring-rose-500'
                                    : 'bg-emerald-500 hover:bg-emerald-600 text-white focus:ring-emerald-500'
                            }`}
                        >
                            {tenant.is_active ? 'إيقاف المتجر مؤقتاً' : 'تفعيل المتجر'}
                        </button>
                    </div>
                </div>
            </div>

            {/* Quick Stats Grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg">
                        📦
                    </div>
                    <div>
                        <span className="text-xs text-gray-400 font-medium block">عدد المنتجات</span>
                        <span className="text-lg font-bold text-gray-800">{productsCount} منتج</span>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                        🛒
                    </div>
                    <div>
                        <span className="text-xs text-gray-400 font-medium block">إجمالي الطلبات</span>
                        <span className="text-lg font-bold text-gray-800">{ordersCount} طلب</span>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg">
                        💰
                    </div>
                    <div>
                        <span className="text-xs text-gray-400 font-medium block">رصيد المحفظة</span>
                        <span className="text-lg font-bold text-gray-800">{Math.round(tenant.wallet_balance || 0)} ج.م</span>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-lg">
                        ⚡
                    </div>
                    <div>
                        <span className="text-xs text-gray-400 font-medium block">حالة المتجر</span>
                        <span className={`text-sm font-bold ${tenant.is_active ? 'text-emerald-600' : 'text-rose-600'}`}>
                            {tenant.is_active ? 'نشط' : 'موقوف'}
                        </span>
                    </div>
                </div>
            </div>

            {/* Main Details Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {/* Column 1: Store & Owner Info */}
                <div className="lg:col-span-1 space-y-6">
                    {/* Store Info Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 className="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-4">بيانات المتجر</h3>
                        <div className="space-y-4 text-sm">
                            <div>
                                <span className="block text-gray-400 font-medium">تاريخ التسجيل</span>
                                <span className="text-gray-700 font-semibold">
                                    {new Date(tenant.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                                </span>
                            </div>
                            <div className="pt-2 border-t border-gray-100">
                                <span className="block text-gray-400 font-medium mb-1">رصيد المحفظة</span>
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-emerald-600 font-bold text-lg">{Math.round(tenant.wallet_balance || 0)} ج.م</span>
                                    <div className="flex items-center gap-1.5">
                                        <button 
                                            onClick={() => setIsWalletModalOpen(true)}
                                            className="px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-100 rounded text-xs font-bold transition-colors flex items-center gap-1"
                                        >
                                            <span>+</span> إضافة
                                        </button>
                                        <button 
                                            onClick={() => setIsDeductWalletModalOpen(true)}
                                            className="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-100 rounded text-xs font-bold transition-colors flex items-center gap-1"
                                        >
                                            <span>-</span> خصم
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Owner Info Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 className="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-4">بيانات مالك المتجر</h3>
                        <div className="space-y-4 text-sm">
                            <div>
                                <span className="block text-gray-400 font-medium">الاسم بالكامل</span>
                                <span className="text-gray-700 font-semibold">{tenant.owner?.name || 'غير معروف'}</span>
                            </div>
                            <div>
                                <span className="block text-gray-400 font-medium">البريد الإلكتروني للعميل</span>
                                <span className="text-gray-700 font-semibold">{tenant.owner?.email || tenant.email || '-'}</span>
                            </div>
                            <div>
                                <span className="block text-gray-400 font-medium">رقم الهاتف</span>
                                <span className="text-gray-700 font-semibold">{tenant.owner?.phone || tenant.phone || '-'}</span>
                            </div>
                            <div>
                                <span className="block text-gray-400 font-medium">تاريخ إنشاء حساب العميل</span>
                                <span className="text-gray-700 font-semibold">
                                    {tenant.owner?.created_at ? (
                                        new Date(tenant.owner.created_at).toLocaleDateString('en-US')
                                    ) : (
                                        '-'
                                    )}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Meta Ads Campaign IDs Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div className="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                            <div className="flex items-center gap-2">
                                <span className="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                    📢
                                </span>
                                <div>
                                    <h3 className="text-sm font-bold text-gray-800">إعلانات فيسبوك (Meta Ads)</h3>
                                    <p className="text-[11px] text-gray-500">ربط معرّفات الحملات للمتجر</p>
                                </div>
                            </div>
                            {tenant.meta_campaign_ids && tenant.meta_campaign_ids.length > 0 ? (
                                <span className="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    مربوط ({tenant.meta_campaign_ids.length}) 🟢
                                </span>
                            ) : (
                                <span className="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-50 text-gray-500 border border-gray-200">
                                    غير مربوط ⚪
                                </span>
                            )}
                        </div>

                        <form onSubmit={handleMetaSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 mb-1">
                                    معرّفات الحملات (Campaign IDs)
                                </label>
                                <textarea
                                    rows={3}
                                    value={metaData.meta_campaign_ids}
                                    onChange={(e) => setMetaData('meta_campaign_ids', e.target.value)}
                                    placeholder="أدخل معرّفات الحملات مفصولة بفواصل أو أسطر، مثال: 120252521017770114"
                                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dir-ltr text-left"
                                />
                                {metaErrors?.meta_campaign_ids && (
                                    <p className="text-xs text-red-600 mt-1">{metaErrors.meta_campaign_ids}</p>
                                )}
                                <p className="text-[11px] text-gray-400 mt-1.5 leading-relaxed">
                                    يتم جلب الإحصائيات (المصروف، النتائج، CPA، ROAS، ومعاينة الإعلانات) تلقائياً من منصة الإعلانات وعرضها في لوحة التاجر.
                                </p>
                            </div>

                            {Array.isArray(tenant.meta_campaign_ids) && tenant.meta_campaign_ids.length > 0 && (
                                <div className="flex flex-wrap gap-1.5 pt-1">
                                    {tenant.meta_campaign_ids.map((id, idx) => (
                                        <span key={idx} className="font-mono text-[11px] bg-blue-50 text-blue-800 px-2 py-0.5 rounded-md border border-blue-100 select-all">
                                            {id}
                                        </span>
                                    ))}
                                </div>
                            )}

                            <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                                <button
                                    type="submit"
                                    disabled={savingMeta}
                                    className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-colors disabled:opacity-50 shadow-sm"
                                >
                                    {savingMeta ? 'جاري الحفظ...' : 'حفظ الحملات'}
                                </button>

                                {tenant.meta_campaign_ids && tenant.meta_campaign_ids.length > 0 && (
                                    <a
                                        href={`${typeof window !== 'undefined' ? window.location.protocol : 'http:'}//${tenant.slug}.${typeof window !== 'undefined' ? window.location.host.replace('app.', '') : 'fastorder.localhost'}/admin/marketing/ads`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1"
                                    >
                                        <span>معاينة لوحة الإعلانات</span>
                                        <span>↗</span>
                                    </a>
                                )}
                            </div>
                        </form>
                    </div>
                </div>

                {/* Column 2 & 3: Subscription History & Settings */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Active Subscription Status Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 className="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-4">الاشتراك الحالي والفعال</h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div className="bg-indigo-50/60 p-4 rounded-xl border border-indigo-100/60">
                                <span className="block text-indigo-600 font-semibold text-xs mb-1">نوع باقة الاشتراك</span>
                                <span className="text-base font-extrabold text-indigo-950 block truncate">
                                    {activePlan ? activePlan.name : 'غير محدد'}
                                </span>
                            </div>
                            <div className="bg-emerald-50/60 p-4 rounded-xl border border-emerald-100/60">
                                <span className="block text-emerald-600 font-semibold text-xs mb-1">حالة الاشتراك</span>
                                <span className="text-base font-extrabold text-emerald-950 block">
                                    {tenant.subscription_status === 'active' 
                                        ? 'نشط 🟢' 
                                        : tenant.subscription_status === 'trial' 
                                        ? 'فترة تجريبية ⏳' 
                                        : 'منتهي / غير نشط 🔴'}
                                </span>
                            </div>
                            {isCommissionPlan ? (
                                <div className="bg-purple-50/60 p-4 rounded-xl border border-purple-100/60">
                                    <span className="block text-purple-600 font-semibold text-xs mb-1">صلاحية الاشتراك</span>
                                    <span className="text-xs font-extrabold text-purple-950 block mt-1 leading-relaxed">
                                        دائم (يعتمد على رصيد المحفظة 💰)
                                    </span>
                                </div>
                            ) : (
                                <div className="bg-amber-50/60 p-4 rounded-xl border border-amber-100/60">
                                    <span className="block text-amber-600 font-semibold text-xs mb-1">تاريخ انتهاء الاشتراك</span>
                                    <span className="text-base font-extrabold text-amber-950 block mt-1">
                                        {activeEndsAt 
                                            ? new Date(activeEndsAt).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
                                            : 'غير محدد'}
                                    </span>
                                </div>
                            )}
                        </div>

                        {/* Subscription History Table */}
                        <div className="mt-8">
                            <h4 className="text-md font-bold text-gray-700 mb-4">سجل الاشتراكات</h4>
                            <div className="overflow-x-auto">
                                <table className="w-full text-right border-collapse">
                                    <thead>
                                        <tr className="bg-gray-50 text-gray-500 text-xs font-semibold uppercase border-b border-gray-100">
                                            <th className="px-4 py-3">الخطة</th>
                                            <th className="px-4 py-3">دورة الدفع</th>
                                            <th className="px-4 py-3">المبلغ</th>
                                            <th className="px-4 py-3">تاريخ البدء</th>
                                            <th className="px-4 py-3">تاريخ الانتهاء</th>
                                            <th className="px-4 py-3">الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 text-xs">
                                        {tenant.subscriptions && tenant.subscriptions.length > 0 ? (
                                            tenant.subscriptions.map((sub) => (
                                                <tr key={sub.id} className="hover:bg-gray-50/30 transition-colors">
                                                    <td className="px-4 py-3 font-semibold text-gray-800">{sub.plan?.name || 'غير معروف'}</td>
                                                    <td className="px-4 py-3 text-gray-600">
                                                        {sub.plan?.slug === 'commission' || sub.plan?.name?.includes('عمولة') ? 'بالعمولة' : (sub.billing_cycle === 'yearly' ? 'سنوي' : 'شهري')}
                                                    </td>
                                                    <td className="px-4 py-3 font-medium text-gray-800">{Math.round(sub.price)} ج.م</td>
                                                    <td className="px-4 py-3 text-gray-600">
                                                        {new Date(sub.starts_at).toLocaleDateString('en-US')}
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-600 font-semibold">
                                                        {sub.plan?.slug === 'commission' || sub.plan?.name?.includes('عمولة') ? (
                                                            <span className="text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-100">دائم (رصيد المحفظة)</span>
                                                        ) : sub.ends_at ? (
                                                            new Date(sub.ends_at).toLocaleDateString('en-US')
                                                        ) : (
                                                            'غير محدد'
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {sub.status === 'active' ? (
                                                            <span className="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-full">نشط</span>
                                                        ) : sub.status === 'expired' ? (
                                                            <span className="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-100 rounded-full">منتهي</span>
                                                        ) : (
                                                            <span className="px-2 py-0.5 bg-gray-50 text-gray-700 border border-gray-100 rounded-full">{sub.status}</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="6" className="px-4 py-6 text-center text-gray-400">
                                                    لا توجد اشتراكات سابقة مسجلة.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Wallet Transactions Audit Log Table */}
                        <div className="mt-8 border-t border-gray-100 pt-6">
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="text-md font-bold text-gray-800 flex items-center gap-2">
                                    <span>💳</span> سجل معاملات المحفظة والعمليات المالية
                                </h4>
                                <span className="text-xs text-gray-400 font-semibold bg-gray-50 px-2.5 py-1 rounded-full border border-gray-100">
                                    إجمالي المعاملات: {walletTransactions ? walletTransactions.length : 0}
                                </span>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-right border-collapse">
                                    <thead>
                                        <tr className="bg-gray-50 text-gray-500 text-xs font-semibold uppercase border-b border-gray-100">
                                            <th className="px-4 py-3">البيان والتفاصيل</th>
                                            <th className="px-4 py-3">المبلغ</th>
                                            <th className="px-4 py-3">نوع العملية</th>
                                            <th className="px-4 py-3">بواسطة</th>
                                            <th className="px-4 py-3">التاريخ والوقت</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 text-xs">
                                        {walletTransactions && walletTransactions.length > 0 ? (
                                            walletTransactions.map((tx) => (
                                                <tr key={tx.id} className="hover:bg-gray-50/30 transition-colors">
                                                    <td className="px-4 py-3 font-semibold text-gray-800" title={tx.description}>
                                                        {tx.description}
                                                    </td>
                                                    <td className="px-4 py-3 font-extrabold text-sm">
                                                        <span className={tx.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'}>
                                                            {tx.type === 'credit' ? '+' : ''}{Math.round(tx.amount)} ج.م
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 font-semibold">
                                                        {tx.type === 'credit' ? (
                                                            <span className="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-md">إضافة رصيد</span>
                                                        ) : (
                                                            <span className="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-100 rounded-md">خصم رصيد</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-700 font-medium">
                                                        {tx.creator?.name || 'Super Admin'}
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-500 font-mono">
                                                        {new Date(tx.created_at).toLocaleString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true })}
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="5" className="px-4 py-6 text-center text-gray-400">
                                                    لا توجد معاملات رصيد سابقة على هذا المتجر.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {/* Settings Details Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 className="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-4">إعدادات المتجر الحالية</h3>
                        
                        {Object.keys(groupedSettings).length > 0 ? (
                            <div className="space-y-6">
                                {Object.entries(groupedSettings).map(([group, list]) => (
                                    <div key={group} className="border border-gray-100 rounded-xl p-4">
                                        <h4 className="font-bold text-sm text-indigo-900 bg-indigo-50 px-3 py-1.5 rounded-lg inline-block mb-4">
                                            {groupNamesAr[group] || group}
                                        </h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                            {list.map((setting) => (
                                                <div key={setting.id} className="flex justify-between border-b border-gray-50 pb-2">
                                                    <span className="text-gray-400 font-medium">{setting.key}</span>
                                                    <span className="text-gray-800 font-semibold max-w-[200px] truncate select-all" title={setting.value}>
                                                        {setting.value || 'فارغ'}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-center py-6 text-sm text-gray-400">لا توجد إعدادات مخصصة لهذا المتجر حالياً.</p>
                        )}
                    </div>
                </div>

            </div>

            {/* Delete Modal */}
            {isDeleteModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
                        <div className="p-6 border-b border-gray-100">
                            <h3 className="text-lg font-bold text-gray-800">تحذير: حذف المتجر نهائياً</h3>
                        </div>
                        <div className="p-6">
                            <p className="text-gray-600 mb-4">
                                هل أنت متأكد أنك تريد حذف المتجر <strong>{tenant.name}</strong> بجميع بياناته، والمنتجات، والطلبات، وحساب المالك؟
                            </p>
                            <p className="text-red-600 font-semibold text-sm">
                                هذا الإجراء لا يمكن التراجع عنه بأي شكل من الأشكال!
                            </p>
                        </div>
                        <div className="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                            <button
                                onClick={() => setIsDeleteModalOpen(false)}
                                className="px-5 py-2 text-gray-600 hover:bg-gray-200 bg-gray-100 rounded-lg font-semibold transition-colors"
                            >
                                إلغاء
                            </button>
                            <button
                                onClick={handleDelete}
                                className="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold shadow-md transition-colors"
                            >
                                نعم، احذف المتجر
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Wallet Add Modal */}
            {isWalletModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
                        <form onSubmit={handleWalletSubmit}>
                            <div className="p-6 border-b border-gray-100 flex items-center justify-between">
                                <h3 className="text-lg font-bold text-emerald-800 flex items-center gap-2">
                                    <span className="w-3 h-3 rounded-full bg-emerald-500"></span>
                                    إضافة رصيد محفظة للمتجر
                                </h3>
                                <button type="button" onClick={() => setIsWalletModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            <div className="p-6 space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        المبلغ المراد إضافته (جنيه مصري)
                                    </label>
                                    <input
                                        type="number"
                                        required
                                        min="1"
                                        step="1"
                                        value={walletData.amount}
                                        onChange={(e) => setWalletData('amount', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                                        placeholder="مثال: 50"
                                    />
                                    {walletErrors.amount && <span className="text-xs text-red-500 mt-1 block">{walletErrors.amount}</span>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        ملاحظة / سبب الإضافة (اختياري)
                                    </label>
                                    <input
                                        type="text"
                                        value={walletData.note || ''}
                                        onChange={(e) => setWalletData('note', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"
                                        placeholder="مثال: شحن رصيد يدوياً"
                                    />
                                </div>
                            </div>
                            <div className="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setIsWalletModalOpen(false);
                                        resetWallet();
                                    }}
                                    className="px-5 py-2 text-gray-600 hover:bg-gray-200 bg-gray-100 rounded-lg font-semibold transition-colors"
                                    disabled={walletProcessing}
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    disabled={walletProcessing}
                                    className="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold shadow-md transition-colors disabled:opacity-50"
                                >
                                    {walletProcessing ? 'جاري الإضافة...' : 'تأكيد إضافة الرصيد'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Wallet Deduct Modal */}
            {isDeductWalletModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
                        <form onSubmit={handleDeductWalletSubmit}>
                            <div className="p-6 border-b border-gray-100 flex items-center justify-between">
                                <h3 className="text-lg font-bold text-rose-800 flex items-center gap-2">
                                    <span className="w-3 h-3 rounded-full bg-rose-500"></span>
                                    خصم رصيد من محفظة المتجر
                                </h3>
                                <button type="button" onClick={() => setIsDeductWalletModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            <div className="p-6 space-y-4">
                                <div className="bg-amber-50 p-3 rounded-lg border border-amber-100 text-xs text-amber-800 font-semibold">
                                    💰 رصيد المحفظة الحالي للمتجر: <strong>{Math.round(tenant.wallet_balance || 0)} ج.م</strong>
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        المبلغ المراد خصمه (جنيه مصري)
                                    </label>
                                    <input
                                        type="number"
                                        required
                                        min="1"
                                        max={tenant.wallet_balance || 999999}
                                        step="1"
                                        value={deductWalletData.amount}
                                        onChange={(e) => setDeductWalletData('amount', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all"
                                        placeholder="مثال: 50"
                                    />
                                    {deductWalletErrors.amount && <span className="text-xs text-red-500 mt-1 block">{deductWalletErrors.amount}</span>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        ملاحظة / سبب الخصم (اختياري)
                                    </label>
                                    <input
                                        type="text"
                                        value={deductWalletData.note || ''}
                                        onChange={(e) => setDeductWalletData('note', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"
                                        placeholder="مثال: تسوية حسابات أو خصم عمولة"
                                    />
                                </div>
                            </div>
                            <div className="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setIsDeductWalletModalOpen(false);
                                        resetDeductWallet();
                                    }}
                                    className="px-5 py-2 text-gray-600 hover:bg-gray-200 bg-gray-100 rounded-lg font-semibold transition-colors"
                                    disabled={deductWalletProcessing}
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    disabled={deductWalletProcessing}
                                    className="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold shadow-md transition-colors disabled:opacity-50"
                                >
                                    {deductWalletProcessing ? 'جاري الخصم...' : 'تأكيد خصم الرصيد'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Assign Subscription Modal */}
            {isAssignModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
                        <form onSubmit={handleAssignSubmit}>
                            <div className="p-6 border-b border-gray-100 flex items-center justify-between">
                                <h3 className="text-lg font-bold text-gray-800">تعديل اشتراك المتجر</h3>
                                <button type="button" onClick={() => setIsAssignModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            <div className="p-6 space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">اختر الباقة</label>
                                    <select
                                        required
                                        value={assignData.plan_id}
                                        onChange={(e) => handlePlanChange(e.target.value)}
                                        style={{
                                            backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E")`,
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
                                    {assignErrors.plan_id && <span className="text-xs text-red-500 mt-1 block">{assignErrors.plan_id}</span>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">تاريخ انتهاء الاشتراك</label>
                                    {plans?.find(p => String(p.id) === String(assignData.plan_id))?.slug === 'commission' || plans?.find(p => String(p.id) === String(assignData.plan_id))?.name?.includes('عمولة') ? (
                                        <div className="p-3 bg-purple-50 border border-purple-100 rounded-lg text-purple-800 text-xs font-semibold leading-relaxed">
                                            ⚡ <strong>باقة العمولة (شحن المحفظة):</strong> تعمل هذه الباقة بشكل دائم دون تاريخ انتهاء محدد، وتعتمد مستمراً على وجود رصيد بمحفظة التاجر.
                                        </div>
                                    ) : (
                                        <input
                                            type="date"
                                            required
                                            value={assignData.ends_at}
                                            onChange={(e) => setAssignData('ends_at', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"
                                        />
                                    )}
                                    {assignErrors.ends_at && <span className="text-xs text-red-500 mt-1 block">{assignErrors.ends_at}</span>}
                                </div>
                            </div>
                            <div className="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                                <button type="button" onClick={() => setIsAssignModalOpen(false)} className="px-5 py-2 text-gray-600 hover:bg-gray-200 bg-gray-100 rounded-lg font-semibold transition-colors" disabled={assigning}>
                                    إلغاء
                                </button>
                                <button type="submit" className="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition-colors disabled:opacity-50" disabled={assigning}>
                                    {assigning ? 'جاري الحفظ...' : 'حفظ التعديل'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </SuperAdminLayout>
    );
}

