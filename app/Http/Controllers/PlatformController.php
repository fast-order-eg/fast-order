<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class PlatformController extends Controller
{
    /**
     * Display the platform landing page (fastorder.test).
     */
    public function index(Request $request)
    {
        $stats = [
            'active_stores' => [
                'ar' => '0%',
                'en' => '0%',
                'label_ar' => 'عمولة على مبيعات متجرك',
                'label_en' => 'Sales Commission',
            ],
            'daily_orders' => [
                'ar' => '< 0.5s',
                'en' => '< 0.5s',
                'label_ar' => 'سرعة تحميل صفحة الشيك أوت',
                'label_en' => 'Checkout Load Speed',
            ],
            'uptime' => [
                'ar' => '99.9%',
                'en' => '99.9%',
                'label_ar' => 'نسبة استقرار وسرعة السيرفرات',
                'label_en' => 'Server Uptime Guarantee',
            ],
            'monthly_sales' => [
                'ar' => '27',
                'en' => '27',
                'label_ar' => 'محافظة مدعومة بحسابات الشحن',
                'label_en' => 'Supported Governorates',
            ],
        ];

        $features = [
            [
                'icon' => 'bolt',
                'title_ar' => 'أسرع شيك أوت لزيادة المبيعات',
                'title_en' => 'Ultra-Fast Checkout Conversion',
                'description_ar' => 'صفحة شيك أوت سريعة ومباشرة في خطوة واحدة، مصممة خصيصاً لمضاعفة مبيعات الدفع عند الاستلام وتقليل السلات المتروكة لأدنى حد.',
                'description_en' => 'Instant one-step checkout designed to maximize Cash on Delivery sales and eliminate cart abandonment.',
                'color' => 'from-amber-500 to-orange-500',
            ],
            [
                'icon' => 'palette',
                'title_ar' => 'ثيمات خفيفة ومصممة للموبايل',
                'title_en' => 'Mobile-First Fast Themes',
                'description_ar' => 'تشكيلة تصاميم عصرية خفيفة جداً تفتح في أقل من ثانية، وتبرز تفاصيل منتجاتك مع تخصيص كامل للألوان والبنرات.',
                'description_en' => 'High-converting, lightweight mobile designs crafted for Egyptian shoppers with instant visual branding.',
                'color' => 'from-indigo-500 to-purple-500',
            ],
            [
                'icon' => 'chart-line',
                'title_ar' => 'ربط فوري لبكسل فيسبوك وتيك توك',
                'title_en' => 'Instant Meta & TikTok Pixel',
                'description_ar' => 'تتبع دقيق لحملاتك الإعلانية بأحداث الشراء الحقيقية (Purchase) على فيسبوك، تيك توك، وسناب شات بضغطة زر واحدة بدون أي كود.',
                'description_en' => 'Accurate real-time tracking for your ads with verified Purchase events on Meta & TikTok with zero coding.',
                'color' => 'from-emerald-500 to-teal-500',
            ],
            [
                'icon' => 'box',
                'title_ar' => 'إدارة شحن ومحافظات مرنة',
                'title_en' => 'Custom Shipping Rates & Governorates',
                'description_ar' => 'حدد أسعار شحن مستقلة لكل محافظة مصرية من الـ 27 محافظة، مع إمكانية تحديد شحن مجاني عند طلب أكثر من قطعة.',
                'description_en' => 'Set custom delivery rates per Egyptian governorate and seamlessly manage order fulfillments.',
                'color' => 'from-blue-500 to-cyan-500',
            ],
            [
                'icon' => 'headset',
                'title_ar' => 'تأكيد الأوردرات بالواتساب لحظياً',
                'title_en' => 'Instant WhatsApp Order Confirmation',
                'description_ar' => 'زر واتساب مباشر لمراسلة العميل بتفاصيل طلبه فوراً وتأكيد المقاس والعنوان بضغطة واحدة لتقليل المرتجعات.',
                'description_en' => 'Quick WhatsApp actions right on order receipt to confirm details fast and reduce delivery returns.',
                'color' => 'from-rose-500 to-pink-500',
            ],
            [
                'icon' => 'cubes',
                'title_ar' => 'إدارة المخزون، المقاسات، والألوان',
                'title_en' => 'Variants & Inventory Control',
                'description_ar' => 'لوحة تحكم مرنة لإضافة متغيرات المنتجات (ألوان، مقاسات، خصومات كمية) مع تتبع فوري لحركة المخزون.',
                'description_en' => 'Effortlessly manage product variants (colors, sizes, bulk offers) with automated low-stock alerts.',
                'color' => 'from-amber-600 to-yellow-500',
            ],
            [
                'icon' => 'qrcode',
                'title_ar' => 'طباعة الفواتير وبوالص الشحن بـ QR',
                'title_en' => 'Waybills & Invoices with QR Code',
                'description_ar' => 'إصدار فواتير احترافية وبوالص شحن مجهزة لشركات الشحن المختلفة بضغطة زر واحدة لتسريع تجهيز وتسليم الطلبات.',
                'description_en' => 'One-click professional invoices and printable shipping waybills with QR codes for fast order packaging.',
                'color' => 'from-teal-500 to-emerald-600',
            ],
            [
                'icon' => 'wand-magic-sparkles',
                'title_ar' => 'أدوات ذكاء اصطناعي لكتابة الإعلانات',
                'title_en' => 'AI Marketing & Copywriting Tools',
                'description_ar' => 'أدوات ذكية مدمجة لمساعدتك في كتابة عناوين ووصف مقنع لمنتجاتك وحملاتك الإعلانية لمضاعفة معدل الشراء.',
                'description_en' => 'Built-in AI copywriting tools to generate high-converting product descriptions and marketing hooks.',
                'color' => 'from-purple-500 to-pink-500',
            ],
            [
                'icon' => 'shield-halved',
                'title_ar' => '0% عمولة واستقرار 100%',
                'title_en' => '0% Commission & High Uptime',
                'description_ar' => 'كل أرباح مبيعاتك بتدخل جيبك بالكامل بنسبة 100% بدون أي نسب مستقطعة، مع استضافة سحابية فائقة السرعة وأمان عالي.',
                'description_en' => 'Keep 100% of your earnings with zero hidden commissions and ultra-reliable cloud hosting.',
                'color' => 'from-violet-500 to-fuchsia-500',
            ],
        ];

        $steps = [
            [
                'number' => '01',
                'title_ar' => 'سجل حسابك واختر اسم متجرك',
                'title_en' => 'Register & Choose Your Store Name',
                'description_ar' => 'أنشئ حسابك في أقل من دقيقة، اختر اسم الرابط لمتجرك، وابدأ تجربتك فوراً وبدون الحاجة لفيزا أو بطاقة ائتمان.',
                'description_en' => 'Create your account in under a minute, pick your store subdomain, and start instantly with no credit card.',
            ],
            [
                'number' => '02',
                'title_ar' => 'ارفع منتجاتك وحدد أسعار الشحن',
                'title_en' => 'Add Products & Shipping Rates',
                'description_ar' => 'ضيف صور منتجاتك، المقاسات، الألوان، وأسعار الشحن لمحافظاتك بكل سهولة ومن شاشة واحدة واضحة.',
                'description_en' => 'Upload product photos, sizes, colors, and set governorate shipping rates quickly from one simple screen.',
            ],
            [
                'number' => '03',
                'title_ar' => 'أطلق إعلاناتك واستقبل الأوردرات!',
                'title_en' => 'Launch Ads & Receive Orders!',
                'description_ar' => 'اربط بيكسل فيسبوك وتيك توك بضغطة زر، شارك رابط متجرك، واستقبل طلبات الدفع عند الاستلام على لوحة التحكم لحظياً.',
                'description_en' => 'Connect Meta & TikTok pixels with one click, run your ads, and manage cash-on-delivery orders live on your dashboard.',
            ],
        ];

        $plans = [
            [
                'id' => 'trial_7_days',
                'name_ar' => 'الباقة المجانية (تجربة 7 أيام)',
                'name_en' => '7-Day Free Trial Plan',
                'badge_ar' => 'مجاناً لمدة 7 أيام',
                'badge_en' => 'Free 7 Days',
                'price' => '0',
                'period_ar' => 'تجربة أولى',
                'period_en' => 'first trial',
                'description_ar' => 'باقة مجانية شاملة لتجربة فاست اوردر واستكشاف كافة المميزات بدون أي مخاطرة أو بطاقة ائتمان.',
                'description_en' => 'Full access to all platform features for 7 days free to explore Fast Order with zero risk and no credit card.',
                'featured' => false,
                'features_ar' => [
                    'تجربة مجانية كاملة لمدة 7 أيام',
                    'متجر إلكتروني سريع بنظام صفحة المنتج الواحدة',
                    'لوحة تحكم كاملة وسهلة للموبايل والكمبيوتر',
                    'استقبال ومعاينة الأوردرات وتأكيدها بالواتساب',
                    '0% عمولة على المبيعات بالكامل',
                    'دعم فني وتدريب مجاني 24/7',
                ],
                'features_en' => [
                    '7 days full free trial',
                    'Ultra-fast single-page storefront',
                    'Full control panel for mobile & desktop',
                    'Order viewing & instant WhatsApp confirmation',
                    '0% sales commission',
                    '24/7 free support & onboarding',
                ],
                'cta_text_ar' => 'ابدأ تجربتك المجانية (7 أيام)',
                'cta_text_en' => 'Start 7-Day Free Trial',
                'cta_link' => Route::has('register') ? route('register') : '#',
            ],
            [
                'id' => 'pay_per_order',
                'name_ar' => 'باقة الدفع على الطلب (2ج)',
                'name_en' => 'Pay Per Order (2 EGP)',
                'badge_ar' => 'الأكثر مرونة وتوفيراً 🔥',
                'badge_en' => 'Most Flexible',
                'price' => '2',
                'period_ar' => 'لكل أوردر مفتوح',
                'period_en' => 'per order',
                'description_ar' => 'بدون أي اشتراك شهري ثابت! اشحن محفظتك بإنستاباي أو فودافون كاش، وادفع 2 جنيه فقط لما يجيلك أوردر وتفتحه.',
                'description_en' => 'Zero monthly subscription! Top up via InstaPay or Vodafone Cash and pay only 2 EGP per unlocked order.',
                'featured' => true,
                'features_ar' => [
                    'خصم 2 ج.م فقط عند فتح الأوردر ومعاينته',
                    'بدون أي مصاريف أو اشتراكات شهرية ثابتة',
                    'شحن المحفظة فوراً بـ فودافون كاش وإنستا باي',
                    'منتجات وثيمات وتصاميم غير محدودة',
                    'ربط دومين خاص وبكسل فيسبوك وتيك توك مجاناً',
                    'دعم فني وتجاوب لحظي متواصل',
                ],
                'features_en' => [
                    'Pay only 2 EGP per unlocked order',
                    'Zero fixed monthly fees or hidden deductions',
                    'Instant top-up via Vodafone Cash & InstaPay',
                    'Unlimited products, themes & designs',
                    'Free custom domain & pixel connections',
                    'Continuous live technical support',
                ],
                'cta_text_ar' => 'اختر باقة الطلب (2ج لكل أوردر)',
                'cta_text_en' => 'Choose Pay Per Order Plan',
                'cta_link' => Route::has('register') ? route('register') : '#',
            ],
            [
                'id' => 'monthly_unlimited',
                'name_ar' => 'الاشتراك الشهري الشامل',
                'name_en' => 'Monthly Unlimited Plan',
                'badge_ar' => '🔥 خصم 50% لفترة محدودة!',
                'badge_en' => '🔥 50% OFF Limited Time!',
                'price' => '500',
                'original_price' => '1000',
                'period_ar' => 'شهرياً (بدلاً من 1000ج)',
                'period_en' => 'monthly (instead of 1000 EGP)',
                'description_ar' => 'عرض خاص لأصحاب الحجم العالي: 500 ج.م شهرياً فقط مع فتح غير محدود لجميع الأوردرات وبدون أي خصومات إضافية!',
                'description_en' => 'Limited time offer: 500 EGP/mo (instead of 1000 EGP) with unlimited order views & 0% commission!',
                'featured' => false,
                'features_ar' => [
                    'خصم 50% لفترة محدودة (500ج بدلاً من 1000ج)',
                    'فتح ومعاينة غير محدودة لجميع الأوردرات',
                    '0% عمولة وبدون خصم 2 ج.م لكل أوردر',
                    'جميع الثيمات والمميزات الاحترافية مفتوحة بالكامل',
                    'سيرفرات فائقة السرعة وأولوية معالجة',
                    'مدير حساب مخصص ودعم فني سريع VIP',
                ],
                'features_en' => [
                    '50% OFF for a limited time (500 EGP instead of 1000 EGP)',
                    'Unlimited order viewing & management',
                    '0% commission and no deduction per order',
                    'All premium themes & features unlocked',
                    'Dedicated high-performance servers',
                    'Dedicated account manager + VIP 24/7 support',
                ],
                'cta_text_ar' => 'احصل على خصم 50% واشترك بـ 500ج 🔥',
                'cta_text_en' => 'Get 50% OFF & Subscribe for 500 EGP',
                'cta_link' => Route::has('register') ? route('register') : '#',
            ],
        ];

        $testimonials = [
            [
                'name_ar' => 'أحمد محمد',
                'name_en' => 'Ahmed Mohamed',
                'role_ar' => 'مؤسس براند "أناقة للملابس"',
                'role_en' => 'Founder of "Anaka Fashion"',
                'avatar' => 'https://ui-avatars.com/api/?name=أحمد+محمد&background=6366f1&color=fff&size=128',
                'quote_ar' => 'انتقالنا إلى منصة فاست اوردر كان أفضل قرار تجاري. سرعة الشيك أوت خلت معدل التحويل يزيد 35% والسلات المتروكة قلت جداً لأن العميل بيسجل في خطوة واحدة!',
                'quote_en' => 'Moving to Fast Order increased our conversion rate by 35%. One-step checkout made ordering seamless for our Egyptian buyers!',
                'rating' => 5,
            ],
            [
                'name_ar' => 'سارة خالد',
                'name_en' => 'Sarah Khaled',
                'role_ar' => 'مالكة متجر "جلوري لمستحضرات التجميل"',
                'role_en' => 'Owner of "Glory Cosmetics"',
                'avatar' => 'https://ui-avatars.com/api/?name=سارة+خالد&background=ec4899&color=fff&size=128',
                'quote_ar' => 'أهم ميزة بالنسبة لي هي بيكسل تيك توك وفيسبوك، بتشتغل بضغطة زر وبدون كود وبتسجل كل المبيعات بدقة. والدعم الفني متواجد وبيساعد بسرعة.',
                'quote_en' => 'TikTok & Facebook pixels connected in seconds with zero code. Customer support is always responsive and helpful.',
                'rating' => 5,
            ],
            [
                'name_ar' => 'محمود علي',
                'name_en' => 'Mahmoud Ali',
                'role_ar' => 'مدير عام "تِك زون للإلكترونيات"',
                'role_en' => 'General Manager of "TechZone Electronics"',
                'avatar' => 'https://ui-avatars.com/api/?name=محمود+علي&background=3b82f6&color=fff&size=128',
                'quote_ar' => 'كنا بنعاني من عمولات المنصات التانية والاشتراكات الدولارية العالية. مع فاست اوردر بندفع 2 جنيه بس مع كل أوردر بيجي، وسرعة السيرفرات خيالية!',
                'quote_en' => 'We used to pay hefty dollar subscriptions and commissions. With Fast Order, we pay only 2 EGP per order, with rock-solid server uptime!',
                'rating' => 5,
            ],
        ];

        $faqs = [
            [
                'question_ar' => 'ما هي منصة فاست اوردر (Fast Order)؟',
                'question_en' => 'What is Fast Order platform?',
                'answer_ar' => 'فاست اوردر (Fast Order) هي منصة مصرية متكاملة لإنشاء المتاجر الإلكترونية وصفحات الهبوط (Landing Pages) المتخصصة في زيادة مبيعات الدفع عند الاستلام. توفر شيك أوت فائق السرعة، ربط فوري لبكسل فيسبوك وتيك توك، وعمولة 0% على المبيعات.',
                'answer_en' => 'Fast Order is a dedicated SaaS e-commerce platform crafted for ultra-fast Cash on Delivery (COD) sales, featuring instant one-step checkout, seamless TikTok/Meta pixel integration, and 0% sales commission.',
            ],
            [
                'question_ar' => 'إزاي أعمل ستور أونلاين في مصر في دقايق؟',
                'question_en' => 'How can I create an online store in Egypt in minutes?',
                'answer_ar' => 'كل اللي عليك تسجل حسابك على فاست اوردر بدون بطاقة ائتمان، تختار اسم متجرك، وتضيف صور المنتجات والمقاسات وأسعار الشحن للمحافظات. متجرك بيكون جاهز ومتاح لاستقبال طلبات الزوار فوراً وبأعلى سرعة تصفح.',
                'answer_en' => 'Simply register your account without a credit card, choose your store name, and add your products and governorate shipping rates. Your store is instantly ready to receive customer orders.',
            ],
            [
                'question_ar' => 'هل توجد أي عمولات على المبيعات؟',
                'question_en' => 'Are there any commissions on sales?',
                'answer_ar' => 'لا نهائياً! فاست اوردر تطبق نظام 0% عمولة على المبيعات في جميع الباقات. كل أرباح متجرك تعود إليك بنسبة 100% دون أي اقتطاع.',
                'answer_en' => 'Never! Fast Order charges 0% commission on your sales across all plans. 100% of your earnings belong to you.',
            ],
            [
                'question_ar' => 'ما الفرق بين فاست اوردر وشوبيفاي أو المنصات الأخرى؟',
                'question_en' => 'What makes Fast Order different from Shopify or other platforms?',
                'answer_ar' => 'فاست اوردر مصممة خصيصاً لاحتياجات التاجر والمشتري في مصر: شيك أوت في خطوة واحدة بدون تعقيد، أسعار بالجنيه المصري بدون اشتراكات دولارية ثقيلة، ربط فوري بشركات الشحن والمحافظات، ودعم فني مصري متاح 24/7 عبر الواتساب.',
                'answer_en' => 'Fast Order is built specifically for COD and local commerce: instant one-page checkout, fair pricing in Egyptian Pounds, pre-built governorate shipping tables, and 24/7 local support via WhatsApp.',
            ],
            [
                'question_ar' => 'ما هي باقة الدفع على الطلب (2 جنيه لكل أوردر)؟',
                'question_en' => 'What is the Pay Per Order (2 EGP) plan?',
                'answer_ar' => 'باقة مبتكرة تتيح لك تشغيل متجرك بدون أي اشتراك شهري ثابت؛ تقوم بشحن محفظتك بإنستاباي أو فودافون كاش، ويتم خصم 2 ج.م فقط عند فتح ومعاينة كل أوردر جديد، لتدفع فقط مقابل ما تبيعه.',
                'answer_en' => 'An innovative plan with zero fixed monthly fees: top up your balance using Vodafone Cash or InstaPay, and pay only 2 EGP when unlocking each new order.',
            ],
            [
                'question_ar' => 'كيف أربط بيكسل الفيسبوك وتيك توك بمتجري؟',
                'question_en' => 'How do I connect Facebook and TikTok pixels?',
                'answer_ar' => 'من لوحة تحكم المتجر، تدخل معرف البيكسل (Pixel ID) بضغطة زر واحدة بدون أي كود برمجي. النظام يقوم تلقائياً بتتبع أحداث مشاهدة المحتوى، بدء الشيك أوت، وإتمام الشراء (Purchase) بدقة متناهية.',
                'answer_en' => 'From your dashboard settings, enter your Pixel ID with one click without writing code. The system automatically tracks PageView, InitiateCheckout, and Purchase events.',
            ],
            [
                'question_ar' => 'هل يمكنني ربط دومين خاص (Custom Domain) بمتجري؟',
                'question_en' => 'Can I link my custom domain to my store?',
                'answer_ar' => 'نعم بكل تأكيد! يمكنك ربط أي دومين دوت كوم أو دومين خاص بعلامتك التجارية من لوحة التحكم، مع توفير شهادة أمان SSL مجانية تلقائياً.',
                'answer_en' => 'Yes, you can easily connect any custom domain to your store with automated, free SSL security included.',
            ],
            [
                'question_ar' => 'هل تدعم فاست اوردر شركات الشحن في مصر وتأكيد الأوردرات؟',
                'question_en' => 'Does Fast Order support Egyptian shipping & order confirmations?',
                'answer_ar' => 'نعم، المنصة تدعم تحديد أسعار شحن مخصصة لجميع محافظات مصر الـ 27، وتتوافق مع شركات الشحن مثل بوسطة ومايلرز، وتوفر أزرار اتصال وواتساب سريعة للتواصل مع العميل وتأكيد الطلب فوراً لتقليل المرتجعات.',
                'answer_en' => 'Yes, Fast Order supports custom shipping rates across all 27 Egyptian governorates, integrates smoothly with shipping couriers like Bosta and Mylerz, and provides one-click WhatsApp order confirmation.',
            ],
        ];

        $schemaData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => 'https://fast-order-eg.tech/#software',
                    'name' => 'Fast Order',
                    'alternateName' => ['فاست اوردر', 'فاست أوردر', 'FastOrder'],
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'All (Web-based SaaS)',
                    'url' => 'https://fast-order-eg.tech',
                    'description' => 'منصة برمجية سحابية (SaaS) رائدة في مصر لإنشاء وإدارة المتاجر الإلكترونية وصفحات الهبوط فائقة السرعة لمبيعات الدفع عند الاستلام بعمولة 0% وباقات مرنة.',
                    'offers' => [
                        [
                            '@type' => 'Offer',
                            'name' => 'تجربة مجانية لمدة 7 أيام',
                            'price' => '0',
                            'priceCurrency' => 'EGP',
                            'availability' => 'https://schema.org/InStock',
                        ],
                        [
                            '@type' => 'Offer',
                            'name' => 'باقة الدفع على الطلب',
                            'price' => '2',
                            'priceCurrency' => 'EGP',
                            'description' => '2 جنيه مصري فقط لكل أوردر مستلم بدون اشتراك شهري ثابت',
                            'availability' => 'https://schema.org/InStock',
                        ],
                    ],
                    'featureList' => [
                        'أسرع شيك أوت صفحة واحدة للمنتجات في مصر',
                        'ربط بيكسل فيسبوك وتيك توك بضغطة زر',
                        '0% عمولة على المبيعات',
                        'دعم كامل للدفع عند الاستلام وتخصيص أسعار 27 محافظة',
                        'تأكيد الأوردرات السريع عبر الواتساب',
                    ],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => 'https://fast-order-eg.tech/#organization',
                    'name' => 'Fast Order',
                    'url' => 'https://fast-order-eg.tech',
                    'logo' => asset('images/logo.png'),
                    'sameAs' => [
                        'https://www.youtube.com/@RadyEmam-x4z',
                    ],
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'contactType' => 'customer support',
                        'availableLanguage' => ['Arabic', 'English'],
                        'areaServed' => 'EG',
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => 'https://fast-order-eg.tech/#faq',
                    'mainEntity' => array_map(function ($faq) {
                        return [
                            '@type' => 'Question',
                            'name' => $faq['question_ar'],
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => $faq['answer_ar'],
                            ],
                        ];
                    }, $faqs),
                ],
            ],
        ];

        return view('platform.index', compact('stats', 'features', 'steps', 'plans', 'testimonials', 'faqs', 'schemaData'));
    }

    /**
     * Display the about platform page.
     */
    public function about(Request $request)
    {
        return view('platform.about');
    }

    /**
     * Display the pricing page.
     */
    public function pricing(Request $request)
    {
        return redirect()->route('main.home', ['#pricing']);
    }

    /**
     * Display the contact page.
     */
    public function contact(Request $request)
    {
        return view('platform.contact');
    }

    /**
     * Handle the contact form submission.
     */
    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|in:support,sales,custom,other',
            'message' => 'required|string|max:5000',
        ], [
            'name.required' => 'حقل الاسم الكامل مطلوب.',
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email' => 'الرجاء إدخال بريد إلكتروني صحيح.',
            'subject.required' => 'الرجاء اختيار موضوع الاستفسار.',
            'message.required' => 'حقل الرسالة مطلوب.',
        ]);

        // Here, in a real environment, we would dispatch an email or save to DB.
        // For now, we will return a success state to the view.
        return redirect()->route('main.contact')->with('success', 'شكراً لتواصلك معنا! لقد تم استلام رسالتك بنجاح وسيتواصل معك فريق فاست أوردر في أقرب وقت ممكن.');
    }

    /**
     * Display the privacy policy page.
     */
    public function privacy(Request $request)
    {
        return view('platform.privacy');
    }

    /**
     * Display the terms of service page.
     */
    public function terms(Request $request)
    {
        return view('platform.terms');
    }

    /**
     * Display the Service Level Agreement (SLA) page.
     */
    public function sla(Request $request)
    {
        return view('platform.sla');
    }

    /**
     * Display the Help Center page.
     */
    public function help(Request $request)
    {
        return view('platform.help');
    }
}
