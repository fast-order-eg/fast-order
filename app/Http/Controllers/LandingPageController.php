<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    /**
     * عرض صفحة الهبوط للزائر (Custom URL: /lp/{slug})
     */
    public function show(Request $request, $slug)
    {
        $tenant = $request->attributes->get('tenant');
        $tenantId = $tenant ? $tenant->id : (session()->get('tenant_id') ?? config('tenant.id'));

        $query = LandingPage::where('slug', $slug);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // لو لم يكن في وضع المعاينة، نعرض فقط الصفحات النشطة
        if (!$request->has('preview') && !$request->has('is_preview')) {
            $query->where('is_active', true);
        }

        $landingPage = $query->first();

        // محاولة بحث احتياطية في حالة وضع المعاينة لو لم نجد الصفحة
        if (!$landingPage && $request->has('preview')) {
            $landingPage = LandingPage::withoutGlobalScopes()->where('slug', $slug)->first();
        }

        if (!$landingPage) {
            abort(404, 'صفحة الهبوط غير موجودة أو غير مفعلة حالياً.');
        }

        // تسجيل الزيارة إن لم تكن معاينة
        if (!$request->has('preview') && !$request->has('is_preview')) {
            $landingPage->incrementViews();
        }

        // تجهيز الأقسام للرندرة
        $sections = $landingPage->parsed_sections;

        // إثراء قسم عرض المنتج (Product Showcase) بالبيانات الفعلية للمنتج إن وجد
        foreach ($sections as &$section) {
            if (isset($section['type']) && $section['type'] === 'product_showcase') {
                $productId = $section['product_id'] ?? null;
                $product = null;

                if ($productId) {
                    $productQuery = Product::where('id', $productId);
                    if ($tenantId) {
                        $productQuery->where('tenant_id', $tenantId);
                    }
                    $product = $productQuery->first();
                }

                // إذا لم يُحدد منتج، نأخذ أحدث منتج نشط كنموذج رائع للعرض
                if (!$product) {
                    $fallbackQuery = Product::query();
                    if ($tenantId) {
                        $fallbackQuery->where('tenant_id', $tenantId);
                    }
                    $product = $fallbackQuery->first();
                }

                if ($product) {
                    // Fetch product secondary images
                    $secondaryImages = $product->images->map(function($img) {
                        return $img->image_path ? (str_starts_with($img->image_path, 'http') ? $img->image_path : asset('storage/' . $img->image_path)) : null;
                    })->filter()->values()->toArray();

                    $mainImage = $product->image_display_url ?: ($product->main_image_path ? asset('storage/' . $product->main_image_path) : ($product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : asset('storage/' . $product->image_url)) : null));

                    $allImages = array_values(array_unique(array_filter(array_merge([$mainImage], $secondaryImages))));

                    $section['product_data'] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'original_price' => $product->price_before ?? ($product->price * 1.5),
                        'image_url' => $mainImage,
                        'images' => $allImages,
                        'sizes' => $product->sizes ?? [],
                        'colors' => $product->colors ?? [],
                        'price_tiers' => $product->price_tiers ?? [],
                        'shipping_type' => $product->shipping_type ?? 'free',
                    ];

                    if ($mainImage) {
                        $section['image'] = $mainImage;
                        $section['product_image'] = $mainImage;
                    }
                }
            }
        }
        unset($section);

        $theme = $this->getThemeData($tenant);
        $governorates = \App\Models\ShippingGovernorate::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return view('shop.landing', compact('landingPage', 'sections', 'tenant', 'theme', 'governorates'));
    }

    /**
     * تسجيل تحويل جديد (Conversion Tracking via AJAX)
     */
    public function convert(Request $request, $slug)
    {
        $tenant = $request->attributes->get('tenant');
        $tenantId = $tenant ? $tenant->id : (session()->get('tenant_id') ?? config('tenant.id'));

        $query = LandingPage::where('slug', $slug);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $landingPage = $query->first();

        if (!$landingPage) {
            return response()->json([
                'success' => false,
                'message' => 'الصفحة غير موجودة'
            ], 404);
        }

        $landingPage->incrementConversions();

        return response()->json([
            'success' => true,
            'conversions_count' => $landingPage->conversions_count,
            'conversion_rate' => $landingPage->conversion_rate,
            'message' => 'تم تسجيل التحويل بنجاح ✓'
        ]);
    }

    /**
     * استخراج بيانات السيم والألوان الخاصة بالتاجر
     */
    private function getThemeData($tenant): array
    {
        if (!$tenant) {
            return [
                'primary_color'   => '#6c63ff',
                'secondary_color' => '#ff6584',
                'font_family'     => 'Cairo',
            ];
        }

        $settings = is_array($tenant->settings)
            ? $tenant->settings
            : json_decode($tenant->settings ?? '{}', true);

        return [
            'primary_color'   => $settings['primary_color']   ?? '#6c63ff',
            'secondary_color' => $settings['secondary_color'] ?? '#ff6584',
            'font_family'     => $settings['font_family']     ?? 'Cairo',
        ];
    }
}
