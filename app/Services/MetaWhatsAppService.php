<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppService
{
    protected string $phoneNumberId;
    protected string $accessToken;
    protected string $wabaId;
    protected string $templateName;
    protected string $templateLanguage;
    protected string $apiVersion;
    protected float $costPerOrder;

    public function __construct()
    {
        $this->phoneNumberId    = (string) Setting::get('meta_phone_number_id', config('services.meta_whatsapp.phone_number_id', 'TEST_PHONE_ID_1029384756'));
        $this->accessToken      = (string) Setting::get('meta_access_token', config('services.meta_whatsapp.access_token', 'EAAB...TEST_TOKEN'));
        $this->wabaId           = (string) Setting::get('meta_waba_id', config('services.meta_whatsapp.waba_id', 'TEST_WABA_ID'));
        $this->templateName     = (string) Setting::get('meta_template_name', 'order_confirmation');
        $this->templateLanguage = (string) Setting::get('meta_template_language', 'ar');
        $this->costPerOrder     = (float) Setting::get('meta_cost_per_order', 1.00);
        $this->apiVersion       = 'v19.0';
    }

    /**
     * Check if Meta WhatsApp credentials are fully configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->phoneNumberId) 
            && !empty($this->accessToken) 
            && !str_starts_with($this->phoneNumberId, 'TEST_') 
            && !str_starts_with($this->accessToken, 'EAAB...TEST');
    }

    /**
     * Standardize phone number for WhatsApp (E.164 without leading +)
     * e.g. 01012345678 -> 201012345678
     */
    public function formatPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // If Egyptian number starting with 01
        if (preg_match('/^01[0-9]{9}$/', $cleaned)) {
            return '2' . $cleaned;
        }
        
        // If already starts with 20
        if (preg_match('/^201[0-9]{9}$/', $cleaned)) {
            return $cleaned;
        }

        return $cleaned;
    }

    /**
     * Clean and sanitize template parameter for Meta Cloud API.
     * Meta rejects parameters containing newlines, tabs, or >= 4 consecutive spaces.
     */
    protected function sanitizeParam(?string $text, string $fallback = '-'): string
    {
        if ($text === null || trim($text) === '') {
            return $fallback;
        }

        // Replace carriage returns, newlines and tabs with single space
        $clean = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $text);

        // Collapse multiple spaces into single space
        $clean = preg_replace('/ {2,}/', ' ', $clean);

        $clean = trim($clean);

        return !empty($clean) ? mb_substr($clean, 0, 1000) : $fallback;
    }

    /**
     * Deduct order confirmation fee from tenant wallet and record transaction.
     */
    protected function chargeTenantFee(Order $order, \App\Models\Tenant $tenant): void
    {
        if (($order->whatsapp_charge_amount ?? 0) <= 0 && $this->costPerOrder > 0) {
            $cost = $this->costPerOrder;
            $tenant->decrement('wallet_balance', $cost);

            \App\Models\WalletTransaction::create([
                'tenant_id'   => $tenant->id,
                'amount'      => $cost,
                'type'        => 'debit',
                'description' => 'رسوم رسالة تأكيد واتساب للطلب رقم (' . $order->reference_number . ')',
            ]);

            $order->update([
                'whatsapp_charge_amount' => $cost,
            ]);

            // If remaining wallet balance is now below minimum required (3 EGP), auto-disable the service
            if ($tenant->fresh()->wallet_balance < 3) {
                Setting::set('auto_confirm_enabled', false, 'auto_confirm', $tenant->id);
                Log::info("Auto-confirm disabled automatically for Tenant #{$tenant->id} due to low balance (< 3 EGP).");
            }
        }
    }

    /**
     * Send interactive Order Confirmation message via Meta WhatsApp API.
     */
    public function sendOrderConfirmation(Order $order): array
    {
        $recipientPhone = $this->formatPhoneNumber($order->customer_phone);

        if (empty($recipientPhone) || strlen($recipientPhone) < 10) {
            $order->update([
                'whatsapp_status' => 'no_whatsapp',
            ]);

            return [
                'success' => false,
                'status'  => 'no_whatsapp',
                'error'   => 'رقم هاتف العميل غير صالح لإرسال واتساب.',
            ];
        }

        // Check Tenant Wallet Balance
        $cost = $this->costPerOrder;
        $tenant = \App\Models\Tenant::find($order->tenant_id);

        if (!$tenant || ($tenant->wallet_balance ?? 0) < $cost) {
            Log::warning("WhatsApp auto-confirmation skipped for Order #{$order->reference_number}: Tenant #{$order->tenant_id} wallet balance ({$tenant?->wallet_balance}) is less than cost ({$cost} EGP)");
            $order->update([
                'whatsapp_status' => 'failed',
            ]);

            return [
                'success' => false,
                'status'  => 'failed',
                'error'   => 'رصيد محفظة المتجر غير كافٍ لإرسال رسالة التأكيد التلقائي (أقل من ' . $cost . ' ج.م).',
            ];
        }

        // Format Items list (Meta requires NO newlines/tabs in template parameters)
        $itemsList = [];
        if (is_array($order->items)) {
            foreach ($order->items as $item) {
                $name = $item['name'] ?? $item['product_name'] ?? 'منتج';
                $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                $price = (float) ($item['price'] ?? 0);
                $cleanName = $this->sanitizeParam($name, 'منتج');
                $itemsList[] = "• {$cleanName} (×{$qty}) - " . number_format($price * $qty) . " ج.م";
            }
        }
        $itemsText = !empty($itemsList) ? implode(' | ', $itemsList) : 'تفاصيل الطلب';
        $itemsText = $this->sanitizeParam($itemsText, 'تفاصيل الطلب');

        // Check if in Test/Simulated Mode
        if (!$this->isConfigured()) {
            $simulatedMsgId = 'wamid.TEST_' . strtoupper(\Illuminate\Support\Str::random(24));
            $sentTime = now();

            $order->update([
                'whatsapp_status'        => 'pending',
                'whatsapp_message_id'    => $simulatedMsgId,
                'whatsapp_sent_at'       => $sentTime,
            ]);

            // Deduct service fee from wallet
            $this->chargeTenantFee($order, $tenant);

            Log::info("WhatsApp Confirmation simulated for Order #{$order->reference_number} to {$recipientPhone}");

            return [
                'success'    => true,
                'simulated'  => true,
                'message_id' => $simulatedMsgId,
                'phone'      => $recipientPhone,
            ];
        }

        // Sanitize all parameters for Meta Cloud API
        $customerName = $this->sanitizeParam($order->customer_name, 'عميلنا العزيز');
        $refNumber    = $this->sanitizeParam((string) $order->reference_number, (string) $order->id);
        $shippingText = $this->sanitizeParam(number_format($order->shipping_cost) . ' ج.م (' . ($order->governorate ?: 'شحن عادي') . ')', '0 ج.م');
        $totalText    = $this->sanitizeParam(number_format($order->total) . ' ج.م', '0 ج.م');
        $addressText  = $this->sanitizeParam($order->customer_address, ($order->governorate ?: 'العنوان المسجل بالطلب'));

        // Real Meta Cloud API Call
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        try {
            // Send Template Message
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $recipientPhone,
                'type'              => 'template',
                'template'          => [
                    'name'     => $this->templateName,
                    'language' => ['code' => $this->templateLanguage],
                    'components' => [
                        [
                            'type'       => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $customerName],
                                ['type' => 'text', 'text' => $refNumber],
                                ['type' => 'text', 'text' => $itemsText],
                                ['type' => 'text', 'text' => $shippingText],
                                ['type' => 'text', 'text' => $totalText],
                                ['type' => 'text', 'text' => $addressText],
                            ]
                        ],
                        [
                            'type'     => 'button',
                            'sub_type' => 'quick_reply',
                            'index'    => '0',
                            'parameters' => [
                                ['type' => 'payload', 'payload' => 'CONFIRM_ORDER_' . $order->id]
                            ]
                        ],
                        [
                            'type'     => 'button',
                            'sub_type' => 'quick_reply',
                            'index'    => '1',
                            'parameters' => [
                                ['type' => 'payload', 'payload' => 'CANCEL_ORDER_' . $order->id]
                            ]
                        ]
                    ]
                ]
            ];

            $response = Http::withoutVerifying()
                ->withToken($this->accessToken)
                ->timeout(12)
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['messages'][0]['id'] ?? 'wamid.' . \Illuminate\Support\Str::random(16);
                $sentTime = now();

                $order->update([
                    'whatsapp_status'        => 'pending',
                    'whatsapp_message_id'    => $messageId,
                    'whatsapp_sent_at'       => $sentTime,
                ]);

                // Deduct service fee from wallet
                $this->chargeTenantFee($order, $tenant);

                return [
                    'success'    => true,
                    'message_id' => $messageId,
                    'phone'      => $recipientPhone,
                ];
            }

            // Error response from Meta
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? 'فشل الاتصال بخوادم ميتا.';
            $errorCode = $errorBody['error']['code'] ?? 0;

            // If recipient phone has no WhatsApp
            $status = ($errorCode == 131026 || str_contains(strtolower($errorMessage), 'not a valid whatsapp user'))
                ? 'no_whatsapp'
                : 'failed';

            $order->update([
                'whatsapp_status' => $status,
            ]);

            Log::error("Meta WhatsApp API Error for Order #{$order->reference_number}:", $errorBody ?: [$response->body()]);

            return [
                'success' => false,
                'status'  => $status,
                'error'   => $errorMessage,
            ];

        } catch (\Throwable $e) {
            Log::error("Exception in MetaWhatsAppService@sendOrderConfirmation: " . $e->getMessage());

            $order->update([
                'whatsapp_status' => 'failed',
            ]);

            return [
                'success' => false,
                'status'  => 'failed',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a single test message from Super Admin to verify Meta credentials.
     */
    public function sendTestMessage(string $recipientPhone, string $customMessage = ''): array
    {
        $phone = $this->formatPhoneNumber($recipientPhone);

        if (empty($phone) || strlen($phone) < 10) {
            return [
                'success' => false,
                'message' => 'رقم الهاتف التجريبي غير صحيح.',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success'   => true,
                'simulated' => true,
                'message'   => "تمت المحاكاة بنجاح: الحساب في وضع الاختبار التجريبي. بمجرد إضافة توكن ومعرف ميتا الفعلي سيتم الإرسال الفوري للرقم ({$phone}).",
            ];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";
        $text = $customMessage ?: "مرحباً بك! هذه رسالة تجريبية لتأكيد ربط بوابة الواتساب الرسمية (Meta WhatsApp Cloud API) بنجاح 🚀";

        try {
            $response = Http::withoutVerifying()
                ->withToken($this->accessToken)
                ->timeout(10)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $phone,
                    'type'              => 'text',
                    'text'              => ['preview_url' => false, 'body' => $text]
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => "تم إرسال الرسالة التجريبية بنجاح إلى الرقم ({$phone}) عبر خوادم ميتا الرسمية.",
                    'data'    => $response->json(),
                ];
            }

            $error = $response->json();
            return [
                'success' => false,
                'message' => $error['error']['message'] ?? 'فشل إرسال الرسالة التجريبية من خوادم ميتا.',
                'error'   => $error,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء محاولة الاتصال: ' . $e->getMessage(),
            ];
        }
    }
}
