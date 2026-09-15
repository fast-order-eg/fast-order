<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\OrderController as ApiOrderController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\CustomerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Orders API endpoint (no authentication required) - للواجهة الأمامية
Route::post('/orders', [OrderController::class, 'storeApi'])
    ->middleware([\App\Http\Middleware\IdentifyTenant::class])
    ->name('api.orders.store');

// Meta WhatsApp Webhook endpoints (Verification challenge + Event callbacks)
use App\Http\Controllers\Api\WhatsAppWebhookController;
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('api.webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('api.webhooks.whatsapp.handle');

// Paymob Transaction Processed Webhook
use App\Http\Controllers\Api\PaymobWebhookController;
Route::post('/webhooks/paymob', [PaymobWebhookController::class, 'handle'])->name('api.webhooks.paymob');

// Health check endpoint (no authentication required)
use App\Http\Controllers\Api\HealthCheckController;
Route::get('/health', [HealthCheckController::class, 'check'])->name('api.health');

// ============================================================
// API v1 - يتطلب مفتاح API في Authorization header
// ============================================================
Route::prefix('v1')->name('api.v1.')->middleware(['auth.apikey', 'throttle:api'])->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::apiResource('orders', ApiOrderController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('customers', CustomerController::class);
});

// ============================================================
// Internal Services API (Fast Order CRM WhatsApp Bot)
// ============================================================
use App\Http\Controllers\Api\Internal\ProductDraftController;

Route::prefix('internal')->name('api.internal.')->middleware(['auth.internal'])->group(function () {
    Route::get('/store-lookup', [ProductDraftController::class, 'lookupStore'])->name('store.lookup');
    Route::post('/products/draft', [ProductDraftController::class, 'createDraft'])->name('products.draft.create');
    Route::patch('/products/draft/{id}', [ProductDraftController::class, 'updateDraft'])->name('products.draft.update');
    Route::post('/products/draft/{id}/publish', [ProductDraftController::class, 'publishDraft'])->name('products.draft.publish');
    Route::delete('/products/draft/{id}', [ProductDraftController::class, 'discardDraft'])->name('products.draft.discard');
});