<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Tenant;
use App\Http\Requests\UpdateSettingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Display the merchant settings.
     */
    public function index(Request $request)
    {
        $tenant = app()->bound(Tenant::class) ? app(Tenant::class) : null;
        if (!$tenant && auth()->check()) {
            $user = auth()->user();
            $tenant = $user->ownedTenants()->first() ?? $user->currentTenant;
        }
        $tenantId = $tenant?->id ?? session('tenant_id') ?? config('tenant.id');

        $settings = [
            'logo' => Setting::get('logo', null, $tenantId),
            'logo_url' => Setting::get('logo', null, $tenantId) ? asset('storage/' . Setting::get('logo', null, $tenantId)) : ($tenant?->logo ? asset('storage/' . $tenant->logo) : null),
            'phone' => Setting::get('phone', $tenant?->phone ?? '', $tenantId),
            'whatsapp' => Setting::get('whatsapp', $tenant?->whatsapp ?? $tenant?->phone ?? '', $tenantId),
            'store_name' => Setting::get('store_name', $tenant?->name ?? 'متجري', $tenantId),
            'facebook_pixel_id' => Setting::get('facebook_pixel_id', '', $tenantId),
            'tiktok_pixel_id' => Setting::get('tiktok_pixel_id', '', $tenantId),
            'snapchat_pixel_id' => Setting::get('snapchat_pixel_id', '', $tenantId),
            'google_analytics_id' => Setting::get('google_analytics_id', '', $tenantId),
            'facebook_page' => Setting::get('facebook_page', '', $tenantId),
            'instagram_page' => Setting::get('instagram_page', '', $tenantId),
            'tiktok_page' => Setting::get('tiktok_page', '', $tenantId),
            'google_maps_url' => Setting::get('google_maps_url', '', $tenantId),
            'address' => Setting::get('address', '', $tenantId),
            'main_categories' => Category::getMainCategories(),
        ];

        return Inertia::render('Merchant/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the merchant settings.
     */
    public function update(UpdateSettingRequest $request)
    {
        $tenant = app()->bound(Tenant::class) ? app(Tenant::class) : null;
        if (!$tenant && auth()->check()) {
            $user = auth()->user();
            $tenant = $user->ownedTenants()->first() ?? $user->currentTenant;
        }
        $tenantId = $tenant?->id ?? session('tenant_id') ?? config('tenant.id');

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $oldLogo = Setting::get('logo', null, $tenantId);
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = \App\Services\ImageCompressionService::compressAndStore($request->file('logo'), 'settings', 'public');
            Setting::set('logo', $path, 'general', $tenantId);
        }

        // Save scalar settings
        $keys = [
            'phone', 'whatsapp', 'store_name', 
            'facebook_pixel_id', 'tiktok_pixel_id', 'snapchat_pixel_id', 'google_analytics_id',
            'facebook_page', 'instagram_page', 'tiktok_page', 'google_maps_url', 'address'
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                $val = $request->input($key);
                if (is_bool($val)) {
                    $val = $val ? '1' : '0';
                }

                if (is_string($val)) {
                    $val = trim($val);
                }

                // Smart Extraction for FB Pixel if full JS snippet pasted
                if ($key === 'facebook_pixel_id' && $val) {
                    if (str_contains($val, 'fbq') || str_contains($val, 'script') || str_contains($val, 'facebook.com')) {
                        preg_match_all('/fbq\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"]?(\d+)[\'"]?|[?&]id=(\d+)|\b(\d{13,17})\b/i', $val, $matches);
                        $extracted = array_filter(array_merge($matches[1], $matches[2], $matches[3]));
                        if (!empty($extracted)) {
                            $val = implode("\n", array_unique($extracted));
                        }
                    }
                }

                // Smart Extraction for TikTok Pixel if full JS snippet pasted
                if ($key === 'tiktok_pixel_id' && $val) {
                    if (str_contains($val, 'ttq') || str_contains($val, 'script') || str_contains($val, 'analytics.tiktok.com')) {
                        preg_match_all('/ttq\.load\s*\(\s*[\'"]([a-zA-Z0-9_-]+)[\'"]\s*\)|[?&]sdkid=([a-zA-Z0-9_-]+)/i', $val, $matches);
                        $extracted = array_filter(array_merge($matches[1], $matches[2]));
                        if (!empty($extracted)) {
                            $val = implode("\n", array_unique($extracted));
                        }
                    }
                }

                // Smart Extraction for Snapchat Pixel if full JS snippet pasted
                if ($key === 'snapchat_pixel_id' && $val) {
                    if (str_contains($val, 'snaptr') || str_contains($val, 'script') || str_contains($val, 'sc-static.net')) {
                        preg_match_all('/snaptr\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"]([a-zA-Z0-9_-]+)[\'"]/i', $val, $matches);
                        $extracted = array_filter($matches[1]);
                        if (!empty($extracted)) {
                            $val = implode("\n", array_unique($extracted));
                        }
                    }
                }

                // Smart Extraction for Google Analytics ID if full JS snippet pasted
                if ($key === 'google_analytics_id' && $val) {
                    if (str_contains($val, 'gtag') || str_contains($val, 'script') || str_contains($val, 'googletagmanager.com')) {
                        preg_match_all('/[\'"]?(G-[a-zA-Z0-9]+|UA-\d+-\d+)[\'"]?/i', $val, $matches);
                        $extracted = array_filter($matches[1]);
                        if (!empty($extracted)) {
                            $val = $extracted[0];
                        }
                    }
                }

                // Auto prepend https:// for social URLs if missing
                if (in_array($key, ['facebook_page', 'instagram_page', 'tiktok_page', 'google_maps_url'], true) && !empty($val)) {
                    if (!preg_match('~^(?:f|ht)tps?://~i', $val)) {
                        $val = 'https://' . ltrim($val, '/');
                    }
                }

                Setting::set($key, $val ?? '', 'general', $tenantId);
            }
        }

        // Save main categories (array of strings)
        if ($request->has('main_categories')) {
            $cats = $request->input('main_categories', []);
            // Filter empty ones
            $cats = array_values(array_filter(array_map('trim', (array) $cats)));
            Category::saveMainCategories($cats);
        }

        return redirect()->route('settings.index')->with('success', 'تم حفظ الإعدادات والبيكسلات بنجاح ✓');
    }
}
