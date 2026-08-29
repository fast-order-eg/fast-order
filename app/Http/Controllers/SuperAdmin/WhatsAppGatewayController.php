<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\MetaWhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class WhatsAppGatewayController extends Controller
{
    /**
     * Display Super Admin WhatsApp Gateway & Merchants Billing Dashboard.
     */
    public function index(Request $request): Response
    {
        $whatsAppService = new MetaWhatsAppService();

        $phoneNumberId = Setting::get('meta_phone_number_id', 'TEST_PHONE_ID_1029384756');
        $wabaId        = Setting::get('meta_waba_id', 'TEST_WABA_ID');
        $accessToken   = Setting::get('meta_access_token', 'EAAB...TEST_TOKEN');
        $templateName  = Setting::get('meta_template_name', 'order_confirmation');
        $templateLang  = Setting::get('meta_template_language', 'ar');
        $verifyToken   = Setting::get('meta_webhook_verify_token', 'fastorder_wa_secret_2026');
        $costPerOrder  = (float) Setting::get('meta_cost_per_order', 1.00);

        // Mask Token for security
        $maskedToken = strlen($accessToken) > 12 
            ? substr($accessToken, 0, 6) . '••••••••' . substr($accessToken, -4) 
            : $accessToken;

        // Fetch all tenants and their WhatsApp usage stats
        $tenants = Tenant::with('owner')->get()->map(function ($tenant) {
            $isEnabled = (bool) Setting::where('tenant_id', $tenant->id)
                ->where('key', 'auto_confirm_enabled')
                ->value('value');

            $ordersQuery = Order::where('tenant_id', $tenant->id);

            $totalMessages = (clone $ordersQuery)->where('whatsapp_status', '!=', 'none')->count();
            $confirmedCount = (clone $ordersQuery)->where(function($q) {
                $q->where('whatsapp_status', 'confirmed')
                  ->orWhere('notes', 'like', '%تأكيد بواسطة الواتس%');
            })->count();

            $cancelledCount = (clone $ordersQuery)->where(function($q) {
                $q->where('whatsapp_status', 'cancelled')
                  ->orWhere('notes', 'like', '%إلغاء بواسطة الواتس%');
            })->count();

            $totalCharges = (float) (clone $ordersQuery)->sum('whatsapp_charge_amount');

            return [
                'id'                     => $tenant->id,
                'name'                   => $tenant->name,
                'subdomain'              => $tenant->slug ?? $tenant->subdomain ?? 'store',
                'owner_name'             => $tenant->owner?->name ?? $tenant->name,
                'owner_phone'            => $tenant->owner?->phone ?? $tenant->phone ?? '-',
                'owner_email'            => $tenant->owner?->email ?? $tenant->email ?? '-',
                'is_auto_confirm_enabled'=> $isEnabled,
                'total_messages'         => $totalMessages,
                'confirmed_count'        => $confirmedCount,
                'cancelled_count'        => $cancelledCount,
                'total_charges_egp'      => $totalCharges ?: ($totalMessages * 1.00),
                'created_at'             => $tenant->created_at?->format('Y-m-d'),
            ];
        });

        $totalMerchantsEnabled = $tenants->where('is_auto_confirm_enabled', true)->count();
        $totalMessagesAllTime  = $tenants->sum('total_messages');
        $totalConfirmedAllTime = $tenants->sum('confirmed_count');
        $totalRevenueEgp       = $tenants->sum('total_charges_egp');

        return Inertia::render('SuperAdmin/WhatsApp/Index', [
            'settings' => [
                'meta_phone_number_id'       => $phoneNumberId,
                'meta_waba_id'               => $wabaId,
                'meta_access_token'          => $maskedToken,
                'meta_template_name'         => $templateName,
                'meta_template_language'     => $templateLang,
                'meta_webhook_verify_token'  => $verifyToken,
                'meta_cost_per_order'        => $costPerOrder,
                'is_configured'              => $whatsAppService->isConfigured(),
                'webhook_url'                => url('/api/webhooks/whatsapp'),
            ],
            'summary' => [
                'total_merchants'         => $tenants->count(),
                'total_merchants_enabled' => $totalMerchantsEnabled,
                'total_messages_all_time' => $totalMessagesAllTime,
                'total_confirmed_all_time'=> $totalConfirmedAllTime,
                'total_revenue_egp'       => $totalRevenueEgp,
                'confirmation_rate'       => $totalMessagesAllTime > 0 
                    ? round(($totalConfirmedAllTime / $totalMessagesAllTime) * 100, 1) 
                    : 0,
            ],
            'merchants' => $tenants,
        ]);
    }

    /**
     * Update Meta WhatsApp gateway settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meta_phone_number_id'      => ['required', 'string', 'max:100'],
            'meta_waba_id'              => ['nullable', 'string', 'max:100'],
            'meta_access_token'         => ['nullable', 'string'],
            'meta_template_name'        => ['required', 'string', 'max:100'],
            'meta_template_language'    => ['required', 'string', 'max:10'],
            'meta_webhook_verify_token' => ['required', 'string', 'max:100'],
            'meta_cost_per_order'       => ['required', 'numeric', 'min:0'],
        ]);

        Setting::set('meta_phone_number_id', trim($validated['meta_phone_number_id']), 'meta_whatsapp');
        
        if (!empty($validated['meta_waba_id'])) {
            Setting::set('meta_waba_id', trim($validated['meta_waba_id']), 'meta_whatsapp');
        }

        if (!empty($validated['meta_access_token']) && !str_contains($validated['meta_access_token'], '••••')) {
            Setting::set('meta_access_token', trim($validated['meta_access_token']), 'meta_whatsapp');
        }

        Setting::set('meta_template_name', trim($validated['meta_template_name']), 'meta_whatsapp');
        Setting::set('meta_template_language', trim($validated['meta_template_language']), 'meta_whatsapp');
        Setting::set('meta_webhook_verify_token', trim($validated['meta_webhook_verify_token']), 'meta_whatsapp');
        Setting::set('meta_cost_per_order', $validated['meta_cost_per_order'], 'meta_whatsapp');

        return redirect()->back()->with('success', 'تم حفظ وتحديث إعدادات بوابة Meta WhatsApp بنجاح');
    }

    /**
     * Send test WhatsApp message from Super Admin.
     */
    public function sendTestMessage(Request $request): JsonResponse
    {
        $request->validate([
            'phone'   => ['required', 'string'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $service = new MetaWhatsAppService();
        $result = $service->sendTestMessage($request->phone, $request->message ?: '');

        return response()->json($result);
    }

    /**
     * Toggle auto-confirm service for a specific tenant from Super Admin.
     */
    public function toggleMerchantStatus(Tenant $tenant): RedirectResponse
    {
        $current = (bool) Setting::where('tenant_id', $tenant->id)
            ->where('key', 'auto_confirm_enabled')
            ->value('value');

        Setting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'auto_confirm_enabled'],
            ['value' => !$current, 'group' => 'auto_confirm']
        );

        $statusText = !$current ? 'تفعيل' : 'إيقاف';

        return redirect()->back()->with('success', "تم {$statusText} خدمة التأكيد التلقائي لمتجر ({$tenant->name}) بنجاح");
    }
}
