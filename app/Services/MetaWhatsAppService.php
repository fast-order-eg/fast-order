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
     * Send interactive Order Confirmation message via Meta WhatsApp API.
     */
    public function sendOrderConfirmation(Order $order): array
    {
        $recipientPhone = $this->formatPhoneNumber($order->customer_phone);

        if (empty($recipientPhone) || strlen($recipientPhone) < 10) {
            $order->update([
                'whatsapp_status' => 'no_whatsapp',
                'notes' => trim(($order->notes ? $order->notes . "\n" : '') . '⚠️ [واتساب] رقم الهاتف غير صالح لإرسال رسالة واتساب.'),
            ]);

            return [
                'success' => false,
                'status'  => 'no_whatsapp',
                'error'   => 'رقم هاتف العميل غير صالح لإرسال واتساب.',
            ];
        }

        // Format Items list
        $itemsText = '';
        if (is_array($order->items)) {
            foreach ($order->items as $item) {
                $name = $item['name'] ?? $item['product_name'] ?? 'منتج';
                $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                $price = $item['price'] ?? 0;
                $itemsText .= "• {$name} (العدد: {$qty}) - " . number_format($price * $qty) . " ج.م\n";
            }
        }
        $itemsText = trim($itemsText) ?: 'تفاصيل الطلب';

        // Check if in Test/Simulated Mode
        if (!$this->isConfigured()) {
            $simulatedMsgId = 'wamid.TEST_' . strtoupper(\Illuminate\Support\Str::random(24));
            $sentTime = now();

            $order->update([
                'whatsapp_status'        => 'pending',
                'whatsapp_message_id'    => $simulatedMsgId,
                'whatsapp_sent_at'       => $sentTime,
                'whatsapp_charge_amount' => $this->costPerOrder,
                'notes'                  => trim(($order->notes ? $order->notes . "\n" : '') . "💬 [واتساب] تم إرسال رسالة التأكيد عبر الواتساب في {$sentTime->format('Y-m-d H:i:s')} (معرف الرسالة: {$simulatedMsgId})"),
            ]);

            Log::info("WhatsApp Confirmation simulated for Order #{$order->reference_number} to {$recipientPhone}");

            return [
                'success'    => true,
                'simulated'  => true,
                'message_id' => $simulatedMsgId,
                'phone'      => $recipientPhone,
            ];
        }

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
                                ['type' => 'text', 'text' => $order->customer_name ?: 'عميلنا العزيز'],
                                ['type' => 'text', 'text' => $order->reference_number],
                                ['type' => 'text', 'text' => $itemsText],
                                ['type' => 'text', 'text' => (string) number_format($order->shipping_cost) . ' ج.م (' . $order->governorate . ')'],
                                ['type' => 'text', 'text' => (string) number_format($order->total) . ' ج.م'],
                                ['type' => 'text', 'text' => $order->customer_address],
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
                    'whatsapp_charge_amount' => $this->costPerOrder,
                    'notes'                  => trim(($order->notes ? $order->notes . "\n" : '') . "💬 [واتساب] تم إرسال رسالة التأكيد عبر الواتساب في {$sentTime->format('Y-m-d H:i:s')} (معرف الرسالة: {$messageId})"),
                ]);

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

            $statusText = $status === 'no_whatsapp' 
                ? '⚠️ [واتساب] الرقم لا يمتلك حساب واتساب، يرجى الاتصال بالعميل هاتفياً للتأكيد.' 
                : "⚠️ [واتساب] فشل إرسال رسالة الواتساب: {$errorMessage}";

            $order->update([
                'whatsapp_status' => $status,
                'notes'           => trim(($order->notes ? $order->notes . "\n" : '') . $statusText),
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
                'notes'           => trim(($order->notes ? $order->notes . "\n" : '') . '⚠️ [واتساب] تعذر إرسال الرسالة نظراً لخطأ في الاتصال بالشبكة.'),
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
