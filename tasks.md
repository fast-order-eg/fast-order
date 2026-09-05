# خطة تطوير الطلبات والربط مع شركة الشحن J&T وتنظيف الفواتير

- [x] إنشاء مايجريشن لإضافة حقول الطباعة `is_printed` و `printed_at` لجدول `orders` وتحديث موديل `Order` بالعلاقات <!-- id: 0 -->
- [x] منع تسجيل رسائل الواتساب الفنية في حقل `notes` في `MetaWhatsAppService` و `WhatsAppWebhookController` وعمل سكربت تنظيف للطلبات القديمة <!-- id: 1 -->
- [x] تنظيف وتحديث فواتير الطباعة (`invoice.blade.php` وإنشاء `bulk-invoice.blade.php`) وتطبيق `@page { margin: 0mm !important; }` <!-- id: 2 -->
- [x] تطوير درايفر شركة الشحن `JntShippingDriver.php` بالكامل (عناصر الطلب، الراسل ديناميكياً، معايير J&T، خيارات المنتجات في remark، وتأكيد بيانات الإلغاء) <!-- id: 3 -->
- [x] إضافة دالة `cancelShipment` في `ShippingManager` وإلغاء الشحنات تلقائياً عند إلغاء أي طلب وإضافة راوت الإلغاء في `routes/web.php` <!-- id: 4 -->
- [x] تحديث `OrderController.php` بإضافة العمليات الجماعية (`bulkShip`, `bulkStatus`, `bulkPrint`, `bulkExport`) والفلترة السريعة وتغيير مسمى `shipped` إلى "مع شركة الشحن" <!-- id: 5 -->
- [x] تطوير واجهة الطلبات `Merchant/Orders/Index.jsx` (الشريط العائم، التحديد الجماعي، عمود الطباعة والشحن، الفلاتر السريعة، وتغيير المسمى) <!-- id: 6 -->
- [x] فحص الكود محلياً، بناء أصول الواجهة (`npm run build`)، تشغيل المايجريشن وسكربت التنظيف، ورفع التعديلات بنظام Zero-Downtime <!-- id: 7 -->

