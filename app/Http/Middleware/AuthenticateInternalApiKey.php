<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateInternalApiKey
{
    /**
     * Handle an incoming request for internal services (e.g. Fast Order CRM WhatsApp Bot).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('services.internal.api_key', env('INTERNAL_API_KEY', 'fastorder_secret_api_key_2026'));

        $providedKey = $request->header('X-Internal-Token')
            ?: $request->bearerToken()
            ?: $request->header('x-api-key')
            ?: $request->input('api_key');

        if (!$providedKey || !hash_equals((string)$configuredKey, (string)$providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized internal API access.',
            ], 401);
        }

        return $next($request);
    }
}
