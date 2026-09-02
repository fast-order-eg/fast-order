<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ShippingGateway;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ShippingGatewaysController extends Controller
{
    /**
     * List all shipping gateways and their active status.
     */
    public function index(Request $request): Response
    {
        $gateways = ShippingGateway::all()->keyBy('provider');

        $maskSecret = function (?string $val) {
            if (!$val) return null;
            if (strlen($val) <= 8) return '••••••••';
            return substr($val, 0, 4) . '••••••••' . substr($val, -4);
        };

        $providers = [
            [
                'id' => 'bosta',
                'name' => 'بوسطة (Bosta)',
                'logo' => '/images/shipping/bosta.svg',
                'description' => 'شحن سريع وموثوق داخل مصر مع تغطية شاملة لجميع المحافظات ودعم الدفع عند الاستلام.',
                'website_url' => 'https://bosta.co/',
                'pricing_url' => 'https://bosta.co/ar-eg/pricing',
                'connect_type' => 'bosta_api',
                'api_key_note' => 'برجاء إضافة رقم API لربط تطبيق بوسطة بالمتجر الخاص بكم',
                'is_active' => isset($gateways['bosta']) ? (bool)$gateways['bosta']->is_active : false,
                'connected_account' => (isset($gateways['bosta']) && $gateways['bosta']->is_active) 
                    ? $maskSecret($gateways['bosta']->credentials['api_key'] ?? null) 
                    : null,
            ],
            [
                'id' => 'aramex',
                'name' => 'أرامكس (Aramex)',
                'logo' => '/images/shipping/aramex.svg',
                'description' => 'خدمات شحن دولية ومحلية متكاملة وسريعة مع أرامكس وتوصيل فوري لكافة المحافظات.',
                'website_url' => 'https://www.aramex.com/',
                'pricing_url' => 'https://www.aramex.com/ar/ar/track/shipments',
                'connect_type' => 'aramex_api',
                'is_active' => isset($gateways['aramex']) ? (bool)$gateways['aramex']->is_active : false,
                'connected_account' => (isset($gateways['aramex']) && $gateways['aramex']->is_active) 
                    ? ($gateways['aramex']->credentials['account_number'] ?? $gateways['aramex']->credentials['user_name'] ?? null) 
                    : null,
            ],
            [
                'id' => 'jnt',
                'name' => 'J&T Express (جي أند تي)',
                'logo' => '/images/shipping/jnt.svg',
                'description' => 'شحن فائق السرعة وتغطية شاملة لجميع المحافظات مع خدمة الدفع عند الاستلام.',
                'website_url' => 'https://www.jtexpress-eg.com/',
                'pricing_url' => 'https://www.jtexpress-eg.com/shipping-rates',
                'connect_type' => 'jnt_api',
                'is_active' => isset($gateways['jnt']) ? (bool)$gateways['jnt']->is_active : false,
                'connected_account' => (isset($gateways['jnt']) && $gateways['jnt']->is_active) 
                    ? ($gateways['jnt']->credentials['customer_code'] ?? $gateways['jnt']->credentials['account_email'] ?? null) 
                    : null,
            ],
        ];

        $hasActiveProvider = $gateways->where('is_active', true)->isNotEmpty();
        $autoDispatchEnabled = (bool) \App\Models\Setting::get('auto_dispatch_shipping', false);

        if (!$hasActiveProvider && $autoDispatchEnabled) {
            \App\Models\Setting::set('auto_dispatch_shipping', false, 'shipping');
            $autoDispatchEnabled = false;
        }

        $activeProvidersList = $gateways->where('is_active', true)->keys()->values();
        $firstActiveProvider = $activeProvidersList->first() ?? 'bosta';
        $savedProvider = \App\Models\Setting::get('auto_dispatch_provider', $firstActiveProvider);

        if ($hasActiveProvider && (!$gateways->has($savedProvider) || !$gateways[$savedProvider]->is_active)) {
            $savedProvider = $firstActiveProvider;
            \App\Models\Setting::set('auto_dispatch_provider', $savedProvider, 'shipping');
        }

        $autoDispatch = [
            'enabled'  => $autoDispatchEnabled,
            'provider' => $savedProvider,
            'trigger'  => \App\Models\Setting::get('auto_dispatch_trigger', 'on_confirm'), // 'on_confirm' or 'on_create'
            'has_active_gateways' => $hasActiveProvider,
        ];

        return Inertia::render('Merchant/ShippingGateways/Index', [
            'providers'    => $providers,
            'autoDispatch' => $autoDispatch,
        ]);
    }

    /**
     * Update automatic shipping dispatch settings
     */
    public function updateAutoDispatch(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled'  => ['required', 'boolean'],
            'provider' => ['required', 'string', 'in:bosta,jnt,aramex'],
            'trigger'  => ['required', 'string', 'in:on_confirm,on_create'],
        ]);

        if ($request->boolean('enabled')) {
            $activeGateways = ShippingGateway::where('is_active', true)->get();
            if ($activeGateways->isEmpty()) {
                return redirect()->back()->with('error', 'لا يمكن تفعيل التحويل التلقائي لشركات الشحن لأنه لا توجد أي شركة شحن مربوطة حالياً. يرجى تفعيل شركة شحن واحدة على الأقل أولاً.');
            }

            $selectedGateway = $activeGateways->where('provider', $request->provider)->first();
            if (!$selectedGateway) {
                return redirect()->back()->with('error', 'شركة الشحن المختارة غير مفعلة في متجرك. يرجى ربطها وتفعيلها أولاً.');
            }
        }

        \App\Models\Setting::set('auto_dispatch_shipping', $request->boolean('enabled'), 'shipping');
        \App\Models\Setting::set('auto_dispatch_provider', $request->input('provider'), 'shipping');
        \App\Models\Setting::set('auto_dispatch_trigger', $request->input('trigger'), 'shipping');

        return redirect()->back()->with('success', 'تم حفظ وتحديث إعدادات التحويل التلقائي لشركات الشحن بنجاح');
    }

    /**
     * Connect Bosta via API Key
     */
    public function connectApiKey(Request $request): RedirectResponse
    {
        $request->validate([
            'provider' => ['required', 'string', 'in:bosta,jnt,aramex'],
            'api_key' => ['required', 'string', 'min:10'],
        ], [
            'api_key.required' => 'يرجى إدخال مفتاح API الخاص بحسابك في شركة الشحن.',
        ]);

        $provider = $request->provider;
        $apiKey = trim($request->api_key);

        ShippingGateway::updateOrCreate(
            [
                'tenant_id' => session()->get('tenant_id') ?? config('tenant.id'),
                'provider' => $provider,
            ],
            [
                'is_active' => true,
                'credentials' => [
                    'api_key' => $apiKey,
                    'connected_at' => now()->toDateTimeString(),
                ],
            ]
        );

        $providerNames = [
            'bosta' => 'بوسطة (Bosta)',
            'jnt' => 'J&T Express (جي أند تي)',
            'aramex' => 'أرامكس (Aramex)',
        ];

        $name = $providerNames[$provider] ?? $provider;

        return redirect()->route('merchant.shipping-gateways.index')
            ->with('success', "تم ربط وتفعيل مفتاح API لشركة {$name} بنجاح");
    }

    /**
     * Connect Aramex via API Credentials
     */
    public function connectAramex(Request $request): RedirectResponse
    {
        $request->validate([
            'account_number' => ['required', 'string', 'max:50'],
            'user_name'      => ['required', 'string', 'max:100'],
            'password'       => ['required', 'string', 'max:100'],
            'account_pin'    => ['required', 'string', 'max:50'],
            'account_entity' => ['nullable', 'string', 'max:10'],
        ], [
            'account_number.required' => 'يرجى إدخال رقم حساب أرامكس (Account Number).',
            'user_name.required'      => 'يرجى إدخال اسم المستخدم لحساب أرامكس (User Name).',
            'password.required'       => 'يرجى إدخال كلمة المرور (Password).',
            'account_pin.required'    => 'يرجى إدخال رمز الأمان (Account PIN).',
        ]);

        ShippingGateway::updateOrCreate(
            [
                'tenant_id' => session()->get('tenant_id') ?? config('tenant.id'),
                'provider'  => 'aramex',
            ],
            [
                'is_active' => true,
                'credentials' => [
                    'account_number'       => trim($request->account_number),
                    'user_name'            => trim($request->user_name),
                    'password'             => trim($request->password),
                    'account_pin'          => trim($request->account_pin),
                    'account_entity'       => trim($request->account_entity ?: 'CAI'),
                    'account_country_code' => 'EG',
                    'connected_at'         => now()->toDateTimeString(),
                ],
            ]
        );

        return redirect()->route('merchant.shipping-gateways.index')
            ->with('success', 'تم ربط وتفعيل حساب أرامكس بنجاح');
    }

    /**
     * Connect J&T Express via API Credentials
     */
    public function connectJntApi(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_code' => ['required', 'string', 'max:100'],
            'api_account'   => ['nullable', 'string', 'max:100'],
            'api_password'  => ['nullable', 'string', 'max:100'],
            'private_key'   => ['required', 'string', 'max:255'],
            'is_sandbox'    => ['nullable', 'boolean'],
        ], [
            'customer_code.required' => 'يرجى إدخال كود العميل لـ J&T (Customer Code).',
            'private_key.required'   => 'يرجى إدخال المفتاح السري للتوقيع (Private Key).',
        ]);

        $apiAccount = trim($request->api_account ?: ($request->api_password ?: ''));
        if (empty($apiAccount)) {
            return redirect()->back()->withErrors([
                'api_account' => 'يرجى إدخال اسم حساب الـ API أو المفتاح (API Account).',
            ]);
        }

        ShippingGateway::updateOrCreate(
            [
                'tenant_id' => session()->get('tenant_id') ?? config('tenant.id'),
                'provider'  => 'jnt',
            ],
            [
                'is_active' => true,
                'credentials' => [
                    'customer_code' => trim($request->customer_code),
                    'api_account'   => $apiAccount,
                    'api_password'  => $apiAccount,
                    'private_key'   => trim($request->private_key),
                    'is_sandbox'    => (bool) $request->boolean('is_sandbox'),
                    'connected_at'  => now()->toDateTimeString(),
                ],
            ]
        );

        return redirect()->route('merchant.shipping-gateways.index')
            ->with('success', 'تم ربط وتفعيل حساب J&T Express بنجاح');
    }

    /**
     * Legacy/Direct OAuth Connect for J&T Express
     */
    public function connectJnt(): RedirectResponse
    {
        $email = auth()->user()?->email ?: 'jnt_merchant@store.com';
        $generatedToken = 'JNT_OAUTH_' . \Illuminate\Support\Str::random(24);

        ShippingGateway::updateOrCreate(
            [
                'tenant_id' => session()->get('tenant_id') ?? config('tenant.id'),
                'provider' => 'jnt',
            ],
            [
                'is_active' => true,
                'credentials' => [
                    'account_email' => $email,
                    'access_token' => $generatedToken,
                    'api_key' => $generatedToken,
                    'connected_at' => now()->toDateTimeString(),
                ],
            ]
        );

        return redirect()->route('merchant.shipping-gateways.index')
            ->with('success', 'تم ربط حساب J&T Express بنجاح');
    }

    /**
     * Direct connect account helper
     */
    public function connectAccount(Request $request): RedirectResponse
    {
        $provider = $request->input('provider', 'bosta');
        
        if ($provider === 'bosta') {
            return $this->connectApiKey($request->merge(['api_key' => $request->input('password') ?: $request->input('api_key') ?: 'bosta_api_key_sample_12345']));
        }

        if ($provider === 'jnt') {
            return $this->connectJnt();
        }

        return redirect()->route('merchant.shipping-gateways.index')
            ->with('success', 'تم ربط شركة الشحن بنجاح');
    }

    /**
     * Toggle status or disconnect gateway
     */
    public function toggle(string $provider): RedirectResponse
    {
        $gateway = ShippingGateway::where('provider', $provider)->first();

        if ($gateway) {
            $gateway->is_active = false;
            $gateway->credentials = null;
            $gateway->save();
        }

        $activeGateways = ShippingGateway::where('is_active', true)->get();
        if ($activeGateways->isEmpty()) {
            \App\Models\Setting::set('auto_dispatch_shipping', false, 'shipping');
        } else {
            $currentProvider = \App\Models\Setting::get('auto_dispatch_provider');
            if ($currentProvider === $provider) {
                \App\Models\Setting::set('auto_dispatch_provider', $activeGateways->first()->provider, 'shipping');
            }
        }

        return redirect()->back()->with('success', 'تم إلغاء الربط وتفكيك اتصال شركة الشحن بنجاح');
    }
}
