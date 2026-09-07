<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductRecommendation;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * عرض قائمة المنتجات مع البحث والفلتر بالتصنيف
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $categoryId = $request->get('category_id');

        $products = Product::with('category')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name', 'name_ar']);

        return Inertia::render('Merchant/Products/Index', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => [
                'q'           => $q,
                'category_id' => $categoryId,
            ],
        ]);
    }

    /**
     * صفحة إنشاء منتج جديد
     */
    public function create(\Illuminate\Http\Request $request)
    {
        $categories = Category::orderBy('name')->get(['id', 'name', 'name_ar']);

        $duplicateFrom = null;
        if ($request->filled('duplicate_from')) {
            $sourceProduct = Product::with(['images', 'upsells', 'crossSells'])
                ->find($request->query('duplicate_from'));

            if ($sourceProduct) {
                $duplicateFrom = $sourceProduct;
            }
        }

        $allProducts = Product::orderBy('name')->get(['id', 'name', 'price_after', 'main_image_path']);

        return Inertia::render('Merchant/Products/Create', [
            'categories'    => $categories,
            'duplicateFrom' => $duplicateFrom,
            'allProducts'   => $allProducts,
        ]);
    }

    /**
     * حفظ المنتج الجديد
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $data = [
            'name'                => $validated['name'],
            'description'         => $validated['description'] ?? null,
            'category_id'         => $validated['category_id'],
            'price'               => $validated['price_after'],
            'price_before'        => $validated['price_before'] ?? 0,
            'price_after'         => $validated['price_after'],
            'stock'               => $validated['stock'],
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'shipping_type'       => $validated['shipping_type'],
        ];

        // حفظ المقاسات والألوان والمتغيرات المخصصة
        if ($request->has('sizes') && is_array($request->sizes)) {
            $data['sizes'] = array_values(array_filter($request->sizes));
        }
        if ($request->has('colors') && is_array($request->colors)) {
            $data['colors'] = array_values(array_filter($request->colors));
        }
        if ($request->has('custom_variants')) {
            $cv = $request->custom_variants;
            $cvArr = is_string($cv) ? json_decode($cv, true) : $cv;
            if (is_array($cvArr)) {
                $filtered = array_values(array_filter($cvArr, fn($item) => !empty($item['name']) && !empty($item['values']) && count($item['values']) > 0));
                $data['custom_variants'] = count($filtered) > 0 ? $filtered : null;
            }
        }

        // حفظ شرائح الأسعار
        if ($request->filled('price_tiers_json')) {
            $tiers = json_decode($request->price_tiers_json, true);
            if (is_array($tiers) && count($tiers) > 0) {
                $filtered = array_filter($tiers, fn($t) => isset($t['min_qty']) && isset($t['price']) && $t['min_qty'] >= 2 && $t['price'] > 0);
                $data['price_tiers'] = count($filtered) > 0 ? array_values($filtered) : null;
            }
        }

        // حفظ مخزون المتغيرات
        if ($request->filled('variants_stock')) {
            $data['variants_stock'] = is_string($request->variants_stock) ? json_decode($request->variants_stock, true) : $request->variants_stock;
        }

        if ($request->hasFile('main_image')) {
            $path = \App\Services\ImageCompressionService::compressAndStore($request->file('main_image'), 'products', 'public');
            $data['main_image_path'] = $path;
        } elseif ($request->filled('existing_main_image')) {
            $data['main_image_path'] = $request->input('existing_main_image');
        }

        // tenant_id يُعبأ تلقائياً عبر BelongsToTenant trait
        $product = Product::create($data);

        // تسجيل حركة المخزون الابتدائية
        if ($product->stock > 0) {
            \App\Models\StockMovement::create([
                'product_id'  => $product->id,
                'quantity'    => $product->stock,
                'type'        => 'in',
                'description' => 'المخزون الابتدائي للمنتج عند الإنشاء',
            ]);
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $imageFile) {
                if ($imageFile && $imageFile->isValid()) {
                    $gpath = \App\Services\ImageCompressionService::compressAndStore($imageFile, 'products/gallery', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $gpath,
                    ]);
                }
            }
        } elseif ($request->has('existing_gallery') && is_array($request->existing_gallery)) {
            foreach ($request->existing_gallery as $gpath) {
                if ($gpath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $gpath,
                    ]);
                }
            }
        }

        if ($request->has('upsell_ids') && is_array($request->upsell_ids)) {
            foreach ($request->upsell_ids as $targetId) {
                ProductRecommendation::create([
                    'product_id'             => $product->id,
                    'recommended_product_id' => $targetId,
                    'type'                   => 'upsell',
                ]);
            }
        }

        if ($request->has('cross_sell_ids') && is_array($request->cross_sell_ids)) {
            foreach ($request->cross_sell_ids as $targetId) {
                ProductRecommendation::create([
                    'product_id'             => $product->id,
                    'recommended_product_id' => $targetId,
                    'type'                   => 'cross_sell',
                ]);
            }
        }

        // Trigger Webhook product.created
        \App\Services\WebhookSender::trigger('product.created', $product->toArray(), $product->tenant_id);

        return redirect()->route('merchant.products.index')
            ->with('success', 'تم إضافة المنتج بنجاح ✓');
    }

    /**
     * صفحة تعديل المنتج
     */
    public function edit(Product $product)
    {
        $product->load(['images', 'upsells', 'crossSells']);
        $categories = Category::orderBy('name')->get(['id', 'name', 'name_ar']);

        $allProducts = Product::where('id', '!=', $product->id)
            ->orderBy('name')
            ->get(['id', 'name', 'price_after', 'main_image_path']);

        return Inertia::render('Merchant/Products/Edit', [
            'product'     => $product,
            'categories'  => $categories,
            'allProducts' => $allProducts,
        ]);
    }

    /**
     * تحديث المنتج
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $data = [
            'name'                => $validated['name'],
            'description'         => $validated['description'] ?? null,
            'category_id'         => $validated['category_id'],
            'price'               => $validated['price_after'],
            'price_before'        => $validated['price_before'] ?? 0,
            'price_after'         => $validated['price_after'],
            'stock'               => $validated['stock'],
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'shipping_type'       => $validated['shipping_type'],
        ];

        // تحديث المقاسات والألوان والمتغيرات المخصصة
        if ($request->has('sizes') && is_array($request->sizes)) {
            $data['sizes'] = array_values(array_filter($request->sizes));
        } else {
            $data['sizes'] = null;
        }
        if ($request->has('colors') && is_array($request->colors)) {
            $data['colors'] = array_values(array_filter($request->colors));
        } else {
            $data['colors'] = null;
        }
        if ($request->has('custom_variants')) {
            $cv = $request->custom_variants;
            $cvArr = is_string($cv) ? json_decode($cv, true) : $cv;
            if (is_array($cvArr)) {
                $filtered = array_values(array_filter($cvArr, fn($item) => !empty($item['name']) && !empty($item['values']) && count($item['values']) > 0));
                $data['custom_variants'] = count($filtered) > 0 ? $filtered : null;
            } else {
                $data['custom_variants'] = null;
            }
        } else {
            $data['custom_variants'] = null;
        }

        // تحديث شرائح الأسعار
        if ($request->filled('price_tiers_json')) {
            $rawTiers = $request->price_tiers_json;
            $tiers = is_string($rawTiers) ? json_decode($rawTiers, true) : $rawTiers;
            if (is_array($tiers) && count($tiers) > 0) {
                $filtered = array_filter($tiers, fn($t) => isset($t['min_qty']) && isset($t['price']) && $t['min_qty'] >= 2 && $t['price'] > 0);
                $data['price_tiers'] = count($filtered) > 0 ? array_values($filtered) : null;
            } else {
                $data['price_tiers'] = null;
            }
        } else {
            $data['price_tiers'] = null;
        }

        // تحديث مخزون المتغيرات
        if ($request->filled('variants_stock')) {
            $data['variants_stock'] = is_string($request->variants_stock) ? json_decode($request->variants_stock, true) : $request->variants_stock;
        } else {
            $data['variants_stock'] = null;
        }

        if ($request->hasFile('main_image')) {
            if ($product->main_image_path && Storage::disk('public')->exists($product->main_image_path)) {
                Storage::disk('public')->delete($product->main_image_path);
            }
            $path = \App\Services\ImageCompressionService::compressAndStore($request->file('main_image'), 'products', 'public');
            $data['main_image_path'] = $path;
        }

        $oldStock = $product->stock;
        $product->update($data);

        // تسجيل حركة المخزون في حال تعديله يدوياً
        if ($oldStock != $product->stock) {
            $diff = $product->stock - $oldStock;
            \App\Models\StockMovement::create([
                'product_id'  => $product->id,
                'quantity'    => abs($diff),
                'type'        => 'adjustment',
                'description' => 'تعديل يدوي للمخزون (من ' . $oldStock . ' إلى ' . $product->stock . ')',
            ]);
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $imageFile) {
                if ($imageFile && $imageFile->isValid()) {
                    $gpath = \App\Services\ImageCompressionService::compressAndStore($imageFile, 'products/gallery', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $gpath,
                    ]);
                }
            }
        }

        // حفظ علاقات الـ Upsell والـ Cross-sell
        ProductRecommendation::where('product_id', $product->id)->delete();

        if ($request->has('upsell_ids') && is_array($request->upsell_ids)) {
            foreach ($request->upsell_ids as $recId) {
                ProductRecommendation::create([
                    'tenant_id'      => $product->tenant_id,
                    'product_id'     => $product->id,
                    'recommended_id' => $recId,
                    'type'           => 'upsell',
                ]);
            }
        }

        if ($request->has('cross_sell_ids') && is_array($request->cross_sell_ids)) {
            foreach ($request->cross_sell_ids as $recId) {
                ProductRecommendation::create([
                    'tenant_id'      => $product->tenant_id,
                    'product_id'     => $product->id,
                    'recommended_id' => $recId,
                    'type'           => 'cross-sell',
                ]);
            }
        }

        return redirect()->route('merchant.products.index')
            ->with('success', 'تم تحديث المنتج بنجاح ✓');
    }

    /**
     * حذف المنتج
     */
    public function destroy(Product $product)
    {
        // حذف الصورة الرئيسية
        if ($product->main_image_path && Storage::disk('public')->exists($product->main_image_path)) {
            Storage::disk('public')->delete($product->main_image_path);
        }

        // حذف صور المعرض
        foreach ($product->images ?? [] as $image) {
            if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $product->delete();

        return redirect()->route('merchant.products.index')
            ->with('success', 'تم حذف المنتج بنجاح ✓');
    }

    /**
     * حذف صورة من معرض صور المنتج
     */
    public function destroyImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            return redirect()->back()->with('error', 'الصورة لا تنتمي لهذا المنتج');
        }

        if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return redirect()->back()->with('success', 'تم حذف الصورة بنجاح ✓');
    }

    /**
     * تبديل حالة ظهور المنتج في المتجر (On / Off)
     */
    public function toggleStatus(Product $product)
    {
        $newStatus = !$product->is_active;
        $product->update([
            'is_active' => $newStatus,
        ]);

        $message = $newStatus ? 'تم إظهار المنتج في المتجر بنجاح ✓' : 'تم إخفاء المنتج من المتجر بنجاح ✓';

        if (request()->wantsJson()) {
            return response()->json([
                'success'   => true,
                'is_active' => (bool) $newStatus,
                'message'   => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
