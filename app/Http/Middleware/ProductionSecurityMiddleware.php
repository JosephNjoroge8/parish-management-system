<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProductionSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS in production
        if (config('production.security.force_https', false) && !$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        // Add security headers
        $this->addSecurityHeaders($response);

        // Log suspicious activities
        $this->logSuspiciousActivity($request);

        return $response;
    }

    /**
     * Add security headers to the response
     */
    private function addSecurityHeaders(Response $response): void
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), fullscreen=self',
        ];

        // Add HSTS header for HTTPS requests
        if (request()->secure()) {
            $maxAge = config('production.security.hsts_max_age', 31536000);
            $headers['Strict-Transport-Security'] = "max-age={$maxAge}; includeSubDomains; preload";
        }

        // Content Security Policy
        if (config('production.security.csp_enabled', false)) {
            $csp = $this->buildContentSecurityPolicy();
            $headers['Content-Security-Policy'] = $csp;
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value, false);
        }
    }

    /**
     * Build Content Security Policy
     */
    private function buildContentSecurityPolicy(): string
    {
        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $policies);
    }

    /**
     * Log suspicious activities
     */
    private function logSuspiciousActivity(Request $request): void
    {
        // Log failed login attempts
        if ($request->is('login') && $request->isMethod('POST')) {
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            
            Log::channel('security')->info('Login attempt', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'timestamp' => now(),
            ]);
        }

        // Log requests with suspicious patterns
        $suspiciousPatterns = [
            '/\.\./i',           // Directory traversal
            '/union.*select/i',  // SQL injection
            '/<script/i',        // XSS attempts
            '/eval\s*\(/i',      // Code injection
        ];

        $uri = $request->getRequestUri();
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                Log::channel('security')->warning('Suspicious request detected', [
                    'ip' => $request->ip(),
                    'uri' => $uri,
                    'user_agent' => $request->userAgent(),
                    'pattern' => $pattern,
                    'timestamp' => now(),
                ]);
                break;
            }
        }
    }
}