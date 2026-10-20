<?php

namespace App\Http\Controllers;

use App\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ThemeController extends Controller
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
     * Display a listing of all available store themes and active theme configuration.
     */
    public function index(Request $request)
    {
        $themes = $this->themeService->getAllThemes();
        $activeTheme = $this->themeService->getActiveTheme();
        $cssVariables = $this->themeService->getThemeCssVariables();

        $data = [
            'themes' => $themes,
            'active_theme' => $activeTheme,
            'active_theme_config' => $themes[$activeTheme] ?? $this->themeService->getThemeConfig($activeTheme),
            'css_variables' => $cssVariables,
        ];

        // 1. React / Inertia Support (Check Inertia first)
        if ($request->header('X-Inertia') || (class_exists(\Inertia\Inertia::class) && !$request->wantsJson())) {
            return \Inertia\Inertia::render('Merchant/Themes/Index', $data);
        }

        // 2. API or AJAX Request (non-Inertia only)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        }

        // 3. Blade Views Fallback
        if (view()->exists('merchant.themes.index')) {
            return view('merchant.themes.index', $data);
        }

        if (view()->exists('themes.index')) {
            return view('themes.index', $data);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Themes retrieved successfully.',
            'data' => $data,
        ]);
    }

    /**
     * Get detailed configuration for a specific theme by slug.
     */
    public function show(Request $request, string $slug)
    {
        if (!$this->themeService->isThemeAvailable($slug)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'الثيم المطلوب غير متاح'], 404);
            }
            abort(404, 'الثيم المطلوب غير متاح');
        }

        $config = $this->themeService->getThemeConfig($slug);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $config,
            ]);
        }

        if (class_exists(\Inertia\Inertia::class) && ($request->header('X-Inertia') || $request->is('merchant/*'))) {
            return \Inertia\Inertia::render('Merchant/Themes/Show', ['theme' => $config]);
        }

        if (view()->exists('merchant.themes.show')) {
            return view('merchant.themes.show', ['theme' => $config]);
        }

        return response()->json(['status' => 'success', 'data' => $config]);
    }

    /**
     * Activate a theme (Hot-swap) for the current store/tenant.
     */
    public function activate(Request $request)
    {
        $themeSlug = $request->input('theme') ?? $request->input('slug');

        if (empty($themeSlug)) {
            if ($request->header('X-Inertia')) {
                return Redirect::back()->withErrors(['theme' => 'يرجى تحديد الثيم المطلوب تفعيله']);
            }
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'يرجى تحديد الثيم المطلوب تفعيله'
                ], 422);
            }
            return Redirect::back()->withErrors(['theme' => 'يرجى تحديد الثيم المطلوب تفعيله']);
        }

        if (!$this->themeService->isThemeAvailable($themeSlug)) {
            if ($request->header('X-Inertia')) {
                return Redirect::back()->withErrors(['theme' => 'هذا الثيم غير متاح أو تعذر الوصول لمجلد ملفاته']);
            }
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'هذا الثيم غير متاح أو تعذر الوصول لمجلد ملفاته'
                ], 422);
            }
            return Redirect::back()->withErrors(['theme' => 'هذا الثيم غير متاح أو تعذر الوصول لمجلد ملفاته']);
        }

        // Activate theme via ThemeService
        $this->themeService->setActiveTheme($themeSlug);

        // Cancel any active preview session
        if (session()->has('preview_theme')) {
            session()->forget('preview_theme');
        }

        $themes = $this->themeService->getAllThemes();
        $themeName = $themes[$themeSlug]['name'] ?? $themeSlug;
        $successMessage = "تم تفعيل الثيم بنجاح: {$themeName}";

        if ($request->header('X-Inertia')) {
            return Redirect::back()->with('status', $successMessage)->with('success', $successMessage);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMessage,
                'active_theme' => $themeSlug,
            ]);
        }

        return Redirect::back()->with('status', $successMessage)->with('success', $successMessage);
    }

    /**
     * Alias for activate() method to support RESTful update routing.
     */
    public function update(Request $request)
    {
        return $this->activate($request);
    }

    /**
     * Save theme customizations (CSS variables like colors, typography, and section settings).
     */
    public function customize(Request $request)
    {
        $request->validate([
            'theme' => ['nullable', 'string', 'max:100'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font_family' => ['nullable', 'string', 'max:50'],
            'customizations' => ['nullable', 'array'],
        ], [
            'primary_color.regex' => 'صيغة اللون الأساسي غير صحيحة (مثال: #4f46e5)',
            'secondary_color.regex' => 'صيغة اللون الثانوي غير صحيحة (مثال: #64748b)',
            'accent_color.regex' => 'صيغة اللون المميز غير صحيحة',
        ]);

        $themeSlug = $request->input('theme') ?: $this->themeService->getActiveTheme();

        if (!$this->themeService->isThemeAvailable($themeSlug)) {
            return response()->json(['status' => 'error', 'message' => 'الثيم المحدد غير موجود'], 404);
        }

        $customizations = $request->input('customizations', []);

        if ($request->has('primary_color') && $request->input('primary_color')) {
            $customizations['primary_color'] = $request->input('primary_color');
        }
        if ($request->has('secondary_color') && $request->input('secondary_color')) {
            $customizations['secondary_color'] = $request->input('secondary_color');
        }
        if ($request->has('accent_color') && $request->input('accent_color')) {
            $customizations['accent_color'] = $request->input('accent_color');
        }
        if ($request->has('font_family') && $request->input('font_family')) {
            $customizations['font_family'] = $request->input('font_family');
        }

        $this->themeService->saveThemeCustomizations($themeSlug, $customizations);

        $successMessage = 'تم حفظ إعدادات وتخصيصات الثيم بنجاح';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMessage,
                'css_variables' => $this->themeService->getThemeCssVariables(),
            ]);
        }

        return Redirect::back()->with('status', $successMessage)->with('success', $successMessage);
    }

    /**
     * Live preview a theme without changing the global store setting.
     */
    public function preview(Request $request, string $slug)
    {
        if (!$this->themeService->isThemeAvailable($slug)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'الثيم غير متاح للمعاينة'], 404);
            }
            return Redirect::back()->withErrors(['theme' => 'الثيم غير متاح للمعاينة']);
        }

        session()->put('preview_theme', $slug);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'تم تفعيل وضع المعاينة للثيم: ' . $slug,
                'preview_theme' => $slug,
            ]);
        }

        return Redirect::to('/shop/index.html')->with('status', 'أنت الآن في وضع المعاينة للثيم: ' . $slug);
    }

    /**
     * Exit theme preview mode and return to active theme.
     */
    public function cancelPreview(Request $request)
    {
        session()->forget('preview_theme');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'تم إلغاء وضع المعاينة والرجوع للثيم النشط',
            ]);
        }

        return Redirect::back()->with('status', 'تم إلغاء وضع المعاينة والرجوع للثيم النشط');
    }

    /**
     * Reset customizations for the active theme to default values.
     */
    public function reset(Request $request)
    {
        $themeSlug = $request->input('theme') ?: $this->themeService->getActiveTheme();

        $this->themeService->saveThemeCustomizations($themeSlug, []);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'تمت استعادة الإعدادات الافتراضية للثيم بنجاح',
                'css_variables' => $this->themeService->getThemeCssVariables(),
            ]);
        }

        return Redirect::back()->with('status', 'تمت استعادة الإعدادات الافتراضية للثيم بنجاح');
    }
}
