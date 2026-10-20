<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class ThemeMarketplace extends Model
{
    use HasFactory;

    protected $table = 'theme_marketplaces';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'author',
        'version',
        'type', // free, paid
        'price',
        'currency',
        'preview_url',
        'thumbnail',
        'features',
        'compatibility',
        'rating',
        'reviews_count',
        'reviews',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'integer',
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
        'compatibility' => 'array',
        'reviews' => 'array',
    ];

    /**
     * Scope للثيمات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للثيمات المجانية
     */
    public function scopeFree($query)
    {
        return $query->where('type', 'free');
    }

    /**
     * Scope للثيمات المدفوعة
     */
    public function scopePaid($query)
    {
        return $query->where('type', 'paid');
    }

    /**
     * Scope للترتيب الافتراضي حسب الأولوية
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * إضافة مراجعة وتقييم للثيم وتحديث متوسط التقييمات
     */
    public function addReview(array $reviewData): self
    {
        $reviews = $this->reviews ?? [];
        
        array_unshift($reviews, [
            'id' => uniqid('rev_'),
            'reviewer' => $reviewData['reviewer'] ?? 'تاجر فاست أوردر',
            'rating' => (float) ($reviewData['rating'] ?? 5),
            'comment' => $reviewData['comment'] ?? '',
            'date' => now()->format('Y-m-d'),
        ]);

        $this->reviews = $reviews;
        $this->reviews_count = count($reviews);

        // حساب متوسط التقييمات الجديد
        $totalRating = array_sum(array_column($reviews, 'rating'));
        $this->rating = round($totalRating / max(1, $this->reviews_count), 2);

        $this->save();

        return $this;
    }

    /**
     * جلب جميع الثيمات المتاحة في السوق (من قاعدة البيانات أو البيانات الافتراضية كبديل مرن)
     */
    public static function getAvailableThemes(?string $typeFilter = null, ?string $searchQuery = null): Collection
    {
        $hasTable = false;
        try {
            $hasTable = Schema::hasTable('theme_marketplaces');
        } catch (\Exception $e) {
            $hasTable = false;
        }

        if ($hasTable && self::count() > 0) {
            $query = self::active()->ordered();
            
            if ($typeFilter && in_array($typeFilter, ['free', 'paid'])) {
                $query->where('type', $typeFilter);
            }

            if ($searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'like', "%{$searchQuery}%")
                      ->orWhere('description', 'like', "%{$searchQuery}%")
                      ->orWhere('slug', 'like', "%{$searchQuery}%");
                });
            }

            return $query->get();
        }

        // استخدام البيانات الافتراضية في حال عدم وجود بيانات في الجدول بعد
        $themes = collect(self::getDefaultThemesData());

        if ($typeFilter && in_array($typeFilter, ['free', 'paid'])) {
            $themes = $themes->where('type', $typeFilter);
        }

        if ($searchQuery) {
            $searchQuery = mb_strtolower($searchQuery);
            $themes = $themes->filter(function ($theme) use ($searchQuery) {
                return str_contains(mb_strtolower($theme['name']), $searchQuery) ||
                       str_contains(mb_strtolower($theme['description']), $searchQuery) ||
                       str_contains(mb_strtolower($theme['slug']), $searchQuery);
            });
        }

        return $themes->sortBy('sort_order')->values();
    }

    /**
     * جلب ثيم محدد بواسطة الـ slug
     */
    public static function findBySlug(string $slug)
    {
        try {
            if (Schema::hasTable('theme_marketplaces')) {
                $theme = self::where('slug', $slug)->first();
                if ($theme) {
                    return $theme;
                }
            }
        } catch (\Exception $e) {
            // Fallback to default data
        }

        $defaultThemes = collect(self::getDefaultThemesData());
        $themeData = $defaultThemes->where('slug', $slug)->first();

        if ($themeData) {
            return new self($themeData);
        }

        return null;
    }

    /**
     * بذر وتغذية جدول سوق الثيمات بالبيانات الافتراضية الشاملة
     */
    public static function seedDefaultThemes(): void
    {
        try {
            if (!Schema::hasTable('theme_marketplaces')) {
                return;
            }

            foreach (self::getDefaultThemesData() as $themeData) {
                self::updateOrCreate(
                    ['slug' => $themeData['slug']],
                    $themeData
                );
            }
        } catch (\Exception $e) {
            // Silent catch if database is unavailable
        }
    }

    /**
     * البيانات الافتراضية الشاملة لسوق الثيمات (Default, Modern, Bold, Starter وغيرها)
     */
    public static function getDefaultThemesData(): array
    {
        return [
            [
                'slug' => 'default',
                'name' => 'الثيم الافتراضي الكلاسيكي (Default Theme)',
                'description' => 'الثيم الافتراضي السريع والمتجاوب لجميع المتاجر، يتميز بتجربة تسوق سلسة وبسيطة مع دعم كامل للغتين العربية والإنجليزية وسرعة تحميل فائقة.',
                'author' => 'Fast Order Team',
                'version' => '1.0.0',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'EGP',
                'preview_url' => '/merchant/themes/preview/default',
                'thumbnail' => '/shop/images/themes/default-preview.webp',
                'features' => [
                    'تصميم متجاوب بالكامل مع جميع الشاشات (موبايل، تابلت، ديسكتوب)',
                    'دعم كامل ومثالي للغتين العربية والإنجليزية (RTL/LTR)',
                    'سلة مشتريات ذكية وسريعة وإتمام طلب في خطوة واحدة',
                    'سرعة تحميل عالية ومحسنة وفق معايير Google PageSpeed',
                    'تخصيص كامل وشامل للألوان، الخطوط، والأقسام الرئيسية',
                ],
                'compatibility' => [
                    'متوافق مع أحدث إصدارات فاست أوردر (2.0+)',
                    'يدعم جميع بوابات الدفع المحلية والدولية وخيارات الدفع عند الاستلام',
                    'متوافق مع جميع إضافات وأدوات التسويق والتحليلات في المنصة',
                ],
                'rating' => 4.90,
                'reviews_count' => 142,
                'reviews' => [
                    [
                        'id' => 'rev_1',
                        'reviewer' => 'أحمد محمد - متجر الأناقة',
                        'rating' => 5,
                        'date' => '2026-06-28',
                        'comment' => 'ثيم ممتاز وسريع جداً، زادت نسبة التحويل وإتمام الطلبات في متجري بعد استخدامه مباشرة!',
                    ],
                    [
                        'id' => 'rev_2',
                        'reviewer' => 'سارة محمود - متجر كيدز',
                        'rating' => 5,
                        'date' => '2026-06-15',
                        'comment' => 'التصميم بسيط وسهل للعملاء في الجوال، وخيارات التخصيص مرنة جداً.',
                    ],
                    [
                        'id' => 'rev_3',
                        'reviewer' => 'خالد عبد الله - متجر إكسسواراتي',
                        'rating' => 4.8,
                        'date' => '2026-05-20',
                        'comment' => 'أفضل ثيم للبدء السريع، خط عربية ممتاز وسرعة تحميل ممتازة.',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'fast_cod',
                'name' => 'ثيم Fast COD Express (إيزي أوردر)',
                'description' => 'ثيم فائق السرعة مخصص لحملات الميديا باينج والـ COD، يتميز بنموذج طلب مدمج بالصفحة، شريط شراء سفلي عائم، باقات كميات جذابة، وطلب سريع عبر واتساب.',
                'author' => 'Fast Order Lab',
                'version' => '1.0.0',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'EGP',
                'preview_url' => '/merchant/themes/preview/fast_cod',
                'thumbnail' => '/shop/images/themes/fast_cod-preview.webp',
                'features' => [
                    'نموذج طلب فوري مدمج بصفحة المنتج (One-Page Product Checkout) يرفع التحويل بنسبة تصل إلى 45%',
                    'شريط شراء سفلي عائم (Sticky Buy Bar) مخصص للموبايل مع نزول انسيابي فوري لطلب المنتج',
                    'باقات عروض وخصومات كميات تفاعلية (Tiered Bundle Selector)',
                    'زر طلب ومحادثة فوري عبر واتساب بنص رسالة جاهز وتتبع بيكسل تلقائي',
                    'شارات الأمان والتحفيز (عداد تنازلي، حالة توفر المخزون، ومعاينة قبل الدفع)',
                ],
                'compatibility' => [
                    'متوافق مع جميع إعدادات الدفع والشحن بالمحافظات في فاست أوردر',
                    'متوافق مع بيكسل فيسبوك وتيك توك العام والمخصص لصفحات الهبوط',
                    'تصميم خفيف جداً فائق السرعة لتقليل معدل الارتداد (Bounce Rate)',
                ],
                'rating' => 4.98,
                'reviews_count' => 189,
                'reviews' => [
                    [
                        'id' => 'rev_fc_1',
                        'reviewer' => 'إسلام جابر - متجر تريند إيجيبت',
                        'rating' => 5,
                        'date' => '2026-09-10',
                        'comment' => 'ثيم خارق لحملات إعلانات تيك توك وفيسبوك، فورم الطلب بصفحة المنتج ضاعف المبيعات فوراً!',
                    ],
                    [
                        'id' => 'rev_fc_2',
                        'reviewer' => 'كريم ممدوح - متجر سمارت تك',
                        'rating' => 5,
                        'date' => '2026-09-02',
                        'comment' => 'الشريط العائم وباقات الكميات خلت العملاء يشتروا قطعتين وتلاتة بدون تردد.',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'luxury_store',
                'name' => 'ثيم Luxury Brand Store (شوبيفاي وزاره)',
                'description' => 'تصميم راقٍ وفخم مستوحى من كبرى متاجر شوبيفاي وبراندات الموضة العالمية، يتميز بشبكة عرض عصرية وفلاتر متقدمة وسلة تسوق أنيقة.',
                'author' => 'Fast Order Lab',
                'version' => '1.0.0',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'EGP',
                'preview_url' => '/merchant/themes/preview/luxury_store',
                'thumbnail' => '/shop/images/themes/luxury_store-preview.webp',
                'features' => [
                    'شبكة عرض إيديتوريال عصرية للمنتجات تشبه أرقى الماركات كزارة وشوبيفاي Dawn',
                    'فلاتر جانبية متقدمة لتصفح المنتجات حسب الأقسام، السعر، والترتيب',
                    'صفحة سلة مشتريات راقية ومستقلة مع حساب الشحن ومراجعة الطلب بكل وضوح',
                    'معرض صور فخم وتقليب صور المنتجات السلس والمريح للعين',
                    'تايبوجرافي وألوان أنيقة تبرز هوية البراند وتزرع الثقة لدى العملاء',
                ],
                'compatibility' => [
                    'متوافق مع متاجر الملابس، الأزياء، العطور، النظارات، والإكسسوارات الفاخرة',
                    'دعم فوري كامل للغتين العربية والإنجليزية بتبديل اتجاه سلس (RTL/LTR)',
                    'متوافق مع كل شاشات الموبايل والتابلت والديسكتوب بأعلى دقة',
                ],
                'rating' => 4.96,
                'reviews_count' => 124,
                'reviews' => [
                    [
                        'id' => 'rev_lx_1',
                        'reviewer' => 'ندى زهران - متجر فيلفيت براند',
                        'rating' => 5,
                        'date' => '2026-09-15',
                        'comment' => 'شكل المتجر بقى زي أرقى براندات شوبيفاي العالمية، زبايني انبهروا بالشكل والشياكة!',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'modern_minimalist',
                'name' => 'الثيم العصري الحديث (Modern Minimalist)',
                'description' => 'تصميم عصري ونظيف يركز على المساحات البيضاء وعرض صور المنتجات بوضوح عالٍ وبشكل سينمائي جذاب مع تأثيرات الزجاج الحديثة (Glassmorphism).',
                'author' => 'Fast Order Design Lab',
                'version' => '1.2.0',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'EGP',
                'preview_url' => '/merchant/themes/preview/modern_minimalist',
                'thumbnail' => '/shop/images/themes/modern-preview.webp',
                'features' => [
                    'تأثيرات بصرية حديثة مع لمسات Glassmorphism الأنيقة',
                    'عرض سينمائي للصور والمنتجات الفاخرة مع تكبير تفاعلي',
                    'قائمة تنقل علوية ذكية وثابتة (Sticky Header) لسهولة التصفح',
                    'دعم الفلترة السريعة والبحث المتقدم الفوري دون تحميل الصفحة',
                    'تصميم بطاقات منتجات تفاعلية مع زر إضافة سريعة للسلة',
                ],
                'compatibility' => [
                    'متوافق مع أحدث إصدارات فاست أوردر (2.0+)',
                    'مثالي لمتاجر الملابس، الموضة، العطور، والإلكترونيات الفاخرة',
                    'متوافق بالكامل مع أنظمة الخصومات والكوبونات وعروض الباندل',
                ],
                'rating' => 4.95,
                'reviews_count' => 98,
                'reviews' => [
                    [
                        'id' => 'rev_4',
                        'reviewer' => 'منى السيد - متجر فاشون استايل',
                        'rating' => 5,
                        'date' => '2026-07-01',
                        'comment' => 'تصميم يبهر العملاء من أول نظرة! عرض الصور فيه رائع جداً ومناسب للبراندات الفاخرة.',
                    ],
                    [
                        'id' => 'rev_5',
                        'reviewer' => 'عمر خالد - متجر تك لايف',
                        'rating' => 4.9,
                        'date' => '2026-06-20',
                        'comment' => 'خفيف وعصري، والبحث الفوري فيه ممتاز جداً وسريع.',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'slug' => 'bold',
                'name' => 'الثيم الجريء المبهج (Bold & Vibrant)',
                'description' => 'ثيم احترافي مدفوع يتميز بالألوان الجريئة والتباين العالي، مصمم خصيصاً لجذب انتباه العملاء وزيادة المبيعات وحملات العروض الخاصة.',
                'author' => 'Fast Order Design Lab',
                'version' => '1.0.0',
                'type' => 'paid',
                'price' => 299.00,
                'currency' => 'EGP',
                'preview_url' => '/merchant/themes/preview/bold',
                'thumbnail' => '/shop/images/themes/bold-preview.webp',
                'features' => [
                    'ألوان جريئة وتباين بصري عالي الجاذبية لتحفيز الشراء الفوري',
                    'عدادات تنازلية للعروض والخصومات الحصرية (Countdown Timers)',
                    'شريط إعلانات متحرك ومميز في الهيدر (Announcement Bar)',
                    'تنسيق مخصص لصفحات الهبوط والعروض الترويجية الخاطفة',
                    'نظام عرض سريع للمنتج (Quick View) دون مغادرة الصفحة الرئيسية',
                ],
                'compatibility' => [
                    'متوافق مع فاست أوردر 2.0+ وجميع التحديثات القادمة',
                    'يدعم جميع بوابات الدفع وخيارات التقسيط (فاليو، تابي، تمارا)',
                    'محسّن تماماً لمحركات البحث SEO وبحملات الإعلانات المموّلة على سوشيال ميديا',
                ],
                'rating' => 4.88,
                'reviews_count' => 65,
                'reviews' => [
                    [
                        'id' => 'rev_6',
                        'reviewer' => 'ياسر علي - متجر عروض سوبر',
                        'rating' => 5,
                        'date' => '2026-06-25',
                        'comment' => 'العدادات التنازلية والألوان المبهجة رفعت مبيعات أيام العروض بنسبة 40%! يستحق كل قرش.',
                    ],
                    [
                        'id' => 'rev_7',
                        'reviewer' => 'ريهام نبيل - بيوتي شوب',
                        'rating' => 4.8,
                        'date' => '2026-06-10',
                        'comment' => 'تصميم قوي ومميز جداً، وخدمة العملاء في الدعم الفني ساعدوني في تخصيصه.',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'slug' => 'starter',
                'name' => 'ثيم البداية السريعة (Starter Express)',
                'description' => 'ثيم خفيف وسريع للغاية مصمم للمتاجر الناشئة والطلبات السريعة، يركز على سهولة الاستخدام وإتمام الطلب في ثوانٍ معدودة دون أي تعقيد.',
                'author' => 'Fast Order Team',
                'version' => '1.0.0',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'EGP',
                'preview_url' => '/merchant/themes/preview/starter',
                'thumbnail' => '/shop/images/themes/starter-preview.webp',
                'features' => [
                    'خفيف الحجم وفائق السرعة في التحميل على كافة سرعات الإنترنت',
                    'نموذج طلب مباشر وسريع في صفحة واحدة (One-Page Checkout)',
                    'شريط سفلي للتنقل السريع في الجوال يشبه تطبيقات الهواتف',
                    'تكامل فوري ورابط مباشر مع واتساب للطلب والاستفسار السريع',
                    'إعداد وتجهيز المتجر والانطلاق في أقل من دقيقة واحدة',
                ],
                'compatibility' => [
                    'متوافق مع فاست أوردر 2.0+ وجميع بيئات الاستضافة',
                    'مثالي للمطاعم، المقاهي، والمتاجر ذات المنتج الواحد أو المنتجات المحدودة',
                    'متوافق مع أنظمة التوصيل المحلي والشحن السريع',
                ],
                'rating' => 4.85,
                'reviews_count' => 84,
                'reviews' => [
                    [
                        'id' => 'rev_8',
                        'reviewer' => 'محمود كمال - مطعم برجر فاير',
                        'rating' => 5,
                        'date' => '2026-06-29',
                        'comment' => 'أسهل وأسرع ثيم للطلب السريع من الموبايل، العملاء يطلبون بسهولة عبر واتساب وفي الموقع مباشرة.',
                    ],
                    [
                        'id' => 'rev_9',
                        'reviewer' => 'إيمان حسن - متجر حلوياتي',
                        'rating' => 4.8,
                        'date' => '2026-05-30',
                        'comment' => 'بسيط وجميل جداً ومش معقد خالص، أنصح به أي حد لسه بيبدأ متجره.',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'slug' => 'dark_elegance',
                'name' => 'ثيم الأناقة الداكنة (Dark Elegance)',
                'description' => 'تصميم فاخر بالوضع الداكن (Dark Mode) مع لمسات ذهبية وزرقاء أنيقة، مثالي لمتاجر العطور والساعات والمجوهرات والأزياء الفاخرة.',
                'author' => 'Fast Order Design Lab',
                'version' => '1.1.0',
                'type' => 'paid',
                'price' => 499.00,
                'currency' => 'EGP',
                'preview_url' => '/merchant/themes/preview/dark_elegance',
                'thumbnail' => '/shop/images/themes/dark-preview.webp',
                'features' => [
                    'وضع داكن فاخر (Dark Mode) مريح للعين ويبرز ألوان المنتجات',
                    'لمسات تصميمية ذهبية وأنيقة تعكس الفخامة والجودة العالية',
                    'معرض صور عالي الدقة مع تكبير تفاعلي وعرض 360 درجة للمنتج',
                    'تجربة تصفح سلسة ومؤثرات حركية ناعمة تشبه التطبيقات الفاخرة',
                    'دعم كامل للفيديوهات وخلفيات الفيديو في بانر المتجر الرئيسي',
                ],
                'compatibility' => [
                    'متوافق مع أحدث إصدارات فاست أوردر (2.0+)',
                    'مثالي للعلامات التجارية الفاخرة (Luxury Brands) والمنتجات المتميزة',
                    'متوافق مع جميع طرق الدفع والشحن وأنظمة بطاقات الهدايا',
                ],
                'rating' => 4.97,
                'reviews_count' => 112,
                'reviews' => [
                    [
                        'id' => 'rev_10',
                        'reviewer' => 'طارق الزيني - رويال للعطور',
                        'rating' => 5,
                        'date' => '2026-07-02',
                        'comment' => 'أفخم ثيم رأيته على أي منصة تجارة إلكترونية، عملاء متجري أشادوا بالتصميم الداكن الفاخر.',
                    ],
                    [
                        'id' => 'rev_11',
                        'reviewer' => 'نادين لبيز - جواهر للساعات',
                        'rating' => 5,
                        'date' => '2026-06-18',
                        'comment' => 'اللمسات الذهبية مع الخلفية الداكنة خلت صور الساعات تطلع تحفة فنية!',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'slug' => 'fresh_market',
                'name' => 'ثيم السوق الطازج (Fresh Market)',
                'description' => 'تصميم حيوي ومشرق بالألوان الخضراء الطبيعية، مصمم خصيصاً لمتاجر المواد الغذائية، المكملات الصحية، والمنتجات العضوية والطازجة.',
                'author' => 'Fast Order Team',
                'version' => '1.0.0',
                'type' => 'paid',
                'price' => 399.00,
                'currency' => 'EGP',
                'preview_url' => '/merchant/themes/preview/fresh_market',
                'thumbnail' => '/shop/images/themes/fresh-preview.webp',
                'features' => [
                    'تصميم منعش وألوان مريحة وموحية بالطبيعة والصحة العامة',
                    'نظام تصنيفات متفرع وذكي لعرض مئات المنتجات الغذائية بسهولة',
                    'زر إضافة سريعة للسلة مع تحديد الكمية والوزن مباشرة من كارت المنتج',
                    'عرض بطاقات التغذية والمكونات بوضوح في صفحة تفاصيل المنتج',
                    'دعم عروض الحزم التوفيرية (Bundles) والخصومات الكمية',
                ],
                'compatibility' => [
                    'متوافق مع فاست أوردر 2.0+',
                    'مثالي لمتاجر السوبرماركت، الخضراوات والفواكه، والمكملات الغذائية',
                    'يدعم أنظمة التوصيل المجدول وتحديد أوقات الاستلام السريع',
                ],
                'rating' => 4.92,
                'reviews_count' => 73,
                'reviews' => [
                    [
                        'id' => 'rev_12',
                        'reviewer' => 'سليمان فاروق - أورجانيك ماركت',
                        'rating' => 5,
                        'date' => '2026-06-22',
                        'comment' => 'أفضل ثيم لمتاجر الأغذية والمكملات، خيار تحديد الكمية السريع من الصفحة الرئيسية سهل جداً على الزبائن.',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'slug' => 'tech_store',
                'name' => 'ثيم التكنولوجيا والمعدات (Tech Pro)',
                'description' => 'تصميم تقني حاد يتميز بالألوان الزرقاء والسيان مع تقسيمات شبكية دقيقة لعرض المواصفات التقنية والأجهزة والإلكترونيات بشكل احترافي.',
                'author' => 'Fast Order Team',
                'version' => '1.0.0',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'EGP',
                'preview_url' => '/merchant/themes/preview/tech_store',
                'thumbnail' => '/shop/images/themes/tech-preview.webp',
                'features' => [
                    'جدول مقارنة للمواصفات التقنية للمنتجات بوضوح وسهولة',
                    'تقسيم شبكي دقيق لعرض أكبر عدد من الأجهزة والملحقات',
                    'فلترة متقدمة حسب العلامة التجارية، السعر، والمواصفات الفنية',
                    'عرض حالة المخزون والضمان بوضوح تام للعميل',
                    'شريط بحث فوري وذكي مع اقتراحات تلقائية للمنتجات والأقسام',
                ],
                'compatibility' => [
                    'متوافق مع أحدث إصدارات فاست أوردر (2.0+)',
                    'مثالي لمتاجر الإلكترونيات، الهواتف، أجهزة الكمبيوتر، والمعدات الفنية',
                    'يدعم بوابات الدفع وتقسيط المشتريات والضمان الممتد',
                ],
                'rating' => 4.89,
                'reviews_count' => 91,
                'reviews' => [
                    [
                        'id' => 'rev_13',
                        'reviewer' => 'مجدي إبراهيم - عالم الديجيتال',
                        'rating' => 5,
                        'date' => '2026-06-12',
                        'comment' => 'جدول المقارنة وعرض المواصفات ساعدنا جداً في إقناع العملاء بشراء أجهزة اللابتوب والتابلت.',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 70,
            ],
        ];
    }
}
