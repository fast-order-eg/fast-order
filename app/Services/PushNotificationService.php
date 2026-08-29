<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $publicKey  = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');
        $subject    = config('services.vapid.subject', config('app.url'));

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => $subject,
                'publicKey'  => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $this->webPush->setReuseVAPIDHeaders(true);
    }

    /**
     * ارسال اشعار لكل اجهزة التاجر المشتركة.
     */
    public function sendToTenant(int $tenantId, string $title, string $body, string $url = '', array $extra = []): void
    {
        $subscriptions = PushSubscription::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
            'icon'  => '/images/notification-icon.png',
            'badge' => '/images/notification-badge.png',
            'data'  => $extra,
        ]);

        $failedIds = [];

        foreach ($subscriptions as $sub) {
            try {
                $subscription = new Subscription(
                    $sub->endpoint,
                    $sub->public_key,
                    $sub->auth_token,
                    $sub->content_encoding ?: 'aesgcm'
                );

                $report = $this->webPush->sendOneNotification($subscription, $payload);

                if ($report->isSubscriptionExpired()) {
                    $failedIds[] = $sub->id;
                }
            } catch (\Throwable $e) {
                Log::warning("Push notification failed for subscription #{$sub->id}: " . $e->getMessage());
                if (str_contains($e->getMessage(), '410') || str_contains($e->getMessage(), 'expired')) {
                    $failedIds[] = $sub->id;
                }
            }
        }

        if (!empty($failedIds)) {
            PushSubscription::whereIn('id', $failedIds)->delete();
        }
    }

    /**
     * ارسال اشعار طلب جديد للتاجر.
     */
    public function notifyNewOrder(int $tenantId, array $orderData): void
    {
        $orderRef = $orderData['reference_number'] ?? '#???';
        $total    = number_format((float)($orderData['total'] ?? 0), 2);
        $customer = $orderData['customer_name'] ?? 'عميل';
        $orderId  = $orderData['id'] ?? '';
        $url      = "/admin/orders/{$orderId}";

        $this->sendToTenant(
            $tenantId,
            "طلب جديد {$orderRef}",
            "العميل: {$customer} | {$total} ج.م",
            $url,
            ['order_id' => $orderId, 'type' => 'new_order']
        );
    }

    /**
     * الحصول على المفتاح العام VAPID.
     */
    public static function getVapidPublicKey(): string
    {
        return config('services.vapid.public_key', '');
    }
}
