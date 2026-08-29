import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';

export default function SuperAdminLayout({ children }) {
    const { auth } = usePage().props;
    const [isSidebarOpen, setIsSidebarOpen] = useState(true);
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    const closeMobileMenu = () => setIsMobileMenuOpen(false);

    const navItems = [
        {
            href: '/dashboard',
            label: 'الرئيسية',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            )
        },
        {
            href: '/tenants',
            label: 'المتاجر',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            )
        },
        {
            href: '/subscriptions/plans',
            label: 'خطط الاشتراك',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            )
        },
        {
            href: '/subscriptions/receipts',
            label: 'إيصالات الدفع',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4M7.833 8.667H14.17m-6.337 3.5h6.337m-6.337 3.5h6.337M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            )
        },
        {
            href: '/backups',
            label: 'النسخ الاحتياطي',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
            )
        },
        {
            href: '/support-contacts',
            label: 'أرقام الدعم الفني',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
            )
        },
        {
            href: '/whatsapp-gateway',
            label: 'بوابة الواتساب والتأكيد',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            )
        },
        {
            href: '/tutorials',
            label: 'الشروحات والدروس',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            )
        },
    ];

    return (
        <div className="min-h-screen bg-gray-100 flex flex-col md:flex-row font-sans" dir="rtl">
            {/* Mobile Drawer Overlay Backdrop */}
            {isMobileMenuOpen && (
                <div 
                    onClick={closeMobileMenu} 
                    className="fixed inset-0 bg-gray-900/60 backdrop-blur-xs z-40 md:hidden transition-opacity"
                    aria-hidden="true"
                />
            )}

            {/* Mobile Drawer Sidebar */}
            <aside 
                className={`fixed top-0 right-0 bottom-0 w-72 bg-indigo-950 text-white z-50 transform transition-transform duration-300 ease-in-out md:hidden flex flex-col shadow-2xl ${
                    isMobileMenuOpen ? 'translate-x-0' : 'translate-x-full'
                }`}
            >
                {/* Mobile Drawer Header */}
                <div className="h-16 flex items-center justify-between px-5 border-b border-indigo-800/60 shrink-0">
                    <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-pink-500 flex items-center justify-center font-black text-white text-sm shadow-sm">
                            FO
                        </div>
                        <span className="font-extrabold text-base text-white">لوحة التحكم السوبر</span>
                    </div>
                    <button 
                        onClick={closeMobileMenu}
                        className="w-9 h-9 rounded-full bg-indigo-900/80 text-indigo-200 hover:text-white flex items-center justify-center focus:outline-none"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Mobile Nav Links */}
                <nav className="flex-1 py-4 px-3 space-y-1.5 overflow-y-auto">
                    {navItems.map((item) => (
                        <Link 
                            key={item.href}
                            href={item.href}
                            onClick={closeMobileMenu}
                            className="flex items-center gap-3 px-4 py-3 rounded-xl text-indigo-100 hover:bg-indigo-800/80 hover:text-white font-medium text-sm transition-all"
                        >
                            <span className="text-indigo-300">{item.icon}</span>
                            <span>{item.label}</span>
                        </Link>
                    ))}
                </nav>

                {/* Mobile User Profile */}
                <div className="p-4 border-t border-indigo-900/80 bg-indigo-900/40 shrink-0">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-black text-sm shrink-0 border border-indigo-400">
                            {auth?.user?.name ? auth.user.name.substring(0, 2).toUpperCase() : 'AD'}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-bold text-white truncate">{auth?.user?.name || 'Super Admin'}</p>
                            <p className="text-xs text-indigo-300 truncate">{auth?.user?.email}</p>
                        </div>
                    </div>
                    <button
                        onClick={(e) => {
                            e.preventDefault();
                            closeMobileMenu();
                            router.post('/logout', {}, {
                                onSuccess: () => { window.location.href = '/login'; },
                                onError: () => { window.location.href = '/login'; }
                            });
                        }}
                        className="w-full py-2 bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 rounded-lg text-xs font-bold transition-colors flex items-center justify-center gap-1.5 border border-rose-500/30"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        تسجيل الخروج
                    </button>
                </div>
            </aside>

            {/* Desktop Sidebar (hidden on mobile) */}
            <aside className={`hidden md:flex ${isSidebarOpen ? 'w-64' : 'w-20'} bg-indigo-950 text-white transition-all duration-300 flex-col shrink-0 min-h-screen sticky top-0 h-screen overflow-y-auto`}>
                {/* Desktop Logo Area */}
                <div className="h-16 flex items-center justify-between px-4 border-b border-indigo-900 shrink-0">
                    <span className={`font-extrabold text-base whitespace-nowrap overflow-hidden text-white ${!isSidebarOpen && 'hidden'}`}>
                        لوحة التحكم السوبر
                    </span>
                    <button 
                        onClick={() => setIsSidebarOpen(!isSidebarOpen)} 
                        className="text-indigo-200 hover:text-white focus:outline-none p-1.5 rounded-lg hover:bg-indigo-900 transition-colors"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                {/* Desktop Nav Links */}
                <nav className="flex-1 py-4 space-y-1 px-2">
                    {navItems.map((item) => (
                        <Link 
                            key={item.href}
                            href={item.href} 
                            className="flex items-center px-3 py-2.5 rounded-xl text-indigo-100 hover:bg-indigo-900/80 hover:text-white transition-colors"
                        >
                            <span className="shrink-0 text-indigo-300 ml-3">{item.icon}</span>
                            <span className={`text-sm font-medium ${!isSidebarOpen && 'hidden'} transition-opacity duration-300 whitespace-nowrap`}>
                                {item.label}
                            </span>
                        </Link>
                    ))}
                </nav>

                {/* Desktop User Profile */}
                <div className="p-4 border-t border-indigo-900 flex items-center justify-start mt-auto shrink-0 bg-indigo-900/30">
                    <div className="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-extrabold text-xs shrink-0 border border-indigo-400">
                        {auth?.user?.name ? auth.user.name.substring(0, 2).toUpperCase() : 'AD'}
                    </div>
                    <div className={`mr-3 min-w-0 ${!isSidebarOpen && 'hidden'}`}>
                        <p className="text-xs font-bold text-white truncate">{auth?.user?.name || 'Super Admin'}</p>
                        <button
                            onClick={(e) => {
                                e.preventDefault();
                                router.post('/logout', {}, {
                                    onSuccess: () => { window.location.href = '/login'; },
                                    onError: () => { window.location.href = '/login'; }
                                });
                            }}
                            className="text-xs text-rose-300 hover:text-rose-100 block text-right font-medium mt-0.5"
                        >
                            تسجيل الخروج
                        </button>
                    </div>
                </div>
            </aside>

            {/* Main Content Area */}
            <div className="flex-1 flex flex-col min-w-0 w-full overflow-x-hidden">
                {/* Header */}
                <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 shrink-0 sticky top-0 z-30 shadow-2xs">
                    <div className="flex items-center gap-3">
                        {/* Mobile Menu Toggle Button */}
                        <button
                            onClick={() => setIsMobileMenuOpen(true)}
                            className="md:hidden p-2 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 focus:outline-none transition-colors border border-indigo-100"
                            aria-label="فتح القائمة"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <h1 className="text-base sm:text-lg md:text-xl font-bold text-gray-800 truncate">
                            لوحة تحكم المدير العام
                        </h1>
                    </div>

                    <div className="flex items-center gap-3">
                        <span className="text-xs sm:text-sm text-gray-500 font-medium hidden sm:inline">
                            {auth?.user?.email}
                        </span>
                        <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs border border-indigo-200">
                            {auth?.user?.name ? auth.user.name.substring(0, 1).toUpperCase() : 'A'}
                        </div>
                    </div>
                </header>

                {/* Main Content Body */}
                <main className="flex-1 p-3 sm:p-4 md:p-6 bg-gray-50/80 min-w-0 overflow-x-hidden">
                    {children}
                </main>
            </div>
        </div>
    );
}
