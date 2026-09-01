<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound(Tenant::class) ? app(Tenant::class) : config('app.tenant');

        if ($tenant) {
            $isSuperAdmin = \Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()?->user_type === 'super_admin';

            // Check if active subscription has expired
            $activeSub = $tenant->subscriptions()->where('status', 'active')->latest()->first();
            $isCommission = $tenant->isCommissionPlan() || ($activeSub && ($activeSub->plan?->slug === 'commission' || str_contains($activeSub->plan?->name ?? '', 'عمولة') || str_contains($activeSub->plan?->name ?? '', 'المحفظة')));

            $subExpired = false;
            $trialExpired = false;

            if (!$isCommission) {
                $subExpired = $tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast();
                $trialExpired = $tenant->trial_ends_at && $tenant->trial_ends_at->isPast() && !$tenant->subscription_ends_at;

                if ($subExpired || $trialExpired) {
                    if ($tenant->subscription_status !== 'expired') {
                        $tenant->update([
                            'subscription_status' => 'expired',
                        ]);
                    }
                }
            }

            // Determine if store is expired or suspended
            $isExpired = !$isCommission && ($tenant->subscription_status === 'expired' || $subExpired || $trialExpired);
            $isSuspended = !$tenant->is_active;

            $isAdminRoute = $request->is('admin*') || $request->is('*/admin*');
            $canBypass = $isSuperAdmin && $isAdminRoute;

            // Block access if store is suspended or expired
            if (($isSuspended || $isExpired) && !$canBypass) {
                // Allow expired merchant to access subscription renewal pages in /admin
                if ($isAdminRoute && ($request->is('*subscription*') || $request->is('*logout*'))) {
                    return $next($request);
                }

                if ($request->expectsJson() || $request->is('api/*') || $request->is('public-api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => $isExpired ? 'عذراً، هذا المتجر متوقف مؤقتاً لانتهاء مدة الاشتراك.' : 'عذراً، هذا المتجر موقوف مؤقتاً بواسطة الإدارة.',
                    ], 403);
                }

                return response()->view('errors.tenant_suspended', [
                    'tenant' => $tenant,
                ], 403);
            }
        }

        return $next($request);
    }
}
