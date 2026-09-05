<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware - يُطبَّق على جميع الطلبات
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // إضافة SanitizeInputs و SetLocale لمجموعة web
        $middleware->web(append: [
            \App\Http\Middleware\HandleImpersonation::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\SanitizeInputs::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\AppendMenusToSettingsResponse::class,
        ]);

        // إضافة SanitizeInputs لمجموعة api
        $middleware->api(append: [
            \App\Http\Middleware\SanitizeInputs::class,
        ]);

        $middleware->alias([
            'tenant'           => \App\Http\Middleware\TenantMiddleware::class,
            'tenant.identify'  => \App\Http\Middleware\IdentifyTenant::class,
            'tenant.active'    => \App\Http\Middleware\EnsureTenantIsActive::class,
            'permission'       => \App\Http\Middleware\CheckPermission::class,
            'super_admin'      => \App\Http\Middleware\SuperAdminMiddleware::class,
            'auth.apikey'      => \App\Http\Middleware\AuthenticateApiKey::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'impersonate.cookie' => \App\Http\Middleware\HandleImpersonation::class,
        ]);

        // استثناء مسارات المتجر العامة و checkout من CSRF لمنع فشل الطلبات في متصفحات التطبيقات والويب
        $middleware->validateCsrfTokens(except: [
            '*/admin/logout',
            'logout',
            'tenants/*',
            'tenants',
            'subscriptions/*',
            'backups/*',
            'backups',
            'checkout',
            '*/checkout',
            'orders',
            '*/orders',
            'orders/*',
            '*/orders/*',
            'api/orders',
            '*/api/orders',
            'api/cart/*',
            '*/api/cart/*',
            'api/wishlist/*',
            '*/api/wishlist/*',
            'shop/checkout/track-partial',
            '*/shop/checkout/track-partial',
            'checkout/track-partial',
            '*/checkout/track-partial',
            'api/abandoned-cart/*',
            '*/api/abandoned-cart/*',
            'public-api/*',
            '*/public-api/*',
            'webhooks/*',
            '*/webhooks/*',
            'api/webhooks/*',
            '*/api/webhooks/*',
        ]);

        $middleware->redirectTo(
            guests: function (\Illuminate\Http\Request $request) {
                if (str_starts_with($request->getHost(), 'app.')) {
                    return '/login'; // Super Admin login
                }
                return '/admin/login'; // Merchant login
            },
            users: function (\Illuminate\Http\Request $request) {
                $user = $request->user();
                if ($user) {
                    $host     = $request->getHost();
                    $scheme   = $request->getScheme();
                    $port     = $request->getPort();
                    $portStr  = ($port && $port != 80 && $port != 443) ? ':' . $port : '';

                    if ($user->isSuperAdmin()) {
                        if (str_starts_with($host, 'app.')) {
                            return '/dashboard';
                        }
                        $parts = explode('.', $host);
                        $baseHost = count($parts) >= 3 ? implode('.', array_slice($parts, 1)) : $host;
                        return "{$scheme}://app.{$baseHost}{$portStr}/dashboard";
                    }

                    if ($user->isMerchant() || $user->isStaff()) {
                        $tenant = $user->currentTenant
                            ?? $user->ownedTenants()->first()
                            ?? $user->tenants()->first();
                        if ($tenant) {
                            $parts = explode('.', $host);
                            $baseHost = count($parts) >= 3 ? implode('.', array_slice($parts, 1)) : $host;
                            if ($host === "{$tenant->slug}.{$baseHost}") {
                                return '/admin/dashboard';
                            }
                            return "{$scheme}://{$tenant->slug}.{$baseHost}{$portStr}/admin/dashboard";
                        }
                    }

                    if ($user->isCustomer()) {
                        $tenant = $user->currentTenant;
                        if ($tenant) {
                            $parts = explode('.', $host);
                            $baseHost = count($parts) >= 3 ? implode('.', array_slice($parts, 1)) : $host;
                            if ($host === "{$tenant->slug}.{$baseHost}") {
                                return '/account';
                            }
                            return "{$scheme}://{$tenant->slug}.{$baseHost}{$portStr}/account";
                        }
                    }
                }

                // fallback
                if (str_starts_with($request->getHost(), 'app.')) {
                    return '/dashboard';
                }
                return '/admin/dashboard';
            }
        );
    })
    ->withSchedule(function ($schedule) {
        // فحص الاشتراكات المنتهية يومياً
        $schedule->command('subscriptions:check-expired')->daily();
        // تنظيف الملفات المؤقتة أسبوعياً
        $schedule->command('storage:cleanup-temp')->weekly();
        // تحديث إحصائيات الداشبورد كل ساعة
        $schedule->command('stats:update-dashboard')->hourly();
        // فحص صحة النظام كل 10 دقائق
        $schedule->command('monitor:health')->everyTenMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('fastorder-errors')->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(10)->toArray(),
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
                'input' => request()?->except(['password', 'password_confirmation']),
            ]);
        });
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // نسخ احتياطي يومي لقاعدة البيانات الساعة 2:00 صباحاً
        $schedule->command('backup:database')->daily()->at('02:00');
    })
    ->create();
