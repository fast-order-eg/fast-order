<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle Meta Webhook Verification Challenge.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $storedVerifyToken = Setting::get('meta_webhook_verify_token', 'fastorder_wa_secret_2026');

        if ($mode === 'subscribe' && $token === $storedVerifyToken) {
            Log::info("Meta WhatsApp Webhook verified successfully.");
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning("Meta WhatsApp Webhook verification failed.", [
            'received_token' => $token,
            'expected_token' => $storedVerifyToken,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle Incoming WhatsApp Webhook Events (Messages, Quick Reply Buttons, Statuses).
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info("WhatsApp Webhook Event Received:", $payload);

        try {
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];

                    // 1. Process Messages (Button Clicks / Text Replies)
                    if (!empty($value['messages'])) {
                        foreach ($value['messages'] as $message) {
                            $this->processIncomingMessage($message, $value['contacts'] ?? []);
                        }
                    }

                    // 2. Process Message Status Updates (delivered, read, failed)
                    if (!empty($value['statuses'])) {
                        foreach ($value['statuses'] as $status) {
                            $this->processMessageStatus($status);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("Error processing WhatsApp Webhook: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        return response()->json(['status' => 'EVENT_RECEIVED'], 200);
    }

    /**
     * Process incoming customer response.
     */
    protected function processIncomingMessage(array $message, array $contacts): void
    {
        $fromPhone = $message['from'] ?? '';
        $msgType = $message['type'] ?? 'text';
        $payloadAction = null;
        $orderId = null;

        // A. Quick reply button (Template button click)
        if ($msgType === 'button') {
            $payloadAction = $message['button']['payload'] ?? $message['button']['text'] ?? null;
        } 
        // B. Interactive list / button reply
        elseif ($msgType === 'interactive') {
            $payloadAction = $message['interactive']['button_reply']['id'] 
                ?? $message['interactive']['button_reply']['title'] 
                ?? null;
        }
        // C. Standard text reply
        elseif ($msgType === 'text') {
            $text = trim($message['text']['body'] ?? '');
            $payloadAction = $this->parseTextToIntent($text);
        }

        if (!$payloadAction) {
            return;
        }

        // Try extracting order ID from payload (e.g. CONFIRM_ORDER_48)
        if (preg_match('/^(CONFIRM|CANCEL)_ORDER_(\d+)$/i', $payloadAction, $matches)) {
            $actionType = strtoupper($matches[1]);
            $orderId = (int) $matches[2];
            $order = Order::find($orderId);
        } else {
            // Find latest pending order matching customer phone
            $formattedPhoneSuffix = substr($fromPhone, -9); // last 9 digits
            $order = Order::where('customer_phone', 'like', "%{$formattedPhoneSuffix}")
                ->where('whatsapp_status', 'pending')
                ->latest()
                ->first();

            $actionType = str_contains($payloadAction, 'CONFIRM') ? 'CONFIRM' : (str_contains($payloadAction, 'CANCEL') ? 'CANCEL' : null);
        }

        if (!$order) {
            Log::info("No matching pending order found for WhatsApp reply from: {$fromPhone}, Payload: {$payloadAction}");
            return;
        }

        if ($actionType === 'CONFIRM') {
            $this->confirmOrderViaWhatsApp($order);
        } elseif ($actionType === 'CANCEL') {
            $this->cancelOrderViaWhatsApp($order);
        }
    }

    /**
     * Map text replies to intents.
     */
    protected function parseTextToIntent(string $text): ?string
    {
        $lower = mb_strtolower($text, 'UTF-8');

        $confirmWords = ['تأكيد', 'تاكيد', 'موافق', 'نعم', 'تمام', 'أكد', 'اكد', '1', 'ok', 'yes', 'confirm'];
        foreach ($confirmWords as $word) {
            if (str_contains($lower, $word)) {
                return 'CONFIRM';
            }
        }

        $cancelWords = ['إلغاء', 'الغاء', 'لا', 'مش عايز', 'ملغي', '2', 'no', 'cancel'];
        foreach ($cancelWords as $word) {
            if (str_contains($lower, $word)) {
                return 'CANCEL';
            }
        }

        return null;
    }

    /**
     * Confirm Order via WhatsApp & Auto-Dispatch Shipping.
     */
    protected function confirmOrderViaWhatsApp(Order $order): void
    {
        $nowTime = now();
        $timeStr = $nowTime->format('Y-m-d H:i:s');

        $order->update([
            'status'               => 'confirmed',
            'whatsapp_status'      => 'confirmed',
            'whatsapp_response_at' => $nowTime,
            'notes'                => trim(($order->notes ? $order->notes . "\n" : '') . "✅ [واتساب] تم التأكيد بواسطة الواتس من العميل في تمام {$timeStr}"),
        ]);

        Log::info("Order #{$order->reference_number} confirmed via WhatsApp Webhook.");

        // Automatically dispatch to shipping if merchant has auto dispatch enabled
        $this->dispatchOrderToShipping($order);
    }

    /**
     * Cancel Order via WhatsApp & Restore Stock.
     */
    protected function cancelOrderViaWhatsApp(Order $order): void
    {
        $nowTime = now();
        $timeStr = $nowTime->format('Y-m-d H:i:s');

        if ($order->status !== 'cancelled') {
            $this->restoreOrderStock($order);
        }

        $order->update([
            'status'               => 'cancelled',
            'whatsapp_status'      => 'cancelled',
            'whatsapp_response_at' => $nowTime,
            'notes'                => trim(($order->notes ? $order->notes . "\n" : '') . "❌ [واتساب] تم الإلغاء بواسطة الواتس من العميل في تمام {$timeStr}"),
        ]);

        Log::info("Order #{$order->reference_number} cancelled via WhatsApp Webhook.");
    }

    /**
     * Dispatch to shipping carrier automatically upon confirmation.
     */
    protected function dispatchOrderToShipping(Order $order): void
    {
        try {
            // Check merchant auto dispatch setting
            $enabled = (bool) Setting::where('tenant_id', $order->tenant_id)
                ->where('key', 'auto_dispatch_shipping')
                ->value('value');

            if (!$enabled) return;

            // Prevent duplicate shipments
            $exists = Shipment::where('order_id', $order->id)->exists();
            if ($exists) return;

            $provider = Setting::where('tenant_id', $order->tenant_id)
                ->where('key', 'auto_dispatch_provider')
                ->value('value') ?: 'bosta';

            $shippingManager = new ShippingManager();
            $shipment = $shippingManager->createShipment($order, $provider);

            if ($shipment && $order->status !== 'shipped') {
                $order->update(['status' => 'shipped']);
                Log::info("Order #{$order->reference_number} auto-dispatched to shipping carrier ({$provider}) via WhatsApp confirmation.");
            }
        } catch (\Throwable $e) {
            Log::warning("Auto shipping dispatch failed for WhatsApp confirmed Order #{$order->id}: " . $e->getMessage());
        }
    }

    /**
     * Restore stock for cancelled order.
     */
    protected function restoreOrderStock(Order $order): void
    {
        if (empty($order->items) || !is_array($order->items)) {
            return;
        }

        foreach ($order->items as $item) {
            $productId = $item['product_id'] ?? $item['id'] ?? null;
            $variantId = $item['variant_id'] ?? null;
            $quantity  = (int) ($item['quantity'] ?? $item['qty'] ?? 1);

            if ($variantId) {
                $variant = ProductVariant::find($variantId);
                if ($variant && $variant->stock_quantity !== null) {
                    $variant->increment('stock_quantity', $quantity);
                }
            } elseif ($productId) {
                $product = Product::find($productId);
                if ($product && $product->stock_quantity !== null) {
                    $product->increment('stock_quantity', $quantity);
                }
            }
        }
    }

    /**
     * Process message delivery/read/failed statuses.
     */
    protected function processMessageStatus(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $statusType = $status['status'] ?? null; // delivered, read, failed

        if (!$messageId) return;

        $order = Order::where('whatsapp_message_id', $messageId)->first();
        if (!$order) return;

        if ($statusType === 'failed') {
            $errors = $status['errors'] ?? [];
            $errorCode = $errors[0]['code'] ?? 0;

            if ($errorCode == 131026) {
                $order->update([
                    'whatsapp_status' => 'no_whatsapp',
                    'notes' => trim(($order->notes ? $order->notes . "\n" : '') . '⚠️ [واتساب] لا يوجد حساب واتساب لرقم العميل، يرجى الاتصال هاتفياً للتأكيد.'),
                ]);
            }
        }
    }
}
