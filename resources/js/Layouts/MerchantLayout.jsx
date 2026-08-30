import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';

const navLinks = [
    {
        href: '/admin/dashboard',
        label: 'الرئيسية',
        pathMatch: '/admin/dashboard',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
        ),
    },
    {
        href: '/admin/orders',
        label: 'الطلبات',
        pathMatch: '/admin/orders',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
        ),
    },
    {
        href: '/admin/reports',
        label: 'التقارير والتحليلات',
        pathMatch: '/admin/reports',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        ),
    },
    {
        href: '/admin/products',
        label: 'المنتجات',
        pathMatch: '/admin/products',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        ),
    },
    {
        href: '/admin/categories',
        label: 'التصنيفات',
        pathMatch: '/admin/categories',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
        ),
    },
    {
        href: '/admin/subscription',
        label: 'الاشتراك والفوترة',
        pathMatch: '/admin/subscription',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        ),
    },
    {
        href: '/admin/wallet',
        label: 'المحفظة',
        pathMatch: '/admin/wallet',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z" />
            </svg>
        ),
    },
    {
        href: '/admin/support',
        label: 'الدعم الفني',
        pathMatch: '/admin/support',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.172l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        ),
    },
    {
        href: '/admin/tutorials',
        label: 'الشروحات والدروس',
        pathMatch: '/admin/tutorials',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
        ),
    },
    {
        href: '/admin/settings',
        label: 'الإعدادات',
        pathMatch: '/admin/settings',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        ),
    },
];

// روابط الإعدادات المتقدمة - مجمّعة في قائمة منسدلة
const advancedLinks = [
    {
        href: '/admin/landing-pages',
        label: 'صفحات الهبوط',
        pathMatch: '/admin/landing-pages',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        ),
    },
    {
        href: '/admin/domain',
        label: 'تغيير رابط المتجر',
        pathMatch: '/admin/domain',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
        ),
    },
    {
        href: '/admin/theme',
        label: 'مظهر المتجر',
        pathMatch: '/admin/theme',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
        ),
    },
    {
        href: '/admin/media',
        label: 'مكتبة الوسائط',
        pathMatch: '/admin/media',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        ),
    },
    {
        href: '/admin/coupons',
        label: 'الكوبونات',
        pathMatch: '/admin/coupons',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
            </svg>
        ),
    },
    {
        href: '/admin/blacklist',
        label: 'منع الطلبات الوهمية',
        pathMatch: '/admin/blacklist',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
        ),
    },
    /*
    {
        href: '/admin/webhooks',
        label: 'الـ Webhooks',
        pathMatch: '/admin/webhooks',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
        ),
    },
    */
    {
        href: '/admin/auto-confirm',
        label: 'التأكيد التلقائي',
        pathMatch: '/admin/auto-confirm',
        badge: 'جديد',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        ),
    },
    {
        href: '/admin/push-notifications',
        label: 'إشعارات الطلبات',
        pathMatch: '/admin/push-notifications',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        ),
    },
    {
        href: '/admin/store-ratings',
        label: 'تقييمات المتجر',
        pathMatch: '/admin/store-ratings',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
        ),
    },
    {
        href: '/admin/shipping-gateways',
        label: 'ربط شركات الشحن',
        pathMatch: '/admin/shipping-gateways',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        ),
    },
    {
        href: '/admin/conversion-api',
        label: 'Conversion API',
        pathMatch: '/admin/conversion-api',
        badge: 'CAPI',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        ),
    },
    {
        href: '/admin/payment-gateways',
        label: 'ربط دفع إلكتروني',
        pathMatch: '/admin/payment-gateways',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        ),
    },
    {
        href: '/admin/ai-tools',
        label: 'الذكاء الاصطناعي',
        pathMatch: '/admin/ai-tools',
        badge: 'قريباً',
        icon: (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        ),
    },
];

function isActive(link) {
    if (typeof window === 'undefined') return false;
    const path = window.location.pathname;
    return path.startsWith(link.pathMatch);
}

function isAdvancedActive() {
    if (typeof window === 'undefined') return false;
    const path = window.location.pathname;
    return advancedLinks.some(l => path.startsWith(l.pathMatch));
}

export default function MerchantLayout({ children, title }) {
    const { auth, storeName, storefrontUrl } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [advancedOpen, setAdvancedOpen] = useState(isAdvancedActive());
    const [userDropdownOpen, setUserDropdownOpen] = useState(false);

    const handleLogout = (e) => {
        e.preventDefault();
        router.post('/admin/logout', {}, {
            onSuccess: () => {
                window.location.href = '/admin/login';
            },
            onError: () => {
                window.location.href = '/admin/login';
            }
        });
    };

    const displayName = storeName || auth?.user?.name || 'متجر';
    const userName = auth?.user?.name || 'المالك';
    const userEmail = auth?.user?.email || '';
    const initials = userName.substring(0, 2).toUpperCase();

    const renderNavItem = (link) => {
        const active = isActive(link);
        return (
            <li key={link.href}>
                <Link
                    href={link.href}
                    className={`flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group ${
                        active
                            ? 'bg-white/15 text-white shadow-sm'
                            : 'text-indigo-200 hover:bg-white/10 hover:text-white'
                    } ${!sidebarOpen ? 'justify-center' : ''}`}
                    title={!sidebarOpen ? link.label : undefined}
                >
                    <span className={`flex-shrink-0 ${active ? 'text-amber-300' : 'text-indigo-300 group-hover:text-white'}`}>
                        {link.icon}
                    </span>
                    {sidebarOpen && (
                        <div className="flex items-center justify-between w-full">
                            <span className="text-sm font-medium">{link.label}</span>
                            {link.badge && (
                                <span className="px-2 py-0.5 text-[10px] font-bold bg-amber-400/20 text-amber-300 rounded-full border border-amber-400/30">
                                    {link.badge}
                                </span>
                            )}
                        </div>
                    )}
                    {active && sidebarOpen && !link.badge && (
                        <span className="mr-auto w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0" />
                    )}
                </Link>
            </li>
        );
    };

    const renderAdvancedDropdown = () => {
        const anyActive = isAdvancedActive();
        return (
            <li key="advanced-dropdown">
                {/* زر الإعدادات المتقدمة */}
                <button
                    type="button"
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        setAdvancedOpen(prev => !prev);
                    }}
                    className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group cursor-pointer ${
                        anyActive
                            ? 'bg-white/15 text-white shadow-sm'
                            : 'text-indigo-200 hover:bg-white/10 hover:text-white'
                    } ${!sidebarOpen ? 'justify-center' : ''}`}
                    title={!sidebarOpen ? 'إعدادات متقدمة' : undefined}
                >
                    <span className={`flex-shrink-0 ${anyActive ? 'text-amber-300' : 'text-indigo-300 group-hover:text-white'}`}>
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </span>
                    {sidebarOpen && (
                        <>
                            <span className="text-sm font-medium flex-1 text-right">إعدادات متقدمة</span>
                            <svg
                                className={`w-4 h-4 flex-shrink-0 text-indigo-400 transition-transform duration-200 ${advancedOpen ? 'rotate-180' : ''}`}
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </>
                    )}
                </button>

                {/* القائمة المنسدلة */}
                {(advancedOpen || !sidebarOpen) && sidebarOpen && (
                    <ul className="mt-1 mr-4 space-y-0.5 border-r border-indigo-700/50 pr-2">
                        {advancedLinks.map(link => {
                            const active = isActive(link);
                            return (
                                <li key={link.href}>
                                    <Link
                                        href={link.href}
                                        className={`flex items-center gap-2.5 px-3 py-2 rounded-lg transition-all duration-150 group text-sm ${
                                            active
                                                ? 'bg-white/10 text-white'
                                                : 'text-indigo-300 hover:bg-white/8 hover:text-white'
                                        }`}
                                    >
                                        <span className={`flex-shrink-0 ${active ? 'text-amber-300' : 'text-indigo-400 group-hover:text-white'}`}>
                                            {link.icon}
                                        </span>
                                        <span className="font-medium">{link.label}</span>
                                        {active && (
                                            <span className="mr-auto w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0" />
                                        )}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                )}

                {/* عند إغلاق الـ Sidebar نرسم الأيقونات بدون نص */}
                {!sidebarOpen && (
                    <ul className="mt-1 space-y-0.5">
                        {advancedLinks.map(link => {
                            const active = isActive(link);
                            return (
                                <li key={link.href}>
                                    <Link
                                        href={link.href}
                                        title={link.label}
                                        className={`flex items-center justify-center px-3 py-2 rounded-lg transition-all duration-150 ${
                                            active
                                                ? 'bg-white/15 text-amber-300'
                                                : 'text-indigo-400 hover:bg-white/10 hover:text-white'
                                        }`}
                                    >
                                        {link.icon}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </li>
        );
    };

    const renderSidebarContent = (showToggle = true) => (
        <div className="flex flex-col h-full">
            <div className={`flex items-center h-16 border-b border-indigo-800 px-4 ${sidebarOpen ? 'justify-between' : 'justify-center'}`}>
                {sidebarOpen && (
                    <div className="flex items-center gap-2.5 overflow-hidden">
                        <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center flex-shrink-0 shadow-sm">
                            <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z" />
                            </svg>
                        </div>
                        <div className="overflow-hidden">
                            <p className="text-white font-bold text-sm leading-tight truncate max-w-[130px]" title={displayName}>
                                {displayName}
                            </p>
                            <p className="text-indigo-300 text-xs font-medium">لوحة التحكم</p>
                        </div>
                    </div>
                )}
                {showToggle && (
                    <button
                        type="button"
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                        className="text-indigo-200 hover:text-white hover:bg-indigo-700 rounded-lg p-1.5 transition-colors flex-shrink-0 cursor-pointer"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                )}
            </div>

            {/* FULL WIDTH STOREFRONT PREVIEW BUTTON - EMERALD NEON GLASS */}
            {storefrontUrl && storefrontUrl !== '#' && (
                <div className={`p-3 border-b border-indigo-800/60 ${!sidebarOpen ? 'flex justify-center' : ''}`}>
                    {sidebarOpen ? (
                        <a
                            href={storefrontUrl}
                            target="_blank"
                            rel="noreferrer"
                            className="w-full py-2.5 px-3.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-300 hover:text-white border border-emerald-500/35 hover:border-emerald-400 shadow-sm flex items-center justify-between font-extrabold text-xs transition-all duration-200 group hover:shadow-emerald-500/10 backdrop-blur-xs"
                            title="مشاهدة المتجر للعملاء 👁️"
                        >
                            <div className="flex items-center gap-2.5">
                                <span className="relative flex h-2.5 w-2.5">
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
                                </span>
                                <svg className="w-4 h-4 text-emerald-400 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>زيارة المتجر المباشر</span>
                            </div>
                            <svg className="w-4 h-4 text-emerald-400/80 group-hover:text-emerald-200 group-hover:translate-x-[-2px] transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    ) : (
                        <a
                            href={storefrontUrl}
                            target="_blank"
                            rel="noreferrer"
                            className="w-10 h-10 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/35 text-emerald-300 border border-emerald-400/40 flex items-center justify-center transition-all hover:scale-105 shadow-sm relative"
                            title="زيارة المتجر 👁️"
                        >
                            <span className="absolute top-1.5 right-1.5 flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                            </span>
                            <svg className="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </a>
                    )}
                </div>
            )}

            <nav className="flex-1 py-4 overflow-y-auto">
                <ul className="space-y-1 px-2">
                    {navLinks.map((link) => renderNavItem(link))}
                    {/* الإعدادات المتقدمة */}
                    {renderAdvancedDropdown()}
                </ul>
            </nav>
        </div>
    );

    return (
        <div className="h-screen bg-gray-50 flex" dir="rtl">
            {mobileOpen && (
                <div
                    className="fixed inset-0 bg-black/50 z-30 lg:hidden"
                    onClick={() => setMobileOpen(false)}
                />
            )}

            <aside
                className={`fixed inset-y-0 right-0 z-40 w-64 bg-gradient-to-b from-indigo-900 to-indigo-950 shadow-2xl transform transition-transform duration-300 lg:hidden ${
                    mobileOpen ? 'translate-x-0' : 'translate-x-full'
                }`}
            >
                {renderSidebarContent(false)}
            </aside>

            <aside
                className={`hidden lg:flex flex-col flex-shrink-0 bg-gradient-to-b from-indigo-900 to-indigo-950 shadow-xl transition-all duration-300 ${
                    sidebarOpen ? 'w-64' : 'w-20'
                }`}
            >
                {renderSidebarContent(true)}
            </aside>

            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6 shadow-sm flex-shrink-0">
                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => setMobileOpen(!mobileOpen)}
                            className="lg:hidden text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-colors"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <h1 className="text-lg font-semibold text-gray-800 truncate">
                            {title || 'لوحة التحكم'}
                        </h1>
                        {storeName && (
                            <span className="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                {storeName}
                            </span>
                        )}
                    </div>
                    <div className="flex items-center gap-3 relative">
                        <button
                            type="button"
                            onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                            className="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-gray-100 transition-colors focus:outline-none"
                        >
                            <span className="hidden md:block text-sm font-semibold text-gray-700">{userEmail}</span>
                            <div className="w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-xs font-bold shadow-sm ring-2 ring-amber-400/30">
                                {initials}
                            </div>
                        </button>

                        {/* User Dropdown Menu */}
                        {userDropdownOpen && (
                            <>
                                <div
                                    className="fixed inset-0 z-40"
                                    onClick={() => setUserDropdownOpen(false)}
                                />
                                <div className="absolute left-0 top-12 z-50 w-60 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 transition-all">
                                    <div className="px-4 py-3 border-b border-gray-100">
                                        <p className="text-sm font-extrabold text-gray-900 truncate">{userName}</p>
                                        <p className="text-xs text-gray-500 truncate mt-0.5" title={userEmail}>{userEmail}</p>
                                    </div>
                                    
                                    <div className="p-1.5 space-y-1">
                                        <Link
                                            href="/admin/profile"
                                            onClick={() => setUserDropdownOpen(false)}
                                            className="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 text-xs font-extrabold transition-colors"
                                        >
                                            <svg className="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>الملف الشخصي</span>
                                        </Link>

                                        <button
                                            type="button"
                                            onClick={(e) => {
                                                setUserDropdownOpen(false);
                                                handleLogout(e);
                                            }}
                                            className="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-rose-600 hover:bg-rose-50 text-xs font-extrabold transition-colors"
                                        >
                                            <svg className="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                                    d="M17 16l4-4m0 0l4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            <span>تسجيل الخروج</span>
                                        </button>
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                </header>

                <main className="flex-1 overflow-y-auto p-4 lg:p-6">
                    {children}
                </main>
            </div>
        </div>
    );
}
