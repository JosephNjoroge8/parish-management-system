<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('Unauthenticated admin access attempt', [
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Authentication required for admin access',
                    'error' => 'Unauthenticated'
                ], 401);
            }
            return redirect()->route('login')
                ->with('message', 'Please log in to access the admin area.');
        }

        /** @var User $user */
        $user = Auth::user();
        
        // 2. Check if user account is active
        if (!$user->is_active) {
            Log::warning('Inactive user attempted admin access', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => $request->route()?->getName(),
            ]);
            
            Auth::logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been deactivated.',
                    'error' => 'Account inactive'
                ], 403);
            }
            
            return redirect()->route('login')
                ->with('error', 'Your account has been deactivated.');
        }

        // 3. Simplified admin check - only users with is_admin flag allowed
        if (!$user->is_admin) {
            Log::warning('Admin access denied - not an admin user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => $request->route()?->getName(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Only system administrators can access this area.',
                    'error' => 'Insufficient privileges'
                ], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Access denied. Only system administrators can access this area.');
        }
        
        // 4. Admin access granted
        Log::info('Admin access granted', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'route' => $request->route()?->getName(),
        ]);
        
        // Update last login timestamp
        $user->updateLastLogin();

        return $next($request);
    }
}