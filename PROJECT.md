# FastOrder — وثائق المشروع الكاملة
> ملف مرجعي للذكاء الاصطناعي لفهم المشروع بسرعة في أي جلسة جديدة

---

## نظرة عامة

**FastOrder** منصة SaaS لإنشاء متاجر إلكترونية متعددة المستأجرين (Multi-Tenant).
كل تاجر يسجل على المنصة يحصل على متجر منفصل على Subdomain خاص بيه.

- **المسار المحلي:** `e:\programing\flutter project\fast order`
- **Stack:** Laravel 12 + Inertia.js + React (JSX) + MySQL
- **بيئة التطوير:** Windows — `php artisan serve --host=0.0.0.0 --port=8000` + `npm run dev`

---

## نظام الدومين

### بيئة التطوير (.localhost)

| الغرض | الرابط |
|---|---|
| الصفحة الرئيسية | http://fastorder.localhost:8000 |
| لوحة السوبر أدمن | http://app.fastorder.localhost:8000/dashboard |
| تسجيل دخول السوبر أدمن | http://app.fastorder.localhost:8000/login |
| لوحة التاجر | http://{slug}.fastorder.localhost:8000/admin/dashboard |
| واجهة المتجر | http://{slug}.fastorder.localhost:8000 |

### ملف .env الأساسي
```
APP_URL=http://fastorder.localhost:8000
SESSION_DOMAIN=.fastorder.localhost
DB_DATABASE=bird_store7
DB_USERNAME=root
DB_PASSWORD=
```

---

## البنية المعمارية (Multi-Tenancy)

### كيف يُعرَّف الـ Tenant؟
1. Request يجي لـ {slug}.fastorder.localhost:8000
2. IdentifyTenant Middleware يشيل slug من Host Header
3. يبحث في جدول tenants بـ column slug
4. يحط الـ Tenant في app()->instance() و session('tenant_id')

### Middleware Chain للتاجر: `web → tenant → auth → tenant.active`

---

## هيكل الـ Routes

```
routes/
├── web.php              ← الرئيسي — domain routing
├── auth_superadmin.php
├── auth_merchant.php
└── api.php
```

### routes/web.php — المحاور الثلاثة
```php
$baseDomain = parse_url(config('app.url'), PHP_URL_HOST); // 'fastorder.localhost'
Route::domain($baseDomain)->group(...)                     // الرئيسية
Route::domain('app.' . $baseDomain)->group(...)            // السوبر أدمن
Route::domain('{tenant}.' . $baseDomain)->group(...)       // التجار
```

---

## نظام Auth

### السوبر أدمن
- Controller: SuperAdminSessionController.php
- Logout: `router.post('/logout', ...)`

### التاجر
- Controller: MerchantSessionController.php
- Logout: `router.post('/admin/logout', ...)`

---

## User Types

| النوع | الوصف |
|---|---|
| super_admin | أدمن المنصة |
| merchant | مالك المتجر |
| staff | موظف |
| customer | عميل |

---

## Product Model — القلب الأساسي

```php
// app/Models/Product.php
protected $casts = [
    'sizes'          => 'array',
    'colors'         => 'array',
    'variants_stock' => 'array',   // [{size, color, qty}, ...]
    'price_tiers'    => 'array',   // [{min_qty, price}, ...]
];
```

### قواعد المخزون (variants_stock):
- كل تباين: `{size, color, qty}` — الـ **qty الافتراضية = 100**
- qty فاضي/null/'' = **100 (متوفر غير محدود)**
- qty = 0 = **نفد من المخزن**
- qty ≤ 3 = **قليل (badge تحذيري)**
- حقل `stock` الرئيسي = **مجموع كل qty تلقائياً**
- الأسعار (price_after, price_before) = **integers بدون كسور**

### ⚠️ تحذير مهم:
**لا تعمل json_encode يدوي** لـ variants_stock/sizes/colors/price_tiers في Controllers. الـ Model بيعمل cast تلقائي. لو عملت encode يدوي هيحصل double encoding وخطأ.

---

## واجهة المتجر (Storefront) — HTML Themes

### مسار الثيمات
```
resources/views/shop/
├── product.html
└── themes/
    ├── default/product.html
    ├── fast_cod/product.html          ← ثيم إيزي أوردر السريع (فورم طلب مدمج بالصفحة + ستيكي بار + باقات توفير)
    ├── luxury_store/product.html      ← ثيم الماركات الفاخرة (ستايل شوبيفاي وزاره + فلاتر جانبية + شبكة عصرية)
    ├── starter/product.html
    ├── bold/product.html
    └── modern/product.html
```

### shared.js
```
public/shop/shared.js?v=20260714
```
- `formatEGP(amount)` — بدون كسور + أرقام إنجليزية دايماً (`en-US` locale)
- لو عدّلته → حدّث الـ cache buster في الـ 5 ملفات

### قواعد الأرقام والأسعار
- **لا كسور** في أي سعر
- **أرقام إنجليزية دايماً** حتى لو اللغة عربي
- التواريخ إنجليزية (Carbon locale معدّل في AppServiceProvider)

### نظام عرض المقاسات/الألوان
```javascript
// applyVariantStyle(lbl, qty) — موجود في كل الثيمات
// qty <= 0  → strikethrough + disabled (نفد)
// qty <= 3  → badge "آخر N" أصفر
// else      → عادي متاح
```

#### منطق من أول تحميل الصفحة (بدون اختيار):
- مقاس **كل** ألوانه qty=0 → المقاس يتشطب
- لون **كل** مقاساته qty=0 → اللون يتشطب

#### بعد الاختيار:
- اختر مقاس → الألوان المتوفرة معه فقط تظهر عادية
- اختر لون → المقاسات المتوفرة معه فقط تظهر عادية

### رسائل المخزون (stockIndicator):
- ✅ `متوفر في المخزن` — أخضر
- ⚠️ `قارب على النفاد (متبقي N قطعة!)` — أصفر (≤ 5)
- ❌ `نفد من المخزن` — أحمر
- **محذوف:** رسالة "يرجى تحديد المقاس واللون لعرض التوفر"
- **محذوف:** قسم آراء وتقييمات العملاء كامل

---

## Frontend — React/Inertia

### Layouts
| ملف | الاستخدام |
|---|---|
| SuperAdminLayout.jsx | لوحة السوبر أدمن |
| MerchantLayout.jsx | لوحة التاجر |

### صفحة إضافة/تعديل المنتج (Create.jsx / Edit.jsx)

#### InputField Component
```jsx
const InputField = ({ label, name, data, setData, errors,
                      type='text', required=false, placeholder,
                      hint, disabled=false, children })
```

#### منطق حقل المخزون:
1. `useEffect` يراقب sizes/colors → يعمل sync للـ combinations بـ qty=100 افتراضي
2. `useEffect` تاني يجمع كل qty ويحدث حقل `stock`
3. حقل الكمية الإجمالية: `disabled={hasVariants}` + hint "تم احتسابه تلقائياً"
4. `getVariantStockValue` ترجع `found.qty` أو **100** كـ default

---

## Controllers

### Merchant Admin — app/Http/Controllers/Merchant/
- ProductController.php ← ⚠️ لا json_encode يدوي للـ array fields
- OrderController / CategoryController / SettingController
- StaffController / ShippingController / BannerController
- PromotionController / ThemeController

### Storefront
- StorefrontController.php / CheckoutController.php

---

## قاعدة البيانات

- MySQL | Database: **bird_store7** | localhost:3306 | User: root

---

## تشغيل المشروع

```bash
php artisan serve --host=0.0.0.0 --port=8000   # terminal 1
npm run dev                                       # terminal 2
php artisan optimize:clear                        # بعد تغيير routes/config
```

---

## بيانات تجريبية

| الدور | البريد | كلمة السر |
|---|---|---|
| Super Admin | admin@fastorder.test | password |
| Merchant (demo) | merchant@demo.com | password |

---

## مشاكل حُلت نهائياً ✅

| المشكلة | الحل المطبق |
|---|---|
| أسعار بكسور (50.00) | integers في DB + عرض بدون كسور |
| أرقام عربية في الواجهة | formatEGP يستخدم en-US إجباراً |
| Double JSON encoding | `$casts = 'array'` في Product Model |
| نفد المخزون للمخزون غير المحدود | qty فاضي = 100 مش صفر |
| رسالة "يرجى تحديد المقاس" تظهر دايماً | محذوفة |
| تقييمات العملاء في صفحة المنتج | محذوفة كاملة |
| حقل المخزون قابل للتعديل رغم variants | disabled تلقائي + يتحسب من المجموع |
| المقاسات المنتهية مش واضحة | strikethrough + disabled من أول تحميل |
| تعديل مسميات وعناصر الفاتورة | توحيدها لـ (السعر، الشحن، الإجمالي) وإلغاء "(قبل الشحن)" |
| العداد التنازلي باللاندينج بيدج | يعمل لـ 24 ساعة تفاعلياً ويحفظ حالته بالـ localStorage |
| توجيه زر "اتمام الشراء" بالمنتج | يحول لصفحة الدفع `/checkout` مباشرة بدلاً من السلة |
| زر "متابعة التسوق" بصفحة النجاح | تصحيح المسار لـ `/shop/products.html` لتجنب خطأ 404 |
| بيكسل تتبع لصفحة الهبوط | دعم بيكسل فيسبوك وتيك توك مستقل لكل صفحة هبوط مع Fallback للعام |

---

## تفاصيل الإضافات الأخيرة المفعّلة ⚡

### 1. خيار عرض المنتجات بالموبايل (Grid View Switcher)
- تم إضافة أيقونة/زر تفاعلي بصفحة المنتجات (`products.html` بجميع القوالب) يتيح للمستخدم بالـ Mobile اختيار طريقة العرض: **منتج واحد بالسطر (كامل العرض)** أو **منتجين بالسطر**.
- يتم حفظ التفضيل بالـ `localStorage` لاستمرارية التصفح.

### 2. البيكسل المخصص لصفحة الهبوط (Campaign Pixel Tracking)
- **جدول DB:** إضافة `facebook_pixel_id` و `tiktok_pixel_id` لـ `landing_pages`.
- **لوحة التاجر:** حقول اختيارية بـ React (`Create.jsx` و `Edit.jsx` بتبويب الإعدادات) لإدخال البيكسلات.
- **الواجهة:** يتم التحقق إن كان لصفحة الهبوط بيكسل مخصص فيعمل، وإلا يعود للبيكسل العام للمتجر (Fallback).
- **الأحداث المتبعة:** تتبع أحداث `PageView` و `Purchase` (فيسبوك) و `CompletePayment` (تيك توك) بقيم الطلب الفعلية عند الشراء.

---

## مهام متبقية ❌

- [ ] مشكلة فقدان الـ Focus في حقل اسم المنتج عند الكتابة (Create.jsx / Edit.jsx)
- [ ] أيقونة ترس/سهم بجانب "شحن بالمحافظة" تودي لصفحة الشحن في tab جديد
- [ ] اتجاه سهم الـ dropdown في كل اللوحة يكون على اليسار (عكس اتجاه النص)
