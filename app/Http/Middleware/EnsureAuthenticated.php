<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    /**
     * Handle an incoming request - Ensures user is authenticated and has a valid role
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('Unauthenticated access attempt', [
                'route' => $request->route()?->getName(),
                'url' => $request->url(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Clear any existing session data
            $request->session()->flush();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Authentication required',
                    'error' => 'Unauthenticated'
                ], 401);
            }
            
            return redirect()->route('login')
                ->with('message', 'Please log in to access this area.');
        }

        // 1.5. Check session timeout (4 hours max)
        $lastActivity = $request->session()->get('last_activity', now()->timestamp);
        if (now()->timestamp - $lastActivity > 14400) { // 4 hours
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            Log::info('Session timeout logout', [
                'user_id' => Auth::id(),
                'last_activity' => $lastActivity
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session expired. Please log in again.',
                    'error' => 'Session expired'
                ], 401);
            }
            
            return redirect()->route('login')
                ->with('message', 'Your session has expired. Please log in again.');
        }
        
        // Update last activity timestamp
        $request->session()->put('last_activity', now()->timestamp);

        $user = Auth::user();
        
        // 2. Check if user account is active
        if (!$user->is_active) {
            Log::warning('Inactive user access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => $request->route()?->getName(),
            ]);
            
            // Force logout for inactive users
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact the administrator.',
                    'error' => 'Account inactive'
                ], 403);
            }
            
            return redirect()->route('login')
                ->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }
        
        // 3. Basic authentication check - allow all authenticated active users
        // (Admin-specific routes will be protected by the AdminMiddleware)
        
        // 4. Log successful access and update last login
        Log::info('Authenticated user access granted', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'route' => $request->route()?->getName(),
            'access_level' => $user->is_admin ? 'admin' : 'user',
        ]);
        
        // Update last login timestamp
        $user->updateLastLogin();
        
        return $next($request);
    }
}