<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\SettingController;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

$request = request();
$host = $request?->getHost();

if ($host && $host !== '127.0.0.1' && $host !== 'localhost') {
    $cleanHost = str_starts_with($host, 'app.') ? substr($host, 4) : $host;
    $parts = explode('.', $cleanHost);
    if (count($parts) >= 3) {
        array_shift($parts);
        $baseDomain = implode('.', $parts);
    } else {
        $baseDomain = $cleanHost;
    }
} else {
    $appUrl = config('app.url');
    $baseDomain = parse_url($appUrl, PHP_URL_HOST) ?: 'fastorder.localhost';
    if (str_starts_with($baseDomain, 'app.')) {
        $baseDomain = substr($baseDomain, 4);
    }
}

// Google OAuth Routes are handled per-domain:
// - app.domain → auth_superadmin.php (auth.google)
// - {tenant}.domain/admin → auth_merchant.php (merchant.auth.google)

/*
|--------------------------------------------------------------------------
| 1. Main Site Routing
|--------------------------------------------------------------------------
*/
Route::domain($baseDomain)->group(function () {
    Route::get('/', [\App\Http\Controllers\PlatformController::class, 'index'])->name('main.home');
    Route::get('/about', [\App\Http\Controllers\PlatformController::class, 'about'])->name('main.about');
    Route::get('/pricing', [\App\Http\Controllers\PlatformPricingController::class, 'index'])->name('platform.pricing');
    Route::get('/contact', [\App\Http\Controllers\PlatformController::class, 'contact'])->name('main.contact');
    Route::post('/contact', [\App\Http\Controllers\PlatformController::class, 'contactSubmit'])->name('main.contact.submit');
    Route::get('/privacy', [\App\Http\Controllers\PlatformController::class, 'privacy'])->name('main.privacy');
    Route::get('/terms', [\App\Http\Controllers\PlatformController::class, 'terms'])->name('main.terms');
    Route::get('/sla', [\App\Http\Controllers\PlatformController::class, 'sla'])->name('main.sla');
    Route::get('/help', [\App\Http\Controllers\PlatformController::class, 'help'])->name('main.help');
});

Route::domain('app.' . $baseDomain)->group(function () {
    // Convenience redirects if user types /admin/login on super admin domain
    Route::get('/admin/login', function () {
        return redirect()->route('login');
    });
    Route::get('/admin', function () {
        return redirect()->route('dashboard');
    });

    // Super Admin Auth Routes — منفصلة تماماً عن التجار
    Route::middleware(['web'])->group(function () {
        require __DIR__ . '/auth_superadmin.php';
    });

    // Protected Super Admin Panel Routes
    Route::middleware(['web', 'auth', 'super_admin'])->group(function () {
        Route::get('/', [App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('superadmin.dashboard');
        Route::get('/dashboard', [App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

        // Tenants Management
        Route::get('/tenants', [App\Http\Controllers\SuperAdmin\TenantController::class, 'index'])->name('superadmin.tenants.index');
        Route::get('/tenants/export', [App\Http\Controllers\SuperAdmin\TenantController::class, 'export'])->name('superadmin.tenants.export');
        Route::post('/tenants', [App\Http\Controllers\SuperAdmin\TenantController::class, 'store'])->name('superadmin.tenants.store');
        Route::get('/tenants/{tenant}', [App\Http\Controllers\SuperAdmin\TenantController::class, 'show'])->name('superadmin.tenants.show');
        Route::patch('/tenants/{tenant}/toggle-status', [App\Http\Controllers\SuperAdmin\TenantController::class, 'toggleStatus'])->name('superadmin.tenants.toggle-status');
        Route::post('/tenants/{tenant}/assign-subscription', [App\Http\Controllers\SuperAdmin\TenantController::class, 'assignSubscription'])->name('superadmin.tenants.assign-subscription');
        Route::get('/tenants/{tenant}/impersonate', [App\Http\Controllers\SuperAdmin\TenantController::class, 'impersonate'])->name('superadmin.tenants.impersonate');
        Route::delete('/tenants/{tenant}', [App\Http\Controllers\SuperAdmin\TenantController::class, 'destroy'])->name('superadmin.tenants.destroy');
        Route::post('/tenants/{tenant}/add-wallet-balance', [App\Http\Controllers\SuperAdmin\TenantController::class, 'addWalletBalance'])->name('superadmin.tenants.add-wallet-balance');
        Route::post('/tenants/{tenant}/deduct-wallet-balance', [App\Http\Controllers\SuperAdmin\TenantController::class, 'deductWalletBalance'])->name('superadmin.tenants.deduct-wallet-balance');

        // Subscriptions & Plans Management
        Route::get('/subscriptions/plans', [App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'plans'])->name('superadmin.subscriptions.plans');
        Route::get('/subscriptions/receipts', [App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'receipts'])->name('superadmin.subscriptions.receipts');
        Route::post('/subscriptions/receipts', [App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'storeReceipt'])->name('superadmin.subscriptions.receipts.store');
        Route::post('/subscriptions/receipts/{receipt}/approve', [App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'approveReceipt'])->name('superadmin.subscriptions.receipts.approve');
        Route::post('/subscriptions/receipts/{receipt}/reject', [App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'rejectReceipt'])->name('superadmin.subscriptions.receipts.reject');
        Route::delete('/subscriptions/receipts/{receipt}', [App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'destroyReceipt'])->name('superadmin.subscriptions.receipts.destroy');
        Route::post('/subscriptions/payment-settings', [App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'updatePaymentSettings'])->name('superadmin.subscriptions.update-payment-settings');

        // Backup & Data Export Management
        Route::get('/backups', [App\Http\Controllers\SuperAdmin\BackupController::class, 'index'])->name('superadmin.backups.index');
        Route::post('/backups', [App\Http\Controllers\SuperAdmin\BackupController::class, 'create'])->name('superadmin.backups.create');
        Route::get('/backups/download', [App\Http\Controllers\SuperAdmin\BackupController::class, 'download'])->name('superadmin.backups.download');
        Route::delete('/backups', [App\Http\Controllers\SuperAdmin\BackupController::class, 'destroy'])->name('superadmin.backups.destroy');

        // Support Contacts Management (Super Admin)
        Route::get('/support-contacts', [App\Http\Controllers\SuperAdmin\SupportContactController::class, 'index'])->name('superadmin.support-contacts.index');
        Route::post('/support-contacts', [App\Http\Controllers\SuperAdmin\SupportContactController::class, 'store'])->name('superadmin.support-contacts.store');
        Route::put('/support-contacts/{supportContact}', [App\Http\Controllers\SuperAdmin\SupportContactController::class, 'update'])->name('superadmin.support-contacts.update');
        Route::patch('/support-contacts/{supportContact}/toggle', [App\Http\Controllers\SuperAdmin\SupportContactController::class, 'toggle'])->name('superadmin.support-contacts.toggle');
        Route::delete('/support-contacts/{supportContact}', [App\Http\Controllers\SuperAdmin\SupportContactController::class, 'destroy'])->name('superadmin.support-contacts.destroy');

        // Tutorials & Knowledge Base Management (Super Admin)
        Route::get('/tutorials', [App\Http\Controllers\SuperAdmin\TutorialController::class, 'index'])->name('superadmin.tutorials.index');
        Route::post('/tutorials', [App\Http\Controllers\SuperAdmin\TutorialController::class, 'store'])->name('superadmin.tutorials.store');
        Route::put('/tutorials/{tutorial}', [App\Http\Controllers\SuperAdmin\TutorialController::class, 'update'])->name('superadmin.tutorials.update');
        Route::patch('/tutorials/{tutorial}/toggle', [App\Http\Controllers\SuperAdmin\TutorialController::class, 'toggle'])->name('superadmin.tutorials.toggle');
        Route::delete('/tutorials/{tutorial}', [App\Http\Controllers\SuperAdmin\TutorialController::class, 'destroy'])->name('superadmin.tutorials.destroy');

        // Meta WhatsApp Gateway & Billing Settings (Super Admin)
        Route::get('/whatsapp-gateway', [App\Http\Controllers\SuperAdmin\WhatsAppGatewayController::class, 'index'])->name('superadmin.whatsapp.index');
        Route::post('/whatsapp-gateway/settings', [App\Http\Controllers\SuperAdmin\WhatsAppGatewayController::class, 'updateSettings'])->name('superadmin.whatsapp.update-settings');
        Route::post('/whatsapp-gateway/test', [App\Http\Controllers\SuperAdmin\WhatsAppGatewayController::class, 'sendTestMessage'])->name('superadmin.whatsapp.test');

        // Super Admin Profile & Team Management
        Route::get('/admins', [App\Http\Controllers\SuperAdmin\AdminProfileController::class, 'index'])->name('superadmin.admins.index');
        Route::put('/admins/profile', [App\Http\Controllers\SuperAdmin\AdminProfileController::class, 'updateProfile'])->name('superadmin.admins.profile.update');
        Route::post('/admins', [App\Http\Controllers\SuperAdmin\AdminProfileController::class, 'storeAdmin'])->name('superadmin.admins.store');
        Route::delete('/admins/{admin}', [App\Http\Controllers\SuperAdmin\AdminProfileController::class, 'destroyAdmin'])->name('superadmin.admins.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| 3. Merchant Admin Panel & Tenant Routing
|--------------------------------------------------------------------------
*/

// A. Merchant Admin Panel (Available on app.fastorder.localhost/admin/* & subdomains)
Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        return redirect()->route('merchant.dashboard');
    });

    // Impersonate entry — one-time token login from super admin (no auth middleware needed)
    Route::middleware(['web', 'tenant'])->get('/impersonate-entry', [App\Http\Controllers\SuperAdmin\TenantController::class, 'impersonateEntry'])->name('merchant.impersonate.entry');

    // Google OAuth entry — one-time token login for Google auth (no auth middleware needed)
    Route::middleware(['web', 'tenant'])->get('/google-entry', [App\Http\Controllers\Auth\GoogleAuthController::class, 'googleEntry'])->name('merchant.google.entry');

    // Merchant Auth Routes — منفصلة تماماً عن السوبر أدمن
    Route::middleware(['web', 'tenant'])->group(function () {
        require __DIR__ . '/auth_merchant.php';
    });

        // Protected Merchant Admin Routes (لوحة تحكم التاجر تُفتح دائماً ولا تُغلق بالخطأ)
        Route::middleware(['web', 'impersonate.cookie', 'auth', 'tenant'])->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Merchant\DashboardController::class, 'index'])->name('merchant.dashboard');
            
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

            // Merchant Inertia-powered product & category management
            Route::resource('categories', App\Http\Controllers\Merchant\CategoryController::class)
                ->names('merchant.categories')
                ->except(['show', 'create', 'edit']);
                
            Route::get('/products/bulk', [App\Http\Controllers\Merchant\BulkUploadController::class, 'show'])->name('merchant.products.bulk');
            Route::get('/products/bulk/template', [App\Http\Controllers\Merchant\BulkUploadController::class, 'downloadTemplate'])->name('merchant.products.bulk.template');
            Route::post('/products/bulk', [App\Http\Controllers\Merchant\BulkUploadController::class, 'import'])->name('merchant.products.bulk.import');

            Route::resource('products', App\Http\Controllers\Merchant\ProductController::class)
                ->names('merchant.products')
                ->except(['show']);
            Route::delete('products/{product}/images/{image}', [App\Http\Controllers\Merchant\ProductController::class, 'destroyImage'])->name('products.images.destroy');

            Route::resource('staff', App\Http\Controllers\Merchant\StaffController::class)
                ->names('merchant.staff');


            // Shipping management routes
            Route::get('/shipping', [\App\Http\Controllers\Merchant\ShippingController::class, 'index'])->name('shipping.index');
            Route::post('/shipping', [\App\Http\Controllers\Merchant\ShippingController::class, 'store'])->name('shipping.store');
            Route::put('/shipping/{governorate}', [\App\Http\Controllers\Merchant\ShippingController::class, 'update'])->name('shipping.update');
            Route::patch('/shipping/{governorate}/toggle', [\App\Http\Controllers\Merchant\ShippingController::class, 'toggleStatus'])->name('shipping.toggle');
            Route::delete('/shipping/{governorate}', [\App\Http\Controllers\Merchant\ShippingController::class, 'destroy'])->name('shipping.destroy');

            // Orders management (Inertia-powered)
            Route::get('/orders', [App\Http\Controllers\Merchant\OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/export', [App\Http\Controllers\Merchant\OrderController::class, 'export'])->name('orders.export');
            Route::get('/orders/{order}', [App\Http\Controllers\Merchant\OrderController::class, 'show'])->name('orders.show');
            Route::post('/orders/{order}/unlock', [App\Http\Controllers\Merchant\OrderController::class, 'unlock'])->name('orders.unlock');
            Route::patch('/orders/{order}/status', [App\Http\Controllers\Merchant\OrderController::class, 'updateStatus'])->name('orders.updateStatus');
            Route::patch('/orders/{order}/cancel', [App\Http\Controllers\Merchant\OrderController::class, 'cancel'])->name('orders.cancel');
            Route::delete('/orders/{order}', [App\Http\Controllers\Merchant\OrderController::class, 'destroy'])->name('orders.destroy');
            Route::get('/orders/{order}/invoice', [App\Http\Controllers\Merchant\OrderController::class, 'invoice'])->name('orders.invoice');
            Route::get('/orders/{order}/download-invoice', [App\Http\Controllers\Merchant\OrderController::class, 'downloadInvoice'])->name('orders.downloadInvoice');

            // Customers management routes
            Route::get('/customers', [App\Http\Controllers\Merchant\CustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/{phone}', [App\Http\Controllers\Merchant\CustomerController::class, 'show'])->name('customers.show');

            // Banners management routes
            Route::get('/banners', [App\Http\Controllers\Merchant\BannerController::class, 'index'])->name('banners.index');
            Route::post('/banners', [App\Http\Controllers\Merchant\BannerController::class, 'store'])->name('banners.store');
            Route::put('/banners/{banner}', [App\Http\Controllers\Merchant\BannerController::class, 'update'])->name('banners.update')->where('banner', '[0-9]+');
            Route::patch('/banners/{banner}/toggle', [App\Http\Controllers\Merchant\BannerController::class, 'toggle'])->name('banners.toggle')->where('banner', '[0-9]+');
            Route::delete('/banners/{banner}', [App\Http\Controllers\Merchant\BannerController::class, 'destroy'])->name('banners.destroy')->where('banner', '[0-9]+');

            // Settings routes
            Route::get('/settings', [\App\Http\Controllers\Merchant\SettingController::class, 'index'])->name('settings.index');
            Route::put('/settings', [\App\Http\Controllers\Merchant\SettingController::class, 'update'])->name('settings.update');

            // Store Domain Change routes
            Route::get('/domain', [\App\Http\Controllers\Merchant\DomainController::class, 'edit'])->name('merchant.domain.edit');
            Route::post('/domain/check', [\App\Http\Controllers\Merchant\DomainController::class, 'check'])->name('merchant.domain.check');
            Route::put('/domain', [\App\Http\Controllers\Merchant\DomainController::class, 'update'])->name('merchant.domain.update');

            // Theme routes
            Route::get('/theme', [\App\Http\Controllers\Merchant\ThemeController::class, 'index'])->name('merchant.theme.index');
            Route::put('/theme', [\App\Http\Controllers\Merchant\ThemeController::class, 'update'])->name('merchant.theme.update');

            // Coupons management routes
            Route::patch('/coupons/{coupon}/toggle', [App\Http\Controllers\Merchant\CouponController::class, 'toggle'])->name('merchant.coupons.toggle');
            Route::resource('/coupons', App\Http\Controllers\Merchant\CouponController::class)->names('merchant.coupons');

            // Webhooks management routes
            Route::patch('/webhooks/{webhook}/toggle', [App\Http\Controllers\Merchant\WebhookController::class, 'toggle'])->name('merchant.webhooks.toggle');
            Route::resource('/webhooks', App\Http\Controllers\Merchant\WebhookController::class)->names('merchant.webhooks');
            Route::get('/webhooks/{webhook}/logs', [App\Http\Controllers\Merchant\WebhookController::class, 'logs'])->name('merchant.webhooks.logs');

            // Subscription routes
            Route::get('/subscription', [App\Http\Controllers\Merchant\SubscriptionController::class, 'index'])->name('merchant.subscription.index');
            Route::post('/subscription/subscribe', [App\Http\Controllers\Merchant\SubscriptionController::class, 'subscribe'])->name('merchant.subscription.subscribe');

            // Wallet routes
            Route::get('/wallet', [App\Http\Controllers\Merchant\WalletController::class, 'index'])->name('merchant.wallet.index');
            Route::post('/wallet/deposit', [App\Http\Controllers\Merchant\WalletController::class, 'deposit'])->name('merchant.wallet.deposit');

            // Reports routes
            Route::get('/reports', [App\Http\Controllers\Merchant\ReportController::class, 'index'])->name('merchant.reports.index');

            // API Keys management routes
            Route::resource('/api-keys', App\Http\Controllers\Merchant\ApiKeyController::class)
                ->names('merchant.api-keys')
                ->only(['index', 'store', 'destroy']);

            // Landing Pages management routes (Phase 62)
            Route::resource('landing-pages', \App\Http\Controllers\Merchant\LandingPageController::class)->names('merchant.landing-pages');
            Route::post('landing-pages/{landing_page}/toggle', [\App\Http\Controllers\Merchant\LandingPageController::class, 'toggle'])->name('merchant.landing-pages.toggle');
            Route::post('landing-pages/{landing_page}/duplicate', [\App\Http\Controllers\Merchant\LandingPageController::class, 'duplicate'])->name('merchant.landing-pages.duplicate');

            // Support & Tutorials routes for Merchant
            Route::get('/support', [\App\Http\Controllers\Merchant\SupportController::class, 'index'])->name('merchant.support.index');
            Route::get('/tutorials', [\App\Http\Controllers\Merchant\TutorialController::class, 'index'])->name('merchant.tutorials.index');

            // Auto Confirmation routes (WhatsApp)
            Route::prefix('auto-confirm')->name('merchant.auto-confirm.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\AutoConfirmController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Merchant\AutoConfirmController::class, 'update'])->name('update');
            });

            // Shipping Gateways management routes (Bosta, J&T Express, Aramex)
            Route::prefix('shipping-gateways')->name('merchant.shipping-gateways.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\ShippingGatewaysController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Merchant\ShippingGatewaysController::class, 'store'])->name('store');
                Route::post('/auto-dispatch', [\App\Http\Controllers\Merchant\ShippingGatewaysController::class, 'updateAutoDispatch'])->name('auto-dispatch');
                Route::post('/connect-api-key', [\App\Http\Controllers\Merchant\ShippingGatewaysController::class, 'connectApiKey'])->name('connect-api-key');
                Route::post('/connect-aramex', [\App\Http\Controllers\Merchant\ShippingGatewaysController::class, 'connectAramex'])->name('connect-aramex');
                Route::post('/connect-jnt', [\App\Http\Controllers\Merchant\ShippingGatewaysController::class, 'connectJnt'])->name('connect-jnt');
                Route::post('/connect-account', [\App\Http\Controllers\Merchant\ShippingGatewaysController::class, 'connectAccount'])->name('connect-account');
                Route::patch('/{provider}/toggle', [\App\Http\Controllers\Merchant\ShippingGatewaysController::class, 'toggle'])->name('toggle');
            });

            // Shipment actions for orders
            Route::post('/orders/{order}/shipment', [\App\Http\Controllers\Merchant\ShipmentController::class, 'store'])->name('merchant.orders.shipment.store');
            Route::get('/shipments/{shipment}/track', [\App\Http\Controllers\Merchant\ShipmentController::class, 'track'])->name('merchant.shipments.track');

            // Payment Gateways management
            Route::prefix('payment-gateways')->name('merchant.payment-gateways')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\PaymentGatewaysController::class, 'index']);
                Route::post('/{provider}', [\App\Http\Controllers\Merchant\PaymentGatewaysController::class, 'update'])->name('.update');
                Route::patch('/{provider}/toggle', [\App\Http\Controllers\Merchant\PaymentGatewaysController::class, 'toggle'])->name('.toggle');
            });

            // Conversion API (CAPI) management routes
            Route::prefix('conversion-api')->name('merchant.conversion-api.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\ConversionApiController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Merchant\ConversionApiController::class, 'store'])->name('store');
                Route::put('/{conversionApi}', [\App\Http\Controllers\Merchant\ConversionApiController::class, 'update'])->name('update');
                Route::delete('/{conversionApi}', [\App\Http\Controllers\Merchant\ConversionApiController::class, 'destroy'])->name('destroy');
                Route::patch('/{conversionApi}/toggle', [\App\Http\Controllers\Merchant\ConversionApiController::class, 'toggle'])->name('toggle');
                Route::post('/{conversionApi}/test', [\App\Http\Controllers\Merchant\ConversionApiController::class, 'testEvent'])->name('test');
            });

            // Coming Soon pages (Phase 67)
            Route::get('/ai-tools', fn() => \Inertia\Inertia::render('Merchant/ComingSoon/AiTools'))->name('merchant.ai-tools');

            // Promotions management routes (Phase 63)
            Route::resource('promotions', \App\Http\Controllers\Merchant\PromotionController::class)->names('merchant.promotions');
            Route::patch('promotions/{promotion}/toggle', [\App\Http\Controllers\Merchant\PromotionController::class, 'toggle'])->name('merchant.promotions.toggle');

            // Themes management routes (Phase 66)
            Route::prefix('themes')->name('merchant.themes.')->group(function () {
                Route::get('/', [\App\Http\Controllers\ThemeController::class, 'index'])->name('index');
                Route::get('/{slug}', [\App\Http\Controllers\ThemeController::class, 'show'])->name('show');
                Route::post('/activate', [\App\Http\Controllers\ThemeController::class, 'activate'])->name('activate');
                Route::post('/customize', [\App\Http\Controllers\ThemeController::class, 'customize'])->name('customize');
                Route::post('/reset', [\App\Http\Controllers\ThemeController::class, 'reset'])->name('reset');
                Route::get('/preview/{slug}', [\App\Http\Controllers\ThemeController::class, 'preview'])->name('preview');
                Route::post('/preview/cancel', [\App\Http\Controllers\ThemeController::class, 'cancelPreview'])->name('cancelPreview');
            });

            // Theme preview and live customization routes (Phase 72)
            Route::prefix('themes/preview')->name('merchant.themes.preview.')->group(function () {
                Route::get('/{slug?}', [\App\Http\Controllers\Merchant\ThemePreviewController::class, 'index'])->name('index');
                Route::get('/frame/{slug}/{page}', [\App\Http\Controllers\Merchant\ThemePreviewController::class, 'previewFrame'])->name('frame');
                Route::post('/session/{slug}', [\App\Http\Controllers\Merchant\ThemePreviewController::class, 'updateSession'])->name('session');
                Route::post('/save/{slug}', [\App\Http\Controllers\Merchant\ThemePreviewController::class, 'save'])->name('save');
                Route::post('/reset/{slug}', [\App\Http\Controllers\Merchant\ThemePreviewController::class, 'reset'])->name('reset');
                Route::get('/thumbnails/{slug?}', [\App\Http\Controllers\Merchant\ThemePreviewController::class, 'thumbnails'])->name('thumbnails');
            });

            // Returns management routes (Phase 82)
            Route::prefix('returns')->name('merchant.returns.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\OrderReturnController::class, 'index'])->name('index');
                Route::get('/{orderReturn}', [\App\Http\Controllers\Merchant\OrderReturnController::class, 'show'])->name('show');
                Route::post('/{orderReturn}/approve', [\App\Http\Controllers\Merchant\OrderReturnController::class, 'approve'])->name('approve');
                Route::post('/{orderReturn}/reject', [\App\Http\Controllers\Merchant\OrderReturnController::class, 'reject'])->name('reject');
                Route::post('/{orderReturn}/complete', [\App\Http\Controllers\Merchant\OrderReturnController::class, 'complete'])->name('complete');
            });

            // Ghost Order Blocker routes (Phase 80)
            Route::prefix('blacklist')->name('merchant.blacklist.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\BlacklistController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Merchant\BlacklistController::class, 'store'])->name('store');
                Route::delete('/{blacklistRecord}', [\App\Http\Controllers\Merchant\BlacklistController::class, 'destroy'])->name('destroy');
            });

            // Inventory reports routes (Phase 81)
            Route::get('/reports/inventory', [\App\Http\Controllers\Merchant\InventoryReportController::class, 'index'])->name('merchant.reports.inventory');
            Route::post('/reports/inventory/adjust', [\App\Http\Controllers\Merchant\InventoryReportController::class, 'adjust'])->name('merchant.reports.inventory.adjust');

            // Store ratings routes (Phase 86)
            Route::prefix('store-ratings')->name('merchant.store-ratings.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\StoreRatingController::class, 'index'])->name('index');
                Route::post('/{id}/toggle-visibility', [\App\Http\Controllers\Merchant\StoreRatingController::class, 'toggleVisibility'])->name('toggle-visibility');
            });

            // Abandoned Carts routes (Phase 85)
            Route::prefix('abandoned-carts')->name('merchant.abandoned-carts.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\AbandonedCartController::class, 'index'])->name('index');
                Route::post('/{abandonedCart}/send-reminder', [\App\Http\Controllers\Merchant\AbandonedCartController::class, 'sendReminder'])->name('send-reminder');
                Route::delete('/{abandonedCart}', [\App\Http\Controllers\Merchant\AbandonedCartController::class, 'destroy'])->name('destroy');
            });

            // Custom Menus routes (Phase 88)
            Route::prefix('menus')->name('merchant.menus.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\MenuController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Merchant\MenuController::class, 'store'])->name('store');
                Route::put('/{menu}', [\App\Http\Controllers\Merchant\MenuController::class, 'update'])->name('update');
                Route::delete('/{menu}', [\App\Http\Controllers\Merchant\MenuController::class, 'destroy'])->name('destroy');
                Route::patch('/{menu}/toggle', [\App\Http\Controllers\Merchant\MenuController::class, 'toggle'])->name('toggle');
            });

            // Comprehensive Import/Export routes (Phase 90)
            Route::prefix('import-export')->name('merchant.import-export.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\ImportExportController::class, 'index'])->name('index');
                Route::post('/import', [\App\Http\Controllers\Merchant\ImportExportController::class, 'import'])->name('import');
                Route::get('/export', [\App\Http\Controllers\Merchant\ImportExportController::class, 'export'])->name('export');
                Route::get('/template', [\App\Http\Controllers\Merchant\ImportExportController::class, 'downloadTemplate'])->name('template');
            });

            // Push Notifications (Web Push VAPID) routes
            Route::prefix('push-notifications')->name('merchant.push-notifications.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Merchant\PushNotificationController::class, 'index'])->name('index');
                Route::post('/subscribe', [\App\Http\Controllers\Merchant\PushNotificationController::class, 'subscribe'])->name('subscribe');
                Route::post('/unsubscribe', [\App\Http\Controllers\Merchant\PushNotificationController::class, 'unsubscribe'])->name('unsubscribe');
                Route::post('/test', [\App\Http\Controllers\Merchant\PushNotificationController::class, 'sendTest'])->name('test');
                Route::post('/settings', [\App\Http\Controllers\Merchant\PushNotificationController::class, 'updateSettings'])->name('settings');
                Route::get('/vapid-public-key', [\App\Http\Controllers\Merchant\PushNotificationController::class, 'vapidPublicKey'])->name('vapid-public-key');
            });
        });
    });

    // B. Tenant Storefront Routes (tenant.fastorder.test)
    Route::middleware(['web', 'tenant', 'tenant.active'])->group(function () {
        
        Route::get('/', [\App\Http\Controllers\StorefrontController::class, 'servePage'])->name('storefront.home');

        // Dynamic SEO & Metadata routes
        Route::get('/sitemap.xml', [\App\Http\Controllers\StorefrontController::class, 'sitemap'])->name('storefront.sitemap');
        Route::get('/robots.txt', [\App\Http\Controllers\StorefrontController::class, 'robots'])->name('storefront.robots');

        // Dynamic Storefront rendering routes
        Route::get('/shop', [\App\Http\Controllers\StorefrontController::class, 'servePage']);
        Route::get('/shop/', [\App\Http\Controllers\StorefrontController::class, 'servePage']);
        Route::get('/shop/{page}', [\App\Http\Controllers\StorefrontController::class, 'servePage'])
            ->where('page', '^[a-zA-Z0-9_-]+\.html$')
            ->name('storefront.page');
        Route::get('/{page}', [\App\Http\Controllers\StorefrontController::class, 'servePage'])
            ->where('page', '^[a-zA-Z0-9_-]+\.html$')
            ->name('storefront.root_page');

        // Cart page
        Route::get('/cart', [\App\Http\Controllers\StorefrontController::class, 'cart'])->name('storefront.cart');

        // Cart API Routes
        Route::prefix('api/cart')->group(function () {
            Route::get('/', [\App\Http\Controllers\StorefrontCartController::class, 'index'])->name('storefront.cart.index');
            Route::post('/add', [\App\Http\Controllers\StorefrontCartController::class, 'add'])->name('storefront.cart.add');
            Route::patch('/items/{item}', [\App\Http\Controllers\StorefrontCartController::class, 'update'])->name('storefront.cart.update');
            Route::delete('/items/{item}', [\App\Http\Controllers\StorefrontCartController::class, 'remove'])->name('storefront.cart.remove');
            Route::post('/items/{item}/save-for-later', [\App\Http\Controllers\StorefrontCartController::class, 'saveForLater'])->name('storefront.cart.save-for-later');
            Route::post('/items/{item}/move-to-cart', [\App\Http\Controllers\StorefrontCartController::class, 'moveToCart'])->name('storefront.cart.move-to-cart');
            Route::post('/coupon', [\App\Http\Controllers\StorefrontCartController::class, 'applyCoupon'])->name('storefront.cart.coupon.apply');
            Route::delete('/coupon', [\App\Http\Controllers\StorefrontCartController::class, 'removeCoupon'])->name('storefront.cart.coupon.remove');
        });

        // Public order routes (no auth required)
        Route::get('/checkout', [CheckoutController::class, 'index'])->name('storefront.checkout');
        Route::post('/checkout', [CheckoutController::class, 'store'])
            ->name('storefront.checkout.store')
            ->middleware(\App\Http\Middleware\GhostOrderBlockerMiddleware::class);
        Route::get('/order-success/{referenceNumber}', [CheckoutController::class, 'success'])->name('storefront.order.success');
        Route::get('/orders/success/{referenceNumber}', [CheckoutController::class, 'success'])->name('orders.success');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store'); // legacy HTML form

        // Cart recovery routes (Phase 85)
        Route::get('/shop/cart/recover/{token}', [\App\Http\Controllers\StorefrontCartRecoveryController::class, 'recover'])
            ->name('storefront.cart.recover');
        Route::post('/shop/checkout/track-partial', [\App\Http\Controllers\StorefrontCartRecoveryController::class, 'trackPartial'])
            ->name('storefront.checkout.track_partial');

        // Phase 57: Wishlist routes
        Route::get('/wishlist', function(\Illuminate\Http\Request $r) {
            $tenant = $r->attributes->get('tenant');
            $settings = is_array($tenant->settings) ? $tenant->settings : json_decode($tenant->settings ?? '{}', true);
            $theme = ['primary_color' => $settings['primary_color'] ?? '#6c63ff', 'secondary_color' => $settings['secondary_color'] ?? '#ff6584', 'font_family' => $settings['font_family'] ?? 'Cairo'];
            return view('shop.wishlist', compact('tenant', 'theme'));
        })->name('storefront.wishlist');

        Route::prefix('api/wishlist')->group(function() {
            Route::get('/', [\App\Http\Controllers\WishlistController::class, 'index']);
            Route::post('/toggle', [\App\Http\Controllers\WishlistController::class, 'toggle']);
            Route::get('/check', [\App\Http\Controllers\WishlistController::class, 'check']);
            Route::delete('/{wishlist}', [\App\Http\Controllers\WishlistController::class, 'remove']);
        });

        // Phase 58: Search routes
        Route::get('/search', function(\Illuminate\Http\Request $r) {
            $tenant = $r->attributes->get('tenant');
            $settings = is_array($tenant->settings) ? $tenant->settings : json_decode($tenant->settings ?? '{}', true);
            $theme = ['primary_color' => $settings['primary_color'] ?? '#6c63ff', 'secondary_color' => $settings['secondary_color'] ?? '#ff6584', 'font_family' => $settings['font_family'] ?? 'Cairo'];
            return view('shop.search', compact('tenant', 'theme'));
        })->name('storefront.search');

        Route::prefix('api/search')->group(function() {
            Route::get('/', [\App\Http\Controllers\SearchController::class, 'search']);
            Route::get('/suggestions', [\App\Http\Controllers\SearchController::class, 'suggestions']);
            Route::get('/history', [\App\Http\Controllers\SearchController::class, 'history']);
            Route::get('/popular', [\App\Http\Controllers\SearchController::class, 'popular']);
        });

        // Public JSON endpoints for static frontend
        Route::get('/public-api/categories', function () {
            if (!config('tenant.id') && !app()->bound(\App\Models\Tenant::class)) {
                return response()->json(['data' => []])->header('Access-Control-Allow-Origin', '*');
            }

            $formatImg = function($path) {
                if (!$path) return null;
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
                    return url($path);
                }
                return asset('storage/' . ltrim($path, '/'));
            };

            $locale = app()->getLocale();
            $items = Category::query()
                ->select(['id', 'name', 'name_ar', 'name_en', 'description', 'image_path', 'parent_id', 'main_category'])
                ->orderByDesc('id')
                ->get()
                ->map(function ($c) use ($locale, $formatImg) {
                    $name = $locale === 'en' 
                        ? ($c->name_en ?: ($c->name ?: $c->name_ar))
                        : ($c->name_ar ?: ($c->name ?: $c->name_en));
                    return [
                        'id' => $c->id,
                        'name' => $name,
                        'description' => $c->description,
                        'image_url' => $formatImg($c->image_path),
                        'parent_id' => $c->parent_id,
                        'main_category' => $c->main_category,
                    ];
                });
            return response()->json(['data' => $items])->header('Access-Control-Allow-Origin', '*');
        });

        Route::get('/public-api/products', function () {
            if (!config('tenant.id') && !app()->bound(\App\Models\Tenant::class)) {
                return response()->json(['data' => []])->header('Access-Control-Allow-Origin', '*');
            }
            $formatImg = function($path) {
                if (!$path) return null;
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
                    return url($path);
                }
                return asset('storage/' . ltrim($path, '/'));
            };

            // Calculate sales count per product from real non-cancelled orders
            $productSales = [];
            try {
                $orders = \App\Models\Order::whereNotIn('status', ['cancelled'])->latest()->limit(500)->get(['items']);
                foreach ($orders as $order) {
                    $items = is_array($order->items) ? $order->items : (json_decode($order->items, true) ?: []);
                    foreach ($items as $item) {
                        $pid = (int) ($item['id'] ?? ($item['product_id'] ?? 0));
                        $qty = (int) ($item['qty'] ?? ($item['quantity'] ?? 1));
                        if ($pid > 0) {
                            $productSales[$pid] = ($productSales[$pid] ?? 0) + max(1, $qty);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore gracefully
            }

            $q = Product::query()->with('category');
            if ($catId = request('category_id')) {
                $q->where('category_id', (int) $catId);
            }
            if ($excludeId = request('exclude_id')) {
                $q->where('id', '!=', (int) $excludeId);
            }
            $q->orderByDesc('id');

            $page = request('page');
            $limit = (int) request('limit', 12);

            if ($page) {
                $paginated = $q->paginate($limit);
                $items = collect($paginated->items())->map(function ($p) use ($formatImg, $productSales) {
                    $cat = $p->category;
                    $catName = $cat ? ($cat->name_ar ?: ($cat->name ?: $cat->name_en)) : null;
                    $img = $p->image_url ?: $p->main_image_path;
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'description' => $p->description,
                        'price_before' => $p->price_before,
                        'price_after' => $p->price_after ?? $p->price,
                        'sales_count' => $productSales[$p->id] ?? 0,
                        'stock' => $p->stock,
                        'category_id' => $p->category_id,
                        'category' => $catName,
                        'image_url' => $formatImg($img),
                        'sizes' => is_string($p->sizes) ? json_decode($p->sizes, true) : ($p->sizes ?: []),
                        'colors' => is_string($p->colors) ? json_decode($p->colors, true) : ($p->colors ?: []),
                        'custom_variants' => is_string($p->custom_variants) ? json_decode($p->custom_variants, true) : ($p->custom_variants ?: []),
                        'shipping_type' => $p->shipping_type ?? 'free',
                        'price_tiers' => is_string($p->price_tiers) ? json_decode($p->price_tiers, true) : ($p->price_tiers ?: []),
                        'variants_stock' => is_string($p->variants_stock) ? json_decode($p->variants_stock, true) : ($p->variants_stock ?: []),
                    ];
                });

                return response()->json([
                    'data' => $items,
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'has_more' => $paginated->hasMorePages(),
                    'total' => $paginated->total(),
                ])->header('Access-Control-Allow-Origin', '*');
            }

            if ($limitReq = request('limit')) {
                $q->limit((int) $limitReq);
            }

            $items = $q->get()->map(function ($p) use ($formatImg, $productSales) {
                $cat = $p->category;
                $catName = $cat ? ($cat->name_ar ?: ($cat->name ?: $cat->name_en)) : null;
                $img = $p->image_url ?: $p->main_image_path;
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'price_before' => $p->price_before,
                    'price_after' => $p->price_after ?? $p->price,
                    'sales_count' => $productSales[$p->id] ?? 0,
                    'stock' => $p->stock,
                    'category_id' => $p->category_id,
                    'category' => $catName,
                    'image_url' => $formatImg($img),
                    'sizes' => is_string($p->sizes) ? json_decode($p->sizes, true) : ($p->sizes ?: []),
                    'colors' => is_string($p->colors) ? json_decode($p->colors, true) : ($p->colors ?: []),
                    'custom_variants' => is_string($p->custom_variants) ? json_decode($p->custom_variants, true) : ($p->custom_variants ?: []),
                    'shipping_type' => $p->shipping_type ?? 'free',
                    'price_tiers' => is_string($p->price_tiers) ? json_decode($p->price_tiers, true) : ($p->price_tiers ?: []),
                    'variants_stock' => is_string($p->variants_stock) ? json_decode($p->variants_stock, true) : ($p->variants_stock ?: []),
                ];
            });
            return response()->json(['data' => $items])->header('Access-Control-Allow-Origin', '*');
        });

        Route::get('/public-api/products/{id}', function ($id) {
            $formatImg = function($path) {
                if (!$path) return null;
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
                    return url($path);
                }
                return asset('storage/' . ltrim($path, '/'));
            };

            $p = Product::with(['category', 'images', 'upsells', 'crossSells'])->findOrFail($id);
            $cat = $p->category;
            $catName = $cat ? ($cat->name_ar ?: ($cat->name ?: $cat->name_en)) : null;
            $images = $p->images ? $p->images->map(fn($img) => $formatImg($img->image_path))->values()->all() : [];
            
            $mapProduct = function($prod) use ($formatImg) {
                $img = $prod->image_url ?: $prod->main_image_path;
                return [
                    'id' => $prod->id,
                    'name' => $prod->name,
                    'price_before' => $prod->price_before,
                    'price_after' => $prod->price_after ?? $prod->price,
                    'stock' => $prod->stock,
                    'image_url' => $formatImg($img),
                ];
            };

            $mainImg = $p->image_url ?: $p->main_image_path;

            $data = [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'price_before' => $p->price_before,
                'price_after' => $p->price_after ?? $p->price,
                'stock' => $p->stock,
                'category_id' => $p->category_id,
                'category' => $catName,
                'image_url' => $formatImg($mainImg),
                'sizes' => is_string($p->sizes) ? json_decode($p->sizes, true) : ($p->sizes ?: []),
                'colors' => is_string($p->colors) ? json_decode($p->colors, true) : ($p->colors ?: []),
                'custom_variants' => is_string($p->custom_variants) ? json_decode($p->custom_variants, true) : ($p->custom_variants ?: []),
                'images' => $images,
                'shipping_type' => $p->shipping_type ?? 'free',
                'price_tiers' => is_string($p->price_tiers) ? json_decode($p->price_tiers, true) : ($p->price_tiers ?: []),
                'variants_stock' => is_string($p->variants_stock) ? json_decode($p->variants_stock, true) : ($p->variants_stock ?: []),
                'upsells' => $p->upsells->map($mapProduct)->values()->all(),
                'cross_sells' => $p->crossSells->map($mapProduct)->values()->all(),
            ];
            return response()->json(['data' => $data])->header('Access-Control-Allow-Origin', '*');
        });

        Route::get('/public-api/recommendations', function (\Illuminate\Http\Request $request) {
            $ids = array_filter(explode(',', $request->query('ids', '')));
            $type = $request->query('type', 'cross-sell');
            
            if (empty($ids)) {
                return response()->json(['data' => []])->header('Access-Control-Allow-Origin', '*');
            }
            
            $recs = \App\Models\ProductRecommendation::whereIn('product_id', $ids)
                ->where('type', $type)
                ->with('recommendedProduct')
                ->get()
                ->map(fn($r) => $r->recommendedProduct)
                ->filter(fn($p) => $p && !in_array($p->id, $ids) && $p->stock > 0)
                ->unique('id')
                ->values()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price_before' => $p->price_before,
                    'price_after' => $p->price_after ?? $p->price,
                    'stock' => $p->stock,
                    'image_url' => $p->main_image_path ? asset('storage/' . $p->main_image_path) : ($p->image_url ?: null),
                ]);
                
            return response()->json(['data' => $recs])->header('Access-Control-Allow-Origin', '*');
        });

        Route::get('/public-api/shipping-governorates', function () {
            $locale = app()->getLocale();
            $governorates = \App\Models\ShippingGovernorate::where('is_active', true)->orderBy('name')->get();
            $data = $governorates->map(function ($gov) use ($locale) {
                return [
                    'id' => $gov->id,
                    'name' => $gov->name,
                    'price' => $gov->price,
                    'shipping_cost' => $gov->price,
                    'is_active' => $gov->is_active,
                    'formatted_price' => $gov->price . ($locale === 'en' ? ' EGP' : ' جنيه')
                ];
            });
            return response()->json(['data' => $data])->header('Access-Control-Allow-Origin', '*');
        });

        Route::get('/public-api/banners', function () {
            $banners = \App\Models\Banner::active()->ordered()->get();
            $data = $banners->map(function ($banner) {
                return [
                    'id'        => $banner->id,
                    'title'     => $banner->title,
                    'image_url' => $banner->image_path ? asset('storage/' . $banner->image_path) : null,
                    'link'      => $banner->link,
                    'order'     => $banner->order
                ];
            });
            return response()->json(['data' => $data])->header('Access-Control-Allow-Origin', '*');
        });

        Route::get('/public-api/payment-gateways', function (\Illuminate\Http\Request $request) {
            $tenantId = optional($request->attributes->get('tenant'))->id;
            $gateways = \App\Models\PaymentGateway::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->get()
                ->map(function ($gw) {
                    return [
                        'id'                  => $gw->id,
                        'provider'            => $gw->provider,
                        'display_name'        => $gw->display_name ?: ($gw->provider === 'cod' ? 'الدفع عند الاستلام' : 'دفع إلكتروني (بطاقة بنكية / محفظة)'),
                        'display_description' => $gw->display_description,
                        'settings'            => $gw->settings ?: [],
                    ];
                });

            if ($gateways->isEmpty()) {
                $gateways = collect([
                    [
                        'id'                  => 0,
                        'provider'            => 'cod',
                        'display_name'        => 'الدفع عند الاستلام',
                        'display_description' => 'ادفع نقداً عند استلام شحنتك من مندوب التوصيل',
                        'settings'            => [],
                    ]
                ]);
            }

            return response()->json(['data' => $gateways])->header('Access-Control-Allow-Origin', '*');
        });

        Route::get('/public-api/settings', function () {
            $storedCats = \App\Models\Setting::get('main_categories');
            $mainCategories = $storedCats
                ? (json_decode($storedCats, true) ?: \App\Models\Category::getMainCategories())
                : \App\Models\Category::getMainCategories();

            $homepageSections = \App\Models\Setting::get('homepage_sections');
            if ($homepageSections) {
                $homepageSections = json_decode($homepageSections, true);
            }
            if (!$homepageSections || !is_array($homepageSections)) {
                $homepageSections = [
                    ['id' => 'hero_slider', 'enabled' => true, 'title' => 'البانر الإعلاني', 'title_en' => 'Hero Slider'],
                    ['id' => 'featured_categories', 'enabled' => true, 'title' => 'الأقسام المميزة', 'title_en' => 'Featured Categories'],
                    ['id' => 'best_offers', 'enabled' => true, 'title' => 'أفضل العروض والخصومات', 'title_en' => 'Best Offers & Discounts'],
                    ['id' => 'latest_products', 'enabled' => true, 'title' => 'أحدث المنتجات', 'title_en' => 'Latest Products']
                ];
            }

            $featuredCats = \App\Models\Setting::get('homepage_featured_categories');
            $featuredCats = $featuredCats ? json_decode($featuredCats, true) : [];

            $bestOffersLimit = (int) \App\Models\Setting::get('homepage_best_offers_limit', 4);
            $latestProductsLimit = (int) \App\Models\Setting::get('homepage_latest_products_limit', 4);

            $data = [
                'phone'                       => \App\Models\Setting::get('phone', '01012027705'),
                'whatsapp'                    => \App\Models\Setting::get('whatsapp', '201012027705'),
                'store_name'                  => \App\Models\Setting::get('store_name', 'Store'),
                'logo_url'                    => \App\Models\Setting::get('logo') ? asset('storage/' . \App\Models\Setting::get('logo')) : asset('images/logo.png'),
                'facebook_pixel_id'           => \App\Models\Setting::get('facebook_pixel_id', ''),
                'tiktok_pixel_id'             => \App\Models\Setting::get('tiktok_pixel_id', ''),
                'snapchat_pixel_id'           => \App\Models\Setting::get('snapchat_pixel_id', ''),
                'google_analytics_id'         => \App\Models\Setting::get('google_analytics_id', ''),
                'facebook_page'               => \App\Models\Setting::get('facebook_page') ?: 'https://facebook.com',
                'instagram_page'              => \App\Models\Setting::get('instagram_page') ?: 'https://instagram.com',
                'tiktok_page'                 => \App\Models\Setting::get('tiktok_page') ?: 'https://tiktok.com',
                'google_maps_url'             => \App\Models\Setting::get('google_maps_url') ?: 'https://maps.google.com',
                'address'                     => \App\Models\Setting::get('address') ?: 'مصر',
                'main_categories'             => $mainCategories,
                'homepage_sections'           => $homepageSections,
                'homepage_featured_categories' => $featuredCats,
                'homepage_best_offers_limit'  => $bestOffersLimit,
                'homepage_latest_products_limit' => $latestProductsLimit,
            ];
            return response()->json(['data' => $data])->header('Access-Control-Allow-Origin', '*');
        });

        // ─── Reviews API ───────────────────────────────────────────────
        Route::prefix('api/reviews')->group(function () {
            Route::get('/{productId}', [\App\Http\Controllers\ReviewController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\ReviewController::class, 'store']);
            Route::post('/{review}/helpful', [\App\Http\Controllers\ReviewController::class, 'helpful']);
        });

        // ─── Storefront Store Ratings API (Phase 86) ───────────────────
        Route::post('/shop/store-rating', [\App\Http\Controllers\StoreRatingController::class, 'store'])->name('shop.store-rating.store');
        Route::get('/shop/store-rating/summary', [\App\Http\Controllers\StoreRatingController::class, 'summary'])->name('shop.store-rating.summary');

        // ─── Account (Customer) ────────────────────────────────────────
        Route::get('/account', [\App\Http\Controllers\AccountController::class, 'index'])->name('storefront.account');
        Route::prefix('api/account')->middleware('auth')->group(function () {
            Route::get('/profile',  [\App\Http\Controllers\AccountController::class, 'getProfile']);
            Route::patch('/profile', [\App\Http\Controllers\AccountController::class, 'updateProfile']);
            Route::patch('/password', [\App\Http\Controllers\AccountController::class, 'updatePassword']);
            Route::get('/orders',   [\App\Http\Controllers\AccountController::class, 'getOrders']);
            Route::get('/returns', [\App\Http\Controllers\AccountController::class, 'getReturns']);
            Route::post('/returns', [\App\Http\Controllers\AccountController::class, 'requestReturn']);
        });

        // ─── Phase 61: Order Tracking ─────────────────────────────────
        Route::get('/tracking', [\App\Http\Controllers\OrderTrackingController::class, 'index'])->name('storefront.tracking');
        Route::post('/tracking', [\App\Http\Controllers\OrderTrackingController::class, 'track'])->name('storefront.tracking.submit');
        Route::get('/public-api/tracking', [\App\Http\Controllers\OrderTrackingController::class, 'apiTrack']);
        Route::post('/api/tracking', [\App\Http\Controllers\OrderTrackingController::class, 'apiTrack']);

        // ─── Phase 62: Landing Pages ──────────────────────────────────
        Route::get('/lp/{slug}', [\App\Http\Controllers\LandingPageController::class, 'show'])->name('landing-page.show');
        Route::post('/lp/{slug}/convert', [\App\Http\Controllers\LandingPageController::class, 'convert'])->name('landing-page.convert');

        // ─── Template Preview Redirects (for admin template previews) ─
        Route::get('/lp/preview-classic',  fn() => redirect('/shop/product.html?id=27&preview=lp&tpl=classic'))->name('lp.preview.classic');
        Route::get('/lp/preview-countdown', fn() => redirect('/shop/product.html?id=27&preview=lp&tpl=countdown'))->name('lp.preview.countdown');
        Route::get('/lp/preview-showcase',  fn() => redirect('/shop/product.html?id=27&preview=lp&tpl=showcase'))->name('lp.preview.showcase');

        // ─── Phase 63: Promotions & Discounts ─────────────────────────
        Route::get('/shop/promotions', [\App\Http\Controllers\PromotionController::class, 'index'])->name('shop.promotions');
        Route::get('/shop/promotions/{id}', [\App\Http\Controllers\PromotionController::class, 'show'])->name('shop.promotions.show');
        Route::get('/api/promotions', [\App\Http\Controllers\PromotionController::class, 'apiIndex']);
        Route::post('/api/promotions/calculate-price', [\App\Http\Controllers\PromotionController::class, 'calculatePrice']);

        // ─── Phase 64: Customer Notifications ─────────────────────────
        Route::middleware(['auth'])->prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
            Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
            Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
            Route::delete('/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
            Route::get('/settings', [\App\Http\Controllers\NotificationController::class, 'getSettings'])->name('notifications.settings.get');
            Route::post('/settings', [\App\Http\Controllers\NotificationController::class, 'updateSettings'])->name('notifications.settings.update');
        });
    });

