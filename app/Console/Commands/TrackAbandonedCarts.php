<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cart;
use App\Models\AbandonedCart;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TrackAbandonedCarts extends Command
{
    protected $signature = 'carts:track-abandoned';
    protected $description = 'تتبع السلات المتروكة التي مر عليها أكثر من ساعة وتغذية جدول السلات المتروكة';

    public function handle(): void
    {
        $this->info('بدء فحص وتتبع السلات المتروكة...');

        // البحث عن السلات النشطة التي لم تُحدث منذ ساعة على الأقل ولديها عناصر
        $oneHourAgo = now()->subHour();
        
        $carts = Cart::where('updated_at', '<=', $oneHourAgo)
            ->whereHas('activeItems')
            ->with(['activeItems.product', 'tenant', 'user'])
            ->get();

        $count = 0;

        foreach ($carts as $cart) {
            // التحقق مما إذا كان هناك طلب تم إنشاؤه بالفعل بنفس الجلسة أو لنفس المستخدم بعد آخر تحديث للسلة
            // لمنع اعتبار السلة متروكة إذا كان العميل قد أكمل الشراء بالفعل
            $alreadyRecovered = AbandonedCart::where('tenant_id', $cart->tenant_id)
                ->where(function ($query) use ($cart) {
                    if ($cart->user_id) {
                        $query->where('user_id', $cart->user_id);
                    } else {
                        $query->where('session_id', $cart->session_id);
                    }
                })
                ->whereNotNull('recovered_at')
                ->where('updated_at', '>=', $cart->updated_at)
                ->exists();

            if ($alreadyRecovered) {
                continue;
            }

            // تحديد البريد الإلكتروني والهاتف إذا كان المستخدم مسجلاً
            $email = $cart->user?->email;
            $phone = $cart->user?->phone; // إذا كان متوفراً في جدول المستخدمين

            // تحضير بيانات السلة بصيغة JSON
            $itemsData = $cart->activeItems->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product?->name ?? 'منتج غير معروف',
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'total' => (float) $item->total,
                'image' => $item->product?->main_image_path
                    ? asset('storage/' . $item->product->main_image_path)
                    : ($item->product?->image_url ?? null),
            ])->toArray();

            $subtotal = $cart->subtotal;
            
            // حساب الإجمالي التقريبي
            $settings = is_array($cart->tenant?->settings) 
                ? $cart->tenant->settings 
                : json_decode($cart->tenant?->settings ?? '{}', true);
            $taxRate = (float) ($settings['tax_rate'] ?? $settings['tax'] ?? 0);
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total = round($subtotal + $taxAmount, 2);

            $cartData = [
                'items' => $itemsData,
                'subtotal' => $subtotal,
                'tax' => $taxAmount,
                'tax_rate' => $taxRate,
                'total' => $total,
            ];

            // البحث عن سجل سلة متروكة غير مستردة لنفس الجلسة أو المستخدم
            $abandonedCart = AbandonedCart::where('tenant_id', $cart->tenant_id)
                ->where(function ($query) use ($cart) {
                    if ($cart->user_id) {
                        $query->where('user_id', $cart->user_id);
                    } else {
                        $query->where('session_id', $cart->session_id);
                    }
                })
                ->whereNull('recovered_at')
                ->first();

            if ($abandonedCart) {
                // تحديث البيانات الحالية
                $abandonedCart->update([
                    'cart_data' => $cartData,
                    'email' => $email ?: $abandonedCart->email,
                    'phone' => $phone ?: $abandonedCart->phone,
                ]);
            } else {
                $validUserId = null;
                if ($cart->user_id && DB::table('users')->where('id', $cart->user_id)->exists()) {
                    $validUserId = $cart->user_id;
                }

                // إنشاء سجل جديد مع رمز استعادة فريد
                AbandonedCart::create([
                    'tenant_id' => $cart->tenant_id,
                    'user_id' => $validUserId,
                    'session_id' => $cart->session_id,
                    'email' => $email,
                    'phone' => $phone,
                    'cart_data' => $cartData,
                    'recovery_token' => Str::random(40),
                ]);
                $count++;
            }
        }

        $this->info("تمت العملية بنجاح. تم تسجيل {$count} سلة متروكة جديدة.");
        Log::info("[Scheduler] Tracked abandoned carts: registered {$count} new carts.");
    }
}
