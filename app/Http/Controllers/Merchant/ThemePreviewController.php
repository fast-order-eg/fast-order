<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ThemePreviewController extends Controller
{
    /**
     * @var ThemeService
     */
    protected ThemeService $themeService;

    /**
     * Inject ThemeService into the controller.
     */
    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Display the Live Theme Customizer & Preview interface.
     * Supports real store data, unsaved customizations, device viewports, and presets.
     */
    public function index(Request $request, ?string $slug = null)
    {
        // 1. Resolve theme slug or fallback to current active theme
        if (!$slug) {
            $slug = $this->themeService->getActiveTheme();
        }

        if (!$this->themeService->isThemeAvailable($slug)) {
            $slug = 'default';
        }

        // 2. Retrieve theme configuration and built-in themes
        $themeConfig = $this->themeService->getThemeConfig($slug);
        $allThemes = $this->themeService->getAllThemes();
        $activeThemeSlug = $this->themeService->getActiveTheme();

        // 3. Fetch saved customizations vs unsaved session customizations
        $savedCustomsJson = Setting::get("theme_customs_{$slug}");
        $savedCustoms = $savedCustomsJson ? json_decode($savedCustomsJson, true) : [];
        if (!is_array($savedCustoms)) {
            $savedCustoms = [];
        }

        $sessionCustoms = session()->get("theme_preview_customs_{$slug}", []);
        if (!is_array($sessionCustoms)) {
            $sessionCustoms = [];
        }

        // Merge defaults -> saved -> unsaved session customizations
        $defaultVars = $themeConfig['css_variables'] ?? [
            'primary_color' => '#4f46e5',
            'secondary_color' => '#64748b',
            'accent_color' => '#f59e0b',
            'background_color' => '#ffffff',
            'text_color' => '#1e293b',
            'font_family' => 'Cairo',
            'header_layout' => 'Classic',
            'banner_layout' => 'Slider',
        ];

        $currentCustomizations = array_merge($defaultVars, $savedCustoms, $sessionCustoms);

        // 4. Load real store data for live realistic preview
        $storeData = $this->getRealStoreData();

        // 5. Configure Device Viewports for Simulator (Mobile, Tablet, Desktop)
        $deviceModes = [
            'desktop' => [
                'id' => 'desktop',
                'name' => 'سطح المكتب (Desktop)',
                'width' => '100%',
                'height' => '100%',
                'max_width' => '1440px',
                'icon' => 'desktop',
                'description' => 'عرض شاشة كاملة متجاوبة للكمبيوتر واللابتوب',
            ],
            'tablet' => [
                'id' => 'tablet',
                'name' => 'جهاز لوحي (Tablet)',
                'width' => '768px',
                'height' => '1024px',
                'max_width' => '768px',
                'icon' => 'tablet',
                'description' => 'محاكاة شاشات الآيباد والأجهزة اللوحية المتجاوبة',
            ],
            'mobile' => [
                'id' => 'mobile',
                'name' => 'هاتف ذكي (Mobile)',
                'width' => '375px',
                'height' => '812px',
                'max_width' => '375px',
                'icon' => 'mobile',
                'description' => 'محاكاة شاشات الهواتف الذكية (iPhone / Android)',
            ],
        ];

        // 6. Generate dynamic thumbnails / screenshots
        $thumbnails = $this->generateThumbnailsData($allThemes);

        $previewData = [
            'theme' => $slug,
            'activeThemeSlug' => $activeThemeSlug,
            'themeConfig' => $themeConfig,
            'allThemes' => $allThemes,
            'customizations' => $currentCustomizations,
            'storeData' => $storeData,
            'deviceModes' => $deviceModes,
            'thumbnails' => $thumbnails,
            'previewFrameUrl' => route('merchant.themes.preview.frame', ['slug' => $slug, 'page' => 'index.html']),
            'saveUrl' => route('merchant.themes.preview.save', ['slug' => $slug]),
            'sessionUrl' => route('merchant.themes.preview.session', ['slug' => $slug]),
            'resetUrl' => route('merchant.themes.preview.reset', ['slug' => $slug]),
        ];

        // Return JSON if requested explicitly via API/AJAX
        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'data' => $previewData,
            ]);
        }

        // Render Inertia component if X-Inertia header is present or blade view not requested
        if (class_exists(\Inertia\Inertia::class) && ($request->header('X-Inertia') || $request->query('view') !== 'blade')) {
            return Inertia::render('Merchant/Themes/Preview', $previewData);
        }

        // Fallback to Blade view
        return view('merchant.themes.preview', $previewData);
    }

    /**
     * Render the live storefront HTML frame inside the customizer iframe.
     * Injects unsaved custom CSS variables, Google Fonts, and live postMessage listener.
     */
    public function previewFrame(Request $request, string $slug, string $page = 'index.html')
    {
        if (!$this->themeService->isThemeAvailable($slug)) {
            $slug = 'default';
        }

        // Ensure page filename ends with .html
        if (!Str::endsWith($page, '.html') && !Str::endsWith($page, '.blade.php')) {
            $page .= '.html';
        }

        // Check theme folder first
        $themeFilePath = resource_path("views/shop/themes/{$slug}/{$page}");
        if (file_exists($themeFilePath)) {
            $filePath = $themeFilePath;
        } else {
            $filePath = resource_path("views/shop/{$page}");
            if (!file_exists($filePath)) {
                $filePath = resource_path("views/shop/themes/{$slug}/index.html");
                if (!file_exists($filePath)) {
                    $filePath = resource_path("views/shop/index.html");
                    if (!file_exists($filePath)) {
                        return response()->make('<h1>خطأ: ملف معاينة واجهة المتجر غير موجود</h1>', 404);
                    }
                }
            }
        }

        $html = file_get_contents($filePath);

        // Include theme custom CSS if exists
        $themeCssTag = '';
        if (file_exists(public_path("shop/themes/{$slug}/style.css"))) {
            $themeCssTag = '<link rel="stylesheet" href="/shop/themes/' . e($slug) . '/style.css?v=' . time() . '">';
        }

        // Get live unsaved customizations from request query params or session
        $sessionCustoms = session()->get("theme_preview_customs_{$slug}", []);
        $requestCustoms = $request->except(['page', 'slug', '_token']);
        $customizations = array_merge($sessionCustoms, $requestCustoms);

        $themeConfig = $this->themeService->getThemeConfig($slug);
        $defaultVars = $themeConfig['css_variables'] ?? [];
        
        $primaryColor = $customizations['primary_color'] ?? $defaultVars['primary_color'] ?? '#4f46e5';
        $secondaryColor = $customizations['secondary_color'] ?? $defaultVars['secondary_color'] ?? '#64748b';
        $accentColor = $customizations['accent_color'] ?? $defaultVars['accent_color'] ?? '#f59e0b';
        $backgroundColor = $customizations['background_color'] ?? $defaultVars['background_color'] ?? '#ffffff';
        $textColor = $customizations['text_color'] ?? $defaultVars['text_color'] ?? '#1e293b';
        $fontFamily = $customizations['font_family'] ?? $defaultVars['font_family'] ?? 'Cairo';

        // Adjust HTML lang and dir for RTL support
        $locale = app()->getLocale();
        $dir = $locale === 'en' ? 'ltr' : 'rtl';
        $html = str_ireplace('<html lang="ar" dir="rtl">', '<html lang="' . $locale . '" dir="' . $dir . '">', $html);
        $html = str_ireplace('<html>', '<html lang="' . $locale . '" dir="' . $dir . '">', $html);

        // Build Google Font URL
        $fontLinks = [
            'Cairo' => 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap',
            'Tajawal' => 'https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap',
            'Inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
            'Almarai' => 'https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap',
        ];
        $fontUrl = $fontLinks[$fontFamily] ?? $fontLinks['Cairo'];
        $fontStack = "'{$fontFamily}', system-ui, -apple-system, sans-serif";

        // Calculate color variations
        $primaryHover = $this->themeService->adjustBrightness($primaryColor, -0.08);
        $primaryLight = $this->themeService->adjustBrightness($primaryColor, 0.85);
        $secondaryHover = $this->themeService->adjustBrightness($secondaryColor, -0.08);

        // Fetch real store data
        $storeData = $this->getRealStoreData();

        // Inject dynamic theme styles and live postMessage listener script
        $injections = '
<!-- Fast Order Live Theme Preview Injection -->
' . $themeCssTag . '
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="' . e($fontUrl) . '" rel="stylesheet" id="live-theme-font">
<style id="live-theme-styles">
  :root {
    --primary-color: ' . e($primaryColor) . ';
    --primary-hover: ' . e($primaryHover) . ';
    --primary-light: ' . e($primaryLight) . ';
    --secondary-color: ' . e($secondaryColor) . ';
    --secondary-hover: ' . e($secondaryHover) . ';
    --accent-color: ' . e($accentColor) . ';
    --background-color: ' . e($backgroundColor) . ';
    --text-color: ' . e($textColor) . ';
    --font-family: ' . e($fontStack) . ';
  }
  html, body {
    font-family: var(--font-family) !important;
    background-color: var(--background-color) !important;
    color: var(--text-color) !important;
  }
  .live-preview-banner {
    position: fixed;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(15, 23, 42, 0.92);
    color: #fff;
    padding: 8px 18px;
    border-radius: 9999px;
    font-size: 13px;
    font-weight: bold;
    z-index: 999999;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(255,255,255,0.15);
    backdrop-filter: blur(8px);
    pointer-events: none;
  }
  .live-preview-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 1.5s infinite;
  }
  @keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }
</style>
<script>
  window.__SITE_SETTINGS__ = ' . json_encode($storeData, JSON_UNESCAPED_UNICODE) . ';
  window.__THEME_PREVIEW_MODE__ = true;
  window.__ACTIVE_THEME__ = "' . e($slug) . '";

  // Real-time listener for instant postMessage updates from parent customizer window
  window.addEventListener("message", function(event) {
    if (!event.data || event.data.type !== "UPDATE_THEME_PREVIEW") return;
    const customs = event.data.customizations || {};
    const root = document.documentElement;
    
    if (customs.primary_color) {
      root.style.setProperty("--primary-color", customs.primary_color);
    }
    if (customs.secondary_color) {
      root.style.setProperty("--secondary-color", customs.secondary_color);
    }
    if (customs.background_color) {
      root.style.setProperty("--background-color", customs.background_color);
      document.body.style.backgroundColor = customs.background_color;
    }
    if (customs.text_color) {
      root.style.setProperty("--text-color", customs.text_color);
      document.body.style.color = customs.text_color;
    }
    if (customs.font_family) {
      const fontStack = "\'" + customs.font_family + "\', system-ui, -apple-system, sans-serif";
      root.style.setProperty("--font-family", fontStack);
      document.body.style.fontFamily = fontStack;
      
      const fontLinks = {
        "Cairo": "https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap",
        "Tajawal": "https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap",
        "Inter": "https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap",
        "Roboto": "https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap",
        "Almarai": "https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap"
      };
      const fontEl = document.getElementById("live-theme-font");
      if (fontEl && fontLinks[customs.font_family]) {
        fontEl.href = fontLinks[customs.font_family];
      }
    }
  });
</script>';

        // Inject banner inside body
        $bannerHtml = '
<div class="live-preview-banner">
  <span class="live-preview-dot"></span>
  <span>معاينة حية: ثيم (' . e($themeConfig['name'] ?? $slug) . ')</span>
</div>';

        if (stripos($html, '</head>') !== false) {
            $html = str_ireplace('</head>', $injections . "\n</head>", $html);
        } else {
            $html = $injections . "\n" . $html;
        }

        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $bannerHtml . "\n</body>", $html);
        } else {
            $html .= $bannerHtml;
        }

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Store unsaved customizations in the session for seamless live previewing.
     */
    public function updateSession(Request $request, string $slug): JsonResponse
    {
        if (!$this->themeService->isThemeAvailable($slug)) {
            return response()->json(['status' => 'error', 'message' => 'الثيم غير متاح'], 404);
        }

        $customizations = $request->input('customizations', $request->except(['_token', 'slug']));
        session()->put("theme_preview_customs_{$slug}", $customizations);
        session()->put('preview_theme', $slug);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات المعاينة الحية بنجاح ✓',
            'active_slug' => $slug,
            'customizations' => $customizations,
        ]);
    }

    /**
     * Save theme customizations to database and activate the theme.
     */
    public function save(Request $request, string $slug)
    {
        $request->validate([
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font_family' => ['nullable', 'string', 'max:50'],
            'header_layout' => ['nullable', 'string', 'max:50'],
            'banner_layout' => ['nullable', 'string', 'max:50'],
        ], [
            'primary_color.regex' => 'صيغة اللون الرئيسي غير صالحة',
            'secondary_color.regex' => 'صيغة اللون الثانوي غير صالحة',
        ]);

        if (!$this->themeService->isThemeAvailable($slug)) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'الثيم المطلوب غير متاح'], 404);
            }
            return Redirect::back()->withErrors(['theme' => 'الثيم المطلوب غير متاح']);
        }

        // Gather customizations to save
        $customizations = $request->input('customizations', []);
        $fields = ['primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color', 'font_family', 'header_layout', 'banner_layout'];
        foreach ($fields as $field) {
            if ($request->has($field) && $request->input($field)) {
                $customizations[$field] = $request->input($field);
            }
        }

        // Check if there are unsaved customizations in session to merge
        $sessionCustoms = session()->get("theme_preview_customs_{$slug}", []);
        $finalCustomizations = array_merge($sessionCustoms, $customizations);

        // Save customizations and activate theme via ThemeService
        $this->themeService->saveThemeCustomizations($slug, $finalCustomizations);
        $this->themeService->setActiveTheme($slug);

        // Also update legacy setting for backward compatibility
        Setting::set('theme_customization', json_encode([
            'primary_color' => $finalCustomizations['primary_color'] ?? '#F97316',
            'secondary_color' => $finalCustomizations['secondary_color'] ?? '#1F2937',
            'background_color' => $finalCustomizations['background_color'] ?? '#FFFFFF',
            'font_family' => $finalCustomizations['font_family'] ?? 'Almarai',
            'header_layout' => $finalCustomizations['header_layout'] ?? 'Classic',
            'banner_layout' => $finalCustomizations['banner_layout'] ?? 'Slider',
        ]));

        // Clear preview session
        session()->forget("theme_preview_customs_{$slug}");
        session()->forget('preview_theme');

        $successMsg = 'تم حفظ التخصيصات وتفعيل الثيم في متجرك بنجاح ✓';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMsg,
                'active_theme' => $slug,
                'customizations' => $finalCustomizations,
            ]);
        }

        return Redirect::to('/admin/themes')->with('success', $successMsg);
    }

    /**
     * Reset customizations for the theme back to default presets.
     */
    public function reset(Request $request, string $slug)
    {
        if (!$this->themeService->isThemeAvailable($slug)) {
            return response()->json(['status' => 'error', 'message' => 'الثيم غير متاح'], 404);
        }

        $this->themeService->saveThemeCustomizations($slug, []);
        session()->forget("theme_preview_customs_{$slug}");

        $successMsg = 'تمت استعادة الإعدادات الافتراضية للثيم بنجاح ✓';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMsg,
                'css_variables' => $this->themeService->getThemeCssVariables(),
            ]);
        }

        return Redirect::back()->with('success', $successMsg);
    }

    /**
     * Generate or return thumbnail / screenshot metadata and SVG mockups for themes.
     */
    public function thumbnails(Request $request, ?string $slug = null): JsonResponse
    {
        $allThemes = $this->themeService->getAllThemes();
        
        if ($slug && isset($allThemes[$slug])) {
            $thumbnails = [$slug => $this->generateSingleThumbnail($allThemes[$slug])];
        } else {
            $thumbnails = $this->generateThumbnailsData($allThemes);
        }

        return response()->json([
            'status' => 'success',
            'count' => count($thumbnails),
            'data' => $thumbnails,
        ]);
    }

    /**
     * Get real store data from database to make the preview accurate and realistic.
     */
    protected function getRealStoreData(): array
    {
        $storeName = Setting::get('store_name', 'متجر فاست أوردر التجريبي');
        $logo = Setting::get('logo') ? asset('storage/' . Setting::get('logo')) : asset('images/logo.png');
        $phone = Setting::get('phone', '01146520922');
        $whatsapp = Setting::get('whatsapp', '201146520922');

        // Fetch categories
        try {
            $categories = Category::getMainCategories();
        } catch (\Exception $e) {
            $categories = [];
        }

        // Fetch products
        try {
            $products = Product::with('category')->take(8)->get();
            $bestOffers = Product::where('discount_price', '>', 0)->take(4)->get();
            if ($bestOffers->isEmpty() && $products->isNotEmpty()) {
                $bestOffers = $products->take(4);
            }
            $latestProducts = Product::latest()->take(4)->get();
        } catch (\Exception $e) {
            $products = [];
            $bestOffers = [];
            $latestProducts = [];
        }

        return [
            'store_name' => $storeName,
            'logo_url' => $logo,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
            'categories' => $categories,
            'products' => $products,
            'best_offers' => $bestOffers,
            'latest_products' => $latestProducts,
            'currency' => 'EGP',
            'currency_symbol' => 'ج.م',
        ];
    }

    /**
     * Generate thumbnail metadata and high-res vector SVG mockups for all themes.
     */
    protected function generateThumbnailsData(array $themes): array
    {
        $result = [];
        foreach ($themes as $slug => $config) {
            $result[$slug] = $this->generateSingleThumbnail($config);
        }
        return $result;
    }

    /**
     * Generate single SVG screenshot/thumbnail representing the theme layout and colors.
     */
    protected function generateSingleThumbnail(array $config): array
    {
        $slug = $config['slug'] ?? 'default';
        $name = $config['name'] ?? 'ثيم غير مسمّى';
        $vars = $config['css_variables'] ?? [];
        
        $primary = $vars['primary_color'] ?? '#4f46e5';
        $secondary = $vars['secondary_color'] ?? '#64748b';
        $bg = $vars['background_color'] ?? '#ffffff';
        $text = $vars['text_color'] ?? '#1e293b';

        // Build dynamic SVG mockup
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 260" width="100%" height="100%">
            <rect width="400" height="260" fill="' . $bg . '" rx="12"/>
            <!-- Header -->
            <rect x="16" y="16" width="368" height="32" fill="' . $primary . '" rx="6" opacity="0.95"/>
            <circle cx="36" cy="32" r="6" fill="#ffffff" opacity="0.8"/>
            <rect x="52" y="28" width="60" height="8" fill="#ffffff" rx="4" opacity="0.9"/>
            <rect x="280" y="26" width="88" height="12" fill="#ffffff" rx="6" opacity="0.3"/>
            
            <!-- Hero Banner -->
            <rect x="16" y="58" width="368" height="74" fill="' . $secondary . '" rx="8" opacity="0.85"/>
            <rect x="36" y="78" width="140" height="14" fill="#ffffff" rx="4" opacity="0.95"/>
            <rect x="36" y="98" width="90" height="10" fill="#ffffff" rx="3" opacity="0.7"/>
            <rect x="36" y="114" width="56" height="14" fill="' . $primary . '" rx="4"/>
            
            <!-- Products Grid -->
            <rect x="16" y="144" width="114" height="96" fill="' . $primary . '" rx="8" opacity="0.08"/>
            <rect x="26" y="154" width="94" height="48" fill="' . $primary . '" rx="6" opacity="0.2"/>
            <rect x="26" y="210" width="70" height="8" fill="' . $text . '" rx="3" opacity="0.7"/>
            <rect x="26" y="222" width="40" height="10" fill="' . $primary . '" rx="3"/>
            
            <rect x="143" y="144" width="114" height="96" fill="' . $primary . '" rx="8" opacity="0.08"/>
            <rect x="153" y="154" width="94" height="48" fill="' . $primary . '" rx="6" opacity="0.2"/>
            <rect x="153" y="210" width="70" height="8" fill="' . $text . '" rx="3" opacity="0.7"/>
            <rect x="153" y="222" width="40" height="10" fill="' . $primary . '" rx="3"/>
            
            <rect x="270" y="144" width="114" height="96" fill="' . $primary . '" rx="8" opacity="0.08"/>
            <rect x="280" y="154" width="94" height="48" fill="' . $primary . '" rx="6" opacity="0.2"/>
            <rect x="280" y="210" width="70" height="8" fill="' . $text . '" rx="3" opacity="0.7"/>
            <rect x="280" y="222" width="40" height="10" fill="' . $primary . '" rx="3"/>
        </svg>';

        return [
            'slug' => $slug,
            'name' => $name,
            'description' => $config['description'] ?? '',
            'author' => $config['author'] ?? 'Fast Order Team',
            'version' => $config['version'] ?? '1.0.0',
            'preview_image' => $config['preview_image'] ?? "/shop/images/themes/{$slug}-preview.webp",
            'svg_thumbnail' => 'data:image/svg+xml;base64,' . base64_encode($svg),
            'colors' => [
                'primary' => $primary,
                'secondary' => $secondary,
                'background' => $bg,
                'text' => $text,
            ],
        ];
    }
}
