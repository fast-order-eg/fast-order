<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DomainController extends Controller
{
    /**
     * Reserved system slugs that cannot be registered by merchants.
     */
    protected array $reservedSlugs = [
        'app',
        'admin',
        'superadmin',
        'www',
        'fastorder',
        'api',
        'system',
        'demo',
        'test',
        'shop',
        'checkout',
        'storefront',
    ];

    /**
     * Resolve the current active tenant for merchant.
     */
    private function getTenant()
    {
        $request = request();
        $subdomain = explode('.', $request->getHost())[0] ?? null;

        // 1. If on tenant subdomain, resolve tenant by subdomain slug
        if ($subdomain && !in_array(strtolower($subdomain), ['app', 'www', 'admin'])) {
            $tenant = Tenant::where('slug', $subdomain)->first();
            if ($tenant) return $tenant;
        }

        // 2. If logged in merchant user, resolve directly from user's tenant
        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            $user = auth()->user();
            $tenant = $user->ownedTenants()->first() ?? $user->currentTenant;
            if ($tenant) return $tenant;
        }

        // 3. If super admin impersonating
        if (session()->has('impersonated_tenant_id')) {
            $tenant = Tenant::find(session('impersonated_tenant_id'));
            if ($tenant) return $tenant;
        }

        if (app()->bound(Tenant::class)) {
            return app(Tenant::class);
        }

        return null;
    }

    /**
     * Show domain change form.
     */
    public function edit(Request $request)
    {
        $tenant = $this->getTenant();

        if (!$tenant) {
            abort(404, 'المتجر غير موجود.');
        }

        $host = $request->getHost();
        $scheme = $request->getScheme();
        $port = $request->getPort();
        $portSuffix = ($port && !in_array($port, [80, 443])) ? ":{$port}" : '';

        // Extract base domain cleanly
        $cleanHost = str_starts_with($host, 'app.') ? substr($host, 4) : $host;
        $parts = explode('.', $cleanHost);
        if (count($parts) >= 2) {
            array_shift($parts); // remove subdomain if present
            $baseDomain = implode('.', $parts);
        } else {
            $baseDomain = $cleanHost;
        }

        if (empty($baseDomain) || $baseDomain === 'localhost' || $baseDomain === '127.0.0.1') {
            $baseDomain = 'fastorder.localhost';
        }

        $currentUrl = "{$scheme}://{$tenant->slug}.{$baseDomain}{$portSuffix}";

        return Inertia::render('Merchant/Domain/Edit', [
            'currentSlug' => $tenant->slug,
            'baseDomain'  => "{$baseDomain}{$portSuffix}",
            'currentUrl'  => $currentUrl,
            'scheme'      => $scheme,
        ]);
    }

    /**
     * Check if a slug is available.
     */
    public function check(Request $request)
    {
        $tenant = $this->getTenant();
        $rawSlug = $request->input('slug', '');
        $slug = Str::slug(strtolower($rawSlug));

        if (strlen($slug) < 3) {
            return response()->json([
                'available' => false,
                'message'   => 'رابط المتجر يجب أن يتكون من 3 حروف إنجليزية على الأقل.',
            ]);
        }

        if (in_array($slug, $this->reservedSlugs)) {
            return response()->json([
                'available' => false,
                'message'   => 'عذراً، هذا الاسم محجوز لنظام المنصة ولا يمكن استخدامه.',
            ]);
        }

        if ($tenant && $slug === $tenant->slug) {
            return response()->json([
                'available'  => true,
                'is_current' => true,
                'message'    => 'هذا هو رابط متجرك الحالي بالفعل.',
            ]);
        }

        $query = Tenant::where('slug', $slug);
        if ($tenant && $tenant->exists) {
            $query->where('id', '!=', $tenant->id);
        }

        if ($query->exists()) {
            return response()->json([
                'available' => false,
                'message'   => 'عذراً، هذا الرابط مستخدم بالفعل لمتجر آخر. يرجى اختيار اسم مختلف.',
            ]);
        }

        return response()->json([
            'available' => true,
            'message'   => 'رائع! هذا الرابط متاح للاستخدام 🚀',
        ]);
    }

    /**
     * Update store slug.
     */
    public function update(Request $request)
    {
        $tenant = $this->getTenant();

        if (!$tenant) {
            return back()->withErrors(['slug' => 'عذراً، لم يتم العثور على المتجر الخاص بحسابك.']);
        }

        $request->validate([
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
        ], [
            'slug.required' => 'يرجى كتابة رابط المتجر المطلوب.',
            'slug.min'      => 'رابط المتجر يجب أن يتكون من 3 حروف على الأقل.',
            'slug.max'      => 'رابط المتجر لا يمكن أن يتجاوز 50 حرفاً.',
            'slug.regex'    => 'رابط المتجر يجب أن يحتوي على حروف إنجليزية صغيرة وأرقام وشرطات (-) فقط بدون مسافات.',
        ]);

        $newSlug = Str::slug($request->input('slug'));

        if (in_array(strtolower($newSlug), $this->reservedSlugs)) {
            return back()->withErrors(['slug' => 'عذراً، هذا الاسم محجوز لنظام المنصة ولا يمكن استخدامه.']);
        }

        if ($newSlug === $tenant->slug) {
            return back()->with('info', 'الرابط المدخل هو نفس رابط متجرك الحالي.');
        }

        $exists = Tenant::where('slug', $newSlug)->where('id', '!=', $tenant->id)->exists();
        if ($exists) {
            return back()->withErrors(['slug' => 'عذراً، هذا الرابط مستخدم بالفعل لمتجر آخر.']);
        }

        $oldSlug = $tenant->slug;
        $tenant->slug = $newSlug;
        $tenant->save();

        $host = $request->getHost();
        $scheme = $request->getScheme();
        $port = $request->getPort();
        $portSuffix = ($port && !in_array($port, [80, 443])) ? ":{$port}" : '';

        // Extract base domain cleanly
        $cleanHost = str_starts_with($host, 'app.') ? substr($host, 4) : $host;
        $parts = explode('.', $cleanHost);
        if (count($parts) >= 2) {
            array_shift($parts); // remove old subdomain
            $baseDomain = implode('.', $parts);
        } else {
            $baseDomain = $cleanHost;
        }

        if (empty($baseDomain) || $baseDomain === 'localhost' || $baseDomain === '127.0.0.1') {
            $baseDomain = 'fastorder.localhost';
        }

        $newAdminUrl = "{$scheme}://{$newSlug}.{$baseDomain}{$portSuffix}/admin/domain";

        session()->flash(
            'success',
            "تم تغيير رابط المتجر بنجاح من ({$oldSlug}) إلى ({$newSlug}). تم تفعيل الرابط الجديد وتحديث لوحة التحكم فوراً!"
        );

        if ($request->header('X-Inertia')) {
            return Inertia::location($newAdminUrl);
        }

        return redirect()->away($newAdminUrl);
    }
}
