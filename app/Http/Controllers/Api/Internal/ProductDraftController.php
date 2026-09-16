<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
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
     * Look up store/tenant details by slug or phone number (Route: store.lookup).
     */
    public function lookupStore(Request $request): JsonResponse
    {
        return $this->storeLookup($request);
    }

    /**
     * Look up store/tenant details by slug, url, email, or phone number.
     */
    public function storeLookup(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('store', ''));

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى تقديم كود المتجر، رابط المتجر/المنتج، رقم الهاتف، أو البريد الإلكتروني للبحث.',
            ], 400);
        }

        $tenant = $this->resolveTenant($query);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => "لم يتم العثور على متجر مطابق للبيانات المدخلة: {$query}",
            ], 404);
        }

        $categories = Category::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar', 'main_category']);

        $productsCount = Product::where('tenant_id', $tenant->id)->count();

        // Owner details if available
        $owner = $tenant->owner_id ? User::find($tenant->owner_id) : User::where('tenant_id', $tenant->id)->first();

        $storeName = Setting::where('tenant_id', $tenant->id)->where('key', 'store_name')->value('value')
            ?: $tenant->name
            ?: $tenant->slug;

        $storePhone = $tenant->phone 
            ?: ($owner?->phone ?? null) 
            ?: Setting::where('tenant_id', $tenant->id)->where('key', 'phone')->value('value')
            ?: Setting::where('tenant_id', $tenant->id)->where('key', 'whatsapp')->value('value');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tenant->id,
                'name' => $storeName,
                'slug' => $tenant->slug,
                'domain' => $tenant->custom_domain,
                'email' => $tenant->email ?: ($owner?->email ?? null),
                'phone' => $storePhone,
                'owner_name' => $owner?->name ?? null,
                'store_url' => $tenant->getStoreUrl(),
                'products_count' => $productsCount,
                'main_categories' => Category::getMainCategories($tenant->id),
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * Resolve tenant by URL, subdomain, custom domain, product id, email, phone, slug, or ID.
     */
    private function resolveTenant(string $query): ?Tenant
    {
        $query = trim($query);
        if (empty($query)) {
            return null;
        }

        // =========================================================================
        // 1. URL / Domain Detection (e.g. https://emamrady63122.fast-order-eg.tech/shop/product.html?id=1346)
        // =========================================================================
        if (str_contains($query, 'http://') || str_contains($query, 'https://') || str_contains($query, '/') || str_contains($query, '.')) {
            $rawUrl = $query;
            if (!str_starts_with($rawUrl, 'http://') && !str_starts_with($rawUrl, 'https://')) {
                $rawUrl = 'https://' . ltrim($rawUrl, '/');
            }

            $parsed = parse_url($rawUrl);
            $host = strtolower($parsed['host'] ?? '');
            $path = $parsed['path'] ?? '';
            $queryString = $parsed['query'] ?? '';

            // 1.a) Check if query string contains product ID (e.g. ?id=1346)
            if (!empty($queryString)) {
                parse_str($queryString, $queryParams);
                if (!empty($queryParams['id']) && is_numeric($queryParams['id'])) {
                    $product = Product::find((int) $queryParams['id']);
                    if ($product && $product->tenant_id) {
                        $tenant = Tenant::find($product->tenant_id);
                        if ($tenant) {
                            return $tenant;
                        }
                    }
                }
            }

            // 1.b) Check if path contains product ID or slug (e.g. /products/1346 or /p/some-slug)
            if (!empty($path)) {
                if (preg_match('~/(?:product|products|p)/([^/?#]+)~i', $path, $pathMatches)) {
                    $param = $pathMatches[1];
                    if (is_numeric($param)) {
                        $product = Product::find((int) $param);
                    } else {
                        $product = Product::where('slug', $param)->first();
                    }
                    if ($product && $product->tenant_id) {
                        $tenant = Tenant::find($product->tenant_id);
                        if ($tenant) {
                            return $tenant;
                        }
                    }
                }

                // Path might contain store slug (e.g. /store/{slug} or /shop/{slug})
                if (preg_match('~/(?:store|shop|m)/([^/?#.]+)(?:\.html)?~i', $path, $storeMatches)) {
                    $candidateSlug = $storeMatches[1];
                    if (!in_array(strtolower($candidateSlug), ['product', 'products', 'cart', 'checkout', 'index'])) {
                        $tenant = Tenant::where('slug', $candidateSlug)->first();
                        if ($tenant) {
                            return $tenant;
                        }
                    }
                }
            }

            // 1.c) Check if host matches custom domain on Tenant
            if (!empty($host)) {
                $tenant = Tenant::where('custom_domain', $host)
                    ->orWhere('custom_domain', 'like', "%{$host}%")
                    ->first();
                if ($tenant) {
                    return $tenant;
                }

                // 1.d) Extract subdomain from host (e.g. emamrady63122.fast-order-eg.tech)
                $cleanHost = preg_replace('/:\d+$/', '', $host);
                $parts = explode('.', $cleanHost);

                if (count($parts) >= 3) {
                    $subdomain = $parts[0];
                    $ignored = ['www', 'app', 'api', 'admin', 'mail', 'crm', 'webmail', 'cpanel'];
                    if (!in_array($subdomain, $ignored)) {
                        $tenant = Tenant::where('slug', $subdomain)->first();
                        if ($tenant) {
                            return $tenant;
                        }
                    }
                }
            }
        }

        // =========================================================================
        // 2. Email Detection (e.g. merchant@example.com)
        // =========================================================================
        if (str_contains($query, '@')) {
            $cleanEmail = strtolower($query);
            $tenant = Tenant::where('email', $cleanEmail)->first();
            if ($tenant) {
                return $tenant;
            }

            $user = User::where('email', $cleanEmail)->first();
            if ($user) {
                if ($user->tenant_id) {
                    $tenant = Tenant::find($user->tenant_id);
                    if ($tenant) {
                        return $tenant;
                    }
                }
                $tenant = Tenant::where('owner_id', $user->id)->first();
                if ($tenant) {
                    return $tenant;
                }
            }
        }

        // =========================================================================
        // 3. Direct Slug or Tenant ID Lookup
        // =========================================================================
        $tenant = Tenant::where('slug', $query)
            ->orWhere('slug', strtolower($query))
            ->first();
        if ($tenant) {
            return $tenant;
        }

        if (is_numeric($query)) {
            $tenant = Tenant::find((int) $query);
            if ($tenant) {
                return $tenant;
            }
        }

        // =========================================================================
        // 4. Phone Number Lookup (with Egyptian & International Normalization)
        // =========================================================================
        $digits = preg_replace('/[^0-9]/', '', $query);
        if (!empty($digits) && strlen($digits) >= 7) {
            $phoneVariants = [$digits];

            // If 2010... (12 digits) -> 010... (11 digits)
            if (str_starts_with($digits, '20') && strlen($digits) === 12) {
                $phoneVariants[] = '0' . substr($digits, 2);
                $phoneVariants[] = substr($digits, 2);
            }
            // If 010... (11 digits) -> 2010... (12 digits) and 10... (10 digits)
            elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
                $phoneVariants[] = '2' . $digits;
                $phoneVariants[] = substr($digits, 1);
            }
            // If 10... (10 digits) -> 010... (11 digits) and 2010... (12 digits)
            elseif (strlen($digits) === 10 && in_array(substr($digits, 0, 2), ['10', '11', '12', '15'])) {
                $phoneVariants[] = '0' . $digits;
                $phoneVariants[] = '20' . $digits;
            }

            $phoneVariants = array_unique($phoneVariants);

            // Search Tenant phone (both direct LIKE and digits-only REGEXP_REPLACE)
            $tenant = Tenant::where(function ($q) use ($phoneVariants) {
                foreach ($phoneVariants as $pv) {
                    $q->orWhere('phone', $pv)
                      ->orWhere('phone', 'like', "%{$pv}%")
                      ->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ["%{$pv}%"]);
                }
            })->first();

            if ($tenant) {
                return $tenant;
            }

            // Search User phone (both direct LIKE and digits-only REGEXP_REPLACE)
            $user = User::where(function ($q) use ($phoneVariants) {
                foreach ($phoneVariants as $pv) {
                    $q->orWhere('phone', $pv)
                      ->orWhere('phone', 'like', "%{$pv}%")
                      ->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ["%{$pv}%"]);
                }
            })->first();

            if ($user) {
                if ($user->tenant_id) {
                    $tenant = Tenant::find($user->tenant_id);
                    if ($tenant) {
                        return $tenant;
                    }
                }
                $tenant = Tenant::where('owner_id', $user->id)->first();
                if ($tenant) {
                    return $tenant;
                }
            }
            // Search Settings table (phone, whatsapp, mobile)
            $setting = Setting::whereIn('key', ['phone', 'whatsapp', 'mobile'])
                ->where(function ($q) use ($phoneVariants) {
                    foreach ($phoneVariants as $pv) {
                        $q->orWhere('value', $pv)
                          ->orWhere('value', 'like', "%{$pv}%")
                          ->orWhereRaw("REGEXP_REPLACE(value, '[^0-9]', '') LIKE ?", ["%{$pv}%"]);
                    }
                })->first();

            if ($setting && $setting->tenant_id) {
                $tenant = Tenant::find($setting->tenant_id);
                if ($tenant) {
                    return $tenant;
                }
            }
        }

        // =========================================================================
        // 5. Store Name Lookup (Fallback - checks Tenant name and Settings store_name)
        // =========================================================================
        $tenant = Tenant::where('name', $query)
            ->orWhere('name', 'like', "%{$query}%")
            ->first();

        if ($tenant) {
            return $tenant;
        }

        $storeNameSetting = Setting::where('key', 'store_name')
            ->where(function ($q) use ($query) {
                $q->where('value', $query)
                  ->orWhere('value', 'like', "%{$query}%");
            })->first();

        if ($storeNameSetting && $storeNameSetting->tenant_id) {
            $tenant = Tenant::find($storeNameSetting->tenant_id);
            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Create a new product draft (is_active = false) with secret preview token.
     */
    public function createDraft(Request $request): JsonResponse
    {
        $storeQuery = $request->input('store_slug') ?? $request->input('tenant_id');
        $tenant = !empty($storeQuery) ? $this->resolveTenant((string) $storeQuery) : null;

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
            'custom_variants' => 'nullable',
            'price_tiers' => 'nullable',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|integer',
            'category_name' => 'nullable|string',
            'main_category' => 'nullable|string',
            'images_base64' => 'nullable|array',
            'images_base64.*' => 'nullable|string',
        ]);

        $priceAfter = (float) $validated['price_after'];
        $priceBefore = isset($validated['price_before']) && $validated['price_before'] !== '' ? (float) $validated['price_before'] : 0.0;

        // Resolve Category & Main Category
        $categoryId = $validated['category_id'] ?? null;
        $mainCat = trim((string) ($request->input('main_category') ?? ''));
        $catName = trim((string) ($validated['category_name'] ?? ''));

        // If main_category is provided, ensure it exists in tenant main categories setting
        if (!empty($mainCat) && $tenant) {
            $allMain = Category::getMainCategories($tenant->id);
            if (!in_array($mainCat, $allMain)) {
                $allMain[] = $mainCat;
                Category::saveMainCategories($allMain, $tenant->id);
            }
        }

        if (!$categoryId && !empty($catName)) {
            $existingCat = Category::where('tenant_id', $tenant->id)
                ->where(function ($q) use ($catName) {
                    $q->where('name', $catName)
                      ->orWhere('name_ar', $catName);
                })->first();

            if ($existingCat) {
                $categoryId = $existingCat->id;
                if (!empty($mainCat) && empty($existingCat->main_category)) {
                    $existingCat->update(['main_category' => $mainCat]);
                }
            } else {
                $newCat = Category::create([
                    'tenant_id' => $tenant->id,
                    'name' => $catName,
                    'name_ar' => $catName,
                    'main_category' => $mainCat ?: $catName,
                ]);
                $categoryId = $newCat->id;
            }
        }

        if (!$categoryId) {
            $firstCat = Category::where('tenant_id', $tenant->id)->first();
            if ($firstCat) {
                $categoryId = $firstCat->id;
                if (!empty($mainCat) && empty($firstCat->main_category)) {
                    $firstCat->update(['main_category' => $mainCat]);
                }
            } else {
                $defaultCat = Category::create([
                    'tenant_id' => $tenant->id,
                    'name' => $mainCat ?: 'عام',
                    'name_ar' => $mainCat ?: 'عام',
                    'main_category' => $mainCat ?: 'عام',
                ]);
                $categoryId = $defaultCat->id;
            }
        }

        // Format sizes & colors
        $sizes = $this->normalizeArray($request->input('sizes'));
        $colors = $this->normalizeArray($request->input('colors'));

        // Format custom variants (e.g. [{"name": "الحجم", "values": ["30 جرام"]}])
        $customVariants = null;
        if ($request->has('custom_variants')) {
            $rawCv = $request->input('custom_variants');
            $cvArr = is_string($rawCv) ? json_decode($rawCv, true) : $rawCv;
            if (is_array($cvArr) && count($cvArr) > 0) {
                $filteredCv = [];
                foreach ($cvArr as $cv) {
                    $cName = trim((string) ($cv['name'] ?? ''));
                    $cValues = is_array($cv['values'] ?? null) ? array_values(array_filter($cv['values'])) : [];
                    if (!empty($cName) && count($cValues) > 0) {
                        $filteredCv[] = [
                            'name' => $cName,
                            'values' => $cValues,
                        ];
                    }
                }
                $customVariants = count($filteredCv) > 0 ? $filteredCv : null;
            }
        }

        // Format price tiers (e.g. [{"min_qty": 3, "price": "50"}])
        $priceTiers = null;
        if ($request->has('price_tiers')) {
            $rawTiers = $request->input('price_tiers');
            $tiersArr = is_string($rawTiers) ? json_decode($rawTiers, true) : $rawTiers;
            if (is_array($tiersArr) && count($tiersArr) > 0) {
                $filteredTiers = [];
                foreach ($tiersArr as $tier) {
                    $minQty = (int) ($tier['min_qty'] ?? $tier['quantity'] ?? $tier['qty'] ?? 0);
                    $tierPrice = (float) ($tier['price'] ?? 0);
                    if ($minQty >= 2 && $tierPrice > 0) {
                        $filteredTiers[] = [
                            'min_qty' => $minQty,
                            'price' => (string) $tierPrice,
                        ];
                    }
                }
                $priceTiers = count($filteredTiers) > 0 ? $filteredTiers : null;
            }
        }

        // 1. Process custom variant stock combinations & custom pricing
        $variantsStock = [];
        if ($request->filled('variants_stock')) {
            $rawVs = $request->input('variants_stock');
            $vsArr = is_string($rawVs) ? json_decode($rawVs, true) : $rawVs;
            if (is_array($vsArr) && count($vsArr) > 0) {
                foreach ($vsArr as $item) {
                    $variantsStock[] = [
                        'size' => $item['size'] ?? null,
                        'color' => $item['color'] ?? null,
                        'options' => is_array($item['options'] ?? null) ? $item['options'] : (is_object($item['options'] ?? null) ? (array) $item['options'] : []),
                        'price' => isset($item['price']) && $item['price'] !== '' ? (float) $item['price'] : $priceAfter,
                        'qty' => isset($item['qty']) && $item['qty'] !== '' ? (int) $item['qty'] : 100,
                    ];
                }
            }
        }

        // 2. If not explicitly provided, generate combinations from sizes, colors, and custom variants
        if (empty($variantsStock)) {
            $hasVariants = (count($sizes) > 0 || count($colors) > 0);
            if ($hasVariants) {
                $szList = count($sizes) > 0 ? $sizes : [null];
                $clList = count($colors) > 0 ? $colors : [null];
                foreach ($szList as $s) {
                    foreach ($clList as $c) {
                        $variantsStock[] = [
                            'size' => $s,
                            'color' => $c,
                            'options' => [],
                            'price' => $priceAfter,
                            'qty' => 100,
                        ];
                    }
                }
            } elseif (!empty($customVariants)) {
                foreach ($customVariants as $cv) {
                    foreach ($cv['values'] as $val) {
                        $variantsStock[] = [
                            'size' => null,
                            'color' => null,
                            'options' => [$cv['name'] => $val],
                            'price' => $priceAfter,
                            'qty' => 100,
                        ];
                    }
                }
            }
        }

        $stock = isset($validated['stock']) && $validated['stock'] !== '' 
            ? (int) $validated['stock'] 
            : (count($variantsStock) > 0 ? (count($variantsStock) * 100) : 100);

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
            'custom_variants' => $customVariants,
            'price_tiers' => $priceTiers,
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

        // Auto-assign category image if category has no image
        if ($product->category && empty($product->category->image_path) && !empty($product->main_image_path)) {
            $product->category->update([
                'image_path' => $product->main_image_path,
            ]);
        }

        // Save additional gallery images
        if (count($savedImagePaths) > 1) {
            for ($i = 1; $i < count($savedImagePaths); $i++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $savedImagePaths[$i],
                ]);
            }
        }

        $product->load(['category', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء مسودة المنتج بنجاح (مخفية عن الزوار).',
            'data' => $this->formatProductResponse($product, $tenant, $previewToken, count($savedImagePaths)),
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

        // Main Category & Category update
        $mainCat = trim((string) ($request->input('main_category') ?? ''));
        if (!empty($mainCat) && $tenant) {
            $allMain = Category::getMainCategories($tenant->id);
            if (!in_array($mainCat, $allMain)) {
                $allMain[] = $mainCat;
                Category::saveMainCategories($allMain, $tenant->id);
            }
        }

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
                    'main_category' => $mainCat ?: $catName,
                ]);
            } else {
                if (!empty($mainCat) && empty($cat->main_category)) {
                    $cat->update(['main_category' => $mainCat]);
                }
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

        // Custom Variants update
        if ($request->has('custom_variants')) {
            $rawCv = $request->input('custom_variants');
            $cvArr = is_string($rawCv) ? json_decode($rawCv, true) : $rawCv;
            if (is_array($cvArr)) {
                $filteredCv = [];
                foreach ($cvArr as $cv) {
                    $cName = trim((string) ($cv['name'] ?? ''));
                    $cValues = is_array($cv['values'] ?? null) ? array_values(array_filter($cv['values'])) : [];
                    if (!empty($cName) && count($cValues) > 0) {
                        $filteredCv[] = [
                            'name' => $cName,
                            'values' => $cValues,
                        ];
                    }
                }
                $fieldsToUpdate['custom_variants'] = count($filteredCv) > 0 ? $filteredCv : null;
            }
        }

        // Price Tiers update
        if ($request->has('price_tiers')) {
            $rawTiers = $request->input('price_tiers');
            $tiersArr = is_string($rawTiers) ? json_decode($rawTiers, true) : $rawTiers;
            if (is_array($tiersArr)) {
                $filteredTiers = [];
                foreach ($tiersArr as $tier) {
                    $minQty = (int) ($tier['min_qty'] ?? $tier['quantity'] ?? $tier['qty'] ?? 0);
                    $tierPrice = (float) ($tier['price'] ?? 0);
                    if ($minQty >= 2 && $tierPrice > 0) {
                        $filteredTiers[] = [
                            'min_qty' => $minQty,
                            'price' => (string) $tierPrice,
                        ];
                    }
                }
                $fieldsToUpdate['price_tiers'] = count($filteredTiers) > 0 ? $filteredTiers : null;
            }
        }

        if ($request->has('variants_stock')) {
            $rawVs = $request->input('variants_stock');
            $vsArr = is_string($rawVs) ? json_decode($rawVs, true) : $rawVs;
            if (is_array($vsArr)) {
                $filteredVs = [];
                $basePrice = $fieldsToUpdate['price_after'] ?? $product->price_after;
                foreach ($vsArr as $item) {
                    $filteredVs[] = [
                        'size' => $item['size'] ?? null,
                        'color' => $item['color'] ?? null,
                        'options' => is_array($item['options'] ?? null) ? $item['options'] : (is_object($item['options'] ?? null) ? (array) $item['options'] : []),
                        'price' => isset($item['price']) && $item['price'] !== '' ? (float) $item['price'] : $basePrice,
                        'qty' => isset($item['qty']) && $item['qty'] !== '' ? (int) $item['qty'] : 100,
                    ];
                }
                $fieldsToUpdate['variants_stock'] = count($filteredVs) > 0 ? $filteredVs : null;
            }
        } elseif ($sizesUpdated || $colorsUpdated) {
            $variantsStock = [];
            $szList = count($sizes) > 0 ? $sizes : [null];
            $clList = count($colors) > 0 ? $colors : [null];
            $basePrice = $fieldsToUpdate['price_after'] ?? $product->price_after;
            foreach ($szList as $s) {
                foreach ($clList as $c) {
                    $variantsStock[] = [
                        'size' => $s,
                        'color' => $c,
                        'options' => [],
                        'price' => (float) $basePrice,
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
        $product->load(['category', 'images']);

        // Auto-assign category image if category has no image
        if ($product->category && empty($product->category->image_path) && !empty($product->main_image_path)) {
            $product->category->update([
                'image_path' => $product->main_image_path,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات مسودة المنتج بنجاح.',
            'data' => $this->formatProductResponse($product, $tenant),
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

        // Auto-assign category image if category has no image
        if ($product->category && empty($product->category->image_path) && !empty($product->main_image_path)) {
            $product->category->update([
                'image_path' => $product->main_image_path,
            ]);
        }

        // Record initial stock movement
        if ($product->stock > 0) {
            StockMovement::create([
                'tenant_id' => $product->tenant_id,
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
     * Format consistent product draft response payload.
     */
    protected function formatProductResponse(Product $product, ?Tenant $tenant = null, ?string $previewToken = null, int $imagesCount = 0): array
    {
        $token = $previewToken ?: $product->preview_token;
        $previewUrl = ($tenant && $token) 
            ? ($tenant->getStoreUrl() . "/shop/product.html?id={$product->id}&preview_token={$token}") 
            : null;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price_after' => (int) $product->price_after,
            'price_before' => (int) $product->price_before,
            'sizes' => $product->sizes ?: [],
            'colors' => $product->colors ?: [],
            'custom_variants' => $product->custom_variants ?: [],
            'variants_stock' => $product->variants_stock ?: [],
            'price_tiers' => $product->price_tiers ?: [],
            'stock' => (int) $product->stock,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name_ar ?: $product->category?->name,
            'main_category' => $product->category?->main_category,
            'images_count' => $imagesCount > 0 ? $imagesCount : ($product->images ? $product->images->count() : 0),
            'preview_token' => $token,
            'preview_url' => $previewUrl,
            'is_active' => (bool) $product->is_active,
        ];
    }

    /**
     * Helper to decode/normalize array inputs (sizes, colors).
     */
    protected function normalizeArray($input): array
    {
        if (empty($input)) {
            return [];
        }

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded));
            }
            return array_values(array_filter(array_map('trim', explode(',', $input))));
        }

        if (is_array($input)) {
            return array_values(array_filter($input));
        }

        return [];
    }

    /**
     * Helper to save a Base64-encoded image string to disk.
     */
    protected function saveBase64Image(string $b64, string $directory): ?string
    {
        try {
            if (str_contains($b64, ';base64,')) {
                [$meta, $b64Data] = explode(';base64,', $b64, 2);
            } else {
                $b64Data = $b64;
            }

            $decoded = base64_decode($b64Data);
            if (!$decoded) {
                return null;
            }

            $filename = $directory . '/' . Str::random(40) . '.jpg';
            Storage::disk('public')->put($filename, $decoded);

            return $filename;
        } catch (\Exception $e) {
            return null;
        }
    }
}
