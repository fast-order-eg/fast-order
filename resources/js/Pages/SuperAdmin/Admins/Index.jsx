import React, { useState } from 'react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import { Head, useForm, router } from '@inertiajs/react';

export default function Index({ admins, currentAdmin }) {
    const [activeTab, setActiveTab] = useState('profile'); // 'profile' or 'admins'
    const [showAddModal, setShowAddModal] = useState(false);
    const [showProfilePassword, setShowProfilePassword] = useState(false);
    const [showNewAdminPassword, setShowNewAdminPassword] = useState(false);

    // Profile update form
    const profileForm = useForm({
        name: currentAdmin?.name || '',
        email: currentAdmin?.email || '',
        phone: currentAdmin?.phone || '',
        password: '',
        password_confirmation: '',
    });

    const handleProfileSubmit = (e) => {
        e.preventDefault();
        profileForm.put('/admins/profile', {
            preserveScroll: true,
            onSuccess: () => {
                profileForm.reset('password', 'password_confirmation');
                setShowProfilePassword(false);
            },
        });
    };

    // New Admin form
    const newAdminForm = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
    });

    const handleAddAdminSubmit = (e) => {
        e.preventDefault();
        if (newAdminForm.data.password.length < 8) {
            newAdminForm.setError('password', 'يجب ألا تقل كلمة المرور عن 8 أحرف أو أرقام.');
            return;
        }

        newAdminForm.post('/admins', {
            preserveScroll: true,
            onSuccess: () => {
                setShowAddModal(false);
                newAdminForm.reset();
            },
        });
    };

    const handleDeleteAdmin = (admin) => {
        if (admin.id === currentAdmin.id) {
            alert('لا يمكنك حذف حسابك الشخصي الحالي.');
            return;
        }
        if (confirm(`هل أنت متأكد من حذف المشرف "${admin.name}"؟ لن يتمكن من الدخول للوحة التحكم بعد الآن.`)) {
            router.delete(`/admins/${admin.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <SuperAdminLayout>
            <Head title="إدارة المشرفين والملف الشخصي" />

            <div className="max-w-5xl mx-auto space-y-6">
                {/* Header Section */}
                <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-gray-800">إدارة الحساب والمشرفين (Super Admins)</h2>
                        <p className="text-sm text-gray-500 mt-1">تعديل بياناتك الشخصية، أو إضافة وتعيين مشرفين جدد للوحة التحكم.</p>
                    </div>

                    {/* Tabs switcher */}
                    <div className="flex bg-gray-100/80 p-1 rounded-xl shrink-0 self-start sm:self-auto">
                        <button
                            type="button"
                            onClick={() => setActiveTab('profile')}
                            className={`px-4 py-2 rounded-lg text-sm font-bold transition-all ${
                                activeTab === 'profile'
                                    ? 'bg-white text-indigo-600 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'
                            }`}
                        >
                            👤 بيانات حسابي
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('admins')}
                            className={`px-4 py-2 rounded-lg text-sm font-bold transition-all ${
                                activeTab === 'admins'
                                    ? 'bg-white text-indigo-600 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'
                            }`}
                        >
                            👥 المشرفين ({admins?.length || 1})
                        </button>
                    </div>
                </div>

                {/* Tab 1: Profile Settings */}
                {activeTab === 'profile' && (
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div className="p-6 border-b border-gray-100 flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                👤
                            </div>
                            <div>
                                <h3 className="text-base font-bold text-gray-800">تعديل البيانات الشخصية</h3>
                                <p className="text-xs text-gray-400">تغيير الاسم، البريد الإلكتروني، أو تعيين كلمة مرور جديدة لحسابك الحالي.</p>
                            </div>
                        </div>

                        <form onSubmit={handleProfileSubmit} className="p-6 space-y-5">
                            {profileForm.recentlySuccessful && (
                                <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm font-semibold flex items-center gap-2">
                                    <svg className="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    تم حفظ وتحديث بيانات حسابك بنجاح!
                                </div>
                            )}

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        الاسم الكامل <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={profileForm.data.name}
                                        onChange={(e) => profileForm.setData('name', e.target.value)}
                                        className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="مثال: الإدارة العامة"
                                    />
                                    {profileForm.errors.name && (
                                        <span className="text-xs text-rose-500 mt-1 block">{profileForm.errors.name}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        البريد الإلكتروني <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        required
                                        dir="ltr"
                                        value={profileForm.data.email}
                                        onChange={(e) => profileForm.setData('email', e.target.value)}
                                        className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-left"
                                        placeholder="admin@example.com"
                                    />
                                    {profileForm.errors.email && (
                                        <span className="text-xs text-rose-500 mt-1 block">{profileForm.errors.email}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        رقم الهاتف <span className="text-gray-400 font-normal">(اختياري)</span>
                                    </label>
                                    <input
                                        type="tel"
                                        dir="ltr"
                                        value={profileForm.data.phone}
                                        onChange={(e) => profileForm.setData('phone', e.target.value)}
                                        className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-left"
                                        placeholder="+201000000000"
                                    />
                                    {profileForm.errors.phone && (
                                        <span className="text-xs text-rose-500 mt-1 block">{profileForm.errors.phone}</span>
                                    )}
                                </div>
                            </div>

                            <div className="pt-4 border-t border-gray-100">
                                <div className="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 className="text-sm font-bold text-gray-800">تغيير كلمة المرور</h4>
                                        <p className="text-xs text-gray-400">اترك الحقول فارغة إذا كنت لا ترغب في تغيير كلمة المرور.</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setShowProfilePassword(!showProfilePassword)}
                                        className="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                    >
                                        {showProfilePassword ? 'إخفاء الحقول' : 'تغيير كلمة المرور'}
                                    </button>
                                </div>

                                {showProfilePassword && (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5 mt-3 bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                                                كلمة المرور الجديدة (8 خانات أو أكثر)
                                            </label>
                                            <input
                                                type="password"
                                                dir="ltr"
                                                value={profileForm.data.password}
                                                onChange={(e) => profileForm.setData('password', e.target.value)}
                                                className="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-left"
                                                placeholder="••••••••"
                                            />
                                            {profileForm.errors.password && (
                                                <span className="text-xs text-rose-500 mt-1 block">{profileForm.errors.password}</span>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                                                تأكيد كلمة المرور الجديدة
                                            </label>
                                            <input
                                                type="password"
                                                dir="ltr"
                                                value={profileForm.data.password_confirmation}
                                                onChange={(e) => profileForm.setData('password_confirmation', e.target.value)}
                                                className="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-left"
                                                placeholder="••••••••"
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="pt-3 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={profileForm.processing}
                                    className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 flex items-center gap-2"
                                >
                                    {profileForm.processing ? 'جاري الحفظ...' : 'حفظ التعديلات'}
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Tab 2: Super Admins List & Add New */}
                {activeTab === 'admins' && (
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div className="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h3 className="text-base font-bold text-gray-800">قائمة المشرفين (Super Admins)</h3>
                                <p className="text-xs text-gray-400 mt-0.5">المستخدمون المصرح لهم بالدخول وإدارة لوحة تحكم المنصة بالكامل.</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setShowAddModal(true)}
                                className="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 self-start sm:self-auto"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                </svg>
                                إضافة مشرف جديد
                            </button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-right border-collapse">
                                <thead>
                                    <tr className="border-b border-gray-100 bg-gray-50/70 text-gray-500 text-xs font-bold">
                                        <th className="py-3.5 px-6">المشرف</th>
                                        <th className="py-3.5 px-6">البريد الإلكتروني</th>
                                        <th className="py-3.5 px-6">رقم الهاتف</th>
                                        <th className="py-3.5 px-6">تاريخ الإضافة</th>
                                        <th className="py-3.5 px-6 text-center">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 text-sm">
                                    {admins?.map((admin) => {
                                        const isCurrent = admin.id === currentAdmin.id;
                                        return (
                                            <tr key={admin.id} className="hover:bg-gray-50/50 transition-colors">
                                                <td className="py-4 px-6">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-xs border border-indigo-200">
                                                            {admin.name ? admin.name.substring(0, 2).toUpperCase() : 'AD'}
                                                        </div>
                                                        <div>
                                                            <div className="font-bold text-gray-800 flex items-center gap-2">
                                                                {admin.name}
                                                                {isCurrent && (
                                                                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100">
                                                                        أنت (حسابك الحالي)
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="py-4 px-6 text-gray-600 font-mono text-xs" dir="ltr">
                                                    {admin.email}
                                                </td>
                                                <td className="py-4 px-6 text-gray-500 text-xs" dir="ltr">
                                                    {admin.phone || '-'}
                                                </td>
                                                <td className="py-4 px-6 text-gray-500 text-xs">
                                                    {admin.created_at ? new Date(admin.created_at).toLocaleDateString('ar-EG') : '-'}
                                                </td>
                                                <td className="py-4 px-6 text-center">
                                                    {isCurrent ? (
                                                        <span className="text-xs text-gray-400 font-medium">حسابك الشخصي</span>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDeleteAdmin(admin)}
                                                            className="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"
                                                            title="حذف المشرف"
                                                        >
                                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>

            {/* Modal: Add New Super Admin */}
            {showAddModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div
                            className="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity"
                            aria-hidden="true"
                            onClick={() => setShowAddModal(false)}
                        ></div>

                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <div className="inline-block align-bottom bg-white rounded-2xl text-right overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6">
                            <div className="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-base font-bold text-gray-800">إضافة مشرف سوبر أدمن جديد</h3>
                                        <p className="text-xs text-gray-400">سيكون له صلاحية كاملة للدخول وإدارة النظام.</p>
                                    </div>
                                </div>
                                <button
                                    onClick={() => setShowAddModal(false)}
                                    className="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100"
                                >
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <form onSubmit={handleAddAdminSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        اسم المشرف <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={newAdminForm.data.name}
                                        onChange={(e) => newAdminForm.setData('name', e.target.value)}
                                        placeholder="مثال: أحمد عبد الله"
                                        className="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                    {newAdminForm.errors.name && (
                                        <span className="text-xs text-rose-500 mt-1 block">{newAdminForm.errors.name}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        البريد الإلكتروني <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        required
                                        dir="ltr"
                                        value={newAdminForm.data.email}
                                        onChange={(e) => newAdminForm.setData('email', e.target.value)}
                                        placeholder="admin2@example.com"
                                        className="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-left"
                                    />
                                    {newAdminForm.errors.email && (
                                        <span className="text-xs text-rose-500 mt-1 block">{newAdminForm.errors.email}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        رقم الهاتف <span className="text-gray-400 font-normal">(اختياري)</span>
                                    </label>
                                    <input
                                        type="tel"
                                        dir="ltr"
                                        value={newAdminForm.data.phone}
                                        onChange={(e) => newAdminForm.setData('phone', e.target.value)}
                                        placeholder="+201000000000"
                                        className="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-left"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                                        كلمة المرور <span className="text-rose-500">*</span>
                                    </label>
                                    <div className="relative">
                                        <input
                                            type={showNewAdminPassword ? 'text' : 'password'}
                                            required
                                            dir="ltr"
                                            value={newAdminForm.data.password}
                                            onChange={(e) => newAdminForm.setData('password', e.target.value)}
                                            placeholder="8 خانات أو أكثر"
                                            className="w-full pl-10 pr-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-left"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowNewAdminPassword(!showNewAdminPassword)}
                                            className="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            {showNewAdminPassword ? 'إخفاء' : 'عرض'}
                                        </button>
                                    </div>
                                    {newAdminForm.errors.password && (
                                        <span className="text-xs text-rose-500 mt-1 block font-semibold">{newAdminForm.errors.password}</span>
                                    )}
                                    <span className="text-[11px] text-gray-400 mt-1 block">يجب أن تحتوي كلمة المرور على 8 خانات أو أرقام على الأقل.</span>
                                </div>

                                <div className="pt-4 flex gap-3">
                                    <button
                                        type="submit"
                                        disabled={newAdminForm.processing}
                                        className="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all disabled:opacity-50"
                                    >
                                        {newAdminForm.processing ? 'جاري الإضافة...' : 'إضافة المشرف'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowAddModal(false)}
                                        className="px-5 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl text-sm font-bold transition-colors"
                                    >
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
