<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StorefrontController extends Controller
{
    /**
     * Helper to adjust brightness of a hex color dynamically (for hover/light modes).
     */
    private function adjustBrightness($hex, $steps)
    {
        if (abs($steps) < 1) {
            $steps = $steps * 255;
        }
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        $r_hex = str_pad(dechex(round($r)), 2, '0', STR_PAD_LEFT);
        $g_hex = str_pad(dechex(round($g)), 2, '0', STR_PAD_LEFT);
        $b_hex = str_pad(dechex(round($b)), 2, '0', STR_PAD_LEFT);

        return '#' . $r_hex . $g_hex . $b_hex;
    }

    /**
     * Serve a storefront page with injected pixels, SEO tags and dynamic theme settings.
     */
    public function servePage(Request $request, $page = 'index.html')
    {
        // Default to index.html if empty
        if (empty($page)) {
            $page = 'index.html';
        }

        // Add .html suffix if not present
        if (!Str::endsWith($page, '.html')) {
            $page .= '.html';
        }

        $themeService = app(\App\Services\ThemeService::class);
        $filePath = $themeService->resolveViewPath($page);

        if (!file_exists($filePath)) {
            $filePath = resource_path("views/shop/{$page}");
        }

        if (!file_exists($filePath)) {
            abort(404);
        }

        $html = file_get_contents($filePath);

        // Adjust HTML lang and dir dynamically based on store locale (default to Arabic RTL)
        $locale = session()->get('locale') ?? Setting::get('store_language', 'ar');
        if (empty($locale)) {
            $locale = 'ar';
        }
        $dir = ($locale === 'en') ? 'ltr' : 'rtl';
        $html = str_ireplace('<html lang="ar" dir="rtl">', '<html lang="' . $locale . '" dir="' . $dir . '">', $html);
        $html = str_ireplace('<html lang="ar">', '<html lang="' . $locale . '" dir="' . $dir . '">', $html);
        $html = str_ireplace('<html>', '<html lang="' . $locale . '" dir="' . $dir . '">', $html);

        // Fetch settings for the current tenant
        $storeName = Setting::get('store_name', 'Store');
        $facebookPixelId = Setting::get('facebook_pixel_id', '');
        $tiktokPixelId = Setting::get('tiktok_pixel_id', '');
        $googleAnalyticsId = Setting::get('google_analytics_id', '');

        // Fetch settings for homepage sections and builder
        $storedCats = Setting::get('main_categories');
        $mainCategories = $storedCats
            ? (json_decode($storedCats, true) ?: Category::getMainCategories())
            : Category::getMainCategories();

        $homepageSections = Setting::get('homepage_sections');
        if ($homepageSections) {
            $homepageSections = json_decode($homepageSections, true);
        }
        if (!$homepageSections || !is_array($homepageSections)) {
            $homepageSections = [
                ['id' => 'hero_slider', 'enabled' => true, 'title' => 'البانر الإعلاني', 'title_en' => 'Hero Slider'],
                ['id' => 'featured_categories', 'enabled' => true, 'title' => 'الأقسام المميزة', 'title_en' => 'Featured Categories'],
                ['id' => 'best_offers', 'enabled' => true, 'title' => 'أفضل العروض والخصومات', 'title_en' => 'Best Offers & Discounts'],
                ['id' => 'latest_products', 'enabled' => true, 'title' => 'أحدث المنتجات', 'title_en' => 'Latest Products']
            ];
        }

        $featuredCats = Setting::get('homepage_featured_categories');
        $featuredCats = $featuredCats ? json_decode($featuredCats, true) : [];

        $bestOffersLimit = (int) Setting::get('homepage_best_offers_limit', 4);
        $latestProductsLimit = (int) Setting::get('homepage_latest_products_limit', 5);

        $activeMenus = \App\Models\Menu::active()->get()->groupBy('location')->map(function ($items) {
            return $items->first()->items ?? [];
        })->toArray();

        $settingsData = [
            'phone' => Setting::get('phone', '01146520922'),
            'whatsapp' => Setting::get('whatsapp', '201146520922'),
            'facebook_page' => Setting::get('facebook_page', ''),
            'store_name' => $storeName,
            'logo_url' => Setting::get('logo') ? asset('storage/' . Setting::get('logo')) : asset('images/logo.png'),
            'facebook_pixel_id' => $facebookPixelId,
            'tiktok_pixel_id' => $tiktokPixelId,
            'google_analytics_id' => $googleAnalyticsId,
            'main_categories' => $mainCategories,
            'homepage_sections' => $homepageSections,
            'homepage_featured_categories' => $featuredCats,
            'homepage_best_offers_limit' => $bestOffersLimit,
            'homepage_latest_products_limit' => $latestProductsLimit,
            'menus' => $activeMenus,
        ];

        // Prepare head scripts to inject
        $headInjections = [
            '<script>window.__SITE_SETTINGS__ = ' . json_encode($settingsData, JSON_UNESCAPED_UNICODE) . ';</script>'
        ];
        $bodyInjections = [];

        // Live Chat Settings & Injections
        $liveChatProvider = Setting::get('live_chat_provider', 'none');
        $liveChatWhatsappNumber = Setting::get('live_chat_whatsapp_number', '');
        $liveChatTawktoPropertyId = Setting::get('live_chat_tawkto_property_id', '');
        $liveChatSettings = json_decode(Setting::get('live_chat_settings', '{}'), true);

        if ($liveChatProvider === 'tawkto' && !empty($liveChatTawktoPropertyId)) {
            $headInjections[] = '<!-- Tawk.to Live Chat Widget -->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src=\'https://embed.tawk.to/' . e($liveChatTawktoPropertyId) . '/default\';
s1.charset=\'UTF-8\';
s1.setAttribute(\'crossorigin\',\'*\');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!-- End Tawk.to Live Chat Widget -->';
        } elseif ($liveChatProvider === 'whatsapp' && !empty($liveChatWhatsappNumber)) {
            $bubbleColor = $liveChatSettings['bubble_color'] ?? '#25D366';
            $welcomeMsg = $liveChatSettings['welcome_message'] ?? 'مرحباً بك! كيف يمكنني مساعدتك؟';
            $prefilledText = rawurlencode($liveChatSettings['whatsapp_prefilled_text'] ?? 'أريد الاستفسار عن المنتجات');

            $whatsappWidgetHtml = '<!-- WhatsApp Floating Chat Widget -->
<div id="whatsapp-chat-widget" style="position: fixed; bottom: 24px; right: 24px; z-index: 99999; font-family: \'Cairo\', sans-serif; direction: rtl;">
  <!-- Bubble Button -->
  <button id="whatsapp-bubble" style="width: 60px; height: 60px; border-radius: 50%; background-color: ' . e($bubbleColor) . '; color: white; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; display: flex; align-items: center; justify-content: center; position: relative; transition: all 0.3s ease;">
    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" style="width: 32px; height: 32px;">
      <path d="M12.031 2c-5.516 0-9.985 4.469-9.985 9.985 0 2.22.723 4.27 1.947 5.929L2.5 21.5l3.743-1.423a9.92 9.92 0 005.788 1.875c5.516 0 9.985-4.469 9.985-9.985C22.016 6.469 17.547 2 12.031 2zm6.275 14.153c-.255.719-1.503 1.3-2.072 1.385-.568.087-1.129.176-3.649-.824-2.859-1.135-4.664-4.053-4.808-4.244-.142-.191-1.144-1.522-1.144-2.905 0-1.383.723-2.062.981-2.338.257-.276.568-.344.756-.344.188 0 .376.002.538.01.171.008.397-.065.62.484.228.56.779 1.9.847 2.038.067.138.113.298.02.484-.09.186-.138.302-.274.459-.138.158-.291.353-.415.474-.138.138-.282.289-.122.564.161.276.716 1.185 1.537 1.916.821.731 1.513.957 1.724 1.053.211.096.335.08.459-.063.124-.143.528-.615.67-.824.143-.207.286-.176.48-.104.195.073 1.233.582 1.444.688.211.106.353.158.405.249.053.092.053.53-.202 1.251z"/>
    </svg>
    <span style="position: absolute; top: -4px; right: -4px; width: 14px; height: 14px; background-color: #ef4444; border: 2px solid white; border-radius: 50%;"></span>
  </button>

  <!-- Popup Window -->
  <div id="whatsapp-popup" style="display: none; position: absolute; bottom: 80px; right: 0; width: 330px; background: white; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.15); overflow: hidden; transition: all 0.3s ease; border: 1px solid #f0f0f0;">
    <!-- Header -->
    <div style="background-color: ' . e($bubbleColor) . '; padding: 20px; color: white; position: relative;">
      <button id="whatsapp-close" style="position: absolute; top: 12px; left: 12px; background: transparent; border: none; color: white; font-size: 20px; cursor: pointer; opacity: 0.8;">&times;</button>
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold;">
          💬
        </div>
        <div>
          <h4 style="margin: 0; font-size: 16px; font-weight: bold;">' . e($storeName) . '</h4>
          <p style="margin: 3px 0 0 0; font-size: 12px; opacity: 0.9; display: flex; align-items: center; gap: 4px;">
            <span style="width: 8px; height: 8px; background-color: #4ade80; border-radius: 50%; display: inline-block;"></span>
            متصل الآن (جاهز للمساعدة)
          </p>
        </div>
      </div>
    </div>
    <!-- Body -->
    <div style="padding: 20px; background-color: #f4f7f6; min-height: 80px;">
      <div style="background: white; padding: 12px 16px; border-radius: 12px; font-size: 14px; color: #4a4a4a; line-height: 1.5; box-shadow: 0 1px 2px rgba(0,0,0,0.05); position: relative;">
        ' . e($welcomeMsg) . '
        <span style="position: absolute; right: -6px; top: 12px; width: 0; height: 0; border-top: 6px solid transparent; border-bottom: 6px solid transparent; border-left: 6px solid white;"></span>
      </div>
    </div>
    <!-- Footer -->
    <div style="padding: 16px; background: white; text-align: center;">
      <a href="https://wa.me/' . e($liveChatWhatsappNumber) . '?text=' . $prefilledText . '" target="_blank" id="whatsapp-submit" style="display: block; background-color: ' . e($bubbleColor) . '; color: white; text-decoration: none; padding: 12px 24px; border-radius: 12px; font-weight: bold; font-size: 14px; box-shadow: 0 4px 10px rgba(37,211,102,0.3); transition: all 0.2s ease;">
        ابدأ المحادثة الآن
      </a>
    </div>
  </div>
</div>

<script>
  (function() {
    var bubble = document.getElementById("whatsapp-bubble");
    var popup = document.getElementById("whatsapp-popup");
    var closeBtn = document.getElementById("whatsapp-close");
    
    if (bubble && popup) {
      bubble.addEventListener("click", function() {
        if (popup.style.display === "none") {
          popup.style.display = "block";
        } else {
          popup.style.display = "none";
        }
      });
    }
    
    if (closeBtn && popup) {
      closeBtn.addEventListener("click", function(e) {
        e.stopPropagation();
        popup.style.display = "none";
      });
    }
  })();
</script>';

            $bodyInjections[] = $whatsappWidgetHtml;
        }

        // Fetch Theme Customization settings
        $themeCustomizationJson = Setting::get('theme_customization');
        $themeCustomization = $themeCustomizationJson ? json_decode($themeCustomizationJson, true) : [];

        $primaryColor = $themeCustomization['primary_color'] ?? Setting::get('primary_color', '#F97316');
        $secondaryColor = $themeCustomization['secondary_color'] ?? Setting::get('secondary_color', '#1F2937');
        $backgroundColor = $themeCustomization['background_color'] ?? Setting::get('background_color', '#FFFFFF');
        $fontFamily = $themeCustomization['font_family'] ?? Setting::get('font_family', 'Cairo');
        $favicon = Setting::get('favicon');
        $logo = Setting::get('logo');
        $headerLayout = $themeCustomization['header_layout'] ?? Setting::get('header_layout', 'Classic');
        $headerLayoutLower = strtolower($headerLayout);

        $bannerLayout = $themeCustomization['banner_layout'] ?? Setting::get('banner_layout', 'Slider');
        $bannerLayoutSlug = \Illuminate\Support\Str::slug($bannerLayout);

        $borderRadius = $themeCustomization['border_radius'] ?? Setting::get('border_radius', '8px');

        // Dynamically build font import link and stack
        $fontLinks = [
            'Cairo' => 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap',
            'Tajawal' => 'https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap',
            'Inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
            'Almarai' => 'https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap',
        ];
        $fontStack = "'$fontFamily', system-ui, -apple-system, sans-serif";

        $primaryHover = $this->adjustBrightness($primaryColor, -0.08);
        $primaryLight = $this->adjustBrightness($primaryColor, 0.85);
        $secondaryHover = $this->adjustBrightness($secondaryColor, -0.08);

        // Inject dynamic styles (CSS variables) and font import
        $headInjections[] = '<!-- Dynamic Theme Customization (CSS Variables & Overrides) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="' . ($fontLinks[$fontFamily] ?? $fontLinks['Cairo']) . '" rel="stylesheet">
<style>
  :root {
    --primary-color: ' . $primaryColor . ' !important;
    --primary-hover: ' . $primaryHover . ' !important;
    --primary-light: ' . $primaryLight . ' !important;
    --secondary-color: ' . $secondaryColor . ' !important;
    --secondary-hover: ' . $secondaryHover . ' !important;
    --font-family: ' . $fontStack . ' !important;
    --border-radius: ' . $borderRadius . ' !important;
  }
  html, body {
    font-family: var(--font-family) !important;
  }

  /* Global Border Radius Customization Across Storefront */
  .card, .category-card, .product-card-skeleton, .product-card,
  .hero-slider .slider-container, .hero-slider .slide,
  .price-range-filter, .toolbar input, .toolbar select, .filter-dropdown,
  .category-chip, .cat-chip, .filter-option, .pill,
  .main-category-header, .toolbar, .banner-box, .input-group,
  .search-box input, .cart-item, .order-summary, .checkout-card {
    border-radius: var(--border-radius) !important;
  }

  .btn-primary, .btn-add, .call-now, button.btn-primary, a.btn-primary,
  .add-to-cart-btn, .btn-add-to-cart, button[type="submit"].btn-primary,
  .card .btn-add, .filter-btn, .empty-state a, .p-btn-add, .p-btn-buy-now,
  .btn-buy-now, #layoutToggleBtn, .search-trigger, .form-control,
  input[type="text"], input[type="number"], select, .btn-quantity {
    border-radius: var(--border-radius) !important;
  }

  /* Buttons & Action Items */
  .btn-primary, .btn-add, .call-now, button.btn-primary, a.btn-primary,
  .bg-indigo-600, .bg-blue-600, .bg-indigo-500, .bg-blue-500,
  .add-to-cart-btn, .btn-add-to-cart, button[type="submit"].btn-primary,
  .card .btn-add, .filter-btn, .empty-state a {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: #ffffff !important;
  }
  .btn-primary:hover, .btn-add:hover, .call-now:hover, button.btn-primary:hover,
  a.btn-primary:hover, .card .btn-add:hover, .filter-btn:hover, .empty-state a:hover {
    background-color: var(--primary-hover) !important;
    border-color: var(--primary-hover) !important;
  }

  /* Active Category Chips & Filter Options */
  .category-chip.active, .filter-option.active, .pill.active, .cat-chip.active {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px color-mix(in srgb, var(--primary-color) 25%, transparent) !important;
  }
  .category-chip:hover, .filter-option:hover {
    border-color: var(--primary-color) !important;
    color: var(--primary-color) !important;
  }

  /* Price Range Sliders */
  .slider-input::-webkit-slider-thumb {
    background-color: var(--primary-color) !important;
    box-shadow: 0 2px 6px color-mix(in srgb, var(--primary-color) 35%, transparent) !important;
  }
  .slider-input::-moz-range-thumb {
    background-color: var(--primary-color) !important;
    box-shadow: 0 2px 6px color-mix(in srgb, var(--primary-color) 35%, transparent) !important;
  }
  .price-labels strong, .price-max {
    color: var(--primary-color) !important;
  }

  /* Nav Links & Header Icons */
  .nav a.active, .nav a:hover {
    background: var(--primary-light) !important;
    color: var(--primary-color) !important;
  }
  .icon-round, .icon-round i, .icon-round svg, .icons a, .icons a i,
  #cartIcon, #cartIcon i, .search-trigger, .search-trigger i,
  .lang-switcher, .fa-search, .fa-shopping-cart {
    color: var(--primary-color) !important;
    border-color: color-mix(in srgb, var(--primary-color) 40%, #e5e7eb) !important;
  }
  .icon-round:hover, .icon-round:hover i, .icons a:hover, #cartIcon:hover, .search-trigger:hover {
    background-color: var(--primary-light) !important;
    color: var(--primary-hover) !important;
    border-color: var(--primary-color) !important;
  }

  /* Text & Border Overrides */
  .text-primary, .card .price, .more, .more:hover, a.more, .brand span {
    color: var(--primary-color) !important;
  }

  /* === HEADER LAYOUT 1: CLASSIC (CLEAN STANDARD) === */
  header.header-layout-classic {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
  }

  /* === HEADER LAYOUT 2: CENTERED (BALANCED LOGO CENTER) === */
  @media (min-width: 769px) {
    header.header-layout-centered {
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      text-align: center !important;
      gap: 12px !important;
      padding-top: 14px !important;
      padding-bottom: 14px !important;
      position: relative !important;
    }
    header.header-layout-centered .brand {
      order: 1 !important;
      margin: 0 auto !important;
    }
    header.header-layout-centered .nav {
      order: 2 !important;
      justify-content: center !important;
      width: 100% !important;
      border-top: 1px solid #f1f5f9 !important;
      padding-top: 10px !important;
    }
    header.header-layout-centered .icons {
      position: absolute !important;
      inset-inline-start: 24px !important;
      top: 18px !important;
      order: 3 !important;
    }
  }
  @media (max-width: 768px) {
    header.header-layout-centered {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      position: sticky !important;
      top: 0 !important;
      z-index: 50 !important;
    }
    header.header-layout-centered .menu-toggle {
      order: 1 !important;
    }
    header.header-layout-centered .brand {
      order: 2 !important;
      margin: 0 auto !important;
    }
    header.header-layout-centered .icons {
      order: 3 !important;
      margin-inline-start: 0 !important;
    }
  }

  /* === HERO BANNER LAYOUT 1: SLIDER === */
  .hero-slider.banner-layout-slider .slider-container {
    position: relative;
  }

  /* === HERO BANNER LAYOUT 2: GRID === */
  .hero-slider.banner-layout-grid {
    background: transparent !important;
    padding: 0 !important;
  }
  .hero-slider.banner-layout-grid .slider-container {
    max-width: 1100px !important;
    margin: 0 auto !important;
    background: transparent !important;
    box-shadow: none !important;
    overflow: visible !important;
    border-radius: 0 !important;
  }
  .hero-slider.banner-layout-grid .slides {
    display: grid !important;
    grid-template-columns: 1fr 2fr !important;
    grid-template-rows: 1fr 1fr !important;
    gap: 8px !important;
    width: 100% !important;
    transform: none !important;
    direction: rtl !important;
    align-items: stretch !important;
  }
  .hero-slider.banner-layout-grid .slide {
    display: block !important;
    position: relative !important;
    opacity: 1 !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06) !important;
    height: 100% !important;
    margin: 0 !important;
  }
  .hero-slider.banner-layout-grid .slide img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    display: block !important;
  }
  
  /* Slide 1: Main Large Banner on RIGHT */
  .hero-slider.banner-layout-grid .slide:nth-child(1) {
    grid-column: 2 !important;
    grid-row: 1 / span 2 !important;
    min-height: 200px !important;
    aspect-ratio: 16/10 !important;
  }
  
  /* Slide 2: Top Small Side Banner on LEFT */
  .hero-slider.banner-layout-grid .slide:nth-child(2) {
    grid-column: 1 !important;
    grid-row: 1 !important;
    height: 100% !important;
  }
  
  /* Slide 3: Bottom Small Side Banner on LEFT */
  .hero-slider.banner-layout-grid .slide:nth-child(3) {
    grid-column: 1 !important;
    grid-row: 2 !important;
    height: 100% !important;
  }

  .hero-slider.banner-layout-grid .slider-btn,
  .hero-slider.banner-layout-grid .slider-dots {
    display: none !important;
  }

  /* Mobile responsiveness for Grid: Large Banner on Right, Small Banners on Left */
  @media (max-width: 768px) {
    .hero-slider.banner-layout-grid .slides {
      grid-template-columns: 1fr 1.8fr !important;
      grid-template-rows: 1fr 1fr !important;
      gap: 6px !important;
    }
    .hero-slider.banner-layout-grid .slide {
      border-radius: 8px !important;
    }
    .hero-slider.banner-layout-grid .slide:nth-child(1) {
      min-height: 140px !important;
      aspect-ratio: 4/3 !important;
    }
    .hero-slider.banner-layout-grid .slide:nth-child(2) {
      height: 100% !important;
    }
    .hero-slider.banner-layout-grid .slide:nth-child(3) {
      height: 100% !important;
    }
  }

  /* === HERO BANNER LAYOUT 3: SINGLE BANNER === */
  .hero-slider.banner-layout-single-banner {
    width: 100% !important;
    background: transparent !important;
    padding: 0 !important;
  }
  .hero-slider.banner-layout-single-banner .slider-container {
    max-width: 1100px !important;
    width: 100% !important;
    border-radius: 16px !important;
    box-shadow: 0 8px 30px rgba(0,0,0,0.08) !important;
    margin: 0 auto !important;
    overflow: hidden !important;
  }
  .hero-slider.banner-layout-single-banner .slides {
    display: block !important;
    transform: none !important;
    width: 100% !important;
  }
  .hero-slider.banner-layout-single-banner .slide {
    display: none !important;
  }
  .hero-slider.banner-layout-single-banner .slide:first-child {
    display: block !important;
    opacity: 1 !important;
    position: relative !important;
    width: 100% !important;
    height: 70vh !important;
    min-height: 500px !important;
    max-height: 700px !important;
  }
  .hero-slider.banner-layout-single-banner .slide:first-child img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    object-position: center !important;
    display: block !important;
  }
  .hero-slider.banner-layout-single-banner .slider-btn,
  .hero-slider.banner-layout-single-banner .slider-dots {
    display: none !important;
  }

  @media (max-width: 768px) {
    .hero-slider.banner-layout-single-banner .slider-container {
      border-radius: 12px !important;
    }
    .hero-slider.banner-layout-single-banner .slide:first-child {
      height: 65vh !important;
      min-height: 460px !important;
      max-height: 580px !important;
    }
  }
</style>';

        // Inject header layout class into <header> element
        $html = preg_replace('/<header\s+class="([^"]*header[^"]*)"/i', '<header class="$1 header-layout-' . $headerLayoutLower . '"', $html);

        // Inject banner layout class into <section class="hero-slider">
        $html = preg_replace('/class="hero-slider"/i', 'class="hero-slider banner-layout-' . $bannerLayoutSlug . '"', $html);

        // Inject Favicon dynamically
        $faviconUrl = $favicon ? asset('storage/' . $favicon) : '/favicon.ico';
        $faviconHtml = '<link rel="icon" type="image/x-icon" href="' . e($faviconUrl) . '">';
        
        // Inject CSRF Token dynamically
        $csrfToken = csrf_token();
        $html = str_ireplace('<meta name="csrf-token" content="">', '<meta name="csrf-token" content="' . $csrfToken . '">', $html);

        // Replace existing icon link if present, or add to headInjections
        if (preg_match('/<link[^>]*rel=["\'](shortcut )?icon["\'][^>]*>/i', $html)) {
            $html = preg_replace('/<link[^>]*rel=["\'](shortcut )?icon["\'][^>]*>/i', $faviconHtml, $html);
        } else {
            $headInjections[] = $faviconHtml;
        }

        // Dynamically replace store name & logo in body elements if they exist
        $html = preg_replace('/<span id="siteName">.*?<\/span>/i', '<span id="siteName">' . e($storeName) . '</span>', $html);
        if ($logo) {
            $logoUrl = asset('storage/' . $logo);
            $html = preg_replace('/(<img id="siteLogo"[^>]*src=")[^"]*("[^>]*>)/i', '$1' . e($logoUrl) . '$2', $html);
        }

        // Google Analytics
        if ($googleAnalyticsId) {
            $headInjections[] = '<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($googleAnalyticsId) . '"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag(\'js\', new Date());
  gtag(\'config\', \'' . e($googleAnalyticsId) . '\');
</script>
<!-- End Google Analytics -->';
        }

        // Facebook Pixel
        if ($facebookPixelId) {
            $pixelIds = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $facebookPixelId)));
            if (!empty($pixelIds)) {
                $inits = '';
                $noscripts = '';
                foreach ($pixelIds as $pid) {
                    $escapedPid = e($pid);
                    $inits .= "  fbq('init', '{$escapedPid}');\n";
                    $noscripts .= '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . $escapedPid . '&ev=PageView&noscript=1"/></noscript>';
                }
                $headInjections[] = '<!-- Facebook Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,\'script\',
  \'https://connect.facebook.net/en_US/fbevents.js\');
' . $inits . '  fbq(\'track\', \'PageView\');
</script>';
                $bodyInjections[] = $noscripts;
            }
        }

        // TikTok Pixel
        if ($tiktokPixelId) {
            $pixelIds = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $tiktokPixelId)));
            if (!empty($pixelIds)) {
                $loads = '';
                foreach ($pixelIds as $pid) {
                    $escapedPid = e($pid);
                    $loads .= "    ttq.load('{$escapedPid}');\n";
                }
                $headInjections[] = '<!-- TikTok Pixel Code -->
<script>
  !function (w, d, t) {
    w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
' . $loads . '    ttq.page();
  }(window, document, \'ttq\');
</script>
<!-- End TikTok Pixel Code -->';
            }
        }

        // Handle Product Details Page specific SEO (JSON-LD & Title/Description replacement)
        if ($page === 'product.html' && $request->has('id')) {
            $productId = $request->query('id');
            $product = Product::find($productId);

            if ($product) {
                // Structured Data (JSON-LD) for Product Schema
                $imageUrl = $product->main_image_path 
                    ? asset('storage/' . $product->main_image_path) 
                    : ($product->image_url ?: 'https://dummyimage.com/600x400/e5e7eb/9ca3af.png&text=No+Image');
                
                $description = Str::limit(strip_tags($product->description), 160);
                
                $price = $product->price_after ?? $product->price;

                $jsonLd = [
                    '@context' => 'https://schema.org/',
                    '@type' => 'Product',
                    'name' => $product->name,
                    'image' => $imageUrl,
                    'description' => $description,
                    'sku' => 'PROD-' . $product->id,
                    'offers' => [
                        '@type' => 'Offer',
                        'url' => $request->fullUrl(),
                        'priceCurrency' => 'EGP',
                        'price' => $price,
                        'availability' => $product->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        'priceValidUntil' => '2027-12-31'
                    ]
                ];

                $headInjections[] = '<!-- Structured Data (JSON-LD) -->
<script type="application/ld+json">
' . json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '
</script>';

                // Replace Page Title dynamically
                $titlePattern = '/<title>.*?<\/title>/i';
                $newTitle = '<title>' . e($product->name) . ' - ' . e($storeName) . '</title>';
                if (preg_match($titlePattern, $html)) {
                    $html = preg_replace($titlePattern, $newTitle, $html);
                } else {
                    $headInjections[] = $newTitle;
                }

                // Add Meta Description
                $metaDesc = '<meta name="description" content="' . e($description) . '">';
                $html = str_ireplace('</head>', $metaDesc . "\n</head>", $html);
            }
        } else {
            // General pages: replace title with store name
            $titlePattern = '/<title>.*?<\/title>/i';
            $newTitle = '<title>' . e($storeName) . '</title>';
            if (preg_match($titlePattern, $html)) {
                $html = preg_replace($titlePattern, $newTitle, $html);
            } else {
                $headInjections[] = $newTitle;
            }
        }

        // Enable SEO Crawling - Replace "noindex" robots tag with indexable one
        $html = str_ireplace('<meta name="robots" content="noindex">', '<meta name="robots" content="index, follow">', $html);

        // Inject Head Scripts
        if (!empty($headInjections)) {
            $headContent = implode("\n", $headInjections);
            $html = str_ireplace('</head>', $headContent . "\n</head>", $html);
        }

        // Inject Body Scripts (e.g. facebook noscript)
        if (!empty($bodyInjections)) {
            $bodyContent = implode("\n", $bodyInjections);
            // Inject immediately after <body> tag
            if (preg_match('/<body[^>]*>/i', $html, $matches)) {
                $bodyTag = $matches[0];
                $html = str_ireplace($bodyTag, $bodyTag . "\n" . $bodyContent, $html);
            }
        }

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Generate dynamic sitemap.xml for the tenant.
     */
    public function sitemap(Request $request)
    {
        $categories = Category::all();
        $products = Product::all();
        $host = $request->getSchemeAndHttpHost();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Main Home (Shop Index)
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . $host . '/shop/</loc>' . "\n";
        $xml .= '    <priority>1.0</priority>' . "\n";
        $xml .= '    <changefreq>daily</changefreq>' . "\n";
        $xml .= '  </url>' . "\n";

        // Categories List
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . $host . '/shop/categories.html</loc>' . "\n";
        $xml .= '    <priority>0.8</priority>' . "\n";
        $xml .= '    <changefreq>weekly</changefreq>' . "\n";
        $xml .= '  </url>' . "\n";

        // Products List
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . $host . '/shop/products.html</loc>' . "\n";
        $xml .= '    <priority>0.8</priority>' . "\n";
        $xml .= '    <changefreq>daily</changefreq>' . "\n";
        $xml .= '  </url>' . "\n";

        // Contact Page
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . $host . '/shop/contact.html</loc>' . "\n";
        $xml .= '    <priority>0.5</priority>' . "\n";
        $xml .= '    <changefreq>monthly</changefreq>' . "\n";
        $xml .= '  </url>' . "\n";

        // Categories URLs
        foreach ($categories as $category) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . $host . '/shop/category-products.html?category_id=' . $category->id . '</loc>' . "\n";
            $xml .= '    <priority>0.6</priority>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        // Products URLs
        foreach ($products as $product) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . $host . '/shop/product.html?id=' . $product->id . '</loc>' . "\n";
            $xml .= '    <priority>0.7</priority>' . "\n";
            $xml .= '    <changefreq>daily</changefreq>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return response($xml)->header('Content-Type', 'application/xml');
    }

    /**
     * Serve dynamic robots.txt pointing to the tenant's dynamic sitemap.
     */
    public function robots(Request $request)
    {
        $host = $request->getSchemeAndHttpHost();

        $content = "User-agent: *\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /checkout\n";
        $content .= "Disallow: /order-success\n";
        $content .= "Allow: /\n\n";
        $content .= "Sitemap: " . $host . "/sitemap.xml\n";

        return response($content)->header('Content-Type', 'text/plain');
    }

    /**
     * Serve the enhanced cart page.
     */
    public function cart(Request $request)
    {
        return $this->servePage($request, 'cart.html');
    }
}
