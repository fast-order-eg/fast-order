<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use SoftDeletes;

    protected static function booted()
    {
        static::created(function ($tenant) {
            $governorates = [
                // القاهرة والجيزة 65
                ['name' => 'القاهرة', 'price' => 65, 'is_active' => true],
                ['name' => 'الجيزة', 'price' => 65, 'is_active' => true],

                // وجه بحري 75
                ['name' => 'الإسكندرية', 'price' => 75, 'is_active' => true],
                ['name' => 'البحيرة', 'price' => 75, 'is_active' => true],
                ['name' => 'الغربية', 'price' => 75, 'is_active' => true],
                ['name' => 'كفر الشيخ', 'price' => 75, 'is_active' => true],
                ['name' => 'المنوفية', 'price' => 75, 'is_active' => true],
                ['name' => 'القليوبية', 'price' => 75, 'is_active' => true],
                ['name' => 'الشرقية', 'price' => 75, 'is_active' => true],
                ['name' => 'دمياط', 'price' => 75, 'is_active' => true],
                ['name' => 'بورسعيد', 'price' => 75, 'is_active' => true],
                ['name' => 'الإسماعيلية', 'price' => 75, 'is_active' => true],
                ['name' => 'السويس', 'price' => 75, 'is_active' => true],
                ['name' => 'مطروح', 'price' => 75, 'is_active' => true],
                ['name' => 'الدقهلية', 'price' => 75, 'is_active' => true],

                // وجه قبلي 85
                ['name' => 'المنيا', 'price' => 85, 'is_active' => true],
                ['name' => 'بني سويف', 'price' => 85, 'is_active' => true],
                ['name' => 'الفيوم', 'price' => 85, 'is_active' => true],
                ['name' => 'أسيوط', 'price' => 85, 'is_active' => true],
                ['name' => 'سوهاج', 'price' => 85, 'is_active' => true],
                ['name' => 'قنا', 'price' => 85, 'is_active' => true],
                ['name' => 'الأقصر', 'price' => 85, 'is_active' => true],
                ['name' => 'أسوان', 'price' => 85, 'is_active' => true],
                ['name' => 'البحر الأحمر', 'price' => 85, 'is_active' => true],

                // الوادي الجديد والسيناوات 110 (غير مفعلين)
                ['name' => 'الوادي الجديد', 'price' => 110, 'is_active' => false],
                ['name' => 'شمال سيناء', 'price' => 110, 'is_active' => false],
                ['name' => 'جنوب سيناء', 'price' => 110, 'is_active' => false],
            ];

            foreach ($governorates as $gov) {
                $tenant->shippingGovernorates()->create($gov);
            }

            // 2. Auto-seed Default Main Category ("ملابس") in settings
            Setting::set('main_categories', json_encode(['ملابس'], JSON_UNESCAPED_UNICODE), 'general', $tenant->id);

            // 3. Auto-seed Default Subcategories under "ملابس" with high-quality square images
            $defaultCategories = [
                [
                    'name_ar' => 'ملابس حريمي',
                    'name' => 'ملابس حريمي',
                    'main_category' => 'ملابس',
                    'image_path' => '/images/default_categories/womens_clothing.jpg',
                ],
                [
                    'name_ar' => 'ملابس رجالي',
                    'name' => 'ملابس رجالي',
                    'main_category' => 'ملابس',
                    'image_path' => '/images/default_categories/mens_clothing.jpg',
                ],
                [
                    'name_ar' => 'ملابس اطفالي',
                    'name' => 'ملابس اطفالي',
                    'main_category' => 'ملابس',
                    'image_path' => '/images/default_categories/kids_clothing.jpg',
                ],
            ];

            foreach ($defaultCategories as $catData) {
                Category::create(array_merge($catData, ['tenant_id' => $tenant->id]));
            }
        });
    }


    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'logo',
        'favicon',
        'email',
        'phone',
        'owner_id',
        'subscription_status',
        'trial_ends_at',
        'subscription_ends_at',
        'is_active',
        'wallet_balance',
        'settings',
        'theme_id',
        'custom_domain',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'wallet_balance' => 'integer',
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function shippingGovernorates(): HasMany
    {
        return $this->hasMany(ShippingGovernorate::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    /**
     * Check if tenant is currently on Commission Plan
     */
    public function isCommissionPlan(): bool
    {
        $activeSub = $this->subscriptions()->where('status', 'active')->latest()->first();
        if ($activeSub && $activeSub->plan) {
            return $activeSub->plan->slug === 'commission' || str_contains(mb_strtolower($activeSub->plan->name ?? ''), 'عمولة');
        }
        return false;
    }

    /**
     * Get the store display name
     */
    public function getStoreNameAttribute(): string
    {
        return Setting::get('store_name', $this->name ?: 'متجري', $this->id);
    }
}
