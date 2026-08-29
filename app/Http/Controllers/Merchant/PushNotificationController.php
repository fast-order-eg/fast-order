<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PushNotificationController extends Controller
{
    public function __construct(private PushNotificationService $pushService) {}

    /**
     * صفحة اعدادات الاشعارات.
     */
    public function index(Request $request)
    {
        $tenant    = $request->attributes->get('tenant');
        $tenantId  = $tenant->id;

        $deviceCount = PushSubscription::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        $settings = $tenant->settings['push_notifications'] ?? [
            'enabled'        => true,
            'new_orders'     => true,
        ];

        return Inertia::render('Merchant/Settings/PushNotifications', [
            'vapidPublicKey' => PushNotificationService::getVapidPublicKey(),
            'deviceCount'    => $deviceCount,
            'settings'       => $settings,
        ]);
    }

    /**
     * تسجيل اشتراك جهاز جديد.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint'          => 'required|string',
            'keys.p256dh'       => 'required|string',
            'keys.auth'         => 'required|string',
            'content_encoding'  => 'nullable|string',
            'device_name'       => 'nullable|string|max:100',
        ]);

        $tenant   = $request->attributes->get('tenant');
        $tenantId = $tenant->id;
        $userId   = auth()->id();
        $endpointHash = hash('sha256', $validated['endpoint']);

        PushSubscription::updateOrCreate(
            [
                'tenant_id'     => $tenantId,
                'endpoint_hash' => $endpointHash,
            ],
            [
                'user_id'          => $userId,
                'endpoint'         => $validated['endpoint'],
                'public_key'       => $validated['keys']['p256dh'],
                'auth_token'       => $validated['keys']['auth'],
                'content_encoding' => $validated['content_encoding'] ?? 'aesgcm',
                'device_name'      => $validated['device_name'] ?? 'Unknown Device',
                'is_active'        => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل الإشعارات بنجاح على هذا الجهاز!',
        ]);
    }

    /**
     * الغاء الاشتراك.
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $tenant       = $request->attributes->get('tenant');
        $endpointHash = hash('sha256', $validated['endpoint']);

        PushSubscription::where('tenant_id', $tenant->id)
            ->where('endpoint_hash', $endpointHash)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء تفعيل الإشعارات لهذا الجهاز.',
        ]);
    }

    /**
     * ارسال اشعار تجريبي.
     */
    public function sendTest(Request $request)
    {
        $tenant   = $request->attributes->get('tenant');
        $tenantId = $tenant->id;

        $count = PushSubscription::where('tenant_id', $tenantId)->where('is_active', true)->count();

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'لا يوجد اجهزة مشتركة. فعّل الاشعارات اولاً.'], 422);
        }

        try {
            $this->pushService->sendToTenant(
                $tenantId,
                'اشعار تجريبي',
                'الاشعارات تعمل بنجاح! ستصلك اشعارات الطلبات الجديدة فوراً.',
                '/admin/orders',
                ['type' => 'test']
            );

            return response()->json(['success' => true, 'message' => 'تم ارسال الاشعار التجريبي!']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'فشل ارسال الاشعار: ' . $e->getMessage()], 500);
        }
    }

    /**
     * تحديث اعدادات الاشعارات.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled'    => 'required|boolean',
            'new_orders' => 'required|boolean',
        ]);

        $tenant   = $request->attributes->get('tenant');
        $settings = $tenant->settings ?? [];
        $settings['push_notifications'] = $validated;
        $tenant->update(['settings' => $settings]);

        return response()->json(['success' => true, 'message' => 'تم حفظ الاعدادات.']);
    }

    /**
     * ارجاع المفتاح العام VAPID.
     */
    public function vapidPublicKey()
    {
        return response()->json(['public_key' => PushNotificationService::getVapidPublicKey()]);
    }
}
