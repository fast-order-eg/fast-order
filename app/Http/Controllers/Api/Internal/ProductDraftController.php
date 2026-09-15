<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductDraftController extends Controller
{
    /**
     * Look up a store/tenant by slug or phone number.
     */
    public function lookupStore(Request $request): JsonResponse
    {
        $query = trim((string) ($request->input('store') ?? $request->input('query') ?? ''));

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'اسم المتجر أو رقم الهاتف مطلوب.',
            ], 422);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $query);

        $tenant = Tenant::where('slug', $query)
            ->orWhere('id', is_numeric($query) ? (int)$query : 0)
            ->first();

        if (!$tenant && !empty($cleanPhone)) {
            $tenant = Tenant::where(function ($q) use ($cleanPhone) {
                $q->where('phone', 'like', "%{$cleanPhone}%")
                  ->orWhere('phone', $cleanPhone);
            })->first();

            if (!$tenant) {
                $owner = User::where('phone', 'like', "%{$cleanPhone}%")
                    ->whereNotNull('tenant_id')
                    ->first();
                if ($owner) {
                    $tenant = $owner->tenant;
                }
            }
        }

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => "لم يتم العثور على متجر بالاسم أو الرقم: {$query}",
            ], 404);
        }

        $categories = Category::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar', 'main_category']);

        $productsCount = Product::where('tenant_id', $tenant->id)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name ?: $tenant->slug,
                'slug' => $tenant->slug,
                'domain' => $tenant->custom_domain,
                'phone' => $tenant->phone,
                'store_url' => $tenant->getStoreUrl(),
                'products_count' => $productsCount,
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * Create a new product draft (is_active = false) with secret preview token.
     */
    public function createDraft(Request $request): JsonResponse
    {
        $storeQuery = $request->input('store_slug') ?? $request->input('tenant_id');
        $tenant = null;

        if (is_numeric($storeQuery)) {
            $tenant = Tenant::find((int) $storeQuery);
        }
        if (!$tenant && !empty($storeQuery)) {
            $tenant = Tenant::where('slug', $storeQuery)->first();
        }

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'المتجر المحدد غير موجود.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_after' => 'required|numeric|min:0',
            'price_before' => 'nullable|numeric|min:0',
            'sizes' => 'nullable',
            'colors' => 'nullable',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|integer',
            'category_name' => 'nullable|string',
            'images_base64' => 'nullable|array',
            'images_base64.*' => 'nullable|string',
        ]);

        // Resolve Category
        $categoryId = $validated['category_id'] ?? null;
        if (!$categoryId && !empty($validated['category_name'])) {
            $catName = trim($validated['category_name']);
            $existingCat = Category::where('tenant_id', $tenant->id)
                ->where(function ($q) use ($catName) {
                    $q->where('name', $catName)
                      ->orWhere('name_ar', $catName);
                })->first();

            if ($existingCat) {
                $categoryId = $existingCat->id;
            } else {
                $newCat = Category::create([
                    'tenant_id' => $tenant->id,
                    'name' => $catName,
                    'name_ar' => $catName,
                ]);
                $categoryId = $newCat->id;
            }
        }

        // Format sizes & colors
        $sizes = $this->normalizeArray($request->input('sizes'));
        $colors = $this->normalizeArray($request->input('colors'));

        // Generate variant stock combinations
        $variantsStock = [];
        $hasVariants = (count($sizes) > 0 || count($colors) > 0);
        if ($hasVariants) {
            $szList = count($sizes) > 0 ? $sizes : [null];
            $clList = count($colors) > 0 ? $colors : [null];
            foreach ($szList as $s) {
                foreach ($clList as $c) {
                    $variantsStock[] = [
                        'size' => $s,
                        'color' => $c,
                        'qty' => 100,
                    ];
                }
            }
        }

        $stock = isset($validated['stock']) && $validated['stock'] !== '' 
            ? (int) $validated['stock'] 
            : ($hasVariants ? (count($variantsStock) * 100) : 100);

        $previewToken = Str::random(32);

        $productData = [
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $categoryId,
            'price' => (int) $validated['price_after'],
            'price_after' => (int) $validated['price_after'],
            'price_before' => (int) ($validated['price_before'] ?? 0),
            'stock' => $stock,
            'low_stock_threshold' => 5,
            'shipping_type' => 'free',
            'sizes' => count($sizes) > 0 ? $sizes : null,
            'colors' => count($colors) > 0 ? $colors : null,
            'variants_stock' => count($variantsStock) > 0 ? $variantsStock : null,
            'is_active' => false,
            'preview_token' => $previewToken,
        ];

        // Process images (uploaded files or base64 strings)
        $savedImagePaths = [];

        // 1. Files
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file && $file->isValid()) {
                    $path = \App\Services\ImageCompressionService::compressAndStore($file, 'products', 'public');
                    $savedImagePaths[] = $path;
                }
            }
        }

        // 2. Base64
        if (!empty($validated['images_base64']) && is_array($validated['images_base64'])) {
            foreach ($validated['images_base64'] as $b64) {
                $saved = $this->saveBase64Image($b64, 'products');
                if ($saved) {
                    $savedImagePaths[] = $saved;
                }
            }
        }

        if (count($savedImagePaths) > 0) {
            $productData['main_image_path'] = $savedImagePaths[0];
        }

        $product = Product::create($productData);

        // Save additional gallery images
        if (count($savedImagePaths) > 1) {
            for ($i = 1; $i < count($savedImagePaths); $i++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $savedImagePaths[$i],
                ]);
            }
        }

        $previewUrl = $tenant->getStoreUrl() . "/shop/product.html?id={$product->id}&preview_token={$previewToken}";

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء مسودة المنتج بنجاح (مخفية عن الزوار).',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price_after' => $product->price_after,
                'price_before' => $product->price_before,
                'sizes' => $product->sizes ?: [],
                'colors' => $product->colors ?: [],
                'stock' => $product->stock,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name_ar ?: $product->category?->name,
                'images_count' => count($savedImagePaths),
                'preview_token' => $previewToken,
                'preview_url' => $previewUrl,
                'is_active' => false,
            ],
        ], 201);
    }

    /**
     * Update an existing draft before publishing.
     */
    public function updateDraft(Request $request, $id): JsonResponse
    {
        $product = Product::with(['images', 'category'])->findOrFail($id);

        if ($product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المنتج تم نشره بالفعل ولا يعتبر مسودة.',
            ], 400);
        }

        $tenant = Tenant::find($product->tenant_id);

        $fieldsToUpdate = [];

        if ($request->filled('name')) {
            $fieldsToUpdate['name'] = trim((string) $request->input('name'));
        }
        if ($request->filled('description')) {
            $fieldsToUpdate['description'] = trim((string) $request->input('description'));
        }
        if ($request->filled('price_after')) {
            $fieldsToUpdate['price_after'] = (int) $request->input('price_after');
            $fieldsToUpdate['price'] = (int) $request->input('price_after');
        }
        if ($request->has('price_before')) {
            $fieldsToUpdate['price_before'] = (int) $request->input('price_before');
        }
        if ($request->filled('stock')) {
            $fieldsToUpdate['stock'] = (int) $request->input('stock');
        }

        // Category update
        if ($request->filled('category_name') && $tenant) {
            $catName = trim((string) $request->input('category_name'));
            $cat = Category::where('tenant_id', $tenant->id)
                ->where(function ($q) use ($catName) {
                    $q->where('name', $catName)->orWhere('name_ar', $catName);
                })->first();

            if (!$cat) {
                $cat = Category::create([
                    'tenant_id' => $tenant->id,
                    'name' => $catName,
                    'name_ar' => $catName,
                ]);
            }
            $fieldsToUpdate['category_id'] = $cat->id;
        } elseif ($request->filled('category_id')) {
            $fieldsToUpdate['category_id'] = (int) $request->input('category_id');
        }

        // Sizes & Colors update
        $sizesUpdated = $request->has('sizes');
        $colorsUpdated = $request->has('colors');

        if ($sizesUpdated) {
            $sizes = $this->normalizeArray($request->input('sizes'));
            $fieldsToUpdate['sizes'] = count($sizes) > 0 ? $sizes : null;
        } else {
            $sizes = is_array($product->sizes) ? $product->sizes : [];
        }

        if ($colorsUpdated) {
            $colors = $this->normalizeArray($request->input('colors'));
            $fieldsToUpdate['colors'] = count($colors) > 0 ? $colors : null;
        } else {
            $colors = is_array($product->colors) ? $product->colors : [];
        }

        if ($sizesUpdated || $colorsUpdated) {
            $variantsStock = [];
            $szList = count($sizes) > 0 ? $sizes : [null];
            $clList = count($colors) > 0 ? $colors : [null];
            foreach ($szList as $s) {
                foreach ($clList as $c) {
                    $variantsStock[] = [
                        'size' => $s,
                        'color' => $c,
                        'qty' => 100,
                    ];
                }
            }
            $fieldsToUpdate['variants_stock'] = count($variantsStock) > 0 ? $variantsStock : null;
            if (!isset($fieldsToUpdate['stock'])) {
                $fieldsToUpdate['stock'] = count($variantsStock) > 0 ? (count($variantsStock) * 100) : 100;
            }
        }

        // Additional images upload
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file && $file->isValid()) {
                    $path = \App\Services\ImageCompressionService::compressAndStore($file, 'products', 'public');
                    if (empty($product->main_image_path)) {
                        $product->main_image_path = $path;
                    } else {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $path,
                        ]);
                    }
                }
            }
        }

        $product->update($fieldsToUpdate);
        $product->refresh();

        $previewUrl = $tenant 
            ? ($tenant->getStoreUrl() . "/shop/product.html?id={$product->id}&preview_token={$product->preview_token}")
            : null;

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات مسودة المنتج بنجاح.',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price_after' => $product->price_after,
                'price_before' => $product->price_before,
                'sizes' => $product->sizes ?: [],
                'colors' => $product->colors ?: [],
                'stock' => $product->stock,
                'category_name' => $product->category?->name_ar ?: $product->category?->name,
                'preview_url' => $previewUrl,
                'is_active' => false,
            ],
        ]);
    }

    /**
     * Publish draft product to live store (is_active = true).
     */
    public function publishDraft(Request $request, $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        if (empty($product->name)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن نشر المنتج بدون اسم.',
            ], 422);
        }

        if (($product->price_after ?? 0) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن نشر المنتج بدون تحديد سعر صحيح.',
            ], 422);
        }

        $product->update([
            'is_active' => true,
            'preview_token' => null, // Clear token once live
        ]);

        // Record initial stock movement
        if ($product->stock > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $product->stock,
                'type' => 'in',
                'description' => 'المخزون الابتدائي للمنتج عند النشر عبر الواتساب',
            ]);
        }

        $tenant = Tenant::find($product->tenant_id);
        $liveUrl = $tenant ? ($tenant->getStoreUrl() . "/shop/product.html?id={$product->id}") : null;

        return response()->json([
            'success' => true,
            'message' => 'تم نشر المنتج لايف على المتجر بنجاح!',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price_after,
                'live_url' => $liveUrl,
                'is_active' => true,
            ],
        ]);
    }

    /**
     * Discard / delete a draft product.
     */
    public function discardDraft(Request $request, $id): JsonResponse
    {
        $product = Product::with('images')->findOrFail($id);

        // Delete images
        if ($product->main_image_path && Storage::disk('public')->exists($product->main_image_path)) {
            Storage::disk('public')->delete($product->main_image_path);
        }
        foreach ($product->images as $img) {
            if ($img->image_path && Storage::disk('public')->exists($img->image_path)) {
                Storage::disk('public')->delete($img->image_path);
            }
            $img->delete();
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المسودة بنجاح.',
        ]);
    }

    /**
     * Helper to decode/normalize array inputs (sizes, colors).
     */
    private function normalizeArray($input): array
    {
        if (empty($input)) return [];
        if (is_array($input)) return array_values(array_filter(array_map('trim', $input)));
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('trim', $decoded)));
            }
            $parts = preg_split('/[,،\-\/\s]+/', $input);
            return array_values(array_filter(array_map('trim', $parts)));
        }
        return [];
    }

    /**
     * Helper to save base64 image data to public storage.
     */
    private function saveBase64Image(string $base64Data, string $folder): ?string
    {
        try {
            $cleanBase64 = $base64Data;
            $extension = 'jpg';

            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $cleanBase64 = substr($base64Data, strpos($base64Data, ',') + 1);
                $ext = strtolower($type[1]);
                $extension = ($ext === 'jpeg') ? 'jpg' : $ext;
            }

            $decoded = base64_decode($cleanBase64);
            if (!$decoded) return null;

            $filename = $folder . '/' . Str::random(40) . '.' . $extension;
            Storage::disk('public')->put($filename, $decoded);
            return $filename;
        } catch (\Throwable $e) {
            \Log::error('Error saving base64 image: ' . $e->getMessage());
            return null;
        }
    }
}
