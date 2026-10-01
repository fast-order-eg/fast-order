<?php

namespace App\Console\Commands;

use App\Models\ShippingGovernorate;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UpdateMerchantShippingRates extends Command
{
    protected $signature = 'shipping:update-rates {--tenant=saoud : Tenant subdomain or ID}';
    protected $description = 'تحديث أسعار الشحن للمحافظات لتاجر محدد وتفعيل جميع المحافظات';

    public function handle()
    {
        $tenantInput = $this->option('tenant') ?: 'saoud';

        $tenant = is_numeric($tenantInput)
            ? Tenant::find($tenantInput)
            : Tenant::where('slug', $tenantInput)->orWhere('custom_domain', $tenantInput)->first();

        if (!$tenant) {
            $this->error("التاجر غير موجود: {$tenantInput}");
            return 1;
        }

        $this->info("جاري تحديث أسعار الشحن للتاجر: {$tenant->name} (معرف: {$tenant->id}, نطاق: {$tenant->slug})");

        // تعريف المجموعات
        $cairoGiza = [
            'القاهرة' => 65,
            'الجيزة'  => 65,
        ];

        $upperEgypt = [
            'الفيوم'        => 75,
            'بني سويف'      => 75,
            'المنيا'        => 75,
            'أسيوط'         => 75,
            'اسيوط'         => 75,
            'سوهاج'         => 75,
            'قنا'           => 75,
            'الأقصر'        => 75,
            'الاقصر'        => 75,
            'أسوان'         => 75,
            'اسوان'         => 75,
            'البحر الأحمر'  => 75,
            'الوادي الجديد' => 75,
        ];

        $deltaCanalAndBorder = [
            'الإسكندرية'  => 70,
            'الاسكندرية'  => 70,
            'البحيرة'     => 70,
            'الغربية'     => 70,
            'كفر الشيخ'   => 70,
            'المنوفية'    => 70,
            'القليوبية'   => 70,
            'الشرقية'     => 70,
            'دمياط'       => 70,
            'بورسعيد'     => 70,
            'الإسماعيلية' => 70,
            'الاسماعيلية' => 70,
            'السويس'      => 70,
            'مطروح'       => 70,
            'الدقهلية'    => 70,
            'شمال سيناء'  => 70,
            'جنوب سيناء'  => 70,
        ];

        // القائمة الشاملة لجميع محافظات مصر الـ 27 القياسية
        $standardGovernorates = [
            // القاهرة والجيزة (65)
            'القاهرة'     => ['price' => 65, 'group' => 'قاهرة وجيزة'],
            'الجيزة'      => ['price' => 65, 'group' => 'قاهرة وجيزة'],

            // محافظات الدلتا والقناة والحدود (70)
            'الإسكندرية'  => ['price' => 70, 'group' => 'محافظات'],
            'البحيرة'     => ['price' => 70, 'group' => 'محافظات'],
            'الغربية'     => ['price' => 70, 'group' => 'محافظات'],
            'كفر الشيخ'   => ['price' => 70, 'group' => 'محافظات'],
            'المنوفية'    => ['price' => 70, 'group' => 'محافظات'],
            'القليوبية'   => ['price' => 70, 'group' => 'محافظات'],
            'الشرقية'     => ['price' => 70, 'group' => 'محافظات'],
            'دمياط'       => ['price' => 70, 'group' => 'محافظات'],
            'بورسعيد'     => ['price' => 70, 'group' => 'محافظات'],
            'الإسماعيلية' => ['price' => 70, 'group' => 'محافظات'],
            'السويس'      => ['price' => 70, 'group' => 'محافظات'],
            'مطروح'       => ['price' => 70, 'group' => 'محافظات'],
            'الدقهلية'    => ['price' => 70, 'group' => 'محافظات'],
            'شمال سيناء'  => ['price' => 70, 'group' => 'محافظات'],
            'جنوب سيناء'  => ['price' => 70, 'group' => 'محافظات'],

            // الصعيد ومحافظات الوجه القبلي (75)
            'الفيوم'        => ['price' => 75, 'group' => 'صعيد'],
            'بني سويف'      => ['price' => 75, 'group' => 'صعيد'],
            'المنيا'        => ['price' => 75, 'group' => 'صعيد'],
            'أسيوط'         => ['price' => 75, 'group' => 'صعيد'],
            'سوهاج'         => ['price' => 75, 'group' => 'صعيد'],
            'قنا'           => ['price' => 75, 'group' => 'صعيد'],
            'الأقصر'        => ['price' => 75, 'group' => 'صعيد'],
            'أسوان'         => ['price' => 75, 'group' => 'صعيد'],
            'البحر الأحمر'  => ['price' => 75, 'group' => 'صعيد'],
            'الوادي الجديد' => ['price' => 75, 'group' => 'صعيد'],
        ];

        $normalize = function ($str) {
            $str = trim($str);
            $str = str_replace(['أ', 'إ', 'آ'], 'ا', $str);
            $str = str_replace('ة', 'ه', $str);
            $str = str_replace('ى', 'ي', $str);
            return $str;
        };

        // 1. تحديث السجلات الموجودة حالياً لدى التاجر
        $existing = ShippingGovernorate::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->get();

        $rows = [];
        $handledNorms = [];

        foreach ($existing as $gov) {
            $norm = $normalize($gov->name);
            $handledNorms[$norm] = true;

            $matchedPrice = null;
            $matchedGroup = 'أخرى';

            foreach ($standardGovernorates as $stdName => $meta) {
                if ($norm === $normalize($stdName)) {
                    $matchedPrice = $meta['price'];
                    $matchedGroup = $meta['group'];
                    break;
                }
            }

            if ($matchedPrice === null) {
                // محاولة التعرف بناءً على الكلمات
                if (str_contains($norm, 'قاهر') || str_contains($norm, 'جيز')) {
                    $matchedPrice = 65;
                    $matchedGroup = 'قاهرة وجيزة';
                } elseif (str_contains($norm, 'صعيد') || str_contains($norm, 'منيا') || str_contains($norm, 'اسيوط') || str_contains($norm, 'سوهاج') || str_contains($norm, 'قنا') || str_contains($norm, 'اقصر') || str_contains($norm, 'اسوان') || str_contains($norm, 'فيوم') || str_contains($norm, 'سويف')) {
                    $matchedPrice = 75;
                    $matchedGroup = 'صعيد';
                } else {
                    $matchedPrice = 70;
                    $matchedGroup = 'محافظات';
                }
            }

            $oldPrice = $gov->price;
            $oldActive = $gov->is_active;

            $gov->update([
                'price'     => $matchedPrice,
                'is_active' => true, // التأكد من تفعيل كل المحافظات
            ]);

            $rows[] = [
                $gov->id,
                $gov->name,
                $matchedGroup,
                "{$oldPrice} ج.م",
                "{$matchedPrice} ج.م",
                $oldActive ? 'مفعلة' : 'غير مفعلة',
                'مفعلة ✓',
            ];
        }

        // 2. التحقق من أي محافظة قياسية غير موجودة وإضافتها مفعلة بالسعر الصحيح
        foreach ($standardGovernorates as $stdName => $meta) {
            $norm = $normalize($stdName);
            if (!isset($handledNorms[$norm])) {
                $created = ShippingGovernorate::withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->id,
                    'name'      => $stdName,
                    'price'     => $meta['price'],
                    'is_active' => true,
                ]);

                $rows[] = [
                    $created->id,
                    $stdName,
                    $meta['group'],
                    'جديدة',
                    "{$meta['price']} ج.م",
                    '-',
                    'مفعلة ✓ (أُضيفت حديثاً)',
                ];
            }
        }

        $this->table(
            ['المعرف', 'المحافظة', 'المجموعة', 'السعر السابق', 'السعر الجديد', 'الحالة السابقة', 'الحالة الحالية'],
            $rows
        );

        // مسح الكاش
        Cache::flush();
        $this->info("تم تحديث كافة أسعار المحافظات وتفعيلها بنجاح للتاجر: {$tenant->name} ✓");

        return 0;
    }
}
