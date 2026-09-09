<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Http\Requests\StoreLandingPageRequest;
use App\Http\Requests\UpdateLandingPageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    /**
     * عرض قائمة صفحات الهبوط للتاجر
     */
    public function index()
    {
        $landingPages = LandingPage::latest()->get()->map(function ($page) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'url' => url('/lp/' . $page->slug),
                'is_active' => (bool) $page->is_active,
                'views_count' => number_format($page->views_count),
                'conversions_count' => number_format($page->conversions_count),
                'conversion_rate' => $page->conversion_rate . '%',
                'created_at' => $page->created_at ? $page->created_at->format('Y-m-d H:i') : null,
            ];
        });

        return Inertia::render('Merchant/LandingPages/Index', [
            'landingPages' => $landingPages,
        ]);
    }

    /**
     * واجهة إنشاء صفحة هبوط جديدة
     */
    public function create()
    {
        $products = \App\Models\Product::select('id', 'name', 'price', 'price_after', 'image_url', 'main_image_path', 'description')
            ->latest()
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price_after ?: $p->price,
                'image_url' => $p->image_display_url,
                'description' => $p->description,
            ]);

        return Inertia::render('Merchant/LandingPages/Create', [
            'defaultSections' => LandingPage::getDefaultSections(),
            'products' => $products,
            'templates' => LandingPage::getAvailableTemplates(),
        ]);
    }

    /**
     * حفظ صفحة هبوط جديدة
     */
    public function store(StoreLandingPageRequest $request)
    {
        $validated = $request->validated();

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        if (empty($slug)) {
            $slug = 'page-' . time();
        }

        // التحقق من عدم تكرار الـ slug لنفس التاجر
        $tenantId = session()->get('tenant_id') ?? config('tenant.id') ?? auth()->user()->tenant_id;
        $count = LandingPage::where('tenant_id', $tenantId)->where('slug', $slug)->count();
        if ($count > 0) {
            $slug .= '-' . time();
        }

        $validated['slug'] = $slug;
        
        // جلب الأقسام الافتراضية
        $sections = LandingPage::getDefaultSections();
        
        // ربط المنتج المختار إذا وجد
        if ($request->filled('product_id')) {
            $product = \App\Models\Product::find($request->product_id);
            if ($product) {
                $imgUrl = $product->image_display_url ?: ($product->main_image_path ? asset('storage/' . $product->main_image_path) : $product->image_url);
                foreach ($sections as &$sec) {
                    if (($sec['type'] ?? '') === 'product_showcase') {
                        $sec['product_id'] = $product->id;
                        $sec['product_name'] = $product->name;
                        $sec['product_price'] = $product->price_after ?: $product->price;
                        $sec['product_image'] = $imgUrl;
                        $sec['image'] = $imgUrl;
                        if (!empty($product->description)) {
                            // Extract paragraphs or lines from description
                            $lines = array_filter(array_map('trim', explode("\n", strip_tags($product->description))));
                            if (count($lines) > 0) {
                                $sec['features'] = array_slice($lines, 0, 4);
                            }
                        }
                    }
                }
                unset($sec);
            }
        }
        
        $validated['sections'] = $sections;
        $validated['content'] = $sections;
        $validated['color_theme'] = $request->input('color_theme', 'light');
        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        if (!empty($validated['facebook_pixel_id'])) {
            $validated['facebook_pixel_id'] = $this->cleanPixelInput($validated['facebook_pixel_id'], 'fb');
        }
        if (!empty($validated['tiktok_pixel_id'])) {
            $validated['tiktok_pixel_id'] = $this->cleanPixelInput($validated['tiktok_pixel_id'], 'tt');
        }

        LandingPage::create($validated);

        return redirect()->route('merchant.landing-pages.index')->with('success', 'تم إنشاء صفحة الهبوط بنجاح ✓');
    }

    /**
     * واجهة تعديل صفحة الهبوط
     */
    public function edit(LandingPage $landingPage)
    {
        return Inertia::render('Merchant/LandingPages/Edit', [
            'landingPage' => [
                'id' => $landingPage->id,
                'title' => $landingPage->title,
                'slug' => $landingPage->slug,
                'url' => url('/lp/' . $landingPage->slug),
                'template' => $landingPage->template,
                'color_theme' => $landingPage->color_theme ?? 'light',
                'facebook_pixel_id' => $landingPage->facebook_pixel_id,
                'tiktok_pixel_id' => $landingPage->tiktok_pixel_id,
                'sections' => $landingPage->parsed_sections,
                'custom_css' => $landingPage->custom_css,
                'seo_title' => $landingPage->seo_title,
                'seo_description' => $landingPage->seo_description,
                'featured_image' => $landingPage->featured_image,
                'is_active' => (bool) $landingPage->is_active,
            ],
            'defaultSections' => LandingPage::getDefaultSections(),
            'products' => \App\Models\Product::select('id', 'name', 'price', 'price_after', 'image_url', 'main_image_path', 'description')
                ->latest()
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price_after ?: $p->price,
                    'image_url' => $p->image_display_url,
                    'description' => $p->description,
                ]),
            'templates' => LandingPage::getAvailableTemplates(),
        ]);
    }

    /**
     * تحديث صفحة الهبوط
     */
    public function update(UpdateLandingPageRequest $request, LandingPage $landingPage)
    {
        $validated = $request->validated();

        if (!empty($validated['slug']) && $validated['slug'] !== $landingPage->slug) {
            $slug = Str::slug($validated['slug']);
            $tenantId = $landingPage->tenant_id;
            $count = LandingPage::where('tenant_id', $tenantId)->where('slug', $slug)->where('id', '!=', $landingPage->id)->count();
            if ($count > 0) {
                $slug .= '-' . time();
            }
            $validated['slug'] = $slug;
        }

        if (isset($validated['sections'])) {
            $validated['content'] = $validated['sections'];
        }

        if (array_key_exists('facebook_pixel_id', $validated)) {
            $validated['facebook_pixel_id'] = $this->cleanPixelInput($validated['facebook_pixel_id'], 'fb');
        }
        if (array_key_exists('tiktok_pixel_id', $validated)) {
            $validated['tiktok_pixel_id'] = $this->cleanPixelInput($validated['tiktok_pixel_id'], 'tt');
        }

        $landingPage->update($validated);

        return redirect()->route('merchant.landing-pages.index')->with('success', 'تم تحديث صفحة الهبوط بنجاح ✓');
    }

    private function cleanPixelInput($val, $type = 'fb')
    {
        if (!$val) return null;
        if ($type === 'fb') {
            if (str_contains($val, 'fbq') || str_contains($val, 'script') || str_contains($val, 'facebook.com')) {
                preg_match_all('/fbq\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"]?(\d+)[\'"]?|[?&]id=(\d+)|\b(\d{13,17})\b/i', $val, $matches);
                $extracted = array_filter(array_merge($matches[1], $matches[2], $matches[3]));
                if (!empty($extracted)) {
                    return implode("\n", array_unique($extracted));
                }
            }
        } elseif ($type === 'tt') {
            if (str_contains($val, 'ttq') || str_contains($val, 'script') || str_contains($val, 'analytics.tiktok.com')) {
                preg_match_all('/ttq\.load\s*\(\s*[\'"]([a-zA-Z0-9_-]+)[\'"]\s*\)|[?&]sdkid=([a-zA-Z0-9_-]+)/i', $val, $matches);
                $extracted = array_filter(array_merge($matches[1], $matches[2]));
                if (!empty($extracted)) {
                    return implode("\n", array_unique($extracted));
                }
            }
        }
        return $val;
    }

    /**
     * تبديل حالة التفعيل
     */
    public function toggle(LandingPage $landingPage)
    {
        $landingPage->update(['is_active' => !$landingPage->is_active]);

        return redirect()->route('merchant.landing-pages.index')->with('success', $landingPage->is_active ? 'تم تفعيل صفحة الهبوط بنجاح ✓' : 'تم تعطيل صفحة الهبوط ✓');
    }

    /**
     * استنساخ صفحة هبوط (Duplicate)
     */
    public function duplicate(LandingPage $landingPage)
    {
        $newSlug = $landingPage->slug . '-copy-' . time();
        $newTitle = $landingPage->title . ' (نسخة)';

        LandingPage::create([
            'tenant_id' => $landingPage->tenant_id,
            'title' => $newTitle,
            'slug' => $newSlug,
            'template' => $landingPage->template,
            'color_theme' => $landingPage->color_theme ?? 'light',
            'content' => $landingPage->content,
            'sections' => $landingPage->sections,
            'custom_css' => $landingPage->custom_css,
            'facebook_pixel_id' => $landingPage->facebook_pixel_id,
            'tiktok_pixel_id' => $landingPage->tiktok_pixel_id,
            'seo_title' => $landingPage->seo_title,
            'seo_description' => $landingPage->seo_description,
            'featured_image' => $landingPage->featured_image,
            'is_active' => false,
            'views_count' => 0,
            'conversions_count' => 0,
        ]);

        return redirect()->route('merchant.landing-pages.index')->with('success', 'تم استنساخ صفحة الهبوط بنجاح ✓');
    }

    /**
     * حذف صفحة هبوط
     */
    public function destroy(LandingPage $landingPage)
    {
        $landingPage->delete();

        return redirect()->route('merchant.landing-pages.index')->with('success', 'تم حذف صفحة الهبوط بنجاح ✓');
    }
}
