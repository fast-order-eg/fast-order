<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // إعادة التوجيه التلقائي إلى HTTPS في بيئة الإنتاج لمنع فقدان الكوكيز الآمنة وظهور خطأ 419
        if (app()->environment('production') && !$request->isSecure() && $request->header('X-Forwarded-Proto') !== 'https') {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // HSTS (Strict-Transport-Security) for HTTPS connections
        if ($request->isSecure() || $request->header('X-Forwarded-Proto') === 'https') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy (CSP) - balanced for Vite/React/Inertia compatibility
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: http:; " .
               "style-src 'self' 'unsafe-inline' https: http:; " .
               "img-src 'self' data: blob: https: http:; " .
               "font-src 'self' data: https: http:; " .
               "connect-src 'self' https: http: ws: wss:; " .
               "frame-src 'self' https: http:; " .
               "frame-ancestors 'self';";
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
