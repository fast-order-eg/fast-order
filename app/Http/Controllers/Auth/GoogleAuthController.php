<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle()
    {
        $redirectUri = config('services.google.redirect') ?: url('/auth/google/callback');

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => csrf_token(),
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback(Request $request)
    {
        $code = $request->code;
        $fallbackRedirect = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');

        if (!$code) {
            return redirect($fallbackRedirect)->withErrors(['error' => 'Google authentication failed.']);
        }

        $redirectUri = config('services.google.redirect') ?: url('/auth/google/callback');

        // Exchange auth code for access token using PHP cURL (zero dependencies)
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]));
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            \Illuminate\Support\Facades\Log::error('Google OAuth Token Error', [
                'response' => $response,
                'curl_error' => $curlError,
            ]);
            $errorMsg = $data['error_description'] ?? 'فشل الحصول على رمز الوصول من جوجل.';
            return redirect($fallbackRedirect)->withErrors(['error' => $errorMsg]);
        }

        // Fetch user info from Google Info Endpoint
        $ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $data['access_token']
        ]);
        $userInfoResponse = curl_exec($ch);
        curl_close($ch);

        $userInfo = json_decode($userInfoResponse, true);
        if (!isset($userInfo['email'])) {
            return redirect($fallbackRedirect)->withErrors(['error' => 'فشل الحصول على بيانات حساب جوجل.']);
        }

        // Check if user already exists in database
        $user = User::where('email', $userInfo['email'])->first();

        if ($user && empty($user->google_id)) {
            $user->update(['google_id' => $userInfo['sub'] ?? ('google_' . $user->id)]);
        }

        if (!$user) {
            // Automatically create Tenant and User for 1-Click Instant Google Registration!
            $rawName = !empty($userInfo['name']) ? $userInfo['name'] : explode('@', $userInfo['email'])[0];
            $storeName = 'متجر ' . $rawName;
            
            $emailPrefix = explode('@', $userInfo['email'])[0];
            $baseSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($emailPrefix));
            if (strlen($baseSlug) < 3) {
                $baseSlug = 'store-' . \Illuminate\Support\Str::random(5);
            }

            // Ensure unique slug
            $slug = strtolower($baseSlug);
            $counter = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = strtolower($baseSlug . '-' . $counter);
                $counter++;
            }

            $user = DB::transaction(function () use ($userInfo, $rawName, $storeName, $slug) {
                // 1. Create Tenant (Store)
                $tenant = Tenant::create([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'name' => $storeName,
                    'slug' => $slug,
                    'email' => $userInfo['email'],
                    'subscription_status' => 'trial',
                    'trial_ends_at' => now()->addDays(7),
                    'subscription_ends_at' => now()->addDays(7),
                    'wallet_balance' => 0.00,
                    'settings' => [
                        'activity' => 'تجارة عامة',
                    ],
                ]);

                // 2. Create User
                $user = User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $rawName,
                    'email' => $userInfo['email'],
                    'google_id' => $userInfo['sub'] ?? ('google_' . time()),
                    'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                    'user_type' => 'merchant',
                    'is_active' => true,
                ]);

                // 3. Link owner on Tenant
                $tenant->update(['owner_id' => $user->id]);

                // 4. Attach pivot role entry
                $user->tenants()->attach($tenant->id, [
                    'role' => 'owner',
                    'permissions' => json_encode(['*']),
                ]);

                // 5. Activate 7-Day Free Trial Subscription
                $freePlan = SubscriptionPlan::where('slug', 'free')->first()
                    ?? SubscriptionPlan::where('is_active', true)->first()
                    ?? SubscriptionPlan::first();

                if ($freePlan) {
                    Subscription::create([
                        'tenant_id'     => $tenant->id,
                        'plan_id'       => $freePlan->id,
                        'status'        => 'trial',
                        'billing_cycle' => 'monthly',
                        'price'         => 0,
                        'starts_at'     => now(),
                        'trial_ends_at' => now()->addDays(7),
                        'ends_at'       => now()->addDays(7),
                    ]);
                }

                return $user;
            });
        }

        // Log the user in
        Auth::login($user);
        if ($user->currentTenant) {
            session(['tenant_id' => $user->currentTenant->id]);
        }

        // Redirect directly to the merchant's dashboard on their subdomain using a short-lived token
        if ($user->isMerchant() && $user->currentTenant) {
            $tenant = $user->currentTenant;
            $host = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
            $scheme = $request->getScheme();
            $port = $request->getPort();
            $portStr = ($port && $port != 80 && $port != 443) ? ':' . $port : '';

            $token = \Illuminate\Support\Str::random(40);
            \Illuminate\Support\Facades\Cache::put('google_login_token_' . $token, [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ], now()->addSeconds(60));

            $subdomainUrl = $scheme . '://' . strtolower($tenant->slug) . '.' . $host . $portStr;
            return redirect()->away($subdomainUrl . '/admin/google-entry?token=' . $token);
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Handle one-time token entry on tenant subdomain after Google login.
     */
    public function googleEntry(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return redirect()->route('login');
        }

        $data = \Illuminate\Support\Facades\Cache::pull('google_login_token_' . $token);
        if (!$data) {
            return redirect()->route('login')->withErrors(['error' => 'رابط الدخول المؤقت غير صالح أو انتهت صلاحيته.']);
        }

        $user = User::find($data['user_id']);
        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'المستخدم غير موجود.']);
        }

        Auth::login($user);

        if ($user->currentTenant) {
            session(['tenant_id' => $user->currentTenant->id]);
        }

        return redirect()->route('merchant.dashboard');
    }

    /**
     * Show form to complete registration for Google users.
     */
    public function showCompleteRegistration()
    {
        if (!session()->has('google_user')) {
            return redirect()->route('merchant.login');
        }

        return Inertia::render('Auth/GoogleCompleteRegistration', [
            'email' => session('google_user.email'),
            'name' => session('google_user.name'),
        ]);
    }

    /**
     * Process completing registration for Google users.
     */
    public function completeRegistration(Request $request)
    {
        if (!session()->has('google_user')) {
            return redirect()->route('merchant.login');
        }

        $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'alpha_dash', 'max:255', 'unique:tenants,slug'],
        ]);

        $googleUser = session('google_user');

        $user = DB::transaction(function () use ($request, $googleUser) {
            $trialDays = 7;
            $freePlan = SubscriptionPlan::where('slug', 'free')->first()
                ?? SubscriptionPlan::where('is_active', true)->first()
                ?? SubscriptionPlan::first();

            // 1. Create Tenant (Store)
            $tenant = Tenant::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'name' => $request->store_name,
                'slug' => strtolower($request->subdomain),
                'email' => $googleUser['email'],
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
                'subscription_ends_at' => now()->addDays($trialDays),
                'wallet_balance' => 0.00,
                'is_active' => true,
                'settings' => [
                    'activity' => 'تجارة عامة',
                ],
            ]);

            // 2. Create User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $googleUser['name'],
                'email' => $googleUser['email'],
                'password' => Hash::make(\Illuminate\Support\Str::random(24)), // secure random password for OAuth users
                'user_type' => 'merchant',
                'is_active' => true,
            ]);

            // 3. Link owner on Tenant
            $tenant->update(['owner_id' => $user->id]);

            // 4. Create pivot role entry
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

            return $user;
        });

        session()->forget('google_user');

        Auth::login($user);

        // Redirect to the merchant's dashboard on their subdomain
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $subdomainUrl = $request->getScheme() . '://' . strtolower($request->subdomain) . '.' . $host;
        
        return redirect()->away($subdomainUrl . '/dashboard');
    }
}
