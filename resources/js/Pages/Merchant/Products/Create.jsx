import React, { useState, useRef, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';

const InputField = ({ label, name, data, setData, errors, type = 'text', required = false, placeholder, hint, disabled = false, children }) => (
    <div>
        <label className="block text-sm font-semibold text-gray-700 mb-1.5">
            {label} {required && <span className="text-red-500">*</span>}
        </label>
        {children || (
            <input
                type={type}
                value={data[name] !== undefined && data[name] !== null ? data[name] : ''}
                onChange={(e) => setData(name, e.target.value)}
                placeholder={placeholder}
                disabled={disabled}
                className={`w-full px-3 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-colors ${
                    errors[name] ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white'
                } ${disabled ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' : ''}`}
            />
        )}
        {hint && !errors[name] && <p className="text-xs text-gray-400 mt-1">{hint}</p>}
        {errors[name] && <p className="text-xs text-red-600 mt-1 flex items-center gap-1">
            <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" /></svg>
            {errors[name]}
        </p>}
    </div>
);

export default function ProductCreate({ categories, duplicateFrom, allProducts = [] }) {
    const getProductImage = (p) => {
        if (!p) return null;
        if (p.image_display_url) return p.image_display_url;
        const img = p.image_url || p.main_image_path;
        if (!img) return null;
        if (img.startsWith('http://') || img.startsWith('https://') || img.startsWith('/')) return img;
        return `/storage/${img}`;
    };

    const parseJSON = (val) => {
        if (!val) return [];
        try {
            const arr = typeof val === 'string' ? JSON.parse(val) : (Array.isArray(val) ? val : []);
            return arr.map(t => {
                if (t && typeof t === 'object' && t.price !== undefined) {
                    return { ...t, price: t.price ? Math.round(Number(t.price)) : '' };
                }
                return t;
            });
        } catch (e) {
            return [];
        }
    };

    const initialGalleryPreviews = duplicateFrom?.images
        ? duplicateFrom.images.map(img => {
            const p = img.image_path;
            return (p.startsWith('http://') || p.startsWith('https://') || p.startsWith('/')) ? p : `/storage/${p}`;
        })
        : [];

    const [mainImagePreview, setMainImagePreview] = useState(getProductImage(duplicateFrom));
    const mainImageRef = useRef(null);
    const galleryInputRef = useRef(null);
    
    const [sizeInput, setSizeInput] = useState('');
    const [colorInput, setColorInput] = useState('');
    const [customVariantInputs, setCustomVariantInputs] = useState({});

    // Extra Features States
    const [priceTiers, setPriceTiers] = useState(parseJSON(duplicateFrom?.price_tiers));
    const initialVariants = () => {
        const parsed = parseJSON(duplicateFrom?.variants_stock);
        const defaultPrice = duplicateFrom?.price_after ? Math.round(Number(duplicateFrom.price_after)) : '';
        if (Array.isArray(parsed) && parsed.length > 0) {
            return parsed.map(v => ({
                ...v,
                price: (v.price !== undefined && v.price !== null && v.price !== '') ? v.price : defaultPrice,
                qty: (v.qty !== undefined && v.qty !== null && v.qty !== '') ? v.qty : 100,
            }));
        }
        return [];
    };
    const [variantsStock, setVariantsStock] = useState(initialVariants);
    const [galleryPreviews, setGalleryPreviews] = useState(initialGalleryPreviews);
    const [showVariantsStockSection, setShowVariantsStockSection] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        name: duplicateFrom ? (duplicateFrom.name + ' (نسخة)') : '',
        description: duplicateFrom ? (duplicateFrom.description || '') : '',
        price_before: duplicateFrom?.price_before ? Math.round(Number(duplicateFrom.price_before)) : '',
        price_after: duplicateFrom?.price_after ? Math.round(Number(duplicateFrom.price_after)) : '',
        stock: duplicateFrom?.stock !== undefined ? duplicateFrom.stock : '100',
        low_stock_threshold: duplicateFrom?.low_stock_threshold !== undefined ? duplicateFrom.low_stock_threshold : '5',
        category_id: duplicateFrom?.category_id || '',
        shipping_type: duplicateFrom?.shipping_type || 'free',
        main_image: null,
        existing_main_image: duplicateFrom ? (duplicateFrom.main_image_path || duplicateFrom.image_url) : null,
        gallery: [],
        existing_gallery: duplicateFrom?.images ? duplicateFrom.images.map(img => img.image_path) : [],
        sizes: parseJSON(duplicateFrom?.sizes),
        colors: parseJSON(duplicateFrom?.colors),
        custom_variants: parseJSON(duplicateFrom?.custom_variants),
        price_tiers_json: duplicateFrom?.price_tiers ? (typeof duplicateFrom.price_tiers === 'string' ? duplicateFrom.price_tiers : JSON.stringify(duplicateFrom.price_tiers)) : '[]',
        variants_stock: duplicateFrom?.variants_stock ? (typeof duplicateFrom.variants_stock === 'string' ? duplicateFrom.variants_stock : JSON.stringify(duplicateFrom.variants_stock)) : '',
        upsell_ids: (duplicateFrom?.upsells || []).map(p => p.id),
        cross_sell_ids: (duplicateFrom?.cross_sells || duplicateFrom?.crossSells || []).map(p => p.id),
    });

    const addCustomVariant = () => {
        const newCv = [...(data.custom_variants || []), { name: '', values: [] }];
        setData('custom_variants', newCv);
    };

    const updateCustomVariantName = (index, name) => {
        const newCv = (data.custom_variants || []).map((item, i) => i === index ? { ...item, name } : item);
        setData('custom_variants', newCv);
    };

    const removeCustomVariant = (index) => {
        const newCv = (data.custom_variants || []).filter((_, i) => i !== index);
        setData('custom_variants', newCv);
    };

    const addCustomVariantValue = (index) => {
        const val = (customVariantInputs[index] || '').trim();
        if (!val) return;
        const newCv = (data.custom_variants || []).map((item, i) => {
            if (i === index) {
                if (item.values.includes(val)) return item;
                return { ...item, values: [...item.values, val] };
            }
            return item;
        });
        setData('custom_variants', newCv);
        setCustomVariantInputs({ ...customVariantInputs, [index]: '' });
    };

    const removeCustomVariantValue = (vIdx, valIdx) => {
        const newCv = (data.custom_variants || []).map((item, i) => {
            if (i === vIdx) {
                return { ...item, values: item.values.filter((_, idx) => idx !== valIdx) };
            }
            return item;
        });
        setData('custom_variants', newCv);
    };

    const handleMainImage = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('main_image', file);
            const reader = new FileReader();
            reader.onload = (ev) => setMainImagePreview(ev.target.result);
            reader.readAsDataURL(file);
        }
    };

    const handleGalleryChange = (e) => {
        const files = Array.from(e.target.files);
        if (files.length > 0) {
            const newGallery = [...(data.gallery || []), ...files];
            setData('gallery', newGallery);

            // Generate Previews
            files.forEach((file) => {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    setGalleryPreviews(prev => [...prev, ev.target.result]);
                };
                reader.readAsDataURL(file);
            });
        }
    };

    const removeGalleryImage = (index) => {
        const newGallery = (data.gallery || []).filter((_, i) => i !== index);
        const newPreviews = galleryPreviews.filter((_, i) => i !== index);
        setData('gallery', newGallery);
        setGalleryPreviews(newPreviews);
    };

    // Price Tiers Management
    const addPriceTier = () => {
        const newQty = priceTiers.length > 0 ? Math.max(...priceTiers.map(t => t.min_qty)) + 1 : 2;
        const newTiers = [...priceTiers, { min_qty: newQty, price: '' }];
        setPriceTiers(newTiers);
        setData('price_tiers_json', JSON.stringify(newTiers));
    };

    const handlePriceTierChange = (index, field, value) => {
        const newTiers = priceTiers.map((tier, idx) => {
            if (idx === index) {
                return { ...tier, [field]: value };
            }
            return tier;
        });
        setPriceTiers(newTiers);
        setData('price_tiers_json', JSON.stringify(newTiers));
    };

    const removePriceTier = (index) => {
        const newTiers = priceTiers.filter((_, idx) => idx !== index);
        setPriceTiers(newTiers);
        setData('price_tiers_json', JSON.stringify(newTiers));
    };

    // Variant Combinations
    const activeSizes = data.sizes.filter(s => s.trim() !== '');
    const activeColors = data.colors.filter(c => c.trim() !== '');
    const activeCustomVariants = (data.custom_variants || []).filter(cv => cv.name && cv.name.trim() !== '' && cv.values && cv.values.filter(v => v.trim() !== '').length > 0);
    const hasVariants = activeSizes.length > 0 || activeColors.length > 0 || activeCustomVariants.length > 0;

    const isComboEqual = (v1, v2) => {
        if ((v1.size || null) !== (v2.size || null)) return false;
        if ((v1.color || null) !== (v2.color || null)) return false;
        const opt1 = v1.options || {};
        const opt2 = v2.options || {};
        const keys1 = Object.keys(opt1);
        const keys2 = Object.keys(opt2);
        if (keys1.length !== keys2.length) return false;
        for (let k of keys1) {
            if (opt1[k] !== opt2[k]) return false;
        }
        return true;
    };

    const generateCombinations = () => {
        if (!hasVariants) return [];

        let dimensions = [];

        if (activeSizes.length > 0) {
            dimensions.push(activeSizes.map(s => ({ key: 'size', name: 'المقاس', value: s })));
        }
        if (activeColors.length > 0) {
            dimensions.push(activeColors.map(c => ({ key: 'color', name: 'اللون', value: c })));
        }
        activeCustomVariants.forEach(cv => {
            const vals = cv.values.filter(v => v.trim() !== '');
            if (vals.length > 0) {
                dimensions.push(vals.map(v => ({ key: 'custom', name: cv.name, value: v })));
            }
        });

        if (dimensions.length === 0) return [];

        const cartesian = (args) => {
            return args.reduce((a, b) => {
                return a.flatMap(d => b.map(e => [...(Array.isArray(d) ? d : [d]), e]));
            }, [[]]);
        };

        const rawCombos = cartesian(dimensions);

        return rawCombos.map(comboArr => {
            const arr = Array.isArray(comboArr) ? comboArr : [comboArr];
            let size = null;
            let color = null;
            let options = {};

            arr.forEach(item => {
                if (item.key === 'size') size = item.value;
                else if (item.key === 'color') color = item.value;
                else if (item.key === 'custom') options[item.name] = item.value;
            });

            return { size, color, options };
        });
    };

    const combinations = generateCombinations();

    // Sync variantsStock with combinations, defaulting to 100 and product default price
    useEffect(() => {
        if (!hasVariants) {
            if (variantsStock.length > 0) {
                setVariantsStock([]);
                setData('variants_stock', '');
            }
            return;
        }

        let updated = [];
        let changed = false;
        const defaultPrice = data.price_after ? Math.round(Number(data.price_after)) : '';

        combinations.forEach(combo => {
            const found = variantsStock.find(v => isComboEqual(v, combo));
            if (found) {
                if (found.qty === null || found.qty === undefined || found.qty === '') {
                    found.qty = 100;
                    changed = true;
                }
                if (found.price === null || found.price === undefined || (found.price === '' && !found._userEdited)) {
                    found.price = defaultPrice;
                    changed = true;
                }
                updated.push(found);
            } else {
                updated.push({
                    size: combo.size,
                    color: combo.color,
                    options: combo.options,
                    price: defaultPrice,
                    qty: 100
                });
                changed = true;
            }
        });

        // Remove deleted combinations
        if (variantsStock.length !== updated.length) {
            changed = true;
        }

        if (changed) {
            setVariantsStock(updated);
            setData('variants_stock', JSON.stringify(updated));
        }
    }, [data.sizes, data.colors, data.custom_variants, hasVariants]);

    // Calculate total stock from variants
    useEffect(() => {
        if (hasVariants) {
            const totalStock = variantsStock.reduce((sum, v) => sum + (parseInt(v.qty) || 0), 0);
            if (parseInt(data.stock) !== totalStock) {
                setData('stock', String(totalStock));
            }
        }
    }, [variantsStock, hasVariants]);

    const handleVariantPriceChange = (combo, value) => {
        let updated = [...variantsStock];
        const idx = updated.findIndex(v => isComboEqual(v, combo));
        if (idx > -1) {
            updated[idx].price = value;
            updated[idx]._userEdited = true;
        } else {
            updated.push({ ...combo, price: value, qty: 100, _userEdited: true });
        }
        setVariantsStock(updated);
        setData('variants_stock', JSON.stringify(updated));
    };

    const handleVariantStockChange = (combo, value) => {
        let updated = [...variantsStock];
        const idx = updated.findIndex(v => isComboEqual(v, combo));
        if (idx > -1) {
            updated[idx].qty = value === '' ? '' : (parseInt(value) || 0);
        } else {
            updated.push({ ...combo, price: '', qty: value === '' ? '' : (parseInt(value) || 0) });
        }
        setVariantsStock(updated);
        setData('variants_stock', JSON.stringify(updated));
    };

    const getVariantPriceValue = (combo) => {
        const found = variantsStock.find(v => isComboEqual(v, combo));
        if (found && found.price !== undefined && found.price !== null) {
            return found.price;
        }
        return data.price_after ? Math.round(Number(data.price_after)) : '';
    };

    const getVariantStockValue = (combo) => {
        const found = variantsStock.find(v => isComboEqual(v, combo));
        return found ? found.qty : 100;
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const cleaned = variantsStock.map(v => {
            const { _userEdited, ...rest } = v;
            return {
                ...rest,
                price: (v.price !== undefined && v.price !== null && v.price !== '') ? Number(v.price) : (data.price_after ? Number(data.price_after) : 0),
                qty: (v.qty !== undefined && v.qty !== null && v.qty !== '') ? parseInt(v.qty) : 100,
            };
        });
        setData('variants_stock', JSON.stringify(cleaned));
        post('/admin/products', {
            forceFormData: true,
        });
    };

    return (
        <MerchantLayout title="إضافة منتج جديد">
            <Head title="إضافة منتج جديد" />

            <div className="max-w-3xl mx-auto mb-10">
                {/* Breadcrumb */}
                <nav className="flex items-center gap-2 text-sm text-gray-500 mb-5">
                    <Link href="/admin/products" className="hover:text-orange-600 transition-colors">المنتجات</Link>
                    <svg className="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span className="text-gray-800 font-medium">{duplicateFrom ? `استنساخ: ${duplicateFrom.name}` : 'إضافة منتج جديد'}</span>
                </nav>

                {duplicateFrom && (
                    <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-center justify-between shadow-sm animate-fade-in">
                        <div className="flex items-center gap-3">
                            <span className="text-2xl">📋</span>
                            <div>
                                <h4 className="font-bold text-amber-900 text-sm">جاري استنساخ المنتج "{duplicateFrom.name}"</h4>
                                <p className="text-xs text-amber-700 mt-0.5">تم نسْخ كافة بيانات المنتج والأسعار والتصنيفات والخيارات والصور، يمكنك التعديل عليها وحفظ المنتج كنسخة جديدة.</p>
                            </div>
                        </div>
                        <Link href="/admin/products/create" className="text-xs font-semibold text-amber-800 hover:text-amber-950 bg-white px-3 py-1.5 rounded-lg border border-amber-300 shadow-xs transition-colors shrink-0">
                            إلغاء الاستنساخ ✕
                        </Link>
                    </div>
                )}

                <form onSubmit={handleSubmit} encType="multipart/form-data" className="space-y-6">
                    {/* Basic Info */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                        <h3 className="font-semibold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                            <span className="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">١</span>
                            المعلومات الأساسية
                        </h3>
                        <div className="space-y-4">
                            <InputField label="اسم المنتج" name="name" required placeholder="أدخل اسم المنتج" data={data} setData={setData} errors={errors} />
                            
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-1.5">التصنيف <span className="text-red-500">*</span></label>
                                <select
                                    value={data.category_id}
                                    onChange={(e) => setData('category_id', e.target.value)}
                                    className={`w-full px-3 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent bg-white ${
                                        errors.category_id ? 'border-red-400 bg-red-50' : 'border-gray-300'
                                    }`}
                                >
                                    <option value="">اختر التصنيف...</option>
                                    {categories?.map((cat) => (
                                        <option key={cat.id} value={cat.id}>
                                            {cat.name_ar || cat.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.category_id && (
                                    <p className="text-xs text-red-600 mt-1">{errors.category_id}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Pricing */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                        <h3 className="font-semibold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                            <span className="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">٢</span>
                            السعر والمخزون
                        </h3>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <InputField
                                label="السعر قبل الخصم"
                                name="price_before"
                                type="number"
                                placeholder="0"
                                hint="اتركه فارغاً إذا لا يوجد خصم"
                                data={data}
                                setData={setData}
                                errors={errors}
                            />
                            <InputField
                                label="السعر بعد الخصم"
                                name="price_after"
                                type="number"
                                required
                                placeholder="0"
                                hint="السعر الفعلي للمنتج"
                                data={data}
                                setData={setData}
                                errors={errors}
                            />
                        </div>

                        {/* Volume Tier Pricing (أسعار الكميات) */}
                        <div className="pt-4 border-t border-gray-100">
                            <div className="flex items-center justify-between mb-3">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-800">أسعار الكميات (اختياري)</label>
                                    <p className="text-xs text-gray-400 mt-0.5">حدد سعر شراء مخفض في حالة شراء قطعة أو أكثر</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={addPriceTier}
                                    className="px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-semibold flex items-center gap-1 transition-colors"
                                >
                                    + إضافة شريحة سعرية
                                </button>
                            </div>

                            {priceTiers.length === 0 ? (
                                <p className="text-xs text-gray-400 text-center py-3 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                    لا توجد شرائح سعرية مضافة حالياً.
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {priceTiers.map((tier, index) => (
                                        <div key={index} className="bg-gray-50 p-3 rounded-xl border border-gray-200 shadow-sm space-y-2">
                                            <div className="flex items-center justify-between gap-2 border-b border-gray-200/60 pb-2">
                                                <span className="text-xs font-bold text-gray-800 flex items-center gap-1">
                                                    🏷️ شريحة رقم {index + 1}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => removePriceTier(index)}
                                                    className="text-red-500 hover:text-red-700 p-1 px-2 rounded-lg hover:bg-red-50 text-xs font-bold transition-colors"
                                                    title="حذف الشريحة"
                                                >
                                                    ✕ حذف
                                                </button>
                                            </div>

                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 items-center pt-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs font-semibold text-gray-700 whitespace-nowrap">عند شراء:</span>
                                                    <div className="relative flex-1 sm:flex-initial">
                                                        <input
                                                            type="number"
                                                            min="2"
                                                            value={tier.min_qty}
                                                            onChange={(e) => handlePriceTierChange(index, 'min_qty', e.target.value)}
                                                            className="w-full sm:w-20 px-3 py-1.5 border border-gray-300 rounded-lg text-center text-xs font-bold focus:ring-2 focus:ring-orange-400 bg-white"
                                                        />
                                                    </div>
                                                    <span className="text-xs font-medium text-gray-600 shrink-0">قطع</span>
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs font-semibold text-gray-700 whitespace-nowrap shrink-0">السعر الإجمالي:</span>
                                                    <div className="relative flex-1">
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            value={tier.price}
                                                            onChange={(e) => handlePriceTierChange(index, 'price', e.target.value)}
                                                            placeholder="مثال: 750"
                                                            className="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-bold focus:ring-2 focus:ring-orange-400 bg-white"
                                                        />
                                                    </div>
                                                    <span className="text-xs font-bold text-gray-500 shrink-0">ج.م</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Shipping */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                        <h3 className="font-semibold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                            <span className="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">٣</span>
                            خيارات الشحن
                        </h3>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {[
                                { value: 'free', label: 'شحن مجاني', desc: 'يُشحن المنتج مجاناً', icon: '🚚' },
                                { value: 'governorate', label: 'شحن بالمحافظة', desc: 'السعر يختلف حسب المحافظة', icon: '📦' },
                            ].map((opt) => (
                                <label
                                    key={opt.value}
                                    className={`relative flex items-start gap-3 p-4 rounded-lg border-2 cursor-pointer transition-all ${
                                        data.shipping_type === opt.value
                                            ? 'border-orange-500 bg-orange-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="shipping_type"
                                        value={opt.value}
                                        checked={data.shipping_type === opt.value}
                                        onChange={(e) => setData('shipping_type', e.target.value)}
                                        className="mt-0.5 accent-orange-600"
                                    />
                                    <div className="flex-1">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-1.5">
                                                <span className="text-lg">{opt.icon}</span>
                                                <span className="font-medium text-gray-800 text-sm">{opt.label}</span>
                                            </div>
                                            {opt.value === 'governorate' && (
                                                <a
                                                    href="/admin/shipping"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    onClick={(e) => e.stopPropagation()}
                                                    className="text-orange-600 hover:text-orange-800 p-1 rounded-md hover:bg-orange-100/50 transition-colors flex items-center justify-center"
                                                    title="ضبط أسعار الشحن للمحافظات"
                                                >
                                                    <svg className="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </a>
                                            )}
                                        </div>
                                        <p className="text-xs text-gray-500 mt-1">{opt.desc}</p>
                                    </div>
                                </label>
                            ))}
                        </div>
                        {errors.shipping_type && <p className="text-xs text-red-600 mt-2">{errors.shipping_type}</p>}
                    </div>

                    {/* Stock Management */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                        <h3 className="font-semibold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                            <span className="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">٤</span>
                            كميات المخزون
                        </h3>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <InputField
                                label="الكمية الإجمالية المتاحة"
                                name="stock"
                                type="number"
                                required
                                placeholder="0"
                                hint={hasVariants ? "تم احتسابه تلقائياً من مجموع المقاسات والألوان" : "الكمية الكلية للمنتج"}
                                data={data}
                                setData={setData}
                                errors={errors}
                                disabled={hasVariants}
                            />
                            <InputField
                                label="حد التنبيه بانخفاض المخزون"
                                name="low_stock_threshold"
                                type="number"
                                placeholder="5"
                                hint="سيتم تنبيهك عندما يقل المخزون عن هذا الرقم (اختياري)"
                                data={data}
                                setData={setData}
                                errors={errors}
                            />
                        </div>
                    </div>

                    {/* Description */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                        <h3 className="font-semibold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                            <span className="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">٥</span>
                            تفاصيل ووصف المنتج
                        </h3>
                        <div>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="وصف تفصيلي للمنتج..."
                                rows={4}
                                className={`w-full px-3 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent resize-none ${
                                    errors.description ? 'border-red-400 bg-red-50' : 'border-gray-300'
                                }`}
                            />
                            {errors.description && <p className="text-xs text-red-600 mt-1">{errors.description}</p>}
                        </div>
                    </div>

                    {/* Sizes & Colors */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                        <h3 className="font-semibold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                            <span className="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">٦</span>
                            المقاسات والألوان (اختياري)
                        </h3>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {/* Sizes */}
                            <div className="space-y-3">
                                <label className="block text-sm font-semibold text-gray-700">المقاسات المتاحة</label>
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        placeholder="مثال: M, L, XL, 42"
                                        value={sizeInput}
                                        onChange={(e) => setSizeInput(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                if (sizeInput.trim() && !data.sizes.includes(sizeInput.trim())) {
                                                    setData('sizes', [...data.sizes, sizeInput.trim()]);
                                                    setSizeInput('');
                                                }
                                            }
                                        }}
                                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (sizeInput.trim() && !data.sizes.includes(sizeInput.trim())) {
                                                setData('sizes', [...data.sizes, sizeInput.trim()]);
                                                setSizeInput('');
                                            }
                                        }}
                                        className="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors"
                                    >
                                        إضافة
                                    </button>
                                </div>
                                <div className="flex flex-wrap gap-1.5 mt-2">
                                    {data.sizes.map((sz, idx) => (
                                        <span key={idx} className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 text-xs font-semibold border border-orange-100">
                                            {sz}
                                            <button
                                                type="button"
                                                onClick={() => setData('sizes', data.sizes.filter(x => x !== sz))}
                                                className="text-orange-500 hover:text-orange-700 font-bold"
                                            >
                                                ✕
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            </div>

                            {/* Colors */}
                            <div className="space-y-3">
                                <label className="block text-sm font-semibold text-gray-700">الألوان المتاحة</label>
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        placeholder="مثال: أحمر، أزرق، أسود"
                                        value={colorInput}
                                        onChange={(e) => setColorInput(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                if (colorInput.trim() && !data.colors.includes(colorInput.trim())) {
                                                    setData('colors', [...data.colors, colorInput.trim()]);
                                                    setColorInput('');
                                                }
                                            }
                                        }}
                                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (colorInput.trim() && !data.colors.includes(colorInput.trim())) {
                                                setData('colors', [...data.colors, colorInput.trim()]);
                                                setColorInput('');
                                            }
                                        }}
                                        className="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors"
                                    >
                                        إضافة
                                    </button>
                                </div>
                                <div className="flex flex-wrap gap-1.5 mt-2">
                                    {data.colors.map((col, idx) => (
                                        <span key={idx} className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 text-xs font-semibold border border-orange-100">
                                            {col}
                                            <button
                                                type="button"
                                                onClick={() => setData('colors', data.colors.filter(x => x !== col))}
                                                className="text-orange-500 hover:text-orange-700 font-bold"
                                            >
                                                ✕
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Custom Dynamic Variants (إضافة متغيرات مخصصة) */}
                        <div className="pt-4 border-t border-gray-100 space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-800">متغيرات مخصصة أخرى (مثل: عروض، الموديل، الوزن، النوع...)</label>
                                    <p className="text-xs text-gray-400 mt-0.5">يمكنك إضافة خيارات مخصصة إضافية ليختار منها العميل عند الشراء</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={addCustomVariant}
                                    className="px-3.5 py-1.5 bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200 rounded-lg text-xs font-bold flex items-center gap-1 transition-colors"
                                >
                                    + إضافة متغير
                                </button>
                            </div>

                            {data.custom_variants && data.custom_variants.length > 0 && (
                                <div className="space-y-4">
                                    {data.custom_variants.map((variant, vIdx) => (
                                        <div key={vIdx} className="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3 relative">
                                            <div className="flex items-center justify-between gap-3">
                                                <div className="flex-1">
                                                    <label className="block text-xs font-bold text-gray-700 mb-1">اسم المتغير (مثل: عروض / الموديل):</label>
                                                    <input
                                                        type="text"
                                                        placeholder="عروض، الموديل، الوزن، النوع..."
                                                        value={variant.name}
                                                        onChange={(e) => updateCustomVariantName(vIdx, e.target.value)}
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-orange-400 font-semibold"
                                                    />
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeCustomVariant(vIdx)}
                                                    className="w-9 h-9 flex items-center justify-center bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200 transition-colors self-end mb-0.5 flex-shrink-0"
                                                    title="حذف المتغير"
                                                >
                                                    <svg className="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <div>
                                                <label className="block text-xs font-bold text-gray-700 mb-1">قيم الخيارات المتاحة لهذا المتغير:</label>
                                                <div className="flex gap-2">
                                                    <input
                                                        type="text"
                                                        placeholder="اكتب الخيار واضغط إضافة (مثل: عرض 1 مسكرة مع قلم روج)"
                                                        value={customVariantInputs[vIdx] || ''}
                                                        onChange={(e) => setCustomVariantInputs({ ...customVariantInputs, [vIdx]: e.target.value })}
                                                        onKeyDown={(e) => {
                                                            if (e.key === 'Enter') {
                                                                e.preventDefault();
                                                                addCustomVariantValue(vIdx);
                                                            }
                                                        }}
                                                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-orange-400"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => addCustomVariantValue(vIdx)}
                                                        className="px-4 py-2 bg-gray-800 text-white rounded-lg text-xs font-medium hover:bg-gray-700 transition-colors"
                                                    >
                                                        إضافة
                                                    </button>
                                                </div>

                                                <div className="flex flex-wrap gap-1.5 mt-2">
                                                    {variant.values && variant.values.map((val, valIdx) => (
                                                        <span key={valIdx} className="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-orange-100 text-orange-900 text-xs font-bold border border-orange-200 shadow-sm">
                                                            {val}
                                                            <button
                                                                type="button"
                                                                onClick={() => removeCustomVariantValue(vIdx, valIdx)}
                                                                className="text-orange-600 hover:text-orange-950 font-extrabold"
                                                            >
                                                                ✕
                                                            </button>
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Variants Detailed Stock (مخزون المتغيرات التفصيلي) */}
                        {hasVariants && (
                            <div className="pt-4 border-t border-gray-100">
                                <button
                                    type="button"
                                    onClick={() => setShowVariantsStockSection(!showVariantsStockSection)}
                                    className="w-full flex items-center justify-between px-4 py-3 bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-xl text-sm font-semibold transition-all"
                                >
                                    <span className="flex items-center gap-2">
                                        📋 تفاصيل أكثر
                                    </span>
                                    <span>{showVariantsStockSection ? '▲' : '▼'}</span>
                                </button>

                                {showVariantsStockSection && (
                                    <div className="mt-4 border rounded-xl overflow-hidden border-gray-200 shadow-sm animate-in fade-in slide-in-from-top-2 duration-200 overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200 bg-white">
                                            <thead className="bg-gray-50">
                                                <tr>
                                                    {activeSizes.length > 0 && <th className="px-1.5 sm:px-4 py-2 sm:py-3 text-right text-xs font-bold text-gray-500">المقاس</th>}
                                                    {activeColors.length > 0 && <th className="px-1.5 sm:px-4 py-2 sm:py-3 text-right text-xs font-bold text-gray-500">اللون</th>}
                                                    {activeCustomVariants.map((cv, i) => (
                                                        <th key={i} className="px-1.5 sm:px-4 py-2 sm:py-3 text-right text-xs font-bold text-gray-500">{cv.name}</th>
                                                    ))}
                                                    <th className="px-1.5 sm:px-4 py-2 sm:py-3 text-center text-xs font-bold text-gray-500">السعر المخصص (ج.م)</th>
                                                    <th className="px-1.5 sm:px-4 py-2 sm:py-3 text-center text-xs font-bold text-gray-500">الكمية المتاحة</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200">
                                                {combinations.map((combo, idx) => (
                                                    <tr key={idx} className="hover:bg-gray-50/50">
                                                        {activeSizes.length > 0 && <td className="px-1.5 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-semibold text-gray-900">{combo.size}</td>}
                                                        {activeColors.length > 0 && (
                                                            <td className="px-1.5 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-gray-500">
                                                                <span className="px-1.5 py-0.5 sm:px-2.5 sm:py-1 bg-gray-100 rounded-lg text-gray-700 text-xs border border-gray-200 inline-block">
                                                                    {combo.color}
                                                                </span>
                                                            </td>
                                                        )}
                                                        {activeCustomVariants.map((cv, i) => (
                                                            <td key={i} className="px-1.5 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-gray-700 font-medium">
                                                                <span className="px-1.5 py-0.5 sm:px-2.5 sm:py-1 bg-orange-50 text-orange-800 rounded-lg text-xs border border-orange-200 inline-block">
                                                                    {combo.options ? combo.options[cv.name] : '-'}
                                                                </span>
                                                            </td>
                                                        ))}
                                                        <td className="px-1 sm:px-4 py-2 sm:py-3 text-center">
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                placeholder={data.price_after ? `${Math.round(Number(data.price_after))}` : 'السعر'}
                                                                value={getVariantPriceValue(combo)}
                                                                onChange={(e) => handleVariantPriceChange(combo, e.target.value)}
                                                                className="w-full min-w-[70px] sm:max-w-[130px] px-1.5 py-1 sm:px-2.5 sm:py-1.5 border border-gray-300 rounded-lg text-xs sm:text-sm focus:ring-1 focus:ring-purple-400 focus:border-transparent bg-white text-center font-medium text-gray-800"
                                                            />
                                                        </td>
                                                        <td className="px-1 sm:px-4 py-2 sm:py-3 text-center">
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                placeholder="غير محدود"
                                                                value={getVariantStockValue(combo)}
                                                                onChange={(e) => handleVariantStockChange(combo, e.target.value)}
                                                                className="w-full min-w-[65px] sm:max-w-[110px] px-1.5 py-1 sm:px-2.5 sm:py-1.5 border border-gray-300 rounded-lg text-xs sm:text-sm focus:ring-1 focus:ring-purple-400 focus:border-transparent bg-white text-center"
                                                            />
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Images Section */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">
                        <h3 className="font-semibold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                            <span className="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">٧</span>
                            صور المنتج
                        </h3>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Main Image */}
                            <div className="space-y-2">
                                <label className="block text-sm font-semibold text-gray-700">الصورة الرئيسية <span className="text-red-500">*</span></label>
                                <div
                                    onClick={() => mainImageRef.current?.click()}
                                    className={`relative border-2 border-dashed rounded-xl overflow-hidden cursor-pointer transition-all hover:border-orange-400 flex items-center justify-center bg-gray-50 ${
                                        mainImagePreview ? 'border-orange-400' : 'border-gray-300'
                                    }`}
                                    style={{ height: '180px' }}
                                >
                                    {mainImagePreview ? (
                                        <>
                                            <img
                                                src={mainImagePreview}
                                                alt="Preview"
                                                className="w-full h-full object-cover"
                                            />
                                            <div className="absolute inset-0 bg-black/40 opacity-0 hover:opacity-100 transition-all flex items-center justify-center">
                                                <span className="text-white text-xs font-semibold bg-black/50 px-3 py-1.5 rounded-full">تغيير الصورة</span>
                                            </div>
                                        </>
                                    ) : (
                                        <div className="flex flex-col items-center justify-center text-center p-4">
                                            <svg className="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p className="text-xs text-gray-600 font-semibold">اضغط لرفع الصورة الرئيسية</p>
                                            <p className="text-[10px] text-gray-400 mt-1">JPG, PNG, WebP (حد أقصى 4MB)</p>
                                        </div>
                                    )}
                                </div>
                                <input
                                    ref={mainImageRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={handleMainImage}
                                    className="hidden"
                                />
                                {errors.main_image && (
                                    <p className="text-xs text-red-600 mt-1">{errors.main_image}</p>
                                )}
                            </div>

                            {/* Gallery Images (الصور الفرعية) */}
                            <div className="space-y-2">
                                <label className="block text-sm font-semibold text-gray-700">الصور الفرعية المعرض (اختياري)</label>
                                <div
                                    onClick={() => galleryInputRef.current?.click()}
                                    className="border-2 border-dashed border-gray-300 rounded-xl overflow-hidden cursor-pointer transition-all hover:border-orange-400 flex items-center justify-center bg-gray-50"
                                    style={{ height: '180px' }}
                                >
                                    <div className="flex flex-col items-center justify-center text-center p-4">
                                        <svg className="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <p className="text-xs text-gray-600 font-semibold">اضغط لاختيار صور فرعية متعددة</p>
                                        <p className="text-[10px] text-gray-400 mt-1">يمكنك تحديد عدة صور معاً</p>
                                    </div>
                                </div>
                                <input
                                    ref={galleryInputRef}
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={handleGalleryChange}
                                    className="hidden"
                                />
                            </div>
                        </div>

                        {/* Selected Gallery Previews */}
                        {galleryPreviews.length > 0 && (
                            <div className="space-y-2 pt-2">
                                <h4 className="text-xs font-semibold text-gray-600">الصور الفرعية المحددة ({galleryPreviews.length})</h4>
                                <div className="grid grid-cols-4 sm:grid-cols-6 gap-3">
                                    {galleryPreviews.map((preview, idx) => (
                                        <div key={idx} className="relative group aspect-square rounded-lg border border-gray-200 overflow-hidden bg-gray-50">
                                            <img src={preview} alt="Gallery Preview" className="w-full h-full object-cover" />
                                            <button
                                                type="button"
                                                onClick={() => removeGalleryImage(idx)}
                                                className="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-90 hover:opacity-100 shadow-md transition-opacity"
                                            >
                                                <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-4">
                        <Link
                            href="/admin/products"
                            className="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors"
                        >
                            إلغاء
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex items-center justify-center gap-2 px-8 py-3 bg-orange-600 text-white rounded-xl text-sm font-semibold hover:bg-orange-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed shadow-sm"
                        >
                            {processing ? (
                                <>
                                    <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    جاري الحفظ...
                                </>
                            ) : (
                                <>
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    حفظ المنتج الجديد
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </MerchantLayout>
    );
}
