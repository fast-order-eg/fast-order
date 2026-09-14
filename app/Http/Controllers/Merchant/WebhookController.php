<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookLog;
use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WebhookController extends Controller
{
    /**
     * عرض قائمة الـ Webhooks
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $webhooks = Webhook::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('url', 'like', '%' . $q . '%');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Webhook::count(),
            'active' => Webhook::where('is_active', true)->count(),
            'logs_count' => WebhookLog::whereIn('webhook_id', Webhook::pluck('id'))->count(),
        ];

        return Inertia::render('Merchant/Webhooks/Index', [
            'webhooks' => $webhooks,
            'filters' => [
                'q' => $q
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * حفظ Webhook جديد
     */
    public function store(StoreWebhookRequest $request)
    {
        $validated = $request->validated();

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        Webhook::create($validated);

        return redirect()->route('merchant.webhooks.index')
            ->with('success', 'تم إنشاء الـ Webhook بنجاح ✓');
    }

    /**
     * تحديث Webhook
     */
    public function update(UpdateWebhookRequest $request, Webhook $webhook)
    {
        $validated = $request->validated();

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : $webhook->is_active;

        $webhook->update($validated);

        return redirect()->route('merchant.webhooks.index')
            ->with('success', 'تم تحديث الـ Webhook بنجاح ✓');
    }

    /**
     * تفعيل/تعطيل الـ Webhook
     */
    public function toggle(Webhook $webhook)
    {
        $webhook->update([
            'is_active' => !$webhook->is_active
        ]);

        $message = $webhook->is_active ? 'تم تفعيل الـ Webhook بنجاح ✓' : 'تم تعطيل الـ Webhook بنجاح ✓';
        return redirect()->route('merchant.webhooks.index')->with('success', $message);
    }

    /**
     * حذف الـ Webhook
     */
    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return redirect()->route('merchant.webhooks.index')
            ->with('success', 'تم حذف الـ Webhook بنجاح ✓');
    }

    /**
     * عرض سجل المكالمات الأخيرة
     */
    public function logs(Webhook $webhook)
    {
        $logs = $webhook->logs()->latest()->take(50)->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'event' => $log->event,
                'payload' => $log->payload,
                'response_status' => $log->response_status,
                'response_body' => $log->response_body,
                'duration_ms' => $log->duration_ms,
                'created_at' => $log->created_at->toDateTimeString(),
            ];
        });

        return response()->json($logs);
    }

    /**
     * تجربة إرسال حدث وهمي للتأكد من ربط سيرفر/تطبيق العميل
     */
    public function test(Webhook $webhook)
    {
        $tenant = app(\App\Models\Tenant::class);
        abort_unless($webhook->tenant_id === $tenant->id, 403);

        $mockPayload = [
            'id' => 99999,
            'reference_number' => 'TEST-' . rand(1000, 9999),
            'customer_name' => 'عميل تجريبي (فاست أوردر)',
            'customer_phone' => '01012345678',
            'customer_address' => 'شارع التحرير، الدقي',
            'governorate' => 'الجيزة',
            'payment_method' => 'الدفع عند الاستلام (COD)',
            'payment_status' => 'pending',
            'subtotal' => 350,
            'shipping_cost' => 50,
            'total' => 400,
            'status' => 'pending',
            'notes' => 'طلب تجريبي للتأكد من نجاح ربط تطبيق العميل مع فاست أوردر',
            'items' => [
                [
                    'id' => 1,
                    'name' => 'منتج تجريبي للتكامل',
                    'quantity' => 1,
                    'price' => 350,
                ]
            ],
            'created_at' => now()->toDateTimeString(),
        ];

        $result = \App\Services\WebhookSender::sendSingleWebhook($webhook, 'order.created', $mockPayload);

        $statusText = $result['status'] ? "كود الاستجابة: {$result['status']}" : "فشل الاتصال بالسيرفر";
        if ($result['status'] >= 200 && $result['status'] < 300) {
            return back()->with('success', "تم إرسال الحدث التجريبي بنجاح! ({$statusText}) ✓");
        } else {
            return back()->with('error', "تم إرسال الحدث لكن السيرفر رد بالخطأ ({$statusText})، يرجى مراجعة سجل الـ Logs.");
        }
    }
}
