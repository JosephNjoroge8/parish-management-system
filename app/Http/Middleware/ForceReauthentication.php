<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ForceReauthentication
{
    /**
     * Handle an incoming request.
     * Forces users to log in fresh by clearing any persistent sessions
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for suspicious session patterns
        $suspiciousPatterns = [
            // No login time but user is authenticated
            Auth::check() && !$request->session()->has('login_time'),
            // Session older than 8 hours 
            Auth::check() && $request->session()->has('login_time') && 
            (now()->timestamp - $request->session()->get('login_time')) > 28800,
            // IP address mismatch (session hijacking protection)
            Auth::check() && $request->session()->has('user_ip') && 
            $request->session()->get('user_ip') !== $request->ip(),
        ];
        
        // Force logout if any suspicious pattern is detected
        if (Auth::check() && (in_array(true, $suspiciousPatterns))) {
            Log::warning('Suspicious session detected, forcing logout', [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'current_ip' => $request->ip(),
                'session_ip' => $request->session()->get('user_ip'),
                'login_time' => $request->session()->get('login_time'),
                'suspicious_patterns' => $suspiciousPatterns
            ]);
            
            Auth::logout();
            $request->session()->flush();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session invalid. Please log in again.',
                    'error' => 'Authentication required'
                ], 401);
            }
            
            return redirect()->route('login')
                ->with('error', 'Your session was invalid. Please log in again for security.');
        }
        
        return $next($request);
    }
}