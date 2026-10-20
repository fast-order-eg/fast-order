<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ThemeController extends Controller
{
    /**
     * Display the merchant theme customization page.
     */
    public function index()
    {
        $themeCustomizationJson = Setting::get('theme_customization');
        $themeService = app(\App\Services\ThemeService::class);
        $activeTheme = $themeService->getActiveTheme();
        $themes = $themeService->getAllThemes();
        
        $themeCustomization = $themeCustomizationJson ? json_decode($themeCustomizationJson, true) : [
            'primary_color' => Setting::get('primary_color', '#F97316'),
            'secondary_color' => Setting::get('secondary_color', '#1F2937'),
            'background_color' => Setting::get('background_color', '#FFFFFF'),
            'font_family' => Setting::get('font_family', 'Almarai'),
            'header_layout' => Setting::get('header_layout', 'Classic'),
            'banner_layout' => Setting::get('banner_layout', 'Slider'),
            'border_radius' => Setting::get('border_radius', '8px'),
        ];

        return Inertia::render('Merchant/Theme/Index', [
            'themeCustomization' => $themeCustomization,
            'activeTheme' => $activeTheme,
            'themes' => $themes,
        ]);
    }

    /**
     * Update the merchant theme settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'primary_color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'background_color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'font_family' => ['required', 'string', 'in:Almarai,Cairo,Tajawal,Inter,Roboto'],
            'header_layout' => ['required', 'string', 'in:Classic,Centered'],
            'banner_layout' => ['required', 'string', 'in:Slider,Grid,Single Banner'],
            'border_radius' => ['nullable', 'string', 'in:0px,4px,8px,12px,16px,20px'],
        ], [
            'primary_color.required' => 'اللون الرئيسي مطلوب',
            'primary_color.regex' => 'صيغة اللون الرئيسي غير صالحة',
            'secondary_color.required' => 'اللون الثانوي مطلوب',
            'secondary_color.regex' => 'صيغة اللون الثانوي غير صالحة',
            'background_color.required' => 'لون الخلفية مطلوب',
            'background_color.regex' => 'صيغة لون الخلفية غير صالحة',
            'font_family.required' => 'خط الكتابة مطلوب',
            'font_family.in' => 'الخط المختار غير مدعوم',
            'header_layout.required' => 'تصميم الهيدر مطلوب',
            'header_layout.in' => 'تصميم الهيدر المختار غير مدعوم',
            'banner_layout.required' => 'تصميم البانر مطلوب',
            'banner_layout.in' => 'تصميم البانر المختار غير مدعوم',
            'border_radius.in' => 'نسبة الحواف المختارة غير صالحة',
        ]);

        $themeSettings = $request->only([
            'primary_color',
            'secondary_color',
            'background_color',
            'font_family',
            'header_layout',
            'banner_layout',
            'border_radius',
        ]);

        // Save JSON theme settings
        Setting::set('theme_customization', json_encode($themeSettings));

        // Also save individual keys so storefront reads them seamlessly
        Setting::set('primary_color', $request->primary_color);
        Setting::set('secondary_color', $request->secondary_color);
        Setting::set('background_color', $request->background_color);
        Setting::set('font_family', $request->font_family);
        Setting::set('header_layout', $request->header_layout);
        Setting::set('banner_layout', $request->banner_layout);
        Setting::set('border_radius', $request->border_radius ?? '8px');

        return redirect()->route('merchant.theme.index')->with('success', 'تم حفظ مظهر وتصميم المتجر بنجاح ✓');
    }
}
