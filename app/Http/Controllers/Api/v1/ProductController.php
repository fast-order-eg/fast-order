<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $products = Product::where('tenant_id', $tenant->id)
            ->with(['category', 'images'])
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $product = Product::where('tenant_id', $tenant->id)
            ->with(['category', 'images', 'upsells', 'crossSells'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'nullable|integer|min:0',
            'price_before' => 'nullable|numeric|min:0',
            'shipping_type' => 'nullable|in:free,governorate',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $product = Product::create($validated);

        return response()->json(['success' => true, 'data' => $product], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $product = Product::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'nullable|integer|min:0',
            'price_before' => 'nullable|numeric|min:0',
            'shipping_type' => 'nullable|in:free,governorate',
        ]);

        $product->update($validated);

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $product = Product::where('tenant_id', $tenant->id)->findOrFail($id);
        $product->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المنتج بنجاح.']);
    }
}
