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
        $order = null;
        $orderId = null;
        $rawText = '';
        $mediaType = 'text';
        $mediaUrl = null;
        $caption = null;

        // A. Quick reply button (Template button click)
        if ($msgType === 'button') {
            $payloadAction = $message['button']['payload'] ?? $message['button']['text'] ?? null;
            $rawText = (string) ($message['button']['text'] ?? $message['button']['payload'] ?? 'زر استجابة');
        } 
        // B. Interactive list / button reply
        elseif ($msgType === 'interactive') {
            $payloadAction = $message['interactive']['button_reply']['id'] 
                ?? $message['interactive']['button_reply']['title'] 
                ?? null;
            $rawText = (string) ($message['interactive']['button_reply']['title'] ?? $message['interactive']['button_reply']['id'] ?? 'رد تفاعلي');
        }
        // C. Standard text reply
        elseif ($msgType === 'text') {
            $rawText = trim((string) ($message['text']['body'] ?? ''));
            $payloadAction = $this->parseTextToIntent($rawText);
        }
        // D. Media message (image, audio, voice note, video, document, sticker)
        elseif (in_array($msgType, ['image', 'audio', 'voice', 'video', 'document', 'sticker'])) {
            $mediaObj = $message[$msgType] ?? [];
            $mediaId = $mediaObj['id'] ?? null;
            $mimeType = $mediaObj['mime_type'] ?? null;
            $caption = trim((string)($mediaObj['caption'] ?? ''));
            $origFilename = $mediaObj['filename'] ?? null;
            
            $mediaType = in_array($msgType, ['audio', 'voice']) ? 'audio' : ($msgType === 'sticker' ? 'image' : $msgType);
            
            if ($mediaId) {
                try {
                    $whatsAppService = new \App\Services\MetaWhatsAppService();
                    $mediaUrl = $whatsAppService->downloadMedia($mediaId, $mimeType, $origFilename);
                } catch (\Throwable $e) {
                    Log::error("Failed to download WhatsApp media {$mediaId}: " . $e->getMessage());
                }
            }

            $rawText = $caption ?: match ($mediaType) {
                'image'    => '📷 صورة مرسلة من العميل',
                'audio'    => '🎤 رسالة صوتية من العميل',
                'video'    => '🎥 مقطع فيديو من العميل',
                'document' => '📄 مستند من العميل',
                default    => "[ملف {$msgType}]",
            };
        } else {
            $rawText = "[رسالة {$msgType}]";
        }

        if (empty($rawText) && empty($payloadAction)) {
            return;
        }

        // 1. Try matching Order directly by Meta context message ID (wamid of sent template)
        $contextMsgId = $message['context']['id'] ?? null;
        if ($contextMsgId) {
            $order = Order::where('whatsapp_message_id', $contextMsgId)->first();
        }

        // 2. Try extracting order ID from payload (e.g. CONFIRM_ORDER_48)
        if ($payloadAction && preg_match('/^(CONFIRM|CANCEL)_ORDER_(\d+)$/i', (string) $payloadAction, $matches)) {
            $actionType = strtoupper($matches[1]);
            if (!$order) {
                $orderId = (int) $matches[2];
                $order = Order::find($orderId);
            }
        } else {
            if ($payloadAction && str_contains((string)$payloadAction, 'CONFIRM')) {
                $actionType = 'CONFIRM';
            } elseif ($payloadAction && str_contains((string)$payloadAction, 'CANCEL')) {
                $actionType = 'CANCEL';
            } else {
                $actionType = $this->parseTextToIntent($rawText);
            }
        }

        // 3. Fallback: Match by customer phone number
        if (!$order && !empty($fromPhone)) {
            $cleanDigits = preg_replace('/[^\d]/', '', $fromPhone);
            $formattedPhoneSuffix = substr($cleanDigits, -9);

            // نفضل أولاً الطلبات التي بانتظار رد الواتس
            $order = Order::where('customer_phone', 'like', "%{$formattedPhoneSuffix}")
                ->where('whatsapp_status', 'pending')
                ->latest()
                ->first();

            // إذا لم نجد طلب معلق، نطابق أحدث طلب تم إرسال واتس له للعميل (لتسجيل كل رسائل العميل اللاحقة)
            if (!$order) {
                $order = Order::where('customer_phone', 'like', "%{$formattedPhoneSuffix}")
                    ->whereNotNull('whatsapp_sent_at')
                    ->latest()
                    ->first();
            }

            // ملاذ أخير: أحدث طلب للعميل
            if (!$order) {
                $order = Order::where('customer_phone', 'like', "%{$formattedPhoneSuffix}")
                    ->latest()
                    ->first();
            }
        }

        if (!$order) {
            Log::info("No matching order found for WhatsApp reply from: {$fromPhone}, Text: {$rawText}");
            return;
        }

        // تسجيل الرسالة والملاحظات بالتوقيت الدقيق وتحديث الحالة
        $this->recordCustomerReplyAndProcess($order, $rawText, $actionType, $mediaType, $mediaUrl, $caption);
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
     * Record customer reply in whatsapp_replies and notes, and update status accordingly.
     */
    protected function recordCustomerReplyAndProcess(
        Order $order, 
        string $rawText, 
        ?string $actionType,
        string $mediaType = 'text',
        ?string $mediaUrl = null,
        ?string $caption = null
    ): void
    {
        $nowTime = now();
        $period = $nowTime->format('A') === 'PM' ? 'م' : 'ص';
        $time12 = $nowTime->format('Y-m-d h:i') . ' ' . $period;

        // 1. تسجيل الرد في مصفوفة whatsapp_replies
        $replies = is_array($order->whatsapp_replies)
            ? $order->whatsapp_replies
            : (json_decode($order->whatsapp_replies ?? '[]', true) ?: []);

        $replies[] = [
            'sender'    => 'customer',
            'text'      => $rawText,
            'action'    => $actionType ?: 'CUSTOM',
            'time'      => $time12,
            'type'      => $mediaType,
            'media_url' => $mediaUrl,
            'caption'   => $caption,
        ];

        // 2. تدوين الرد في الملاحظات / سجل الطلب
        $existingNotes = trim((string) $order->notes);
        $actionLabel = $actionType === 'CONFIRM' ? 'تأكيد' : ($actionType === 'CANCEL' ? 'إلغاء' : 'رد العميل');
        $mediaNoteSuffix = $mediaUrl ? " (ملف مرفق: {$mediaUrl})" : '';
        $noteEntry = "💬 [واتساب {$time12} - {$actionLabel}]: {$rawText}{$mediaNoteSuffix}";
        $newNotes = $existingNotes ? ($existingNotes . "\n" . $noteEntry) : $noteEntry;

        $updateData = [
            'whatsapp_replies'     => $replies,
            'whatsapp_response_at' => $nowTime,
            'notes'                => $newNotes,
        ];

        if ($actionType === 'CONFIRM') {
            $updateData['status'] = 'confirmed';
            $updateData['whatsapp_status'] = 'confirmed';
            $order->update($updateData);

            Log::info("Order #{$order->reference_number} confirmed via WhatsApp. Customer message: {$rawText}");
            $this->dispatchOrderToShipping($order);
        } elseif ($actionType === 'CANCEL') {
            if ($order->status !== 'cancelled') {
                $this->restoreOrderStock($order);
            }
            $updateData['status'] = 'cancelled';
            $updateData['whatsapp_status'] = 'cancelled';
            $order->update($updateData);

            Log::info("Order #{$order->reference_number} cancelled via WhatsApp. Customer message: {$rawText}");
        } else {
            // رد مخصص من العميل بدون تأكيد أو إلغاء صريح
            $order->update($updateData);
            Log::info("Recorded custom WhatsApp customer message for Order #{$order->reference_number}: {$rawText}");
        }
    }

    /**
     * Confirm Order via WhatsApp & Auto-Dispatch Shipping.
     */
    protected function confirmOrderViaWhatsApp(Order $order): void
    {
        $this->recordCustomerReplyAndProcess($order, 'تأكيد الطلب', 'CONFIRM');
    }

    /**
     * Cancel Order via WhatsApp & Restore Stock.
     */
    protected function cancelOrderViaWhatsApp(Order $order): void
    {
        $this->recordCustomerReplyAndProcess($order, 'إلغاء الطلب', 'CANCEL');
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
                ]);
            }
        }
    }
}
