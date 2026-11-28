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
            'cost_price'          => $validated['cost_price'] ?? 0,
            'stock'               => $validated['stock'],
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'shipping_type'       => $validated['shipping_type'],
        ];

        // حفظ المقاسات والمتغيرات المخصصة (الألوان تُعالج بعد حفظ الصورة الرئيسية وصور المعرض لربط المراجع بنفس الملف دون تكرار)
        if ($request->has('sizes') && is_array($request->sizes)) {
            $data['sizes'] = array_values(array_filter($request->sizes));
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

        $mainImagePath = null;
        if ($request->hasFile('main_image')) {
            $mainImagePath = \App\Services\ImageCompressionService::compressAndStore($request->file('main_image'), 'products', 'public');
            $data['main_image_path'] = $mainImagePath;
            $data['image_url'] = $mainImagePath;
        } elseif ($request->filled('existing_main_image')) {
            $mainImagePath = $request->input('existing_main_image');
            $data['main_image_path'] = $mainImagePath;
            $data['image_url'] = $mainImagePath;
        }

        // حفظ ملفات المعرض الجديدة على القرص أولاً لربط مراجع الألوان (__gallery_N__) بنفس المسار المحفوظ
        $uploadedGalleryPaths = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $idx => $imageFile) {
                if ($imageFile && $imageFile->isValid()) {
                    $uploadedGalleryPaths[$idx] = \App\Services\ImageCompressionService::compressAndStore($imageFile, 'products/gallery', 'public');
                }
            }
        }

        $existingGalleryPaths = [];
        if ($request->has('existing_gallery') && is_array($request->existing_gallery)) {
            foreach ($request->existing_gallery as $gpath) {
                if ($gpath) {
                    $existingGalleryPaths[] = $gpath;
                }
            }
        }

        $allProductImagePaths = array_values(array_filter(array_merge(
            $mainImagePath ? [$mainImagePath] : [],
            $existingGalleryPaths,
            array_values($uploadedGalleryPaths)
        )));

        if ($request->filled('colors_json')) {
            $data['colors'] = $this->processColorsInput(
                $request->colors_json,
                $mainImagePath,
                $uploadedGalleryPaths,
                $allProductImagePaths
            );
        } elseif ($request->has('colors')) {
            $data['colors'] = $this->processColorsInput(
                $request->colors,
                $mainImagePath,
                $uploadedGalleryPaths,
                $allProductImagePaths
            );
        }

        // tenant_id يُعبأ تلقائياً عبر BelongsToTenant trait
        $product = Product::create($data);

        // إذا كان التصنيف ليس له صورة، يتم وضع صورة المنتج الرئيسية له تلقائياً
        if ($product->category && empty($product->category->image_path) && !empty($product->main_image_path)) {
            $product->category->update([
                'image_path' => $product->main_image_path,
            ]);
        }

        // تسجيل حركة المخزون الابتدائية
        if ($product->stock > 0) {
            \App\Models\StockMovement::create([
                'product_id'  => $product->id,
                'quantity'    => $product->stock,
                'type'        => 'in',
                'description' => 'المخزون الابتدائي للمنتج عند الإنشاء',
            ]);
        }

        foreach ($existingGalleryPaths as $gpath) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $gpath,
            ]);
        }

        foreach ($uploadedGalleryPaths as $gpath) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $gpath,
            ]);
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
            'cost_price'          => $validated['cost_price'] ?? 0,
            'stock'               => $validated['stock'],
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'shipping_type'       => $validated['shipping_type'],
        ];

        // تحديث المقاسات والمتغيرات المخصصة (الألوان تُعالج بعد حفظ الصورة الرئيسية وصور المعرض الجديدة)
        if ($request->has('sizes') && is_array($request->sizes)) {
            $data['sizes'] = array_values(array_filter($request->sizes));
        } else {
            $data['sizes'] = null;
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

        $oldMainImagePath = $product->main_image_path ?: $product->image_url;
        $mainImagePath = $oldMainImagePath;

        if ($request->hasFile('main_image')) {
            $path = \App\Services\ImageCompressionService::compressAndStore($request->file('main_image'), 'products', 'public');
            $data['main_image_path'] = $path;
            $data['image_url'] = $path;
            $mainImagePath = $path;
        }

        // حفظ ملفات المعرض الجديدة أولاً لربط مراجع الألوان (__gallery_N__) بنفس المسار المحفوظ
        $uploadedGalleryPaths = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $idx => $imageFile) {
                if ($imageFile && $imageFile->isValid()) {
                    $uploadedGalleryPaths[$idx] = \App\Services\ImageCompressionService::compressAndStore($imageFile, 'products/gallery', 'public');
                }
            }
        }

        $existingGalleryPaths = $product->images()->pluck('image_path')->filter()->values()->all();
        $allProductImagePaths = array_values(array_filter(array_merge(
            $mainImagePath ? [$mainImagePath] : [],
            $existingGalleryPaths,
            array_values($uploadedGalleryPaths)
        )));

        if ($request->filled('colors_json')) {
            $data['colors'] = $this->processColorsInput(
                $request->colors_json,
                $mainImagePath,
                $uploadedGalleryPaths,
                $allProductImagePaths,
                $product->id
            );
        } elseif ($request->has('colors')) {
            $data['colors'] = $this->processColorsInput(
                $request->colors,
                $mainImagePath,
                $uploadedGalleryPaths,
                $allProductImagePaths,
                $product->id
            );
        } else {
            $data['colors'] = null;
        }

        // حذف الصورة الرئيسية القديمة فقط إذا تغيرت ولم تعد مربوطة بأي لون
        if ($request->hasFile('main_image') && $oldMainImagePath && $oldMainImagePath !== $mainImagePath && Storage::disk('public')->exists($oldMainImagePath)) {
            $oldNormalized = $this->normalizeStorageRelativePath($oldMainImagePath);
            $stillUsedByColor = false;
            foreach ($data['colors'] ?? [] as $c) {
                if (is_array($c) && !empty($c['image']) && $this->normalizeStorageRelativePath($c['image']) === $oldNormalized) {
                    $stillUsedByColor = true;
                    break;
                }
            }
            if (!$stillUsedByColor) {
                Storage::disk('public')->delete($oldMainImagePath);
            }
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

        foreach ($uploadedGalleryPaths as $gpath) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $gpath,
            ]);
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

    /**
     * معالجة مصفوفة الألوان وربط مراجع الصور (__main__ / __gallery_N__) ومنع تكرار الصور المرفوعة
     */
    protected function processColorsInput(
        $colors,
        ?string $mainImagePath = null,
        array $uploadedGalleryPaths = [],
        array $allProductImagePaths = [],
        ?int $excludeProductId = null
    ) {
        if (is_string($colors)) {
            $colors = json_decode($colors, true);
        }
        if (!is_array($colors)) {
            return null;
        }

        $productImageHashes = null;
        $filtered = [];
        foreach ($colors as $c) {
            if (is_string($c)) {
                $name = trim($c);
                if ($name !== '') {
                    $filtered[] = ['name' => $name, 'image' => null];
                }
            } elseif (is_array($c)) {
                $name = trim($c['name'] ?? '');
                $rawImage = !empty($c['image']) ? $c['image'] : null;

                // Support PHP multipart split where colors[][name] and colors[][image] were separated
                if ($name === '' && !empty($rawImage) && count($filtered) > 0) {
                    $lastIdx = count($filtered) - 1;
                    if (empty($filtered[$lastIdx]['image'])) {
                        $filtered[$lastIdx]['image'] = $this->sanitizeColorImagePath(
                            $rawImage,
                            $mainImagePath,
                            $uploadedGalleryPaths,
                            $allProductImagePaths,
                            $productImageHashes,
                            $excludeProductId
                        );
                        continue;
                    }
                }

                if ($name !== '') {
                    $filtered[] = [
                        'name'  => $name,
                        'image' => $this->sanitizeColorImagePath(
                            $rawImage,
                            $mainImagePath,
                            $uploadedGalleryPaths,
                            $allProductImagePaths,
                            $productImageHashes,
                            $excludeProductId
                        ),
                    ];
                }
            }
        }

        return count($filtered) > 0 ? $filtered : null;
    }

    /**
     * تحويل مسار صورة التخزين إلى المسار النسبي داخل القرص public
     */
    protected function normalizeStorageRelativePath(?string $path): ?string
    {
        if (!$path || !is_string($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://[^/]+/storage/(.+)$#i', $path, $m)) {
            return ltrim(explode('?', $m[1], 2)[0], '/');
        }

        if (str_starts_with($path, '/storage/')) {
            return ltrim(explode('?', substr($path, strlen('/storage/')), 2)[0], '/');
        }

        if (str_starts_with($path, 'storage/')) {
            return ltrim(explode('?', substr($path, strlen('storage/')), 2)[0], '/');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:') || str_starts_with($path, 'blob:')) {
            return null;
        }

        return ltrim(explode('?', $path, 2)[0], '/');
    }

    /**
     * تنسيق مسار صورة محفوظة ليُخزن بصيغة موحدة /storage/...
     */
    protected function formatSavedStoragePath(?string $path): ?string
    {
        if (!$path || !is_string($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        $rel = $this->normalizeStorageRelativePath($path);
        if ($rel !== null) {
            return '/storage/' . $rel;
        }

        return $path;
    }

    /**
     * تنظيف وحفظ مسار صورة اللون مع ربط المراجع وإزالة النسخ المتطابقة تلقائياً
     */
    protected function sanitizeColorImagePath(
        $image,
        ?string $mainImagePath = null,
        array $uploadedGalleryPaths = [],
        array $allProductImagePaths = [],
        ?array &$productImageHashes = null,
        ?int $excludeProductId = null
    ) {
        if (!$image || !is_string($image)) {
            return null;
        }

        $image = trim($image);
        if ($image === '' || str_starts_with($image, 'blob:')) {
            return null;
        }

        // مرجع مباشر للصورة الرئيسية المرفوعة في نفس الطلب
        if ($image === '__main__') {
            return $this->formatSavedStoragePath($mainImagePath);
        }

        // مرجع مباشر لصورة فرعية مرفوعة في نفس الطلب
        if (preg_match('/^__gallery_(\d+)__$/', $image, $m)) {
            $idx = (int) $m[1];
            if (isset($uploadedGalleryPaths[$idx])) {
                return $this->formatSavedStoragePath($uploadedGalleryPaths[$idx]);
            }
            return null;
        }

        // إذا كانت الصورة بتنسيق base64 data URI نقوم بفكها وتخزينها كملف حقيقي
        if (str_starts_with($image, 'data:image/')) {
            try {
                preg_match('/^data:image\/(\w+);base64,/', $image, $type);
                $b64Data = substr($image, strpos($image, ',') + 1);
                $decoded = base64_decode($b64Data);
                if ($decoded !== false) {
                    $ext = strtolower($type[1] ?? 'jpg');
                    if ($ext === 'jpeg') $ext = 'jpg';
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) $ext = 'jpg';
                    $filename = 'products/colors/color_' . uniqid('', true) . '.' . $ext;
                    Storage::disk('public')->put($filename, $decoded);
                    $image = '/storage/' . $filename;
                }
            } catch (\Throwable $e) {
                \Log::error("Failed to decode and save color base64 image: " . $e->getMessage());
            }
        } elseif (preg_match('#^https?://[^/]+(/storage/.+)$#i', $image, $m)) {
            // توحيد الروابط الكاملة التابعة لنفس السيرفر إلى مسار نسبي موحد /storage/...
            $candidateRel = ltrim(explode('?', substr($m[1], strlen('/storage/')), 2)[0], '/');
            if ($candidateRel !== '' && Storage::disk('public')->exists($candidateRel)) {
                $image = '/storage/' . $candidateRel;
            }
        } elseif (!str_starts_with($image, 'http://') && !str_starts_with($image, 'https://') && !str_starts_with($image, '/')) {
            $image = '/storage/' . ltrim($image, '/');
        }

        // إذا رُفعت صورة في مجلد الألوان مطابقة تماماً بالبايت لصورة رئيسية أو صورة معرض لنفس المنتج، نوحّد المسار ونحذف النسخة المكررة
        $colorRel = $this->normalizeStorageRelativePath($image);
        if ($colorRel && str_starts_with($colorRel, 'products/colors/') && !empty($allProductImagePaths) && Storage::disk('public')->exists($colorRel)) {
            if ($productImageHashes === null) {
                $productImageHashes = [];
                foreach ($allProductImagePaths as $pPath) {
                    $pRel = $this->normalizeStorageRelativePath($pPath);
                    if ($pRel && !str_starts_with($pRel, 'products/colors/') && Storage::disk('public')->exists($pRel)) {
                        $full = Storage::disk('public')->path($pRel);
                        if (is_file($full)) {
                            $hash = @md5_file($full);
                            if ($hash && !isset($productImageHashes[$hash])) {
                                $productImageHashes[$hash] = '/storage/' . $pRel;
                            }
                        }
                    }
                }
            }

            if (!empty($productImageHashes)) {
                $colorFull = Storage::disk('public')->path($colorRel);
                $colorHash = is_file($colorFull) ? @md5_file($colorFull) : false;
                if ($colorHash && isset($productImageHashes[$colorHash])) {
                    $matchedUrl = $productImageHashes[$colorHash];
                    // حذف الملف المكرر من products/colors فقط إذا لم يكن مستخدماً في أي منتج آخر
                    $basename = basename($colorRel);
                    $usedElsewhere = Product::withoutGlobalScopes()
                        ->when($excludeProductId, fn($q) => $q->where('id', '!=', $excludeProductId))
                        ->where('colors', 'like', '%' . $basename . '%')
                        ->exists();
                    if (!$usedElsewhere) {
                        Storage::disk('public')->delete($colorRel);
                    }
                    return $matchedUrl;
                }
            }
        }

        return $image;
    }

    /**
     * رفع صورة خاصة بلون معين للمنتج
     */
    public function uploadColorImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:20480',
        ]);

        $path = \App\Services\ImageCompressionService::compressAndStore($request->file('image'), 'products/colors', 'public');

        return response()->json([
            'success'    => true,
            'image_path' => $path,
            'image_url'  => asset('storage/' . $path),
        ]);
    }
}
