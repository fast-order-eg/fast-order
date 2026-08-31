<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TenantController extends Controller
{
    /**
     * List all tenants (paginated, filtered by status/search).
     */
    public function index(Request $request): Response
    {
        $query = Tenant::with(['owner', 'subscriptions.plan'])
            ->withCount([
                'orders' => function ($q) {
                    $q->withoutGlobalScopes();
                },
                'products' => function ($q) {
                    $q->withoutGlobalScopes();
                }
            ]);

        // Search filter: Phone, Email, Store Link/Slug, Domain, Store Name, Owner Name
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('custom_domain', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->has('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true)->where('subscription_status', '!=', 'expired');
            } elseif ($status === 'suspended') {
                $query->where('is_active', false);
            } elseif ($status === 'expired') {
                $query->where(function ($q) {
                    $q->where('subscription_status', 'expired')
                      ->orWhere(function ($sq) {
                          $sq->whereNotNull('subscription_ends_at')
                             ->where('subscription_ends_at', '<', now());
                      });
                });
            }
        }

        // Plan filter
        if ($planSlug = $request->input('plan')) {
            if ($planSlug !== 'all') {
                $query->where(function ($q) use ($planSlug) {
                    $q->whereHas('subscriptions', function ($sq) use ($planSlug) {
                        $sq->where('status', 'active')
                          ->whereHas('plan', function ($pq) use ($planSlug) {
                              $pq->where('slug', $planSlug);
                          });
                    });
                    if ($planSlug === 'free') {
                        $q->orWhere('subscription_status', 'trial');
                    }
                });
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'latest');
        if ($sortBy === 'most_products') {
            $query->orderBy('products_count', 'desc');
        } elseif ($sortBy === 'most_orders') {
            $query->orderBy('orders_count', 'desc');
        } elseif ($sortBy === 'expiring_soon') {
            $query->where(function ($q) {
                $q->whereNotNull('subscription_ends_at')
                  ->orWhereNotNull('trial_ends_at');
            })
            ->whereDoesntHave('subscriptions', function ($sq) {
                $sq->where('status', 'active')
                   ->whereHas('plan', fn($pq) => $pq->where('slug', 'commission'));
            })
            ->orderByRaw('CASE 
                WHEN subscription_ends_at IS NOT NULL AND subscription_ends_at >= NOW() THEN 0 
                WHEN trial_ends_at IS NOT NULL AND trial_ends_at >= NOW() THEN 1 
                ELSE 2 
            END ASC, COALESCE(subscription_ends_at, trial_ends_at) ASC');
        } elseif ($sortBy === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $tenants = $query->paginate(20)->withQueryString();
        $plans = SubscriptionPlan::where('is_active', true)->get();

        $planCounts = [
            'all' => Tenant::count(),
            'free' => Tenant::where(function($q) {
                $q->where('subscription_status', 'trial')
                  ->orWhereHas('subscriptions', fn($sq) => $sq->where('status', 'active')->whereHas('plan', fn($pq) => $pq->where('slug', 'free')));
            })->count(),
            'monthly' => Tenant::whereHas('subscriptions', fn($sq) => $sq->where('status', 'active')->whereHas('plan', fn($pq) => $pq->where('slug', 'monthly')))->count(),
            'yearly' => Tenant::whereHas('subscriptions', fn($sq) => $sq->where('status', 'active')->whereHas('plan', fn($pq) => $pq->where('slug', 'yearly')))->count(),
            'commission' => Tenant::whereHas('subscriptions', fn($sq) => $sq->where('status', 'active')->whereHas('plan', fn($pq) => $pq->where('slug', 'commission')))->count(),
        ];

        return Inertia::render('SuperAdmin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status', 'plan', 'sort_by']),
            'plans' => $plans,
            'planCounts' => $planCounts,
        ]);
    }

    /**
     * Show tenant details (owner info, settings, subscriptions).
     */
    public function show(Tenant $tenant): Response
    {
        $tenant->load(['owner', 'subscriptions.plan']);

        // Load settings bypassing the global tenant scope
        $settings = \App\Models\Setting::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->get();

        // Load plans for the subscription modal
        $plans = SubscriptionPlan::where('is_active', true)->get();

        // Count products and orders for this tenant
        $productsCount = \App\Models\Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        $ordersCount = \App\Models\Order::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        // Load wallet transactions for audit history log
        $walletTransactions = \App\Models\WalletTransaction::where('tenant_id', $tenant->id)
            ->with('creator')
            ->latest()
            ->get();

        return Inertia::render('SuperAdmin/Tenants/Show', [
            'tenant'             => $tenant,
            'settings'           => $settings,
            'plans'              => $plans,
            'productsCount'      => $productsCount,
            'ordersCount'        => $ordersCount,
            'walletTransactions' => $walletTransactions,
        ]);
    }

    /**
     * Update tenant status (activate/suspend).
     */
    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        $tenant->is_active = !$tenant->is_active;
        $tenant->save();

        if (!$tenant->is_active) {
            try {
                \Illuminate\Support\Facades\DB::table('sessions')
                    ->where('tenant_id', $tenant->id)
                    ->delete();
            } catch (\Exception $e) {
                // Ignore if session table is not using tenant_id column
            }
        }

        $statusMessage = $tenant->is_active ? 'تم تفعيل المتجر بنجاح.' : 'تم إيقاف المتجر بنجاح وخروج التاجر من الجلسة تلقائياً.';

        return redirect()->back()->with('success', $statusMessage);
    }

    /**
     * Create a new tenant and merchant owner from super admin panel.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:255', 'unique:tenants,slug'],
            'plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'ends_at' => ['nullable', 'date'],
        ], [
            'name.required' => 'يرجى إدخال اسم المتجر.',
            'owner_name.required' => 'يرجى إدخال اسم مالك المتجر.',
            'email.required' => 'يرجى إدخال البريد الإلكتروني.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل لتاجر آخر.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 خانات أو أرقام.',
            'slug.required' => 'يرجى إدخال رابط المتجر.',
            'slug.unique' => 'السب دومين (الرابط) مستخدم بالفعل لمتجر آخر، يرجى اختيار رابط مختلف.',
            'slug.alpha_dash' => 'يجب أن يحتوي الرابط على أحرف إنجليزية وأرقام وشرطات فقط بدون مسافات أو حروف عربية.',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'name' => $request->name,
                'slug' => strtolower($request->slug),
                'email' => $request->email,
                'phone' => $request->phone,
                'is_active' => true,
            ]);

            $user = \App\Models\User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->owner_name,
                'email' => strtolower($request->email),
                'phone' => $request->phone,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'user_type' => 'merchant',
                'is_active' => true,
            ]);

            $tenant->update(['owner_id' => $user->id]);

            $user->tenants()->attach($tenant->id, [
                'role' => 'owner',
                'permissions' => json_encode(['*']),
            ]);

            $planId = $request->input('plan_id') 
                ?? (SubscriptionPlan::where('slug', 'free')->value('id') ?? SubscriptionPlan::value('id'));
            
            if ($planId) {
                $endsAt = $request->ends_at ? \Carbon\Carbon::parse($request->ends_at) : now()->addDays(7);
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $planId,
                    'status' => 'active',
                    'billing_cycle' => 'monthly',
                    'price' => 0,
                    'starts_at' => now(),
                    'ends_at' => $endsAt,
                    'trial_ends_at' => $endsAt,
                ]);
                $tenant->update([
                    'subscription_status' => 'trial',
                    'subscription_ends_at' => $endsAt,
                    'trial_ends_at' => $endsAt,
                ]);
            }
        });

        return redirect()->back()->with('success', 'تم إنشاء متجر التاجر وحسابه بنجاح.');
    }

    /**
     * Assign or update a subscription for a tenant.
     */
    public function assignSubscription(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $plan = SubscriptionPlan::find($request->plan_id);
        $isCommission = $plan && ($plan->slug === 'commission' || str_contains(mb_strtolower($plan->name ?? ''), 'عمولة'));

        if ($request->filled('ends_at')) {
            $endsAt = \Carbon\Carbon::parse($request->ends_at);
        } elseif ($isCommission) {
            $endsAt = now()->addYears(10);
        } else {
            $endsAt = now()->addDays(30);
        }

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id'       => $request->plan_id,
                'status'        => 'active',
                'billing_cycle' => 'monthly',
                'price'         => 0,
                'starts_at'     => now(),
                'ends_at'       => $endsAt,
            ]
        );

        $tenant->update([
            'subscription_status'  => 'active',
            'subscription_ends_at' => $endsAt,
        ]);

        return redirect()->back()->with('success', 'تم تعديل الاشتراك وتاريخ الانتهاء بنجاح.');
    }

    /**
     * Export tenants to CSV
     */
    public function export()
    {
        $tenants = Tenant::with(['owner', 'subscriptions.plan'])->get();
        
        $csvHeader = ['Name', 'Email', 'Phone', 'Subscription Plan', 'Total Orders'];
        
        $callback = function() use ($tenants, $csvHeader) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($file, $csvHeader);
            
            foreach ($tenants as $tenant) {
                $totalOrders = \App\Models\Order::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
                
                $planName = 'N/A';
                $activeSub = $tenant->subscriptions->where('status', 'active')->last();
                if ($activeSub && $activeSub->plan) {
                    $planName = $activeSub->plan->name;
                }

                fputcsv($file, [
                    $tenant->name,
                    $tenant->owner ? $tenant->owner->email : $tenant->email,
                    $tenant->owner ? $tenant->owner->phone : $tenant->phone,
                    $planName,
                    $totalOrders,
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tenants_export.csv"',
        ]);
    }

    /**
     * Impersonate a tenant — generates a short-lived signed URL
     * so the super admin session is NOT destroyed.
     */
    public function impersonate(Tenant $tenant)
    {
        $owner = $tenant->owner;
        if (!$owner) {
            return back()->with('error', 'لا يوجد مالك لهذا المتجر');
        }

        $host    = parse_url(config('app.url'), PHP_URL_HOST) ?: 'fastorder.localhost';
        if (str_starts_with($host, 'app.')) {
            $host = substr($host, 4);
        }
        $port    = request()->getPort();
        $portStr = ($port && $port != 80 && $port != 443) ? ':' . $port : '';
        $scheme  = request()->getScheme();

        $token = \Illuminate\Support\Str::random(64);
        \Illuminate\Support\Facades\Cache::put(
            'impersonate_token_' . $token,
            ['user_id' => $owner->id, 'tenant_id' => $tenant->id],
            now()->addSeconds(60)
        );

        // Build URL on tenant subdomain so cookie is isolated to tenant domain only
        $entryUrl = $scheme . '://' . $tenant->slug . '.' . $host . $portStr . '/admin/impersonate-entry?token=' . $token;

        return redirect()->away($entryUrl);
    }

    /**
     * Entry point on the tenant subdomain for impersonating a merchant store.
     */
    public function impersonateEntry(Request $request)
    {
        $token = $request->query('token');

        // Pull the short-lived token (60 s) placed by impersonate()
        $data = \Illuminate\Support\Facades\Cache::pull('impersonate_token_' . $token);

        if (!$data) {
            abort(403, 'رابط الدخول المؤقت غير صالح أو انتهت صلاحيته.');
        }

        $user = \App\Models\User::find($data['user_id']);
        if (!$user) {
            abort(404, 'المستخدم غير موجود.');
        }

        $tenant = \App\Models\Tenant::find($data['tenant_id']);
        if (!$tenant) {
            abort(404, 'المتجر غير موجود.');
        }

        // Store a long-lived token in cache (8 hours) for cookie-based auth
        $longToken = \Illuminate\Support\Str::random(64);
        \Illuminate\Support\Facades\Cache::put(
            'impersonate_token_' . $longToken,
            ['user_id' => $user->id, 'tenant_id' => $tenant->id],
            now()->addHours(8)
        );

        $host         = parse_url(config('app.url'), PHP_URL_HOST) ?: 'fastorder.localhost';
        if (str_starts_with($host, 'app.')) {
            $host = substr($host, 4);
        }
        $cookieDomain = $tenant->slug . '.' . $host;

        $cookie = cookie(
            name:     'impersonate_token',
            value:    $longToken,
            minutes:  480,          // 8 hours
            path:     '/',
            domain:   $cookieDomain, // tenant subdomain ONLY (isolated)
            secure:   false,
            httpOnly: true,
            sameSite: 'Lax'
        );

        return redirect('/admin/dashboard')->withCookie($cookie);
    }

    /**
     * Delete tenant and all its data.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        // Delete related user/owner if they belong only to this tenant
        $owner = $tenant->owner;
        if ($owner) {
            $owner->delete();
        }

        // Delete all products, categories, orders, settings, and shipping governorates of this tenant
        \App\Models\Product::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();
        \App\Models\Category::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();
        \App\Models\Order::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();
        \App\Models\Setting::where('tenant_id', $tenant->id)->delete();
        \App\Models\ShippingGovernorate::where('tenant_id', $tenant->id)->delete();
        \App\Models\WalletTransaction::where('tenant_id', $tenant->id)->delete();

        $tenant->forceDelete();

        return redirect()->route('superadmin.tenants.index')->with('success', 'تم حذف المتجر وجميع بياناته بنجاح.');
    }

    /**
     * Add wallet balance
     */
    public function addWalletBalance(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $tenant) {
            $amountInt = (int) round($request->amount);
            $tenant->wallet_balance += $amountInt;
            $tenant->save();

            $description = $request->note 
                ? "تم إضافة {$amountInt}ج - السبب: {$request->note}" 
                : "تم إضافة {$amountInt}ج";

            \App\Models\WalletTransaction::create([
                'tenant_id'   => $tenant->id,
                'amount'      => $amountInt,
                'type'        => 'credit',
                'description' => $description,
                'created_by'  => auth()->id(),
            ]);
        });

        \App\Services\CacheService::invalidateDashboardStats($tenant->id);

        return redirect()->back()->with('success', 'تم إضافة الرصيد لمحفظة التاجر وتسجيل المعاملة بنجاح.');
    }

    /**
     * Deduct wallet balance
     */
    public function deductWalletBalance(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        if ($tenant->wallet_balance < $request->amount) {
            return redirect()->back()->with('error', 'المبلغ المطلوب خصمه أكبر من رصيد المحفظة الحالي.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $tenant) {
            $amountInt = (int) round($request->amount);
            $tenant->wallet_balance -= $amountInt;
            $tenant->save();

            $description = $request->note 
                ? "تم خصم {$amountInt}ج - السبب: {$request->note}" 
                : "تم خصم {$amountInt}ج";

            \App\Models\WalletTransaction::create([
                'tenant_id'   => $tenant->id,
                'amount'      => -$amountInt,
                'type'        => 'debit',
                'description' => $description,
                'created_by'  => auth()->id(),
            ]);
        });

        \App\Services\CacheService::invalidateDashboardStats($tenant->id);

        return redirect()->back()->with('success', 'تم خصم الرصيد من محفظة التاجر وتسجيل المعاملة بنجاح.');
    }
}
