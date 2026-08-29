<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeService
{
    /**
     * In-memory cache during request lifecycle to prevent duplicate database queries.
     * @var array<string, string>
     */
    protected static array $activeThemesCache = [];

    /**
     * Get the active theme slug for the current store/tenant with fallback to default.
     */
    public function getActiveTheme($tenantId = null): string
    {
        // 1. Check if there is a preview theme in session (for live preview in theme customizer)
        if (session()->has('preview_theme')) {
            $previewSlug = session()->get('preview_theme');
            if ($this->isThemeAvailable($previewSlug)) {
                return $previewSlug;
            }
        }

        if ($tenantId === null) {
            $tenantId = session()->get('tenant_id') ?? config('tenant.id') ?? 'global';
        }

        // 2. Check in-memory static cache for request duration
        $cacheKey = "tenant_{$tenantId}";
        if (isset(self::$activeThemesCache[$cacheKey])) {
            return self::$activeThemesCache[$cacheKey];
        }

        // 3. Check Laravel Cache / Database via Setting model
        $activeTheme = Cache::remember("theme_active_{$tenantId}", 3600, function () use ($tenantId) {
            $dbTenantId = ($tenantId === 'global') ? null : $tenantId;
            return Setting::get('active_theme', 'default', $dbTenantId);
        });

        // 4. Validate theme availability; fallback to 'default' if missing or corrupted
        if (!$this->isThemeAvailable($activeTheme)) {
            $activeTheme = 'default';
        }

        self::$activeThemesCache[$cacheKey] = $activeTheme;

        return $activeTheme;
    }

    /**
     * Set and activate a new theme for the specified store/tenant.
     */
    public function setActiveTheme(string $slug, $tenantId = null): bool
    {
        if (!$this->isThemeAvailable($slug)) {
            return false;
        }

        if ($tenantId === null) {
            $tenantId = session()->get('tenant_id') ?? config('tenant.id');
        }

        // Save in settings
        Setting::set('active_theme', $slug, 'theme', $tenantId);

        // Clear all relevant caches for this tenant
        $this->clearThemeCache($tenantId);

        return true;
    }

    /**
     * Check if a theme is available either on disk or in built-in presets.
     */
    public function isThemeAvailable(string $slug): bool
    {
        if ($slug === 'default') {
            return true;
        }

        // Check if directory exists in themes folder
        $themeDir = resource_path("views/shop/themes/{$slug}");
        if (File::isDirectory($themeDir) && File::exists("{$themeDir}/theme.json")) {
            return true;
        }

        // Check if theme is in built-in presets
        $builtInThemes = $this->getBuiltInThemePresets();
        return isset($builtInThemes[$slug]);
    }

    /**
     * Get the complete configuration array for a theme (from JSON or built-in preset).
     */
    public function getThemeConfig(?string $slug = null, $tenantId = null): array
    {
        if ($slug === null) {
            $slug = $this->getActiveTheme($tenantId);
        }

        if ($tenantId === null) {
            $tenantId = session()->get('tenant_id') ?? config('tenant.id') ?? 'global';
        }

        return Cache::remember("theme_config_{$tenantId}_{$slug}", 3600, function () use ($slug) {
            // 1. Try reading from theme.json file in theme directory
            $jsonPath = resource_path("views/shop/themes/{$slug}/theme.json");
            if (File::exists($jsonPath)) {
                $content = File::get($jsonPath);
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $this->mergeWithDefaults($decoded);
                }
            }

            // 2. Fallback to built-in presets
            $builtIn = $this->getBuiltInThemePresets();
            if (isset($builtIn[$slug])) {
                return $this->mergeWithDefaults($builtIn[$slug]);
            }

            // 3. Fallback to default theme preset
            return $this->mergeWithDefaults($builtIn['default']);
        });
    }

    /**
     * Get all available themes for the store (used in admin/merchant panels).
     */
    public function getAllThemes(): array
    {
        $themes = $this->getBuiltInThemePresets();

        // Scan themes directory on disk
        $themesDir = resource_path('views/shop/themes');
        if (File::isDirectory($themesDir)) {
            $directories = File::directories($themesDir);
            foreach ($directories as $dir) {
                $slug = basename($dir);
                $jsonPath = "{$dir}/theme.json";
                if (File::exists($jsonPath)) {
                    $decoded = json_decode(File::get($jsonPath), true);
                    if (is_array($decoded) && isset($decoded['name'])) {
                        $themes[$slug] = $this->mergeWithDefaults($decoded);
                    }
                }
            }
        }

        return $themes;
    }

    /**
     * Get customized CSS variables for the active theme merged with store settings.
     */
    public function getThemeCssVariables($tenantId = null): array
    {
        if ($tenantId === null) {
            $tenantId = session()->get('tenant_id') ?? config('tenant.id') ?? 'global';
        }

        $activeTheme = $this->getActiveTheme($tenantId);

        return Cache::remember("theme_css_vars_{$tenantId}_{$activeTheme}", 3600, function () use ($activeTheme, $tenantId) {
            $dbTenantId = ($tenantId === 'global') ? null : $tenantId;
            $config = $this->getThemeConfig($activeTheme, $dbTenantId);
            $defaultVars = $config['css_variables'] ?? [];

            // Store settings override theme defaults
            $primaryColor = Setting::get('primary_color', $defaultVars['primary_color'] ?? '#4f46e5', $dbTenantId);
            $secondaryColor = Setting::get('secondary_color', $defaultVars['secondary_color'] ?? '#64748b', $dbTenantId);
            $accentColor = Setting::get('accent_color', $defaultVars['accent_color'] ?? '#f59e0b', $dbTenantId);
            $fontFamily = Setting::get('font_family', $defaultVars['font_family'] ?? 'Cairo', $dbTenantId);

            // Check if there are theme-specific custom color overrides stored in JSON
            $themeCustomsJson = Setting::get("theme_customs_{$activeTheme}", null, $dbTenantId);
            if ($themeCustomsJson) {
                $customs = json_decode($themeCustomsJson, true);
                if (is_array($customs)) {
                    $primaryColor = $customs['primary_color'] ?? $primaryColor;
                    $secondaryColor = $customs['secondary_color'] ?? $secondaryColor;
                    $accentColor = $customs['accent_color'] ?? $accentColor;
                    $fontFamily = $customs['font_family'] ?? $fontFamily;
                }
            }

            return [
                'primary_color' => $primaryColor,
                'primary_hover' => $this->adjustBrightness($primaryColor, -0.08),
                'primary_light' => $this->adjustBrightness($primaryColor, 0.85),
                'secondary_color' => $secondaryColor,
                'secondary_hover' => $this->adjustBrightness($secondaryColor, -0.08),
                'accent_color' => $accentColor,
                'background_color' => $defaultVars['background_color'] ?? '#ffffff',
                'text_color' => $defaultVars['text_color'] ?? '#1e293b',
                'border_color' => $defaultVars['border_color'] ?? '#e2e8f0',
                'border_radius' => $defaultVars['border_radius'] ?? '0.5rem',
                'font_family' => $fontFamily,
            ];
        });
    }

    /**
     * Generate HTML style tags and Google font links for injecting into storefront head.
     */
    public function injectThemeStyles($tenantId = null): string
    {
        $vars = $this->getThemeCssVariables($tenantId);
        $fontFamily = $vars['font_family'] ?? 'Cairo';

        $fontLinks = [
            'Cairo' => 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap',
            'Tajawal' => 'https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap',
            'Inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
            'Almarai' => 'https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap',
        ];

        $fontUrl = $fontLinks[$fontFamily] ?? $fontLinks['Cairo'];
        $fontStack = "'{$fontFamily}', system-ui, -apple-system, sans-serif";

        $html = '<!-- Fast Order Phase 66 Theme Architecture Styles -->';
        $html .= "\n" . '<link rel="preconnect" href="https://fonts.googleapis.com">';
        $html .= "\n" . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        $html .= "\n" . '<link href="' . e($fontUrl) . '" rel="stylesheet">';
        $html .= "\n" . '<style>';
        $html .= "\n  :root {";
        foreach ($vars as $key => $val) {
            if ($key !== 'font_family') {
                $cssKey = str_replace('_', '-', $key);
                $html .= "\n    --{$cssKey}: {$val};";
            }
        }
        $html .= "\n    --font-family: {$fontStack};";
        $html .= "\n  }";
        $html .= "\n  html, body { font-family: var(--font-family) !important; }";
        $html .= "\n" . '</style>';

        return $html;
    }

    /**
     * Resolve the filesystem path of a storefront view/page supporting custom theme overrides.
     * Falls back to default shop templates if active theme override does not exist.
     */
    public function resolveViewPath(string $page, $tenantId = null): string
    {
        $activeTheme = $this->getActiveTheme($tenantId);

        if (!empty($activeTheme)) {
            $customPath = resource_path("views/shop/themes/{$activeTheme}/{$page}");
            if (File::exists($customPath)) {
                return $customPath;
            }
        }

        // Fallback to standard shop templates
        return resource_path("views/shop/{$page}");
    }

    /**
     * Save theme custom settings (colors, typography, section toggles) for the store.
     */
    public function saveThemeCustomizations(string $themeSlug, array $customizations, $tenantId = null): void
    {
        if ($tenantId === null) {
            $tenantId = session()->get('tenant_id') ?? config('tenant.id');
        }

        // Check if updating global primary/secondary colors
        if (isset($customizations['primary_color'])) {
            Setting::set('primary_color', $customizations['primary_color'], 'colors', $tenantId);
        }
        if (isset($customizations['secondary_color'])) {
            Setting::set('secondary_color', $customizations['secondary_color'], 'colors', $tenantId);
        }
        if (isset($customizations['font_family'])) {
            Setting::set('font_family', $customizations['font_family'], 'typography', $tenantId);
        }

        // Store entire customizations json for this theme
        Setting::set("theme_customs_{$themeSlug}", json_encode($customizations, JSON_UNESCAPED_UNICODE), 'theme', $tenantId);

        $this->clearThemeCache($tenantId);
    }

    /**
     * Clear all theme-related caches for a tenant.
     */
    public function clearThemeCache($tenantId = null): void
    {
        if ($tenantId === null) {
            $tenantId = session()->get('tenant_id') ?? config('tenant.id') ?? 'global';
        }

        Cache::forget("theme_active_{$tenantId}");
        Cache::forget("theme_css_vars_{$tenantId}_default");
        foreach (array_keys($this->getBuiltInThemePresets()) as $slug) {
            Cache::forget("theme_config_{$tenantId}_{$slug}");
            Cache::forget("theme_css_vars_{$tenantId}_{$slug}");
        }

        unset(self::$activeThemesCache["tenant_{$tenantId}"]);
        unset(self::$activeThemesCache["tenant_global"]);
    }

    /**
     * Adjust color brightness for hover and light shade CSS variables.
     */
    public function adjustBrightness(string $hex, float $steps): string
    {
        if (abs($steps) < 1) {
            $steps = $steps * 255;
        }
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) < 6) {
            return '#' . str_pad($hex, 6, '0');
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        return '#' . str_pad(dechex(round($r)), 2, '0', STR_PAD_LEFT) .
                     str_pad(dechex(round($g)), 2, '0', STR_PAD_LEFT) .
                     str_pad(dechex(round($b)), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Ensure all required theme keys exist in a theme config array.
     */
    protected function mergeWithDefaults(array $config): array
    {
        $defaultConfig = [
            'name' => 'ثيم غير مسمّى',
            'slug' => 'custom',
            'version' => '1.0.0',
            'author' => 'Fast Order',
            'description' => 'ثيم مخصص لمتجر فاست أوردر',
            'preview_image' => '/shop/images/themes/default-preview.webp',
            'support_rtl' => true,
            'css_variables' => [
                'primary_color' => '#4f46e5',
                'secondary_color' => '#64748b',
                'accent_color' => '#f59e0b',
                'background_color' => '#ffffff',
                'text_color' => '#1e293b',
                'border_color' => '#e2e8f0',
                'border_radius' => '0.5rem',
                'font_family' => 'Cairo',
            ],
            'layouts' => ['index', 'product', 'cart', 'checkout', 'categories', 'search'],
            'sections' => [],
            'settings_schema' => []
        ];

        return array_merge($defaultConfig, $config);
    }

    /**
     * Built-in preset theme definitions for Fast Order platform.
     */
    protected function getBuiltInThemePresets(): array
    {
        return [
            'default' => [
                'name' => 'الثيم الافتراضي (Default Theme)',
                'slug' => 'default',
                'version' => '1.0.0',
                'author' => 'Fast Order Team',
                'description' => 'الثيم الافتراضي السريع والمتجاوب لجميع المتاجر، مصمم لتجربة تسوق سلسة ودعم كامل للغتين العربية والإنجليزية.',
                'preview_image' => '/shop/images/themes/default-preview.webp',
                'support_rtl' => true,
                'css_variables' => [
                    'primary_color' => '#4f46e5',
                    'secondary_color' => '#64748b',
                    'accent_color' => '#f59e0b',
                    'background_color' => '#ffffff',
                    'text_color' => '#1e293b',
                    'border_color' => '#e2e8f0',
                    'border_radius' => '0.5rem',
                    'font_family' => 'Cairo',
                ],
                'layouts' => ['index', 'product', 'cart', 'checkout', 'categories', 'search', 'wishlist', 'order-success', 'tracking', 'account', 'landing', 'promotions', 'contact'],
            ],
            'modern_minimalist' => [
                'name' => 'الثيم العصري (Modern Minimalist)',
                'slug' => 'modern_minimalist',
                'version' => '1.1.0',
                'author' => 'Fast Order Design Lab',
                'description' => 'تصميم عصري ونظيف يركز على مساحات الفراغ وعرض صور المنتجات بوضوح عالٍ مع تأثيرات Glassmorphism.',
                'preview_image' => '/shop/images/themes/modern-preview.webp',
                'support_rtl' => true,
                'css_variables' => [
                    'primary_color' => '#0f172a',
                    'secondary_color' => '#475569',
                    'accent_color' => '#3b82f6',
                    'background_color' => '#f8fafc',
                    'text_color' => '#0f172a',
                    'border_color' => '#cbd5e1',
                    'border_radius' => '0.75rem',
                    'font_family' => 'Inter',
                ],
                'layouts' => ['index', 'product', 'cart', 'checkout', 'categories', 'search'],
            ],
            'dark_elegance' => [
                'name' => 'ثيم الأناقة الداكنة (Dark Elegance)',
                'slug' => 'dark_elegance',
                'version' => '1.0.0',
                'author' => 'Fast Order Design Lab',
                'description' => 'تصميم فاخر بالوضع الداكن Dark Mode مع لمسات ذهبية وزرقاء، مثالي لمتاجر العطور والساعات والأزياء الفاخرة.',
                'preview_image' => '/shop/images/themes/dark-preview.webp',
                'support_rtl' => true,
                'css_variables' => [
                    'primary_color' => '#6366f1',
                    'secondary_color' => '#94a3b8',
                    'accent_color' => '#eab308',
                    'background_color' => '#0f172a',
                    'text_color' => '#f8fafc',
                    'border_color' => '#334155',
                    'border_radius' => '0.375rem',
                    'font_family' => 'Tajawal',
                ],
                'layouts' => ['index', 'product', 'cart', 'checkout', 'categories', 'search'],
            ],
            'fresh_market' => [
                'name' => 'ثيم السوق الطازج (Fresh Market)',
                'slug' => 'fresh_market',
                'version' => '1.0.0',
                'author' => 'Fast Order Team',
                'description' => 'تصميم حيوي ومشرق بالألوان الخضراء الطبيعية، مصمم خصيصاً لمتاجر المواد الغذائية والمكملات والمنتجات العضوية.',
                'preview_image' => '/shop/images/themes/fresh-preview.webp',
                'support_rtl' => true,
                'css_variables' => [
                    'primary_color' => '#10b981',
                    'secondary_color' => '#34d399',
                    'accent_color' => '#f59e0b',
                    'background_color' => '#ffffff',
                    'text_color' => '#064e3b',
                    'border_color' => '#a7f3d0',
                    'border_radius' => '1rem',
                    'font_family' => 'Almarai',
                ],
                'layouts' => ['index', 'product', 'cart', 'checkout', 'categories', 'search'],
            ],
            'tech_store' => [
                'name' => 'ثيم التكنولوجيا والمعدات (Tech Pro)',
                'slug' => 'tech_store',
                'version' => '1.0.0',
                'author' => 'Fast Order Team',
                'description' => 'تصميم تقني حاد يتميز بالألوان الزرقاء والسيان مع تقسيمات شبكية دقيقة لعرض المواصفات التقنية للمنتجات والإلكترونيات.',
                'preview_image' => '/shop/images/themes/tech-preview.webp',
                'support_rtl' => true,
                'css_variables' => [
                    'primary_color' => '#0284c7',
                    'secondary_color' => '#38bdf8',
                    'accent_color' => '#f97316',
                    'background_color' => '#ffffff',
                    'text_color' => '#0c4a6e',
                    'border_color' => '#bae6fd',
                    'border_radius' => '0.25rem',
                    'font_family' => 'Roboto',
                ],
                'layouts' => ['index', 'product', 'cart', 'checkout', 'categories', 'search'],
            ]
        ];
    }
}
