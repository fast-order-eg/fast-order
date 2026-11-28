<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionReceipt;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * List subscription plans.
     */
    public function plans(): Response
    {
        $plans = SubscriptionPlan::orderBy('price_monthly', 'asc')->get();

        return Inertia::render('SuperAdmin/Subscriptions/Plans', [
            'plans' => $plans
        ]);
    }

    /**
     * View manual subscription receipts.
     */
    public function receipts(Request $request): Response
    {
        $query = SubscriptionReceipt::with(['tenant', 'plan', 'approvedBy']);

        // Search filter (reference_code, payment_reference, amount, tenant name/email/phone/slug/domain/id)
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('slug', 'like', "%{$search}%")
                         ->orWhere('custom_domain', 'like', "%{$search}%");
                      if (ctype_digit($search)) {
                          $tq->orWhere('id', (int) $search);
                      }
                  });
            });
        }

        // Date filter
        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            if ($type === 'wallet') {
                $query->where(function ($q) {
                    $q->where('type', 'wallet')
                      ->orWhereHas('plan', function ($pq) {
                          $pq->where('slug', 'commission')
                             ->orWhere('name', 'like', '%عمولة%');
                      });
                });
            } elseif ($type === 'subscription') {
                $query->where(function ($q) {
                    $q->where('type', 'subscription')
                      ->where(function ($sq) {
                          $sq->whereDoesntHave('plan')
                             ->orWhereHas('plan', function ($pq) {
                                 $pq->where('slug', '!=', 'commission')
                                    ->where('name', 'not like', '%عمولة%');
                             });
                      });
                });
            } else {
                $query->where('type', $type);
            }
        }

        $receipts = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $tenants = Tenant::with(['subscriptions' => function ($q) {
                $q->with('plan:id,name,slug')->latest('id');
            }])
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'slug', 'phone', 'email', 'custom_domain', 'wallet_balance'])
            ->map(function ($t) {
                $latestSub = $t->subscriptions->firstWhere('status', 'active') ?? $t->subscriptions->first();
                return [
                    'id'                => $t->id,
                    'name'              => $t->name,
                    'slug'              => $t->slug,
                    'phone'             => $t->phone,
                    'email'             => $t->email,
                    'custom_domain'     => $t->custom_domain,
                    'wallet_balance'    => (float) ($t->wallet_balance ?? 0),
                    'current_plan_name' => $latestSub?->plan?->name,
                    'current_plan_slug' => $latestSub?->plan?->slug,
                ];
            })
            ->values();

        $plans = SubscriptionPlan::orderBy('price_monthly', 'asc')->get(['id', 'name', 'price_monthly', 'price_yearly', 'slug', 'is_active']);

        $paymentSettings = [
            'vodafone_cash_number' => \App\Models\Setting::getGlobal('vodafone_cash_number', \App\Models\Setting::get('vodafone_cash_number', '')),
            'instapay_number'      => \App\Models\Setting::getGlobal('instapay_number', \App\Models\Setting::get('instapay_number', \App\Models\Setting::get('instapay_address', ''))),
        ];

        return Inertia::render('SuperAdmin/Subscriptions/Receipts', [
            'receipts'        => $receipts,
            'tenants'         => $tenants,
            'plans'           => $plans,
            'paymentSettings' => $paymentSettings,
            'filters'         => $request->only(['search', 'date', 'status', 'type']),
        ]);
    }

    /**
     * Update platform payment numbers for wallet & subscription transfers.
     */
    public function updatePaymentSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'vodafone_cash_number' => ['required', 'string', 'max:50'],
            'instapay_number'      => ['required', 'string', 'max:50'],
        ]);

        \App\Models\Setting::setGlobal('vodafone_cash_number', $request->vodafone_cash_number, 'payment');
        \App\Models\Setting::setGlobal('instapay_number', $request->instapay_number, 'payment');

        return redirect()->back()->with('success', 'تم تحديث أرقام استقبال التحويلات بنجاح.');
    }

    /**
     * Store/attach a new manual receipt for a tenant.
     */
    public function storeReceipt(Request $request): RedirectResponse
    {
        $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:255'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'receipt_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_file')) {
            $receiptPath = $request->file('receipt_file')->store('receipts', 'public');
        }

        $plan = SubscriptionPlan::find($request->plan_id);
        $isSubscriptionPlan = $this->isFixedSubscriptionPlan($plan);
        $isCommission = !$isSubscriptionPlan && $this->isCommissionPlan($plan);

        $receipt = SubscriptionReceipt::create([
            'tenant_id' => $request->tenant_id,
            'plan_id' => $request->plan_id,
            'type' => $isCommission ? 'wallet' : 'subscription',
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'receipt_path' => $receiptPath,
            'status' => 'pending',
        ]);

        return $this->approveReceipt($request, $receipt);
    }

    /**
     * هل الباقة اشتراك ثابت (شهري أو سنوي) لا يضيف رصيداً للمحفظة؟
     */
    protected function isFixedSubscriptionPlan(?SubscriptionPlan $plan): bool
    {
        if (!$plan) {
            return false;
        }
        $slug = mb_strtolower((string) $plan->slug);
        $name = mb_strtolower((string) $plan->name);

        if (in_array($slug, ['monthly', 'yearly'], true)) {
            return true;
        }
        if (str_contains($name, 'شهر') || str_contains($name, 'سنو')) {
            return true;
        }
        if ($slug !== 'commission' && $slug !== 'free' && !str_contains($name, 'عمولة') && !str_contains($name, 'محفظة') && ($plan->price_monthly > 0 || $plan->price_yearly > 0)) {
            return true;
        }
        return false;
    }

    /**
     * هل الباقة هي باقة العمولة (شحن المحفظة)؟
     */
    protected function isCommissionPlan(?SubscriptionPlan $plan): bool
    {
        if (!$plan) {
            return false;
        }
        $slug = mb_strtolower((string) $plan->slug);
        $name = mb_strtolower((string) $plan->name);

        return $slug === 'commission' || str_contains($name, 'عمولة') || str_contains($name, 'محفظة');
    }

    /**
     * Approve a subscription or wallet receipt.
     */
    public function approveReceipt(Request $request, SubscriptionReceipt $receipt): RedirectResponse
    {
        if ($receipt->status !== 'pending') {
            return redirect()->back()->with('error', 'هذا الطلب تم معالجته بالفعل.');
        }

        $plan = $receipt->plan;
        $isSubscriptionPlan = $this->isFixedSubscriptionPlan($plan);
        $isWalletCharge = !$isSubscriptionPlan && ($receipt->type === 'wallet' || $this->isCommissionPlan($plan));

        DB::transaction(function () use ($receipt, $plan, $isWalletCharge) {
            $tenant = $receipt->tenant;

            // 1. Approve receipt (and enforce accurate type for monthly/yearly plans)
            $receipt->update([
                'status'      => 'approved',
                'type'        => $isWalletCharge ? 'wallet' : 'subscription',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);

            if (!$tenant) {
                return;
            }

            // 2. Wallet top-up OR commission plan ONLY -> adds balance to wallet
            if ($isWalletCharge) {
                $amountVal = (float) $receipt->amount;
                $tenant->increment('wallet_balance', $amountVal);

                $methodName = $receipt->payment_method === 'vodafone_cash'
                    ? 'فودافون كاش'
                    : ($receipt->payment_method === 'instapay' ? 'إنستا باي' : $receipt->payment_method);
                $refText = $receipt->payment_reference ? ' (الرقم المحول منه: ' . $receipt->payment_reference . ')' : '';

                \App\Models\WalletTransaction::create([
                    'tenant_id'   => $tenant->id,
                    'amount'      => $amountVal,
                    'type'        => 'credit',
                    'description' => 'شحن محفظة عبر ' . $methodName . $refText,
                    'created_by'  => Auth::id(),
                ]);

                // If a commission plan is selected, switch/activate commission subscription cleanly
                if ($plan) {
                    $latestSub = Subscription::where('tenant_id', $tenant->id)->latest('id')->first();
                    Subscription::where('tenant_id', $tenant->id)
                        ->when($latestSub, fn($q) => $q->where('id', '!=', $latestSub->id))
                        ->update(['status' => 'expired']);

                    $endsAt = now()->addYears(10);
                    if ($latestSub) {
                        $latestSub->update([
                            'plan_id'       => $plan->id,
                            'price'         => $receipt->amount,
                            'starts_at'     => now(),
                            'ends_at'       => $endsAt,
                            'trial_ends_at' => null,
                            'billing_cycle' => 'commission',
                            'status'        => 'active',
                        ]);
                    } else {
                        Subscription::create([
                            'tenant_id'     => $tenant->id,
                            'plan_id'       => $plan->id,
                            'price'         => $receipt->amount,
                            'starts_at'     => now(),
                            'ends_at'       => $endsAt,
                            'billing_cycle' => 'commission',
                            'status'        => 'active',
                        ]);
                    }

                    $tenant->update([
                        'subscription_status'  => 'active',
                        'subscription_ends_at' => $endsAt,
                        'is_active'            => true,
                    ]);
                }
                return;
            }

            // 3. Monthly / Yearly subscription plan -> DO NOT add to wallet, automatically switch/renew tenant plan
            if ($plan) {
                $slug = mb_strtolower((string) $plan->slug);
                $name = mb_strtolower((string) $plan->name);
                $isYearly = $slug === 'yearly'
                    || str_contains($name, 'سنو')
                    || ($plan->price_yearly > 0 && $slug !== 'monthly' && !str_contains($name, 'شهر') && $receipt->amount >= $plan->price_yearly);

                $months = $isYearly ? 12 : 1;
                $billingCycle = $isYearly ? 'yearly' : 'monthly';

                $latestSub = Subscription::where('tenant_id', $tenant->id)->latest('id')->first();
                // تعطيل أي اشتراكات قديمة مكررة لمنع تضارب الباقة الحالية
                Subscription::where('tenant_id', $tenant->id)
                    ->when($latestSub, fn($q) => $q->where('id', '!=', $latestSub->id))
                    ->update(['status' => 'expired']);

                // في حال تجديد نفس الباقة قبل انتهائها نمد المدة من تاريخ الانتهاء الحالي، وإلا نبدأ من اليوم
                $isSameActivePlan = $latestSub
                    && (int) $latestSub->plan_id === (int) $plan->id
                    && $latestSub->status === 'active'
                    && $latestSub->ends_at
                    && $latestSub->ends_at->isFuture();

                $endsAt = $isSameActivePlan
                    ? $latestSub->ends_at->copy()->addMonths($months)
                    : now()->addMonths($months);

                if ($latestSub) {
                    $latestSub->update([
                        'plan_id'       => $plan->id,
                        'price'         => $receipt->amount,
                        'starts_at'     => $isSameActivePlan ? ($latestSub->starts_at ?: now()) : now(),
                        'ends_at'       => $endsAt,
                        'trial_ends_at' => null,
                        'billing_cycle' => $billingCycle,
                        'status'        => 'active',
                    ]);
                } else {
                    Subscription::create([
                        'tenant_id'     => $tenant->id,
                        'plan_id'       => $plan->id,
                        'price'         => $receipt->amount,
                        'starts_at'     => now(),
                        'ends_at'       => $endsAt,
                        'billing_cycle' => $billingCycle,
                        'status'        => 'active',
                    ]);
                }

                $tenant->update([
                    'subscription_status'  => 'active',
                    'subscription_ends_at' => $endsAt,
                    'is_active'            => true,
                ]);
            }
        });

        if ($receipt->tenant_id) {
            \App\Services\CacheService::invalidateDashboardStats($receipt->tenant_id);
        }

        $msg = $isWalletCharge
            ? 'تم تأكيد وصول المبلغ وإضافته إلى محفظة التاجر بنجاح.'
            : ('تم اعتماد الإيصال وتفعيل ' . ($plan?->name ?: 'الباقة') . ' للمتجر تلقائياً بدون إضافة رصيد للمحفظة.');

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Update an existing subscription plan.
     */
    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $plan->update([
            'name' => $request->name,
            'price_monthly' => $request->price_monthly,
            'price_yearly' => $request->price_yearly,
            'trial_days' => $request->trial_days,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'تم تحديث خطة الاشتراك بنجاح.');
    }

    /**
     * Reject a subscription receipt.
     */
    public function rejectReceipt(Request $request, SubscriptionReceipt $receipt): RedirectResponse
    {
        if ($receipt->status !== 'pending') {
            return redirect()->back()->with('error', 'هذا الطلب تم معالجته بالفعل.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $receipt->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        if ($receipt->tenant_id) {
            \App\Services\CacheService::invalidateDashboardStats($receipt->tenant_id);
        }

        return redirect()->back()->with('success', 'تم رفض إيصال الدفع.');
    }

    /**
     * Delete a subscription receipt.
     */
    public function destroyReceipt(SubscriptionReceipt $receipt): RedirectResponse
    {
        $receipt->delete();

        if ($receipt->tenant_id) {
            \App\Services\CacheService::invalidateDashboardStats($receipt->tenant_id);
        }

        return redirect()->back()->with('success', 'تم حذف إيصال الدفع بنجاح.');
    }
}
