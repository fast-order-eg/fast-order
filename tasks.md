# خطة ربط درايفر شركة الشحن J&T Express (جي أند تي إكسبريس)

- [x] تحديث كلاس `JntShippingDriver` وتطبيق معادلات التشفير والتوقيع المعتمدة رسمياً (VIP Password + Body Digest + Header Digest) <!-- id: 0 -->
- [x] تحديث كنترولر `ShippingGatewaysController` لدعم واستقبال الحقول الأربعة (`customer_code`, `api_account`, `private_key`, `password`) <!-- id: 1 -->
- [x] تحديث واجهة إعدادات شركات الشحن `ShippingGateways/Index.jsx` لإضافة حقل كلمة سر الـ VIP وتحسين المودال <!-- id: 2 -->
- [x] فحص الكود محلياً وبناء الأصول (`npm run build`) وتجربة سيناريو التشفير <!-- id: 3 -->
- [ ] رفع التعديلات عبر Git وتشغيل سكريبت Zero-Downtime Deployment على السيرفر <!-- id: 4 -->

