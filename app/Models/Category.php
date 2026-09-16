<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Category extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'image_path',
        'name_ar',
        'name_en',
        'parent_id',
        'main_category',
    ];

    // قائمة الأقسام الرئيسية المتاحة (مخزنة في الـ settings)
    public static function getMainCategories(?int $tenantId = null): array
    {
        $stored = Setting::get('main_categories', null, $tenantId);
        if ($stored) {
            $arr = json_decode($stored, true);
            if (is_array($arr) && count($arr)) {
                return array_values(array_unique(array_filter(array_map('trim', $arr))));
            }
        }
        // القيم الافتراضية
        return [
            'ملابس',
        ];
    }

    public static function saveMainCategories(array $categories, ?int $tenantId = null): void
    {
        $clean = array_values(array_unique(array_filter(array_map('trim', $categories))));
        Setting::set('main_categories', json_encode($clean, JSON_UNESCAPED_UNICODE), 'general', $tenantId);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
