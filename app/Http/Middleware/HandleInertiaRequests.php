<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     * هنا بيتم تمرير البيانات المشتركة لكل صفحة Inertia
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $tenant = null;

        try {
            $tenant = app(\App\Models\Tenant::class);
        } catch (\Exception $e) {
            // Tenant not bound yet
        }

        $storeName = null;
        try {
            $storeName = \App\Models\Setting::get('store_name');
        } catch (\Exception $e) {}

        if (!$storeName || trim($storeName) === '') {
            if ($tenant && isset($tenant->name)) {
                $storeName = $tenant->name;
            } elseif ($user) {
                try {
                    $ownedTenant = $user->ownedTenants()->first();
                    $storeName = $ownedTenant?->name;
                } catch (\Exception $e) {}
            }
        }

        $tenantSlug = $tenant?->slug;
        if (!$tenantSlug && $user && !$user->isSuperAdmin()) {
            try {
                $tenantSlug = $user->ownedTenants()->first()?->slug;
            } catch (\Exception $e) {}
        }

        $host = $request->getHost();
        $scheme = $request->getScheme();
        $port = $request->getPort();
        $portSuffix = ($port && !in_array($port, [80, 443])) ? ":{$port}" : '';

        $cleanHost = str_starts_with($host, 'app.') ? substr($host, 4) : $host;
        $parts = explode('.', $cleanHost);
        if (count($parts) >= 2) {
            array_shift($parts);
            $baseDomain = implode('.', $parts);
        } else {
            $baseDomain = $cleanHost;
        }
        if (empty($baseDomain) || $baseDomain === 'localhost' || $baseDomain === '127.0.0.1') {
            $baseDomain = 'fastorder.localhost';
        }

        $storefrontUrl = $tenantSlug ? "{$scheme}://{$tenantSlug}.{$baseDomain}{$portSuffix}/shop/index.html" : '#';

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'phone'     => $user->phone ?: (\App\Models\Setting::where('key', 'phone')->value('value') ?: $user->tenant?->phone),
                    'user_type' => $user->user_type,
                    'avatar'    => $user->avatar,
                ] : null,
            ],
            'storeName'     => $storeName,
            'tenantSlug'    => $tenantSlug,
            'storefrontUrl' => $storefrontUrl,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
                'new_key' => $request->session()->get('new_key'),
            ],
        ]);
    }
}
