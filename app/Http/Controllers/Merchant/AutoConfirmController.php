<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AutoConfirmController extends Controller
{
    /**
     * Display Auto Confirmation (WhatsApp) management page.
     */
    public function index(Request $request): Response
    {
        $tenant = app(\App\Models\Tenant::class);
        $walletBalance = (float) ($tenant->wallet_balance ?? 0);

        $enabled = (bool) Setting::get('auto_confirm_enabled', false);

        // Auto disable if balance < 3 EGP
        if ($walletBalance < 3 && $enabled) {
            Setting::set('auto_confirm_enabled', false, 'auto_confirm');
            $enabled = false;
        }

        $waitHours = (int) Setting::get('auto_confirm_wait_hours', 12);
        $template = Setting::get('auto_confirm_template', "مرحباً {customer_name} 👋\nشكراً لطلبك من متجرنا!\nرقم الطلب: #{reference_number}\nالمجموع: {total} ج.م\nالعنوان: {address}\n\nيرجى تأكيد طلبك للبدء في تجهيز الشحنة فورا:");

        $tenantId = session()->get('tenant_id') ?? config('tenant.id');

        // Order WhatsApp stats
        $ordersQuery = Order::where('tenant_id', $tenantId);
        $totalOrders = (clone $ordersQuery)->count();
        $totalMessagesSent = (clone $ordersQuery)->where('whatsapp_status', '!=', 'none')->count();
        $confirmedViaWa = (clone $ordersQuery)->where(function($q) {
            $q->where('whatsapp_status', 'confirmed')
              ->orWhere('notes', 'like', '%تأكيد بواسطة الواتس%');
        })->count();

        $cancelledViaWa = (clone $ordersQuery)->where(function($q) {
            $q->where('whatsapp_status', 'cancelled')
              ->orWhere('notes', 'like', '%إلغاء بواسطة الواتس%');
        })->count();

        $pendingWa = (clone $ordersQuery)->where('whatsapp_status', 'pending')->count();
        $totalCharges = (float) (clone $ordersQuery)->sum('whatsapp_charge_amount');

        $isAutoDispatchShipping = (bool) Setting::get('auto_dispatch_shipping', false);

        return Inertia::render('Merchant/AutoConfirm/Index', [
            'settings' => [
                'enabled'                       => $enabled,
                'cost_per_order'                => 1.00,
                'min_wallet_balance'            => 3.00,
                'wait_hours'                    => $waitHours,
                'auto_dispatch_shipping_active' => $isAutoDispatchShipping,
            ],
            'wallet_balance' => $walletBalance,
            'stats' => [
                'total_orders'         => $totalOrders,
                'total_messages_sent'  => $totalMessagesSent,
                'confirmed_via_wa'     => $confirmedViaWa,
                'cancelled_via_wa'     => $cancelledViaWa,
                'pending_wa'           => $pendingWa,
                'total_charges_egp'    => $totalCharges ?: ($totalMessagesSent * 1.00),
            ],
        ]);
    }

    /**
     * Update Auto Confirmation settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled'    => ['required', 'boolean'],
            'wait_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
            'template'   => ['nullable', 'string', 'max:1000'],
        ]);

        $tenant = app(\App\Models\Tenant::class);
        $walletBalance = (float) ($tenant->wallet_balance ?? 0);

        if ($validated['enabled'] && $walletBalance < 3) {
            Setting::set('auto_confirm_enabled', false, 'auto_confirm');
            return redirect()->back()->with('error', 'لا يمكن تفعيل خدمة التأكيد التلقائي لأن رصيد المحفظة (' . round($walletBalance) . ' ج.م) أقل من الحد الأدنى المطلوب (3 ج.م). يرجى شحن المحفظة أولاً.');
        }

        Setting::set('auto_confirm_enabled', (bool) $validated['enabled'], 'auto_confirm');
        
        if (isset($validated['wait_hours'])) {
            Setting::set('auto_confirm_wait_hours', $validated['wait_hours'], 'auto_confirm');
        }

        if (isset($validated['template'])) {
            Setting::set('auto_confirm_template', $validated['template'], 'auto_confirm');
        }

        $statusText = $validated['enabled'] ? 'تفعيل' : 'تعطيل';

        return redirect()->back()->with('success', "تم {$statusText} خدمة التأكيد التلقائي بنجاح");
    }
}
