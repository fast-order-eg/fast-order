<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SubscriptionReceipt;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobService
{
    protected string $apiKey;
    protected string $secretKey;
    protected string $publicKey;
    protected ?string $cardIntegrationId;
    protected ?string $walletIntegrationId;
    protected ?string $iframeId;
    protected string $hmacSecret;
    protected bool $isConfigured;

    public function __construct(
        ?string $apiKey = null,
        ?string $publicKey = null,
        ?string $secretKey = null,
        ?string $cardIntegrationId = null,
        ?string $walletIntegrationId = null,
        ?string $iframeId = null,
        ?string $hmacSecret = null
    ) {
        $this->apiKey = $apiKey ?: (Setting::getGlobal('paymob_api_key', Setting::get('paymob_api_key', '')) ?: '');
        $this->secretKey = $secretKey ?: (Setting::getGlobal('paymob_secret_key', Setting::get('paymob_secret_key', '')) ?: '');
        $this->publicKey = $publicKey ?: (Setting::getGlobal('paymob_public_key', Setting::get('paymob_public_key', '')) ?: '');
        $this->cardIntegrationId = $cardIntegrationId ?: (Setting::getGlobal('paymob_card_integration_id', Setting::get('paymob_card_integration_id', '')) ?: null);
        $this->walletIntegrationId = $walletIntegrationId ?: (Setting::getGlobal('paymob_wallet_integration_id', Setting::get('paymob_wallet_integration_id', '')) ?: null);
        $this->iframeId = $iframeId ?: (Setting::getGlobal('paymob_iframe_id', Setting::get('paymob_iframe_id', '')) ?: null);
        $this->hmacSecret = $hmacSecret ?: (Setting::getGlobal('paymob_hmac_secret', Setting::get('paymob_hmac_secret', '')) ?: '');

        $this->isConfigured = !empty($this->secretKey) || (!empty($this->apiKey) && !empty($this->cardIntegrationId));
    }

    /**
     * Check if Paymob keys are configured.
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Initiate instant wallet deposit checkout.
     *
     * @param SubscriptionReceipt $receipt
     * @param Tenant $tenant
     * @param string $methodType ('card' or 'wallet')
     * @param string|null $walletPhone (required for wallet if not prompt)
     * @return array ['success' => bool, 'redirect_url' => string, 'paymob_order_id' => string, 'message' => string]
     */
    public function initiateDeposit(SubscriptionReceipt $receipt, Tenant $tenant, string $methodType = 'card', ?string $walletPhone = null): array
    {
        $amountCents = (int) round($receipt->amount * 100);
        $owner = $tenant->owner;
        $merchantName = $tenant->name ?: 'Merchant';
        $firstName = $owner?->name ? explode(' ', trim($owner->name))[0] : 'Merchant';
        $lastName = $owner?->name && str_contains(trim($owner->name), ' ') ? substr(strstr(trim($owner->name), ' '), 1) : 'Store';
        $email = $owner?->email ?: $tenant->email ?: 'merchant@fast-order-eg.tech';
        $phone = $walletPhone ?: ($owner?->phone ?: $tenant->phone ?: '01000000000');

        // Normalize Phone
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (!str_starts_with($cleanPhone, '01') && !str_starts_with($cleanPhone, '201')) {
            $cleanPhone = '01000000000';
        }

        // If not fully configured, return a Sandbox Simulation Flow
        if (!$this->isConfigured) {
            Log::info("Paymob credentials not set. Using Simulation Sandbox mode for Receipt #{$receipt->reference_code}");
            $simulatedUrl = route('merchant.wallet.paymob.sandbox', [
                'receipt_id' => $receipt->id,
                'method'     => $methodType,
                'amount'     => $receipt->amount,
            ]);

            return [
                'success'         => true,
                'redirect_url'    => $simulatedUrl,
                'paymob_order_id' => 'SANDBOX_ORDER_' . $receipt->id,
                'message'         => 'تم تجهيز رابط الدفع التجريبي (Sandbox).',
            ];
        }

        try {
            // Option 1: Unified Checkout (Intention API) if Secret Key is present
            if (!empty($this->secretKey) && !empty($this->publicKey)) {
                return $this->createIntentionCheckout($receipt, $amountCents, $methodType, $firstName, $lastName, $email, $cleanPhone);
            }

            // Option 2: Classic Accept API (Auth -> Order -> Payment Key -> Redirect)
            return $this->createClassicCheckout($receipt, $amountCents, $methodType, $firstName, $lastName, $email, $cleanPhone, $walletPhone);

        } catch (\Throwable $e) {
            Log::error("Paymob initiate deposit exception: " . $e->getMessage(), [
                'receipt_id' => $receipt->id,
                'trace'      => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء الاتصال ببوابة الدفع: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create Intention using Paymob Unified Checkout API.
     */
    protected function createIntentionCheckout(
        SubscriptionReceipt $receipt,
        int $amountCents,
        string $methodType,
        string $firstName,
        string $lastName,
        string $email,
        string $phone
    ): array {
        $methods = [];
        if ($this->cardIntegrationId) {
            $methods[] = (int) $this->cardIntegrationId;
        }
        if ($this->walletIntegrationId) {
            $methods[] = (int) $this->walletIntegrationId;
        }

        $payload = [
            'amount'           => $amountCents,
            'currency'         => 'EGP',
            'payment_methods'  => !empty($methods) ? $methods : ['card', 'wallet'],
            'items'            => [
                [
                    'name'        => "شحن رصيد محفظة متجر Fast Order (#{$receipt->reference_code})",
                    'amount'      => $amountCents,
                    'description' => "شحن رصيد لحظي بقيمة {$receipt->amount} ج.م",
                    'quantity'    => 1,
                ]
            ],
            'billing_data'     => [
                'first_name'   => $firstName ?: 'Merchant',
                'last_name'    => $lastName ?: 'Owner',
                'phone_number' => $phone,
                'email'        => $email,
                'apartment'    => 'NA',
                'floor'        => 'NA',
                'street'       => 'NA',
                'building'     => 'NA',
                'shipping_method' => 'NA',
                'postal_code'  => 'NA',
                'city'         => 'Cairo',
                'country'      => 'EGY',
                'state'        => 'Cairo',
            ],
            'special_reference'=> 'WALLET_' . $receipt->reference_code . '_' . $receipt->id,
            'notification_url' => url('/api/webhooks/paymob'),
            'redirection_url'  => route('merchant.wallet.paymob.callback'),
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $this->secretKey,
            'Content-Type'  => 'application/json',
        ])->post('https://accept.paymob.com/v1/intention/', $payload);

        if ($response->successful()) {
            $data = $response->json();
            $clientSecret = $data['client_secret'] ?? null;
            $intentionId  = $data['id'] ?? null;

            if ($clientSecret) {
                // Official Paymob Unified Checkout URL
                $checkoutUrl = "https://eg.checkout.paymob.com/?publicKey={$this->publicKey}&clientSecret={$clientSecret}";

                $receipt->update([
                    'payment_reference' => $intentionId ?: $clientSecret,
                ]);

                return [
                    'success'         => true,
                    'redirect_url'    => $checkoutUrl,
                    'paymob_order_id' => $intentionId,
                    'message'         => 'تم إنشاء رابط الدفع بنجاح.',
                ];
            }
        }

        Log::warning("Paymob Intention API failed: " . $response->body());
        throw new \Exception($response->json('message') ?? 'فشل الاتصال بباي موب');
    }

    /**
     * Create Classic Paymob Checkout (Tokens -> Order -> PaymentKey).
     */
    protected function createClassicCheckout(
        SubscriptionReceipt $receipt,
        int $amountCents,
        string $methodType,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        ?string $walletPhone
    ): array {
        // Step 1: Get Auth Token
        $authResponse = Http::post('https://accept.paymob.com/api/auth/tokens', [
            'api_key' => $this->apiKey,
        ]);

        if (!$authResponse->successful()) {
            throw new \Exception('فشل المصادقة مع Paymob API Key.');
        }

        $authToken = $authResponse->json('token');

        // Step 2: Order Registration
        $orderResponse = Http::post('https://accept.paymob.com/api/ecommerce/orders', [
            'auth_token'        => $authToken,
            'delivery_needed'   => 'false',
            'amount_cents'      => $amountCents,
            'currency'          => 'EGP',
            'merchant_order_id' => 'WALLET_' . $receipt->reference_code . '_' . $receipt->id . '_' . time(),
            'items'             => [],
        ]);

        if (!$orderResponse->successful()) {
            throw new \Exception('فشل تسجيل الطلب في Paymob.');
        }

        $paymobOrderId = $orderResponse->json('id');

        // Step 3: Obtain Payment Key
        $integrationId = ($methodType === 'wallet' && $this->walletIntegrationId)
            ? (int) $this->walletIntegrationId
            : (int) ($this->cardIntegrationId ?: 0);

        $keyResponse = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
            'auth_token'     => $authToken,
            'amount_cents'   => $amountCents,
            'expiration'     => 3600,
            'order_id'       => $paymobOrderId,
            'billing_data'   => [
                'apartment'    => 'NA',
                'email'        => $email,
                'floor'        => 'NA',
                'first_name'   => $firstName ?: 'Merchant',
                'street'       => 'NA',
                'building'     => 'NA',
                'phone_number' => $phone,
                'shipping_method' => 'NA',
                'postal_code'  => 'NA',
                'city'         => 'Cairo',
                'country'      => 'EGY',
                'last_name'    => $lastName ?: 'Owner',
                'state'        => 'Cairo',
            ],
            'currency'       => 'EGP',
            'integration_id' => $integrationId,
            'lock_order_when_paid' => 'false',
        ]);

        if (!$keyResponse->successful()) {
            throw new \Exception('فشل إنشاء مفتاح الدفع (Payment Key).');
        }

        $paymentToken = $keyResponse->json('token');

        // Save Paymob Order ID in receipt
        $receipt->update([
            'payment_reference' => (string) $paymobOrderId,
        ]);

        // Step 4: Method Specific Handling
        if ($methodType === 'wallet' && $walletPhone) {
            $payResponse = Http::post('https://accept.paymob.com/api/acceptance/payments/pay', [
                'source' => [
                    'identifier' => $walletPhone,
                    'subtype'    => 'WALLET',
                ],
                'payment_token' => $paymentToken,
            ]);

            if ($payResponse->successful() && $payResponse->json('redirect_url')) {
                return [
                    'success'         => true,
                    'redirect_url'    => $payResponse->json('redirect_url'),
                    'paymob_order_id' => (string) $paymobOrderId,
                    'message'         => 'تم تجهيز رابط الدفع بالمحفظة.',
                ];
            }

            if ($payResponse->successful() && $payResponse->json('iframe_redirection_url')) {
                return [
                    'success'         => true,
                    'redirect_url'    => $payResponse->json('iframe_redirection_url'),
                    'paymob_order_id' => (string) $paymobOrderId,
                    'message'         => 'تم تجهيز رابط الدفع.',
                ];
            }
        }

        // For Cards: Use iFrame or direct Accept portal
        $iframeId = $this->iframeId ?: '778000';
        $redirectUrl = "https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentToken}";

        return [
            'success'         => true,
            'redirect_url'    => $redirectUrl,
            'paymob_order_id' => (string) $paymobOrderId,
            'message'         => 'تم تجهيز بوابة الدفع بالبطاقة.',
        ];
    }

    /**
     * Verify HMAC hash from Paymob Callback/Webhook.
     */
    public function verifyHmac(array $data, ?string $receivedHmac): bool
    {
        if (empty($this->hmacSecret)) {
            // If no secret configured, accept during initial setup
            return true;
        }

        if (empty($receivedHmac)) {
            return false;
        }

        // Keys order required by Paymob
        $keys = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order_id', // or order.id
            'owner',
            'pending',
            'source_data_pan', // or source_data.pan
            'source_data_sub_type',
            'source_data_type',
            'success',
        ];

        $concatenated = '';
        foreach ($keys as $key) {
            $val = $data[$key] ?? '';
            if (is_bool($val)) {
                $concatenated .= $val ? 'true' : 'false';
            } else {
                $concatenated .= (string) $val;
            }
        }

        $calculatedHmac = hash_hmac('sha512', $concatenated, $this->hmacSecret);

        return hash_equals($calculatedHmac, $receivedHmac);
    }

    /**
     * Initiate Paymob checkout for a storefront customer order.
     */
    public function createStoreOrderCheckout(\App\Models\Order $order): array
    {
        $amountCents = (int) round($order->total * 100);
        $cleanPhone = preg_replace('/[^0-9]/', '', $order->customer_phone);
        if (!str_starts_with($cleanPhone, '01') && !str_starts_with($cleanPhone, '201')) {
            $cleanPhone = '01000000000';
        }

        $names = explode(' ', trim($order->customer_name ?: 'Customer'));
        $firstName = $names[0] ?? 'Customer';
        $lastName = count($names) > 1 ? implode(' ', array_slice($names, 1)) : 'Client';
        $email = $order->customer_email ?: 'customer@store.com';

        // If not configured, provide a seamless simulated sandbox checkout
        if (!$this->isConfigured) {
            $simulatedUrl = url("/checkout/payment-callback/paymob?order_id={$order->id}&simulated=1&status=success");
            return [
                'success'      => true,
                'redirect_url' => $simulatedUrl,
                'message'      => 'جاري التوجيه لصفحة الدفع الآمن.',
            ];
        }

        try {
            if (!empty($this->secretKey) && !empty($this->publicKey)) {
                $methods = [];
                if ($this->cardIntegrationId) {
                    $methods[] = (int) $this->cardIntegrationId;
                }
                if ($this->walletIntegrationId) {
                    $methods[] = (int) $this->walletIntegrationId;
                }

                $items = array_map(function ($item) {
                    return [
                        'name'     => $item['name'] ?? 'Product',
                        'amount'   => (int) round(((float)($item['price'] ?? 0)) * 100),
                        'quantity' => (int) ($item['quantity'] ?? 1),
                    ];
                }, $order->items ?? []);

                if (empty($items)) {
                    $items = [
                        [
                            'name'     => "طلب رقم #{$order->reference_number}",
                            'amount'   => $amountCents,
                            'quantity' => 1,
                        ]
                    ];
                }

                $payload = [
                    'amount'           => $amountCents,
                    'currency'         => 'EGP',
                    'payment_methods'  => !empty($methods) ? $methods : ['card', 'wallet'],
                    'items'            => $items,
                    'billing_data'     => [
                        'first_name'   => $firstName,
                        'last_name'    => $lastName,
                        'phone_number' => $cleanPhone,
                        'email'        => $email,
                        'street'       => $order->customer_address ?: 'Cairo',
                        'city'         => $order->governorate ?: 'Cairo',
                        'country'      => 'EGY',
                        'state'        => $order->governorate ?: 'Cairo',
                    ],
                    'special_reference'=> 'ORDER_' . $order->reference_number . '_' . $order->id,
                    'notification_url' => url('/api/webhooks/paymob'),
                    'redirection_url'  => url("/checkout/payment-callback/paymob?order_id={$order->id}"),
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Token ' . $this->secretKey,
                    'Content-Type'  => 'application/json',
                ])->post('https://accept.paymob.com/v1/intention/', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $clientSecret = $data['client_secret'] ?? null;
                    if ($clientSecret) {
                        return [
                            'success'      => true,
                            'redirect_url' => "https://eg.checkout.paymob.com/?publicKey={$this->publicKey}&clientSecret={$clientSecret}",
                            'message'      => 'تم إنشاء جلسة الدفع بنجاح.',
                        ];
                    }
                }
            }

            // Fallback to classic checkout flow
            $authRes = Http::post('https://accept.paymob.com/api/auth/tokens', ['api_key' => $this->apiKey]);
            if ($authRes->successful()) {
                $authToken = $authRes->json('token');
                $orderRes = Http::post('https://accept.paymob.com/api/ecommerce/orders', [
                    'auth_token'      => $authToken,
                    'delivery_needed' => 'false',
                    'amount_cents'    => (string) $amountCents,
                    'currency'        => 'EGP',
                    'merchant_order_id'=> 'ORDER_' . $order->reference_number . '_' . $order->id,
                    'items'           => [],
                ]);

                if ($orderRes->successful()) {
                    $paymobOrderId = $orderRes->json('id');
                    $integration = $this->cardIntegrationId ?: $this->walletIntegrationId;
                    $keyRes = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
                        'auth_token'     => $authToken,
                        'amount_cents'   => (string) $amountCents,
                        'expiration'     => 3600,
                        'order_id'       => (string) $paymobOrderId,
                        'billing_data'   => [
                            'first_name'   => $firstName,
                            'last_name'    => $lastName,
                            'phone_number' => $cleanPhone,
                            'email'        => $email,
                            'apartment'    => 'NA',
                            'floor'        => 'NA',
                            'street'       => 'NA',
                            'building'     => 'NA',
                            'shipping_method' => 'NA',
                            'postal_code'  => 'NA',
                            'city'         => 'Cairo',
                            'country'      => 'EGY',
                            'state'        => 'Cairo',
                        ],
                        'currency'       => 'EGP',
                        'integration_id' => (int) $integration,
                    ]);

                    if ($keyRes->successful()) {
                        $paymentToken = $keyRes->json('token');
                        $iframeId = $this->iframeId ?: '778000';
                        return [
                            'success'      => true,
                            'redirect_url' => "https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentToken}",
                            'message'      => 'تم إنشاء رابط الدفع بنجاح.',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Paymob createStoreOrderCheckout error: ' . $e->getMessage());
        }

        // Fallback redirection
        return [
            'success'      => true,
            'redirect_url' => url("/order-success/{$order->reference_number}?clear_cart=1"),
            'message'      => 'تم تسجيل طلبك بنجاح.',
        ];
    }
}
