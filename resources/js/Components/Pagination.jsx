import React from 'react';
import { router } from '@inertiajs/react';

export default function Pagination({ links, className = '' }) {
    if (!links || links.length <= 3) return null;

    const prevLink = links[0];
    const nextLink = links[links.length - 1];
    const pageLinks = links.slice(1, links.length - 1);

    // Find current active page index
    const activeIndex = pageLinks.findIndex((l) => l.active);

    return (
        <div className={`bg-white border-t border-gray-100 px-3 sm:px-6 py-4 flex items-center justify-center gap-1 sm:gap-1.5 select-none ${className}`}>
            {/* زر السابق */}
            <button
                type="button"
                disabled={!prevLink?.url}
                onClick={() => prevLink?.url && router.get(prevLink.url)}
                className="h-8 sm:h-9 px-2.5 sm:px-3 rounded-xl text-xs font-bold border transition-all flex items-center gap-1 bg-white border-gray-200 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed shadow-xs"
                title="الصفحة السابقة"
            >
                <svg className="w-3.5 h-3.5 rtl:rotate-0 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                </svg>
                <span className="hidden sm:inline">السابق</span>
            </button>

            {/* أرقام الصفحات */}
            {pageLinks.map((link, idx) => {
                const pageNum = parseInt(link.label, 10);
                const isNumeric = !isNaN(pageNum);
                
                // On mobile screens, show at most 3 pages around active page
                const isFarFromActive = isNumeric && activeIndex !== -1 && Math.abs(idx - activeIndex) > 1 && idx !== 0 && idx !== pageLinks.length - 1;

                if (link.label === '...') {
                    return (
                        <span key={idx} className="hidden sm:inline-flex px-1.5 py-1 text-xs text-gray-400 font-bold">
                            ...
                        </span>
                    );
                }

                return (
                    <button
                        key={idx}
                        disabled={!link.url || link.active}
                        onClick={() => link.url && router.get(link.url)}
                        className={`min-w-[32px] sm:min-w-[36px] h-8 sm:h-9 px-2 rounded-xl text-xs font-extrabold border transition-all flex items-center justify-center ${
                            isFarFromActive ? 'hidden sm:inline-flex' : 'inline-flex'
                        } ${
                            link.active
                                ? 'bg-orange-600 border-orange-600 text-white shadow-md shadow-orange-100 scale-105'
                                : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300'
                        } disabled:cursor-default`}
                    >
                        {link.label}
                    </button>
                );
            })}

            {/* زر التالي */}
            <button
                type="button"
                disabled={!nextLink?.url}
                onClick={() => nextLink?.url && router.get(nextLink.url)}
                className="h-8 sm:h-9 px-2.5 sm:px-3 rounded-xl text-xs font-bold border transition-all flex items-center gap-1 bg-white border-gray-200 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed shadow-xs"
                title="الصفحة التالية"
            >
                <span className="hidden sm:inline">التالي</span>
                <svg className="w-3.5 h-3.5 rtl:rotate-180 rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    );
}
