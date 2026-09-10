<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tutorial;
use Illuminate\Support\Facades\DB;

class TutorialSeeder extends Seeder
{
    public function run(): void
    {
        // Clear old test tutorials
        DB::table('tutorials')->truncate();

        $tutorials = [
            [
                'title'        => 'الفيديو الأول: كيفية التسجيل على المتجر',
                'category'     => 'البداية والسريعة',
                'youtube_url'  => 'https://youtube.com/shorts/Enq-JEUI3pU?si=r5LljIhpVDWuX6hc',
                'youtube_id'   => 'Enq-JEUI3pU',
                'description'  => 'شرح سريع وبسيط لكيفية إنشاء وتفعيل حساب تاجر جديد على المنصة والبدء فوراً.',
                'duration'     => '0:50',
                'sort_order'   => 1,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الثاني: شرح الباقات والاشتراكات',
                'category'     => 'البداية والسريعة',
                'youtube_url'  => 'https://youtube.com/shorts/PIwmQYy_R8w?si=rOVsuZi74OTCLzPu',
                'youtube_id'   => 'PIwmQYy_R8w',
                'description'  => 'تعرف على باقات المنصة ومميزات كل باقة وكيف تختار الباقة الأنسب لحجم تجارتك.',
                'duration'     => '0:55',
                'sort_order'   => 2,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الثالث: كيفية شحن المحفظة',
                'category'     => 'البداية والسريعة',
                'youtube_url'  => 'https://youtube.com/shorts/I5HOD7b3gYQ?si=u36ZCeytwKByiuY9',
                'youtube_id'   => 'I5HOD7b3gYQ',
                'description'  => 'طريقة شحن رصيد المحفظة عبر فودافون كاش أو إنستا باي وتأكيد العملية بسهولة.',
                'duration'     => '0:45',
                'sort_order'   => 3,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الرابع: طريقة تغيير اسم المتجر والشعار',
                'category'     => 'إعدادات المتجر والتصميم',
                'youtube_url'  => 'https://youtube.com/shorts/RzkdZctq2Ro?si=4NUC9ehtUPXCVae1',
                'youtube_id'   => 'RzkdZctq2Ro',
                'description'  => 'تخصيص الهوية البصرية لمتجرك عبر تغيير الاسم ورفع اللوجو والأيقونة المفضلة.',
                'duration'     => '0:50',
                'sort_order'   => 4,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الخامس: طريقة تغيير رابط المتجر',
                'category'     => 'إعدادات المتجر والتصميم',
                'youtube_url'  => 'https://youtube.com/shorts/FrcPDsvgo5g?si=eDoO4EbsWEeF2Udg',
                'youtube_id'   => 'FrcPDsvgo5g',
                'description'  => 'كيفية تعديل وتخصيص رابط المتجر (السب دومين) ليكون معبراً عن براندك.',
                'duration'     => '0:40',
                'sort_order'   => 5,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو السادس: كيفية عمل بانر للمتجر',
                'category'     => 'إعدادات المتجر والتصميم',
                'youtube_url'  => 'https://youtube.com/shorts/PN8o_IIsf3U?si=8v8CO6GQLphb2i31',
                'youtube_id'   => 'PN8o_IIsf3U',
                'description'  => 'إضافة بانرات ترويجية جذابة في واجهة متجرك لزيادة المبيعات ولفت انتباه العملاء.',
                'duration'     => '0:55',
                'sort_order'   => 6,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو السابع: معاينة شكل المتجر والتصميم',
                'category'     => 'إعدادات المتجر والتصميم',
                'youtube_url'  => 'https://youtube.com/shorts/mfEllQ7q7ak?si=tuJm9ztYrMg-szut',
                'youtube_id'   => 'mfEllQ7q7ak',
                'description'  => 'جولة في واجهة المتجر وتجربة تصفح المنتجات كما يراها عميلك النهائي.',
                'duration'     => '0:50',
                'sort_order'   => 7,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الثامن: طريقة إضافة أقسام رئيسية',
                'category'     => 'المنتجات والأقسام',
                'youtube_url'  => 'https://youtube.com/shorts/ngMxhr4GTbc?si=y6jI7fqiI5pIGifz',
                'youtube_id'   => 'ngMxhr4GTbc',
                'description'  => 'خطوات إنشاء أقسام رئيسية لتنظيم منتجات متجرك وتسهيل وصول العملاء لها.',
                'duration'     => '0:45',
                'sort_order'   => 8,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو التاسع: طريقة إضافة أقسام رئيسية وفرعية',
                'category'     => 'المنتجات والأقسام',
                'youtube_url'  => 'https://youtube.com/shorts/oY1By3-dcUY?si=tViLTdBNCgC25ZLJ',
                'youtube_id'   => 'oY1By3-dcUY',
                'description'  => 'كيفية عمل هيكلية تصنيفات احترافية تضم أقساماً رئيسية وتفرعات فرعية تابعة لها.',
                'duration'     => '0:55',
                'sort_order'   => 9,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو العاشر: كيفية تنزيل المنتجات',
                'category'     => 'المنتجات والأقسام',
                'youtube_url'  => 'https://youtube.com/shorts/tjeDdpg5n4g?si=EBnp5zBCke994hQ4',
                'youtube_id'   => 'tjeDdpg5n4g',
                'description'  => 'إضافة منتج جديد وتحديد السعر والصور والوصف والمخزون خطوة بخطوة.',
                'duration'     => '1:00',
                'sort_order'   => 10,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الحادي عشر: طريقة تعديل المنتج',
                'category'     => 'المنتجات والأقسام',
                'youtube_url'  => 'https://youtube.com/shorts/Y4FWEtpyACU?si=2RAJi8PAV3cx6kEs',
                'youtube_id'   => 'Y4FWEtpyACU',
                'description'  => 'تحديث بيانات المنتجات، الأسعار، العروض، والصور في أي وقت بسهولة.',
                'duration'     => '0:50',
                'sort_order'   => 11,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الثاني عشر: شرح زرار المتغير (خصائص المنتج)',
                'category'     => 'المنتجات والأقسام',
                'youtube_url'  => 'https://youtube.com/shorts/nNO3On-3H54?si=thBWH41buE0fY685',
                'youtube_id'   => 'nNO3On-3H54',
                'description'  => 'طريقة تفعيل خيارات المقاسات والألوان والمتغيرات داخل صفحة المنتج.',
                'duration'     => '0:55',
                'sort_order'   => 12,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الثالث عشر: معاينة الأوردرات وتغيير الحالة',
                'category'     => 'الطلبات والمبيعات',
                'youtube_url'  => 'https://youtube.com/shorts/iTJ61zqzbzA?si=pY_qXDrtV_J3JYyg',
                'youtube_id'   => 'iTJ61zqzbzA',
                'description'  => 'إدارة الطلبات الواردة، متابعة تفاصيل العميل، وتحديث حالة الطلب (مؤكد، شحن، تم التسليم).',
                'duration'     => '0:50',
                'sort_order'   => 13,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الرابع عشر: شرح السلة المتروكة واسترجاع الطلبات',
                'category'     => 'الطلبات والمبيعات',
                'youtube_url'  => 'https://youtu.be/pGKkY2h_d5I?si=zxaPDYQMJzbkkpxW',
                'youtube_id'   => 'pGKkY2h_d5I',
                'description'  => 'شرح تفصيلي كامل لكيفية متابعة السلات المتروكة وتذكير العملاء واسترجاع مبيعاتك المفقودة.',
                'duration'     => 'فيديو تفصيلي',
                'sort_order'   => 14,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الخامس عشر: تعديل أسعار وتكاليف الشحن',
                'category'     => 'الطلبات والمبيعات',
                'youtube_url'  => 'https://youtube.com/shorts/Ca0WSDowAzU?si=notf9jgEacRctxsO',
                'youtube_id'   => 'Ca0WSDowAzU',
                'description'  => 'ضبط وتخصيص أسعار التوصيل والشحن لكل محافظة ومنطقة في مصر.',
                'duration'     => '0:45',
                'sort_order'   => 15,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو السادس عشر: طريقة ربط شركات الشحن',
                'category'     => 'إعدادات المتجر والتصميم',
                'youtube_url'  => 'https://youtube.com/shorts/AZMwKkgRK3Y?si=2l3SjYmEZKz4m0a4',
                'youtube_id'   => 'AZMwKkgRK3Y',
                'description'  => 'ربط بوابات الشحن مثل بوسطة وJ&T Express لإصدار بوليصات الشحن تلقائياً.',
                'duration'     => '0:55',
                'sort_order'   => 16,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو السابع عشر: طريقة ربط فيسبوك بكسل (Pixel)',
                'category'     => 'إعدادات المتجر والتصميم',
                'youtube_url'  => 'https://youtube.com/shorts/K4TAlVAowMw?si=bbpafqHoBUc43LCe',
                'youtube_id'   => 'K4TAlVAowMw',
                'description'  => 'تتبع تحويلات الحملات الإعلانية على فيسبوك بدقة عبر ربط الـ Pixel ID.',
                'duration'     => '0:50',
                'sort_order'   => 17,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو الثامن عشر: طريقة ربط Conversion API',
                'category'     => 'إعدادات المتجر والتصميم',
                'youtube_url'  => 'https://youtube.com/shorts/hyVlo_g1Gbc?si=ThSiEhSnhrmqPQzp',
                'youtube_id'   => 'hyVlo_g1Gbc',
                'description'  => 'الربط السيرفري المتطور لـ Meta Conversion API لتجاوز حظر ملفات تعريف الارتباط.',
                'duration'     => '0:55',
                'sort_order'   => 18,
                'is_published' => true,
            ],
            [
                'title'        => 'الفيديو التاسع عشر: طريقة تغيير الإيميل وكلمة المرور',
                'category'     => 'عام',
                'youtube_url'  => 'https://youtube.com/shorts/4rRPN38okQ0?si=ktS_uF6Pb5hslAJo',
                'youtube_id'   => '4rRPN38okQ0',
                'description'  => 'تأمين حسابك وتحديث بيانات الدخول الخاصة بالبريد الإلكتروني والرقم السري.',
                'duration'     => '0:40',
                'sort_order'   => 19,
                'is_published' => true,
            ],
        ];

        foreach ($tutorials as $item) {
            Tutorial::create($item);
        }
    }
}
