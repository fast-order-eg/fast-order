<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class PaymentGatewaysController extends Controller
{
    /**
     * List all payment gateways and their configuration status.
     */
    public function index(Request $request): Response
    {
        $tenant = app(Tenant::class);

        // Ensure default COD gateway exists and is active
        PaymentGateway::firstOrCreate(
            ['tenant_id' => $tenant->id, 'provider' => 'cod'],
            [
                'is_active'           => true,
                'display_name'        => 'الدفع عند الاستلام',
                'display_description' => 'الدفع نقداً عند استلام الطلب من مندوب التوصيل',
                'sort_order'          => 0,
                'credentials'         => [],
                'settings'            => [
                    'fee_enabled'   => false,
                    'fee_type'      => 'fixed',
                    'fee_direction' => 'increase',
                    'fee_value'     => 0,
                ],
            ]
        );

        $savedGateways = PaymentGateway::where('tenant_id', $tenant->id)->get()->keyBy('provider');

        $baseUrl = url('/');
        $webhookBase = rtrim($baseUrl, '/');

        $providers = [
            [
                'id'          => 'cod',
                'name'        => 'cod',
                'title'       => 'الدفع عند الاستلام',
                'logo'        => '/images/payments/cod.svg',
                'badge_image' => null,
                'description' => 'التحصيل نقداً عند تسليم الأوردر للعميل.',
                'is_active'   => isset($savedGateways['cod']) ? (bool) $savedGateways['cod']->is_active : true,
                'is_primary'  => true,
                'is_supported'=> true,
                'config'      => $savedGateways['cod'] ?? null,
            ],
            [
                'id'          => 'paymob',
                'name'        => 'paymob',
                'title'       => 'باي موب (Paymob)',
                'logo'        => '/images/payments/paymob.svg',
                'badge_image' => '/images/payments/cards_meeza_badge.svg',
                'description' => 'بوابة الدفع الإلكتروني الشاملة للبطاقات البنكية (فيزا، ماستركارد، ميزة) والمحافظ الإلكترونية.',
                'is_active'   => isset($savedGateways['paymob']) ? (bool) $savedGateways['paymob']->is_active : false,
                'is_supported'=> true,
                'webhook_url' => "{$webhookBase}/api/webhooks/paymob",
                'config'      => $savedGateways['paymob'] ?? null,
            ],
            [
                'id'          => 'kashier',
                'name'        => 'kashier',
                'title'       => 'كاشير (Kashier)',
                'logo'        => '/images/payments/kashier.svg',
                'badge_image' => '/images/payments/cards_meeza_badge.svg',
                'description' => 'حلول دفع متقدمة للبطاقات والمحافظ الإلكترونية مع لوحة تحكم متميزة وسهلة الربط.',
                'is_active'   => isset($savedGateways['kashier']) ? (bool) $savedGateways['kashier']->is_active : false,
                'is_supported'=> true,
                'webhook_url' => "{$webhookBase}/api/webhooks/kashier",
                'config'      => $savedGateways['kashier'] ?? null,
            ],
            [
                'id'          => 'fawry',
                'name'        => 'fawry',
                'title'       => 'فوري باي (Fawry)',
                'logo'        => '/images/payments/fawry.svg',
                'badge_image' => null,
                'description' => 'الدفع عبر منافذ وشبكة فوري المنتشرة في جميع أنحاء مصر برقم مرجعي.',
                'is_active'   => isset($savedGateways['fawry']) ? (bool) $savedGateways['fawry']->is_active : false,
                'is_supported'=> true,
                'webhook_url' => "{$webhookBase}/api/webhooks/fawry",
                'config'      => $savedGateways['fawry'] ?? null,
            ],
        ];

        return Inertia::render('Merchant/PaymentGateways/Index', [
            'providers'     => $providers,
            'savedGateways' => $savedGateways,
        ]);
    }

    /**
     * Save / update a payment gateway configuration.
     */
    public function update(Request $request, string $provider): RedirectResponse
    {
        $tenant = app(Tenant::class);

        $validated = $request->validate([
            'is_active'           => 'required|boolean',
            'display_name'        => 'nullable|string|max:150',
            'display_description' => 'nullable|string|max:500',
            'sort_order'          => 'nullable|integer',
            'credentials'         => 'nullable|array',
            'settings'            => 'nullable|array',
        ]);

        $isActive = (bool) $validated['is_active'];

        // If activating an online gateway (not COD), deactivate all other online gateways
        if ($isActive && $provider !== 'cod') {
            PaymentGateway::where('tenant_id', $tenant->id)
                ->where('provider', '!=', 'cod')
                ->where('provider', '!=', $provider)
                ->update(['is_active' => false]);
        }

        PaymentGateway::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'provider'  => $provider,
            ],
            [
                'is_active'           => $isActive,
                'display_name'        => $validated['display_name'] ?? null,
                'display_description' => $validated['display_description'] ?? null,
                'sort_order'          => $validated['sort_order'] ?? 0,
                'credentials'         => $validated['credentials'] ?? [],
                'settings'            => $validated['settings'] ?? [],
            ]
        );

        $providerTitle = match($provider) {
            'paymob'  => 'باي موب (Paymob)',
            'kashier' => 'كاشير (Kashier)',
            'fawry'   => 'فوري (Fawry)',
            'cod'     => 'الدفع عند الاستلام',
            default   => $provider,
        };

        return redirect()->back()->with('success', "تم حفظ وتحديث إعدادات {$providerTitle} بنجاح! ✓");
    }

    /**
     * Quick toggle activation status for a gateway.
     */
    public function toggle(Request $request, string $provider): RedirectResponse
    {
        $tenant = app(Tenant::class);

        $gateway = PaymentGateway::where('tenant_id', $tenant->id)
            ->where('provider', $provider)
            ->first();

        $newStatus = $gateway ? !$gateway->is_active : true;

        // If activating an online gateway, deactivate all other online gateways
        if ($newStatus && $provider !== 'cod') {
            PaymentGateway::where('tenant_id', $tenant->id)
                ->where('provider', '!=', 'cod')
                ->where('provider', '!=', $provider)
                ->update(['is_active' => false]);
        }

        if ($gateway) {
            $gateway->update(['is_active' => $newStatus]);
        } else {
            PaymentGateway::create([
                'tenant_id'   => $tenant->id,
                'provider'    => $provider,
                'is_active'   => true,
                'display_name'=> ucfirst($provider),
            ]);
        }

        $statusStr = $newStatus ? 'تفعيل' : 'تعطيل';
        return redirect()->back()->with('success', "تم {$statusStr} بوابة الدفع بنجاح! ✓");
    }
}
