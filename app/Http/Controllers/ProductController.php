<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $products = Product::with('category')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('description', 'like', "%{$q}%")
                       ->orWhereHas('category', function ($qc) use ($q) {
                           $qc->where('name', 'like', "%{$q}%")
                              ->orWhere('name_ar', 'like', "%{$q}%")
                              ->orWhere('name_en', 'like', "%{$q}%");
                       });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('products.index', compact('products', 'q'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->pluck('name','id');
        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'price_before' => ['nullable','numeric','min:0'],
            'price_after' => ['required','numeric','min:0'],
            'stock' => ['required','integer','min:0'],
            'category_id' => ['required','exists:categories,id'],
            'shipping_type' => ['required','in:free,governorate'],
            'main_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'gallery' => ['nullable','array'],
            'gallery.*' => ['image','mimes:jpg,jpeg,png,webp','max:4096'],
        ], [
            'name.required' => 'اسم المنتج مطلوب',
            'price_after.required' => 'السعر بعد الخصم مطلوب',
            'stock.required' => 'الكمية مطلوبة',
            'category_id.required' => 'القسم مطلوب',
            'shipping_type.required' => 'نوع الشحن مطلوب',
            'shipping_type.in' => 'نوع الشحن غير صحيح',
            'main_image.image' => 'يجب أن تكون الصورة الرئيسية ملف صورة صالح',
            'gallery.*.image' => 'كل صورة فرعية يجب أن تكون ملف صورة صالح',
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            // احتفاظ بحقل price لاستخدامات لاحقة (يعرض في القوائم)
            'price' => $validated['price_after'],
            'price_before' => $validated['price_before'] ?? 0,
            'price_after' => $validated['price_after'],
            'stock' => $validated['stock'],
            'shipping_type' => $validated['shipping_type'],
        ];

        // حفظ المقاسات والألوان
        if ($request->has('sizes') && is_array($request->sizes)) {
            $data['sizes'] = array_values(array_filter($request->sizes));
        }
        if ($request->has('colors') && is_array($request->colors)) {
            $data['colors'] = array_values(array_filter($request->colors));
        }

        // حفظ شرائح الأسعار
        if ($request->filled('price_tiers_json')) {
            $tiers = json_decode($request->price_tiers_json, true);
            if (is_array($tiers) && count($tiers) > 0) {
                $filtered = array_filter($tiers, fn($t) => isset($t['min_qty']) && isset($t['price']) && $t['min_qty'] >= 2 && $t['price'] > 0);
                $data['price_tiers'] = count($filtered) > 0 ? array_values($filtered) : null;
            } else {
                $data['price_tiers'] = null;
            }
        }

        // حفظ مخزون المتغيرات
        if ($request->filled('variants_stock')) {
            $data['variants_stock'] = is_string($request->variants_stock) ? json_decode($request->variants_stock, true) : $request->variants_stock;
        } else {
            $data['variants_stock'] = null;
        }

        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('products', 'public');
            $data['main_image_path'] = $path;
            $data['image_url'] = $path;
        }

        $product = Product::create($data);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $imageFile) {
                if ($imageFile && $imageFile->isValid()) {
                    $gpath = $imageFile->store('products/gallery', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $gpath,
                    ]);
                }
            }
        }

        return redirect()->route('products.index')->with('status', 'تم إضافة المنتج بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->pluck('name','id');
        $product->load('images');
        return view('products.edit', compact('product','categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'price_before' => ['nullable','numeric','min:0'],
            'price_after' => ['required','numeric','min:0'],
            'stock' => ['required','integer','min:0'],
            'category_id' => ['required','exists:categories,id'],
            'shipping_type' => ['required','in:free,governorate'],
            'main_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'gallery' => ['nullable','array'],
            'gallery.*' => ['image','mimes:jpg,jpeg,png,webp','max:4096'],
        ], [
            'name.required' => 'اسم المنتج مطلوب',
            'price_after.required' => 'السعر بعد الخصم مطلوب',
            'stock.required' => 'الكمية مطلوبة',
            'category_id.required' => 'القسم مطلوب',
            'shipping_type.required' => 'نوع الشحن مطلوب',
            'shipping_type.in' => 'نوع الشحن غير صحيح',
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'price' => $validated['price_after'],
            'price_before' => $validated['price_before'] ?? 0,
            'price_after' => $validated['price_after'],
            'stock' => $validated['stock'],
            'shipping_type' => $validated['shipping_type'],
        ];

        // تحديث المقاسات والألوان كـ JSON
        // تحديث المقاسات والألوان
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

        // تحديث شرائح الأسعار
        if ($request->filled('price_tiers_json')) {
            $tiers = json_decode($request->price_tiers_json, true);
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
            $path = $request->file('main_image')->store('products', 'public');
            $data['main_image_path'] = $path;
            $data['image_url'] = $path;
        }

        $product->update($data);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $imageFile) {
                if ($imageFile && $imageFile->isValid()) {
                    $gpath = $imageFile->store('products/gallery', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $gpath,
                    ]);
                }
            }
        }

        return redirect()->route('products.index')->with('status', 'تم تحديث المنتج بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('status', 'تم حذف المنتج بنجاح');
    }

    /**
     * Remove a specific image from the product gallery.
     */
    public function destroyImage(Product $product, ProductImage $image)
    {
        // Ensure the image belongs to the product
        if ($image->product_id !== $product->id) {
            abort(404);
        }
        if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }
        $image->delete();
        return back()->with('status', 'تم حذف الصورة بنجاح');
    }
}
