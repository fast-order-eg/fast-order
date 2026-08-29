<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionReceipt;
use App\Models\Tenant;
use App\Models\WalletTransaction;
use App\Services\PaymobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymobWebhookController extends Controller
{
    /**
     * Handle Paymob Transaction Processed Webhook (Callback).
     */
    public function handle(Request $request, PaymobService $paymobService): JsonResponse
    {
        $payload = $request->all();
        $hmac = $request->query('hmac') ?? $request->header('HMAC') ?? null;

        Log::info("Paymob Webhook received:", $payload);

        $obj = $payload['obj'] ?? $payload;
        $success = filter_var($obj['success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $amountCents = (int) ($obj['amount_cents'] ?? 0);
        $amountEgp = $amountCents / 100;
        $orderData = $obj['order'] ?? [];
        $paymobOrderId = (string) ($orderData['id'] ?? $obj['order_id'] ?? '');
        $specialRef = (string) ($orderData['special_reference'] ?? $orderData['merchant_order_id'] ?? $obj['merchant_order_id'] ?? '');
        $sourceData = $obj['source_data'] ?? [];
        $subType = strtolower($sourceData['sub_type'] ?? $sourceData['type'] ?? 'card');
        $transactionId = (string) ($obj['id'] ?? '');

        // Find matching SubscriptionReceipt
        $receipt = null;

        // Try extracting reference code or receipt ID from specialRef (e.g. WALLET_123456_48 or 123456)
        if (preg_match('/WALLET_(\d+)_(\d+)/', $specialRef, $matches)) {
            $receiptId = (int) $matches[2];
            $receipt = SubscriptionReceipt::find($receiptId);
        }

        if (!$receipt && !empty($paymobOrderId)) {
            $receipt = SubscriptionReceipt::where('payment_reference', $paymobOrderId)
                ->where('type', 'wallet')
                ->latest()
                ->first();
        }

        if (!$receipt && preg_match('/(\d{6})/', $specialRef, $matches)) {
            $receipt = SubscriptionReceipt::where('reference_code', $matches[1])
                ->where('type', 'wallet')
                ->latest()
                ->first();
        }

        if (!$receipt) {
            Log::warning("Paymob Webhook: No matching receipt found for Order ID: {$paymobOrderId}, SpecialRef: {$specialRef}");
            return response()->json(['status' => 'RECEIPT_NOT_FOUND'], 200);
        }

        $paymentMethodLabel = str_contains($subType, 'wallet') || in_array($subType, ['vodafone', 'orange', 'etisalat', 'we', 'meeza'])
            ? 'paymob_wallet'
            : 'paymob_card';

        if ($success) {
            if ($receipt->status !== 'approved') {
                DB::transaction(function () use ($receipt, $amountEgp, $paymentMethodLabel, $transactionId, $paymobOrderId) {
                    $tenant = $receipt->tenant;

                    // Update receipt status
                    $receipt->update([
                        'status'            => 'approved',
                        'payment_method'    => $paymentMethodLabel,
                        'payment_reference' => "Paymob Trans #{$transactionId} (Order #{$paymobOrderId})",
                        'approved_at'       => now(),
                        'approved_by'       => null, // Auto approved by Paymob Gateway
                    ]);

                    // Instantly Credit Wallet Balance
                    if ($tenant) {
                        $topUpAmount = $receipt->amount > 0 ? $receipt->amount : $amountEgp;
                        $tenant->increment('wallet_balance', $topUpAmount);

                        $methodName = $paymentMethodLabel === 'paymob_wallet' ? 'المحافظ الإلكترونية (Paymob)' : 'البطاقة البنكية الفيزا/ماستركارد (Paymob)';

                        WalletTransaction::create([
                            'tenant_id'   => $tenant->id,
                            'amount'      => $topUpAmount,
                            'type'        => 'credit',
                            'description' => "⚡ شحن محفظة لحظي عبر {$methodName} - معاملة رقم #{$transactionId}",
                            'created_by'  => null,
                        ]);

                        Log::info("Paymob instant top-up of {$topUpAmount} EGP added to Tenant #{$tenant->id} successfully.");
                    }
                });
            }
        } else {
            // Payment failed
            if ($receipt->status === 'pending') {
                $reason = $obj['data']['message'] ?? $obj['data']['txn_response_code'] ?? 'فشلت عملية الدفع الإلكتروني عبر Paymob أو تم إلغاؤها.';
                $receipt->update([
                    'status'            => 'rejected',
                    'payment_method'    => $paymentMethodLabel,
                    'payment_reference' => "Failed Paymob Trans #{$transactionId}",
                    'rejection_reason'  => "فشل الدفع من البنك: {$reason}",
                ]);

                Log::info("Paymob payment rejected for Receipt #{$receipt->id}. Reason: {$reason}");
            }
        }

        return response()->json(['status' => 'PROCESSED'], 200);
    }
}
