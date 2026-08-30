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
     * الحصول على رابط لوجو المتجر للإشعار.
     */
    public function getTenantLogo(int $tenantId): string
    {
        try {
            $logo = \App\Models\Setting::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('key', 'logo')->value('value');
            if ($logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo)) {
                return asset('storage/' . $logo);
            }
            $tenant = \App\Models\Tenant::find($tenantId);
            if ($tenant && $tenant->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($tenant->logo)) {
                return asset('storage/' . $tenant->logo);
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return asset('images/notification-icon.png');
    }

    /**
     * ارسال اشعار لكل اجهزة التاجر المشتركة.
     */
    public function sendToTenant(int $tenantId, string $title, string $body, string $url = '', array $extra = [], ?string $icon = null): void
    {
        $subscriptions = PushSubscription::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $notificationIcon = $icon ?: $this->getTenantLogo($tenantId);

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
            'icon'  => $notificationIcon,
            'badge' => '/images/notification-badge.png',
            'data'  => array_merge($extra, ['url' => $url]),
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
        
        // استخراج الاسم الأول فقط (فردي)
        $customerFullName = trim($orderData['customer_name'] ?? 'عميل');
        $nameParts = preg_split('/\s+/', $customerFullName);
        $firstName = !empty($nameParts[0]) ? $nameParts[0] : 'عميل';

        $orderId  = $orderData['id'] ?? '';
        
        // فحص باقة التاجر والرصيد لتحديد مسار التوجيه
        $tenant = \App\Models\Tenant::find($tenantId);
        $isLocked = false;
        if ($tenant && $tenant->isCommissionPlan()) {
            if (($tenant->wallet_balance ?? 0) < 2) {
                $isLocked = true;
            }
        }

        // إذا كان الطلب مقفولاً بسبب الرصيد يتم توجيهه إلى صفحة الطلبات العامة فقط
        $url = $isLocked ? "/admin/orders" : "/admin/orders/{$orderId}";
        $storeLogo = $this->getTenantLogo($tenantId);

        $this->sendToTenant(
            $tenantId,
            "طلب جديد {$orderRef}",
            "العميل: {$firstName} | {$total} ج.م",
            $url,
            ['order_id' => $orderId, 'type' => 'new_order'],
            $storeLogo
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
