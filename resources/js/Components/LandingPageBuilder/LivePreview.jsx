import React, { useState, useEffect } from 'react';

export default function LivePreview({ sections, products, template }) {
    return (
        <div className="w-full text-right bg-slate-950 min-h-full pb-16 flex flex-col font-sans select-none pointer-events-none" style={{ fontFamily: 'Cairo, sans-serif' }}>
            {/* Urgency Promo bar */}
            <div className="bg-red-600 text-white text-[10px] py-1.5 px-4 text-center font-bold tracking-wide animate-pulse flex-shrink-0">
                🔥 عـرض خـاص ومحـدود: شحن مجاني ودفع عند الاستلام اليوم فقط!
            </div>

            {/* Header mockup */}
            <div className="bg-slate-900/80 backdrop-blur-md border-b border-white/5 py-3 px-4 flex items-center justify-between sticky top-0 z-10 flex-shrink-0">
                <span className="text-[10px] text-green-400 font-bold flex items-center gap-1">
                    <span className="w-1.5 h-1.5 bg-green-500 rounded-full animate-ping"></span>
                    <span>143 زائر حالياً</span>
                </span>
                <span className="font-extrabold text-white text-sm tracking-tight">Fast Store</span>
            </div>

            {/* Render Sections Mockups */}
            <div className="flex-1 space-y-px bg-white/5">
                {sections.map((section, idx) => {
                    const bgStyle = section.bg_color ? { backgroundColor: section.bg_color } : {};
                    const textStyle = section.text_color ? { color: section.text_color } : {};
                    const buttonColorStyle = section.button_color ? { backgroundColor: section.button_color } : {};

                    // --- 1. HERO PREVIEW ---
                    if (section.type === 'hero') {
                        return (
                            <div key={idx} className="p-8 text-center space-y-4 border-b border-white/5" style={bgStyle}>
                                {section.badge && (
                                    <span className="inline-block px-3 py-1 bg-white/10 border border-white/10 rounded-full text-[10px] font-bold text-orange-400">
                                        {section.badge}
                                    </span>
                                )}
                                <h2 className="text-xl font-extrabold leading-tight" style={textStyle}>{section.title || 'عنوان ترويجي لمنتجك'}</h2>
                                <p className="text-xs text-slate-400 max-w-xs mx-auto leading-relaxed" style={section.text_color ? { color: section.text_color, opacity: 0.7 } : {}}>{section.subtitle}</p>
                                <div className="pt-2">
                                    <span className="inline-flex px-6 py-2.5 bg-orange-500 text-white text-xs font-bold rounded-full shadow-lg" style={buttonColorStyle}>
                                        {section.button_text || 'اطلب الآن'}
                                    </span>
                                </div>
                            </div>
                        );
                    }

                    // --- 2. COUNTDOWN PREVIEW ---
                    if (section.type === 'countdown') {
                        return (
                            <div key={idx} className="p-4 text-center space-y-2 border-b border-white/5" style={bgStyle}>
                                <div className="text-[10px] font-bold text-white/90" style={textStyle}>{section.title || 'ينتهي هذا العرض الحصري خلال:'}</div>
                                <div className="flex items-center justify-center gap-3">
                                    {['ثانية', 'دقيقة', 'ساعة', 'يوم'].map((label, i) => (
                                        <div key={i} className="flex flex-col items-center">
                                            <div className="w-10 h-10 bg-black/40 rounded-lg flex items-center justify-center text-white text-sm font-black border border-white/5">
                                                {i === 3 ? '02' : i === 2 ? '14' : i === 1 ? '45' : '18'}
                                            </div>
                                            <span className="text-[8px] text-white/60 mt-1">{label}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    }

                    // --- 3. PRODUCT SHOWCASE PREVIEW ---
                    if (section.type === 'product_showcase') {
                        return (
                            <div key={idx} className="p-6 bg-slate-900 border-b border-white/5 space-y-4">
                                <h3 className="text-sm font-bold text-center text-orange-400 uppercase tracking-wider">{section.title || 'تفاصيل المنتج المميز'}</h3>
                                
                                {/* Product card preview */}
                                <div className="bg-slate-950 rounded-2xl border border-white/5 overflow-hidden p-4 space-y-3">
                                    {/* Mock Product Image */}
                                    <div className="aspect-[4/3] rounded-xl bg-slate-800/80 overflow-hidden relative flex items-center justify-center border border-white/5">
                                        {(section.product_image || section.image) ? (
                                            <img src={section.product_image || section.image} alt={section.product_name} className="w-full h-full object-cover" />
                                        ) : (
                                            <svg className="w-12 h-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        )}
                                        <span className="absolute top-2 right-2 bg-red-600 text-white font-bold text-[8px] px-2 py-0.5 rounded-full">خصم 50%</span>
                                    </div>

                                    {/* Product Details */}
                                    <div className="space-y-1">
                                        <h4 className="font-extrabold text-white text-sm">{section.product_name || 'اسم المنتج المختار'}</h4>
                                        <div className="flex items-center gap-2">
                                            <span className="text-orange-500 font-bold text-sm">{section.product_price || '299'} ج.م</span>
                                            <span className="text-slate-500 line-through text-[10px]">{(section.product_price || 299) * 2} ج.م</span>
                                        </div>
                                    </div>

                                    {/* Features Checklist */}
                                    {section.features && section.features.length > 0 && (
                                        <div className="pt-2 border-t border-white/5 space-y-1.5">
                                            {section.features.map((feature, fIdx) => (
                                                <div key={fIdx} className="flex items-start gap-1.5 text-[10px] text-slate-300">
                                                    <span className="text-orange-500 flex-shrink-0 font-bold">✓</span>
                                                    <span>{feature}</span>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {/* Action Button */}
                                    <div className="pt-3 border-t border-white/5">
                                        <div className="w-full bg-orange-600 text-white rounded-xl py-2.5 text-center font-bold text-xs shadow-md shadow-orange-950/20">
                                            {section.button_text || 'اطلب الآن - دفع عند الاستلام'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    }

                    // --- 4. FEATURES PREVIEW ---
                    if (section.type === 'features') {
                        return (
                            <div key={idx} className="p-6 bg-slate-950 border-b border-white/5 space-y-4">
                                <h3 className="text-sm font-bold text-center text-white">{section.title || 'مزايا حصرية للمنتج'}</h3>
                                <div className="grid grid-cols-2 gap-3">
                                    {(section.features || []).map((feat, fIdx) => (
                                        <div key={fIdx} className="p-3 bg-white/5 rounded-xl border border-white/5 space-y-1">
                                            <div className="w-6 h-6 rounded-lg bg-orange-500/10 text-orange-400 flex items-center justify-center text-xs font-bold">✨</div>
                                            <h4 className="font-bold text-white text-[11px] leading-tight">{feat.title || 'اسم الميزة'}</h4>
                                            <p className="text-[9px] text-slate-400 leading-normal line-clamp-2">{feat.desc || 'وصف الميزة الفعلي.'}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    }

                    // --- 5. TESTIMONIALS PREVIEW ---
                    if (section.type === 'testimonials') {
                        return (
                            <div key={idx} className="p-6 bg-slate-900 border-b border-white/5 space-y-4">
                                <h3 className="text-sm font-bold text-center text-white">{section.title || 'أراء عملائنا الأوفياء'}</h3>
                                <div className="space-y-3">
                                    {(section.testimonials || []).map((test, tIdx) => (
                                        <div key={tIdx} className="p-3 bg-slate-950 border border-white/5 rounded-xl space-y-1.5">
                                            <div className="flex items-center justify-between">
                                                <span className="text-[9px] text-slate-400 font-bold">{test.name}</span>
                                                <span className="text-[8px] text-yellow-400">{'⭐'.repeat(test.rating || 5)}</span>
                                            </div>
                                            <p className="text-[10px] text-slate-300 leading-relaxed italic">" {test.comment} "</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    }

                    // --- 6. CTA PREVIEW ---
                    if (section.type === 'cta') {
                        return (
                            <div key={idx} className="p-8 text-center space-y-4 border-b border-white/5" style={bgStyle}>
                                <h3 className="text-lg font-black leading-tight" style={textStyle}>{section.title}</h3>
                                <p className="text-xs text-white/70 max-w-xs mx-auto leading-relaxed" style={section.text_color ? { color: section.text_color, opacity: 0.8 } : {}}>{section.subtitle}</p>
                                <div className="pt-2">
                                    <span className="inline-block px-6 py-2.5 bg-white text-slate-900 text-xs font-black rounded-full shadow-lg">
                                        {section.button_text}
                                    </span>
                                </div>
                            </div>
                        );
                    }

                    return null;
                })}

                {/* Bottom Checkout Form Preview Mockup */}
                {sections.some(s => s.type === 'product_showcase' && s.product_id) && (
                    <div className="p-6 bg-slate-900 border-b border-white/5 space-y-4" id="mock-checkout-form">
                        <div className="bg-slate-950 rounded-2xl border border-white/5 overflow-hidden p-5 space-y-4">
                            <div className="text-center">
                                <h3 className="text-xs font-bold text-white">سجل طلبك الآن - الدفع عند الاستلام</h3>
                                <p className="text-[9px] text-slate-400 mt-1">قم بملء البيانات التالية لتأكيد طلبك وسنتواصل معك فوراً</p>
                            </div>
                            <div className="space-y-2">
                                <div className="w-full bg-slate-900 border border-white/5 rounded-lg py-2 px-3 text-[10px] text-slate-500">
                                    الاسم بالكامل
                                </div>
                                <div className="w-full bg-slate-900 border border-white/5 rounded-lg py-2 px-3 text-[10px] text-slate-500">
                                    رقم الهاتف الجوال
                                </div>
                                <div className="w-full bg-slate-900 border border-white/5 rounded-lg py-2 px-3 text-[10px] text-slate-500">
                                    اختر محافظة الشحن
                                </div>
                                <div className="w-full bg-slate-900 border border-white/5 rounded-lg py-2 px-3 text-[10px] text-slate-500">
                                    العنوان بالتفصيل
                                </div>
                                <div className="w-full bg-orange-600 text-white rounded-xl py-2.5 text-center font-bold text-xs shadow-md shadow-orange-950/20">
                                    تأكيد طلب الشراء الآن
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
            
            {/* Footer note mockup */}
            <div className="py-4 text-center text-[8px] text-slate-600 border-t border-white/5 bg-slate-950 flex-shrink-0">
                جميع الحقوق محفوظة © Fast Store
            </div>
        </div>
    );
}
