<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Setting;
use App\Rules\EgyptianPhone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): \Illuminate\View\View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', new EgyptianPhone(null, null, false)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'store_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'alpha_dash', 'max:255', 'unique:tenants,slug'],
        ];

        $request->validate($rules, [
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني المدخل غير صحيح.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'subdomain.unique' => 'اسم الرابط (السب دومين) محجوز بالفعل، اختر اسماً آخر.',
            'subdomain.alpha_dash' => 'الرابط يجب أن يحتوي على أحرف وأرقام وشرطات فقط بدون مسافات.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $user = DB::transaction(function () use ($request) {
            $trialDays = 7;
            $freePlan = SubscriptionPlan::where('slug', 'free')->first()
                ?? SubscriptionPlan::where('is_active', true)->first()
                ?? SubscriptionPlan::first();

            // 1. Create Tenant (Store)
            $tenant = Tenant::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'name' => $request->store_name,
                'slug' => strtolower($request->subdomain),
                'email' => strtolower(trim($request->email)),
                'phone' => $request->phone ? trim($request->phone) : null,
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
                'subscription_ends_at' => now()->addDays($trialDays),
                'wallet_balance' => 0.00,
                'is_active' => true,
                'settings' => [
                    'activity' => 'تجارة عامة',
                    'phone' => $request->phone ? trim($request->phone) : null,
                ],
            ]);

            // 2. Create User
            $userData = [
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => strtolower(trim($request->email)),
                'phone' => $request->phone ? trim($request->phone) : null,
                'password' => Hash::make($request->password),
                'user_type' => 'merchant',
                'is_active' => true,
            ];

            $user = User::create($userData);

            // 3. Link owner on Tenant
            $tenant->update(['owner_id' => $user->id]);

            // 4. Link in tenant_users pivot table
            $user->tenants()->attach($tenant->id, [
                'role' => 'owner',
                'permissions' => json_encode(['*']),
            ]);

            // 5. Activate 7-Day Free Trial Subscription
            if ($freePlan) {
                Subscription::create([
                    'tenant_id'     => $tenant->id,
                    'plan_id'       => $freePlan->id,
                    'status'        => 'trial',
                    'billing_cycle' => 'monthly',
                    'price'         => 0,
                    'starts_at'     => now(),
                    'trial_ends_at' => now()->addDays($trialDays),
                    'ends_at'       => now()->addDays($trialDays),
                ]);
            }

            // Save phone and whatsapp to settings table if provided
            if (!empty($request->phone)) {
                $whatsapp = trim($request->phone);
                if (str_starts_with($whatsapp, '0')) {
                    $whatsapp = '2' . substr($whatsapp, 1);
                } elseif (!str_starts_with($whatsapp, '20') && strlen($whatsapp) === 10) {
                    $whatsapp = '2' . $whatsapp;
                }
                Setting::set('phone', trim($request->phone), 'general', $tenant->id);
                Setting::set('whatsapp', $whatsapp, 'general', $tenant->id);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        if ($user->currentTenant) {
            session(['tenant_id' => $user->currentTenant->id]);
        }

        // Redirect to the merchant's dashboard on their subdomain
        $host    = parse_url(config('app.url'), PHP_URL_HOST);
        $scheme  = $request->getScheme();
        $port    = $request->getPort();
        $portStr = ($port && $port != 80 && $port != 443) ? ':' . $port : '';
        $subdomainUrl = $scheme . '://' . strtolower($request->subdomain) . '.' . $host . $portStr;

        return redirect()->away($subdomainUrl . '/admin/dashboard');
    }

    /**
     * Check if the subdomain slug is available.
     */
    public function checkSubdomain(Request $request): \Illuminate\Http\JsonResponse
    {
        $slug = strtolower(trim((string)$request->input('subdomain')));
        
        if (empty($slug)) {
            return response()->json(['available' => false, 'message' => 'الرجاء إدخال اسم الرابط']);
        }
        
        if (!preg_match('/^[a-z0-9\-]+$/i', $slug)) {
            return response()->json(['available' => false, 'message' => 'الرابط يجب أن يحتوي على أحرف وأرقام وشرطات فقط']);
        }
        
        // System reserved subdomains
        $reserved = ['admin', 'app', 'www', 'mail', 'api', 'platform', 'fastorder', 'demo', 'test'];
        if (in_array($slug, $reserved)) {
            return response()->json(['available' => false, 'message' => 'هذا الاسم محجوز للنظام']);
        }
        
        $exists = Tenant::where('slug', $slug)->exists();
        
        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'اسم الرابط محجوز بالفعل' : 'اسم الرابط متاح للاستخدام ✓'
        ]);
    }
}
