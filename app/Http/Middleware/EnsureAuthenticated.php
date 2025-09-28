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
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Authentication required',
                    'error' => 'Unauthenticated'
                ], 401);
            }
            
            return redirect()->route('login')
                ->with('message', 'Please log in to access this area.');
        }

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
        
        // 3. Verify user has at least one role (every user must have a role)
        try {
            $userRoles = $user->getRoles();
            
            if ($userRoles->isEmpty() && !$user->isSuperAdminByEmail()) {
                Log::warning('User without role attempted access', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'route' => $request->route()?->getName(),
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your account has not been assigned a role. Please contact the administrator.',
                        'error' => 'No role assigned'
                    ], 403);
                }
                
                return redirect()->route('login')
                    ->with('error', 'Your account has not been assigned a role. Please contact the administrator.');
            }
        } catch (\Exception $e) {
            Log::error('Role verification failed', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            
            // If role check fails and not super admin by email, deny access
            if (!$user->isSuperAdminByEmail()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Unable to verify account permissions. Please contact the administrator.',
                        'error' => 'Role verification failed'
                    ], 500);
                }
                
                return redirect()->route('login')
                    ->with('error', 'Unable to verify account permissions. Please contact the administrator.');
            }
        }
        
        // 4. Log successful access and update last login
        Log::info('Authenticated access granted', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'route' => $request->route()?->getName(),
            'user_roles' => $user->getRoles()->pluck('name')->toArray(),
        ]);
        
        // Update last login timestamp
        $user->updateLastLogin();
        
        return $next($request);
    }
}