<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'description',
        'price',
        'price_before',
        'price_after',
        'price_tiers',
        'stock',
        'low_stock_threshold',
        'shipping_type',
        'image_url',
        'main_image_path',
        'sizes',
        'colors',
        'custom_variants',
        'variants_stock',
    ];

    protected $casts = [
        'price' => 'integer',
        'price_before' => 'integer',
        'price_after' => 'integer',
        'price_tiers' => 'array',
        'sizes' => 'array',
        'colors' => 'array',
        'custom_variants' => 'array',
        'variants_stock' => 'array',
    ];

    protected $appends = ['image_display_url'];

    public static function resolveImageUrl(?string $path): ?string
    {
        if (!$path) return null;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $cleanPath = ltrim($path, '/');

        if (str_starts_with($cleanPath, 'images/')) {
            return asset($cleanPath);
        }

        if (str_starts_with($cleanPath, 'storage/')) {
            return asset($cleanPath);
        }

        if (file_exists(public_path($cleanPath))) {
            return asset($cleanPath);
        }

        if (file_exists(public_path('images/' . $cleanPath))) {
            return asset('images/' . $cleanPath);
        }

        if (file_exists(public_path('images/products/' . $cleanPath))) {
            return asset('images/products/' . $cleanPath);
        }

        return asset('storage/' . $cleanPath);
    }

    public function getImageDisplayUrlAttribute()
    {
        $path = $this->main_image_path ?: $this->image_url;
        return static::resolveImageUrl($path);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function upsells()
    {
        return $this->belongsToMany(Product::class, 'product_recommendations', 'product_id', 'recommended_id')
            ->wherePivot('type', 'upsell')
            ->withPivot('type')
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::saving(function ($product) {
            if (!empty($product->variants_stock)) {
                $variants = is_array($product->variants_stock)
                    ? $product->variants_stock
                    : (is_string($product->variants_stock) ? json_decode($product->variants_stock, true) : []);
                if (is_array($variants) && count($variants) > 0) {
                    $product->stock = array_reduce($variants, function ($sum, $v) {
                        return $sum + (int) ($v['qty'] ?? 0);
                    }, 0);
                }
            }
        });

        static::saved(function ($product) {
            if ($product->tenant_id) {
                \App\Services\CacheService::invalidateDashboardStats($product->tenant_id);
            }
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
        });

        static::deleted(function ($product) {
            if ($product->tenant_id) {
                \App\Services\CacheService::invalidateDashboardStats($product->tenant_id);
            }
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
        });
    }

    public function crossSells()
    {
        return $this->belongsToMany(Product::class, 'product_recommendations', 'product_id', 'recommended_id')
            ->wherePivot('type', 'cross-sell')
            ->withPivot('type')
            ->withTimestamps();
    }

    /**
     * تقليل كمية variant محدد من variants_stock + تقليل stock الإجمالي
     */
    public function decrementVariantStock(int $qty, ?string $size = null, ?string $color = null, array $options = []): void
    {
        // 1) خصم من الإجمالي
        $this->decrement('stock', $qty);

        // 2) خصم من variants_stock إن وُجد
        $variantsStock = $this->variants_stock;
        if (!is_array($variantsStock) || empty($variantsStock)) {
            return;
        }

        $hasSize  = !empty($size);
        $hasColor = !empty($color);
        $hasOpts  = !empty($options);

        if (!$hasSize && !$hasColor && !$hasOpts) {
            return;
        }

        foreach ($variantsStock as &$row) {
            $matchSize  = !$hasSize  || (($row['size']  ?? null) === $size);
            $matchColor = !$hasColor || (($row['color'] ?? null) === $color);
            $matchOpts  = true;
            if ($hasOpts) {
                $rowOpts = $row['options'] ?? [];
                foreach ($options as $k => $v) {
                    if (($rowOpts[$k] ?? null) !== $v) { $matchOpts = false; break; }
                }
            }
            if ($matchSize && $matchColor && $matchOpts) {
                $row['qty'] = max(0, (int)($row['qty'] ?? 0) - $qty);
                break;
            }
        }
        unset($row);
        $this->variants_stock = $variantsStock;
        $this->save();
    }

    /**
     * زيادة كمية variant محدد في variants_stock + زيادة stock الإجمالي عند إلغاء أو استرجاع الطلب
     */
    public function incrementVariantStock(int $qty, ?string $size = null, ?string $color = null, array $options = []): void
    {
        // 1) زيادة الإجمالي
        $this->increment('stock', $qty);

        // 2) زيادة في variants_stock إن وُجد
        $variantsStock = $this->variants_stock;
        if (!is_array($variantsStock) || empty($variantsStock)) {
            return;
        }

        $hasSize  = !empty($size);
        $hasColor = !empty($color);
        $hasOpts  = !empty($options);

        if (!$hasSize && !$hasColor && !$hasOpts) {
            return;
        }

        foreach ($variantsStock as &$row) {
            $matchSize  = !$hasSize  || (($row['size']  ?? null) === $size);
            $matchColor = !$hasColor || (($row['color'] ?? null) === $color);
            $matchOpts  = true;
            if ($hasOpts) {
                $rowOpts = $row['options'] ?? [];
                foreach ($options as $k => $v) {
                    if (($rowOpts[$k] ?? null) !== $v) { $matchOpts = false; break; }
                }
            }
            if ($matchSize && $matchColor && $matchOpts) {
                $row['qty'] = (int)($row['qty'] ?? 0) + $qty;
                break;
            }
        }
        unset($row);
        $this->variants_stock = $variantsStock;
        $this->save();
    }

    /**
     * الحصول على سعر المتغير المخصص إن وُجد، أو السعر الأساسي للمنتج
     */
    public function getVariantPrice(?string $size = null, ?string $color = null, array $options = []): float
    {
        $basePrice = (float) ($this->price_after ?? $this->price ?? 0);

        $variantsStock = $this->variants_stock;
        if (!is_array($variantsStock) || empty($variantsStock)) {
            return $basePrice;
        }

        $hasSize  = !empty($size);
        $hasColor = !empty($color);
        $hasOpts  = !empty($options);

        if (!$hasSize && !$hasColor && !$hasOpts) {
            return $basePrice;
        }

        foreach ($variantsStock as $row) {
            $matchSize  = !$hasSize  || (($row['size']  ?? null) === $size);
            $matchColor = !$hasColor || (($row['color'] ?? null) === $color);
            $matchOpts  = true;
            if ($hasOpts) {
                $rowOpts = $row['options'] ?? [];
                foreach ($options as $k => $v) {
                    if (($rowOpts[$k] ?? null) !== $v) { $matchOpts = false; break; }
                }
            }
            if ($matchSize && $matchColor && $matchOpts) {
                if (isset($row['price']) && is_numeric($row['price']) && (float)$row['price'] > 0) {
                    return (float) $row['price'];
                }
                break;
            }
        }

        return $basePrice;
    }
}
