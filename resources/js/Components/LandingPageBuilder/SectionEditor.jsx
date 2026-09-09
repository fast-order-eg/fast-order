import React from 'react';

export default function SectionEditor({ section, onChange, products }) {
    
    const updateField = (field, value) => {
        onChange({
            ...section,
            [field]: value,
        });
    };

    // --- 1. HERO EDITOR ---
    if (section.type === 'hero') {
        return (
            <div className="space-y-4">
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">بادج أعلى العنوان (Badge)</label>
                    <input
                        type="text"
                        value={section.badge || ''}
                        onChange={(e) => updateField('badge', e.target.value)}
                        placeholder="مثال: خصم 50% لفترة محدودة 🔥"
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                </div>
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">العنوان الرئيسي</label>
                    <input
                        type="text"
                        value={section.title || ''}
                        onChange={(e) => updateField('title', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                </div>
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">الوصف الفرعي</label>
                    <textarea
                        value={section.subtitle || ''}
                        onChange={(e) => updateField('subtitle', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 h-20"
                    />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">نص زر الدعوة (CTA)</label>
                        <input
                            type="text"
                            value={section.button_text || 'اطلب الآن'}
                            onChange={(e) => updateField('button_text', e.target.value)}
                            className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">لون زر الدعوة</label>
                        <div className="flex gap-2 items-center">
                            <input
                                type="color"
                                value={section.button_color || '#f97316'}
                                onChange={(e) => updateField('button_color', e.target.value)}
                                className="w-8 h-8 p-0 rounded-lg border-0 cursor-pointer"
                            />
                            <span className="text-xs font-mono text-gray-500">{section.button_color || '#f97316'}</span>
                        </div>
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">لون خلفية القسم</label>
                        <div className="flex gap-2 items-center">
                            <input
                                type="color"
                                value={section.bg_color || '#0f172a'}
                                onChange={(e) => updateField('bg_color', e.target.value)}
                                className="w-8 h-8 p-0 rounded-lg border-0 cursor-pointer"
                            />
                            <span className="text-xs font-mono text-gray-500">{section.bg_color || '#0f172a'}</span>
                        </div>
                    </div>
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">لون نصوص القسم</label>
                        <div className="flex gap-2 items-center">
                            <input
                                type="color"
                                value={section.text_color || '#ffffff'}
                                onChange={(e) => updateField('text_color', e.target.value)}
                                className="w-8 h-8 p-0 rounded-lg border-0 cursor-pointer"
                            />
                            <span className="text-xs font-mono text-gray-500">{section.text_color || '#ffffff'}</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // --- 2. COUNTDOWN EDITOR ---
    if (section.type === 'countdown') {
        // Format ISO date to local datetime-local string
        const formatDate = (isoString) => {
            if (!isoString) return '';
            try {
                const date = new Date(isoString);
                // Adjust to local time zone for standard datetime-local input
                const offset = date.getTimezoneOffset() * 60000;
                const localISOTime = (new Date(date.getTime() - offset)).toISOString().slice(0, 16);
                return localISOTime;
            } catch (e) {
                return '';
            }
        };

        const handleDateChange = (val) => {
            if (!val) return;
            const utcDate = new Date(val).toISOString();
            updateField('end_date', utcDate);
        };

        return (
            <div className="space-y-4">
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">عنوان العداد التنازلي</label>
                    <input
                        type="text"
                        value={section.title || ''}
                        onChange={(e) => updateField('title', e.target.value)}
                        placeholder="ينتهي العرض الترويجي المميز خلال:"
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                </div>
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">تاريخ ووقت انتهاء العرض</label>
                    <input
                        type="datetime-local"
                        value={formatDate(section.end_date)}
                        onChange={(e) => handleDateChange(e.target.value)}
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 text-right"
                    />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">لون الخلفية</label>
                        <div className="flex gap-2 items-center">
                            <input
                                type="color"
                                value={section.bg_color || '#ea580c'}
                                onChange={(e) => updateField('bg_color', e.target.value)}
                                className="w-8 h-8 p-0 rounded-lg border-0 cursor-pointer"
                            />
                            <span className="text-xs font-mono text-gray-500">{section.bg_color || '#ea580c'}</span>
                        </div>
                    </div>
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">لون النصوص والعداد</label>
                        <div className="flex gap-2 items-center">
                            <input
                                type="color"
                                value={section.text_color || '#ffffff'}
                                onChange={(e) => updateField('text_color', e.target.value)}
                                className="w-8 h-8 p-0 rounded-lg border-0 cursor-pointer"
                            />
                            <span className="text-xs font-mono text-gray-500">{section.text_color || '#ffffff'}</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // --- 3. PRODUCT SHOWCASE EDITOR ---
    if (section.type === 'product_showcase') {
        const handleProductChange = (productId) => {
            if (!productId) {
                onChange({
                    ...section,
                    product_id: null,
                    product_name: '',
                    product_price: '',
                    product_image: '',
                });
                return;
            }
            const selectedProd = products.find(p => p.id === parseInt(productId));
            if (selectedProd) {
                let features = section.features || [];
                if (!features.length || features[0] === 'ضمان استبدال واسترجاع بدون أسئلة لمدة 14 يوم') {
                    if (selectedProd.description) {
                        const cleanDesc = selectedProd.description.replace(/<\/?[^>]+(>|$)/g, "");
                        const lines = cleanDesc.split('\n').map(l => l.trim()).filter(l => l.length > 0);
                        if (lines.length > 0) {
                            features = lines.slice(0, 4);
                        }
                    }
                }
                onChange({
                    ...section,
                    product_id: selectedProd.id,
                    product_name: selectedProd.name,
                    product_price: selectedProd.price,
                    product_image: selectedProd.image_url,
                    image: selectedProd.image_url,
                    features: features,
                });
            }
        };

        const handleAddFeature = () => {
            const currentFeatures = section.features || [];
            updateField('features', [...currentFeatures, 'ميزة جديدة للمنتج']);
        };

        const handleFeatureChange = (idx, val) => {
            const updated = [...(section.features || [])];
            updated[idx] = val;
            updateField('features', updated);
        };

        const handleRemoveFeature = (idx) => {
            const updated = (section.features || []).filter((_, i) => i !== idx);
            updateField('features', updated);
        };

        return (
            <div className="space-y-4">
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">اختر المنتج لعرضه <span className="text-gray-400 font-normal">(اختياري)</span></label>
                    <select
                        value={section.product_id || ''}
                        onChange={(e) => handleProductChange(e.target.value)}
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    >
                        <option value="">-- بدون منتج محدد --</option>
                        {products.map(p => (
                            <option key={p.id} value={p.id}>{p.name} ({p.price} ج.م)</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">عنوان العرض</label>
                    <input
                        type="text"
                        value={section.title || ''}
                        onChange={(e) => updateField('title', e.target.value)}
                        placeholder="خصائص ومميزات المنتج الفريدة"
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                </div>
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">نص زر تأكيد الطلب</label>
                    <input
                        type="text"
                        value={section.button_text || ''}
                        onChange={(e) => updateField('button_text', e.target.value)}
                        placeholder="اطلب الآن بالدفع عند الاستلام"
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                </div>

                {/* Features Checklist */}
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <label className="block text-xs font-bold text-gray-700">مواصفات ونقاط تميز المنتج:</label>
                        <button
                            type="button"
                            onClick={handleAddFeature}
                            className="text-[10px] text-orange-600 hover:text-orange-700 font-bold bg-orange-50 px-2 py-1 rounded"
                        >
                            ➕ إضافة ميزة
                        </button>
                    </div>
                    <div className="space-y-2 max-h-48 overflow-y-auto pr-1">
                        {(section.features || []).map((feature, idx) => (
                            <div key={idx} className="flex items-center gap-2">
                                <input
                                    type="text"
                                    value={feature}
                                    onChange={(e) => handleFeatureChange(idx, e.target.value)}
                                    className="flex-1 px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                                <button
                                    type="button"
                                    onClick={() => handleRemoveFeature(idx)}
                                    className="p-1.5 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    // --- 4. FEATURES GRID EDITOR ---
    if (section.type === 'features') {
        const handleAddFeatureObj = () => {
            const current = section.features || [];
            updateField('features', [...current, { title: 'ميزة جديدة', desc: 'شرح مبسط لكيفية استفادة العميل من هذه الميزة بالتحديد.' }]);
        };

        const handleFeatureObjChange = (idx, field, val) => {
            const updated = [...(section.features || [])];
            updated[idx] = {
                ...updated[idx],
                [field]: val,
            };
            updateField('features', updated);
        };

        const handleRemoveFeatureObj = (idx) => {
            const updated = (section.features || []).filter((_, i) => i !== idx);
            updateField('features', updated);
        };

        return (
            <div className="space-y-4">
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">عنوان قسم المزايا</label>
                    <input
                        type="text"
                        value={section.title || ''}
                        onChange={(e) => updateField('title', e.target.value)}
                        placeholder="لماذا يجب عليك اختيار منتجنا؟"
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                </div>

                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <label className="block text-xs font-bold text-gray-700">قائمة المزايا والفوائد:</label>
                        <button
                            type="button"
                            onClick={handleAddFeatureObj}
                            className="text-[10px] text-orange-600 hover:text-orange-700 font-bold bg-orange-50 px-2 py-1 rounded"
                        >
                            ➕ إضافة كارت ميزة
                        </button>
                    </div>

                    <div className="space-y-4 max-h-64 overflow-y-auto pr-1">
                        {(section.features || []).map((feat, idx) => (
                            <div key={idx} className="p-3 border border-gray-100 rounded-xl bg-gray-50/50 space-y-2 relative">
                                <button
                                    type="button"
                                    onClick={() => handleRemoveFeatureObj(idx)}
                                    className="absolute left-2 top-2 p-1 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors"
                                >
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                <div>
                                    <label className="block text-[10px] font-bold text-gray-500 mb-0.5">عنوان الميزة</label>
                                    <input
                                        type="text"
                                        value={feat.title || ''}
                                        onChange={(e) => handleFeatureObjChange(idx, 'title', e.target.value)}
                                        className="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-orange-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-[10px] font-bold text-gray-500 mb-0.5">شرح الميزة</label>
                                    <textarea
                                        value={feat.desc || ''}
                                        onChange={(e) => handleFeatureObjChange(idx, 'desc', e.target.value)}
                                        className="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 h-14"
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    // --- 5. TESTIMONIALS EDITOR ---
    if (section.type === 'testimonials') {
        const handleAddTestimonial = () => {
            const current = section.testimonials || [];
            updateField('testimonials', [...current, { name: 'اسم العميل', role: 'عميل موثق', rating: 5, comment: 'تعليق ورأي العميل الإيجابي في تجربة شراء واستخدام المنتج.' }]);
        };

        const handleTestimonialChange = (idx, field, val) => {
            const updated = [...(section.testimonials || [])];
            updated[idx] = {
                ...updated[idx],
                [field]: val,
            };
            updateField('testimonials', updated);
        };

        const handleRemoveTestimonial = (idx) => {
            const updated = (section.testimonials || []).filter((_, i) => i !== idx);
            updateField('testimonials', updated);
        };

        return (
            <div className="space-y-4">
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">عنوان قسم التقييمات</label>
                    <input
                        type="text"
                        value={section.title || ''}
                        onChange={(e) => updateField('title', e.target.value)}
                        placeholder="ماذا يقول عملائنا عنا؟"
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                </div>

                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <label className="block text-xs font-bold text-gray-700">تقييمات وآراء العملاء:</label>
                        <button
                            type="button"
                            onClick={handleAddTestimonial}
                            className="text-[10px] text-orange-600 hover:text-orange-700 font-bold bg-orange-50 px-2 py-1 rounded"
                        >
                            ➕ إضافة رأي
                        </button>
                    </div>

                    <div className="space-y-4 max-h-64 overflow-y-auto pr-1">
                        {(section.testimonials || []).map((test, idx) => (
                            <div key={idx} className="p-3 border border-gray-100 rounded-xl bg-gray-50/50 space-y-2 relative">
                                <button
                                    type="button"
                                    onClick={() => handleRemoveTestimonial(idx)}
                                    className="absolute left-2 top-2 p-1 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors"
                                >
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <label className="block text-[10px] font-bold text-gray-500 mb-0.5">اسم العميل</label>
                                        <input
                                            type="text"
                                            value={test.name || ''}
                                            onChange={(e) => handleTestimonialChange(idx, 'name', e.target.value)}
                                            className="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-orange-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-[10px] font-bold text-gray-500 mb-0.5">التقييم (النجوم 1-5)</label>
                                        <select
                                            value={test.rating || 5}
                                            onChange={(e) => handleTestimonialChange(idx, 'rating', parseInt(e.target.value))}
                                            className="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-orange-500"
                                        >
                                            <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                                            <option value="4">⭐⭐⭐⭐ (4)</option>
                                            <option value="3">⭐⭐⭐ (3)</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-[10px] font-bold text-gray-500 mb-0.5">تعليق العميل</label>
                                    <textarea
                                        value={test.comment || ''}
                                        onChange={(e) => handleTestimonialChange(idx, 'comment', e.target.value)}
                                        className="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 h-14"
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    // --- 6. CTA EDITOR ---
    if (section.type === 'cta') {
        return (
            <div className="space-y-4">
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">العنوان الرئيسي</label>
                    <input
                        type="text"
                        value={section.title || ''}
                        onChange={(e) => updateField('title', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                </div>
                <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1">الوصف الفرعي</label>
                    <textarea
                        value={section.subtitle || ''}
                        onChange={(e) => updateField('subtitle', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 h-20"
                    />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">نص زر اتخاذ الإجراء</label>
                        <input
                            type="text"
                            value={section.button_text || 'اطلب الآن'}
                            onChange={(e) => updateField('button_text', e.target.value)}
                            className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">رابط الزر (أو # للذهاب لـ Showcase)</label>
                        <input
                            type="text"
                            value={section.button_link || '#product-showcase'}
                            onChange={(e) => updateField('button_link', e.target.value)}
                            className="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 text-left font-mono"
                            dir="ltr"
                        />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">لون خلفية القسم</label>
                        <div className="flex gap-2 items-center">
                            <input
                                type="color"
                                value={section.bg_color || '#6366f1'}
                                onChange={(e) => updateField('bg_color', e.target.value)}
                                className="w-8 h-8 p-0 rounded-lg border-0 cursor-pointer"
                            />
                            <span className="text-xs font-mono text-gray-500">{section.bg_color || '#6366f1'}</span>
                        </div>
                    </div>
                    <div>
                        <label className="block text-xs font-bold text-gray-700 mb-1">لون نصوص القسم</label>
                        <div className="flex gap-2 items-center">
                            <input
                                type="color"
                                value={section.text_color || '#ffffff'}
                                onChange={(e) => updateField('text_color', e.target.value)}
                                className="w-8 h-8 p-0 rounded-lg border-0 cursor-pointer"
                            />
                            <span className="text-xs font-mono text-gray-500">{section.text_color || '#ffffff'}</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="text-center py-6 text-gray-400 text-xs">
            نوع القسم غير معروف أو لا توجد لوحة تعديل مخصصة له.
        </div>
    );
}
