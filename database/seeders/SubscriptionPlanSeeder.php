<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name'          => 'الباقة المجانية',
                'slug'          => 'free',
                'description'   => 'ابدأ مجاناً وجرب المنصة بدون أي تكلفة',
                'price_monthly' => 0,
                'price_yearly'  => 0,
                'trial_days'    => 0,
                'limits'        => [
                    'max_products' => 10,
                    'max_orders'   => 50,
                    'features'     => [
                        'متجر إلكتروني كامل',
                        'نطاق فرعي مجاني',
                        'دعم فني عبر البريد',
                    ]
                ],
                'is_active' => true,
            ],
            [
                'name'          => 'الباقة الشهرية',
                'slug'          => 'monthly',
                'description'   => 'اشتراك شهري مرن بدون التزام طويل',
                'price_monthly' => 500,
                'price_yearly'  => 0,
                'trial_days'    => 0,
                'limits'        => [
                    'max_products' => 500,
                    'max_orders'   => 1000,
                    'features'     => [
                        'جميع مزايا الباقة المجانية',
                        'منتجات غير محدودة',
                        'تقارير مبيعات متقدمة',
                        'دعم فني ذو أولوية',
                    ]
                ],
                'is_active' => true,
            ],
            [
                'name'          => 'الباقة السنوية',
                'slug'          => 'yearly',
                'description'   => 'وفّر أكثر مع الاشتراك السنوي',
                'price_monthly' => 5000,
                'price_yearly'  => 5000,
                'trial_days'    => 0,
                'limits'        => [
                    'max_products' => 9999,
                    'max_orders'   => 9999,
                    'features'     => [
                        'جميع مزايا الباقة الشهرية',
                        'دعم نطاق مخصص',
                        'مدير حساب مخصص',
                        'تكامل كامل مع منصات الإعلانات',
                    ]
                ],
                'is_active' => true,
            ],
            [
                'name'          => 'باقة العمولة (شحن المحفظة)',
                'slug'          => 'commission',
                'description'   => 'ادفع فقط عند البيع — بدون اشتراك شهري',
                'price_monthly' => 0,
                'price_yearly'  => 0,
                'trial_days'    => 0,
                'limits'        => [
                    'max_products' => 9999,
                    'max_orders'   => 9999,
                    'features'     => [
                        'بدون رسوم شهرية',
                        'عمولة على كل طلب فقط',
                        'جميع المزايا الأساسية مشمولة',
                    ]
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}

