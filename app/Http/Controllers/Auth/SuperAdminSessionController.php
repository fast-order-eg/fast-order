<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Handles authentication for SUPER ADMIN only.
 * URL: http://app.fastorder.test:8000/login
 */
class SuperAdminSessionController extends Controller
{
    /**
     * Show the super admin login form.
     */
    public function create(): View
    {
        return view('auth.login-superadmin');
    }

    /**
     * Handle super admin login.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user) {
            $host     = $request->getHost();
            $scheme   = $request->getScheme();
            $port     = $request->getPort();
            $portStr  = ($port && $port != 80 && $port != 443) ? ':' . $port : '';

            $baseHost = str_starts_with($host, 'app.') ? substr($host, 4) : $host;
            $parts    = explode('.', $baseHost);
            if (count($parts) >= 3) {
                array_shift($parts);
                $baseHost = implode('.', $parts);
            }

            // 1. إذا كان سوبر أدمن
            if ($user->isSuperAdmin()) {
                // تمديد وتثبيت الجلسة للسوبر أدمن (Remember Me تلقائي + جلسة 60 يوم)
                Auth::login($user, true);
                return redirect('/dashboard');
            }

            // 2. إذا كان تاجر أو موظف
            if ($user->isMerchant() || $user->isStaff()) {
                $tenant = $user->currentTenant
                    ?? $user->ownedTenants()->first()
                    ?? $user->tenants()->first();

                if ($tenant) {
                    // توجيهه إلى لوحة تحكم متجره الفرعي
                    return redirect("{$scheme}://{$tenant->slug}.{$baseHost}{$portStr}/admin/dashboard");
                }
            }

            // 3. مستخدم غير مصرح له (مثل عميل عادي يحاول دخول لوحة الإدارة)
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => 'هذا الحساب غير مصرح له بالدخول للوحات التحكم الإدارية.',
            ]);
        }

        return redirect('/login');
    }

    /**
     * Destroy the super admin session → redirect to super admin login.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $loginUrl = '/login';

        if ($request->header('X-Inertia')) {
            return \Inertia\Inertia::location($loginUrl);
        }

        return redirect($loginUrl);
    }
}
