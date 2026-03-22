<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProductionSecurityMiddleware
{
    /**
     * Common payload signatures used in SQLi / XSS / traversal probes.
     *
     * @var array<int, string>
     */
    private const BLOCKED_PATTERNS = [
        '/\bunion\b\s+\bselect\b/i',
        '/\b(or|and)\b\s+\d+\s*=\s*\d+/i',
        '/\bdrop\b\s+\btable\b/i',
        '/\binsert\b\s+\binto\b/i',
        '/\bupdate\b\s+\w+\s+\bset\b/i',
        '/\bdelete\b\s+\bfrom\b/i',
        '/<\s*script\b/i',
        '/javascript\s*:/i',
        '/\.\.\//i',
        '/%2e%2e%2f/i',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkipInspection($request) === false) {
            $attackPattern = $this->detectAttackPattern($request);

            if ($attackPattern !== null) {
                Log::warning('Blocked suspicious request payload', [
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'matched_pattern' => $attackPattern,
                    'user_agent' => $request->userAgent(),
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Request blocked by security policy.',
                    ], 403);
                }

                return response('Request blocked by security policy.', 403);
            }
        }

        // Force HTTPS in production
        if (config('production.security.force_https', false) && ! $request->secure() && app()->environment('production')) {
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
        if (app()->environment('local') && ! config('production.security.csp_enabled')) {
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
        $policies[] = 'script-src '.implode(' ', $scriptSources);

        // Style sources
        $styleSources = ["'self'", "'unsafe-inline'"];
        $styleSources[] = 'https://fonts.googleapis.com';
        $styleSources[] = 'https://fonts.bunny.net'; // Add support for Bunny Fonts
        if ($isDevelopment) {
            // Allow Vite development server styles
            $styleSources[] = 'http://localhost:5173';
        }
        $policies[] = 'style-src '.implode(' ', $styleSources);

        // Font sources
        $fontSources = ["'self'", 'data:'];
        $fontSources[] = 'https://fonts.gstatic.com';
        $fontSources[] = 'https://fonts.bunny.net'; // Add support for Bunny Fonts
        $policies[] = 'font-src '.implode(' ', $fontSources);

        // Connect sources
        $connectSources = ["'self'"];
        if ($isDevelopment) {
            // Allow Vite HMR and WebSocket connections
            $connectSources[] = 'http://localhost:5173';
            $connectSources[] = 'ws://localhost:5173';
        }
        $policies[] = 'connect-src '.implode(' ', $connectSources);

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
            Log::info('Login attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
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
                Log::warning('Suspicious request detected', [
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

    private function shouldSkipInspection(Request $request): bool
    {
        return $request->is('build/*') || $request->is('storage/*') || $request->is('favicon.ico');
    }

    private function detectAttackPattern(Request $request): ?string
    {
        $values = [
            $request->getRequestUri(),
            ...$this->flattenPayloadValues($request->query()),
            ...$this->flattenPayloadValues($request->request->all()),
        ];

        foreach ($values as $value) {
            $decodedValue = urldecode((string) $value);

            foreach (self::BLOCKED_PATTERNS as $pattern) {
                if (preg_match($pattern, $decodedValue) === 1) {
                    return $pattern;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    private function flattenPayloadValues(array $payload): array
    {
        $values = [];

        foreach ($payload as $value) {
            if (is_array($value)) {
                $values = [...$values, ...$this->flattenPayloadValues($value)];

                continue;
            }

            if (is_scalar($value) || $value === null) {
                $values[] = (string) $value;
            }
        }

        return $values;
    }
}
