<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\CacheService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // فرض بروتوكول HTTPS في بيئة الإنتاج لضمان عمل الكوكيز والجلسات وأمان النماذج
        if ($this->app->environment('production') || str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // تعيين التوقيت لمكتبة Carbon
        Carbon::setLocale('en');
        date_default_timezone_set('Africa/Cairo');

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $storeName = \App\Models\Setting::get('store_name', 'Store');
                \Illuminate\Support\Facades\View::share('storeName', $storeName);
            }
        } catch (\Exception $e) {
            // تجاهل الخطأ لتجنب توقف أوامر الأرتيزان
        }

        \Illuminate\Support\Facades\View::composer('platform.*', function ($view) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('support_contacts')) {
                    $whatsappContact = \App\Models\SupportContact::where('is_active', true)->where('type', 'whatsapp')->orderBy('sort_order', 'asc')->first();
                    $phoneContact = \App\Models\SupportContact::where('is_active', true)->where('type', 'phone')->orderBy('sort_order', 'asc')->first();
                    $supportContacts = \App\Models\SupportContact::where('is_active', true)->orderBy('sort_order', 'asc')->get();

                    $view->with('whatsappContact', $whatsappContact)
                         ->with('phoneContact', $phoneContact)
                         ->with('supportContacts', $supportContacts);
                }
            } catch (\Exception $e) {
                // Ignore if table missing during migration
            }
        });

        // تسجيل الـ Queries البطيئة (Slow Queries)
        \Illuminate\Support\Facades\DB::listen(function (\Illuminate\Database\Events\QueryExecuted $query) {
            $threshold = env('SLOW_QUERY_THRESHOLD_MS', 500); // 500ms
            if ($query->time > $threshold) {
                \Illuminate\Support\Facades\Log::channel('fastorder-errors')->warning("Slow Query Detected: {$query->time}ms", [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'url' => request()?->fullUrl(),
                ]);
            }
        });

        // Rate Limiting للمسارات الحساسة
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
