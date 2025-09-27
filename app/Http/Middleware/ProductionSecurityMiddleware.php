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

        // Content Security Policy (disabled in local development by default)
        $cspEnabled = config('production.security.csp_enabled', false);
        
        // Auto-disable CSP in local development unless explicitly enabled
        if (app()->environment('local') && !config('production.security.csp_enabled')) {
            $cspEnabled = false;
        }
        
        if ($cspEnabled) {
            $csp = $this->buildContentSecurityPolicy();
            $headers['Content-Security-Policy'] = $csp;
            Log::info('CSP applied', ['policy' => $csp]);
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
        $isProduction = app()->environment('production');
        $isDevelopment = app()->environment(['local', 'development', 'testing']);
        
        // Base policies
        $policies = [
            "default-src 'self'",
        ];

        // Script sources
        $scriptSources = ["'self'", "'unsafe-inline'", "'unsafe-eval'"];
        if ($isDevelopment) {
            // Allow Vite development server
            $scriptSources[] = 'http://localhost:5173';
            $scriptSources[] = 'ws://localhost:5173';
        }
        $policies[] = "script-src " . implode(' ', $scriptSources);

        // Style sources
        $styleSources = ["'self'", "'unsafe-inline'"];
        $styleSources[] = 'https://fonts.googleapis.com';
        $styleSources[] = 'https://fonts.bunny.net'; // Add support for Bunny Fonts
        if ($isDevelopment) {
            // Allow Vite development server styles
            $styleSources[] = 'http://localhost:5173';
        }
        $policies[] = "style-src " . implode(' ', $styleSources);

        // Font sources
        $fontSources = ["'self'", 'data:'];
        $fontSources[] = 'https://fonts.gstatic.com';
        $fontSources[] = 'https://fonts.bunny.net'; // Add support for Bunny Fonts
        $policies[] = "font-src " . implode(' ', $fontSources);

        // Connect sources
        $connectSources = ["'self'"];
        if ($isDevelopment) {
            // Allow Vite HMR and WebSocket connections
            $connectSources[] = 'http://localhost:5173';
            $connectSources[] = 'ws://localhost:5173';
        }
        $policies[] = "connect-src " . implode(' ', $connectSources);

        // Other policies
        $policies[] = "img-src 'self' data: https:";
        $policies[] = "frame-src 'none'";
        $policies[] = "object-src 'none'";
        $policies[] = "base-uri 'self'";
        $policies[] = "form-action 'self'";

        // Only enforce strict CSP in production
        if ($isDevelopment) {
            Log::info('CSP: Development mode - relaxed policy applied');
        }

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