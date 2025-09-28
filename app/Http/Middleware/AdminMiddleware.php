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

        // 3. Check admin permissions with comprehensive validation
        $hasAdminAccess = false;
        
        try {
            // Primary check: Super Admin role
            if ($user->hasRole('super-admin')) {
                $hasAdminAccess = true;
                Log::info('Super admin access granted', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'route' => $request->route()?->getName(),
                ]);
            }
            // Secondary check: Admin role
            elseif ($user->hasRole('admin')) {
                $hasAdminAccess = true;
                Log::info('Admin access granted', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'route' => $request->route()?->getName(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Role check failed, using fallback methods', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            
            // Fallback 1: Check by email for super admin
            if ($user->isSuperAdminByEmail()) {
                $hasAdminAccess = true;
                Log::info('Admin access granted via email fallback', [
                    'user_email' => $user->email,
                ]);
            }
            
            // Fallback 2: Direct database role check
            try {
                $userRoles = $user->getRoles()->pluck('name')->toArray();
                if (array_intersect($userRoles, ['super-admin', 'admin'])) {
                    $hasAdminAccess = true;
                    Log::info('Admin access granted via direct role check', [
                        'user_roles' => $userRoles,
                    ]);
                }
            } catch (\Exception $e2) {
                Log::error('All admin role checks failed', [
                    'user_id' => $user->id,
                    'primary_error' => $e->getMessage(),
                    'fallback_error' => $e2->getMessage()
                ]);
            }
        }

        // 4. Grant or deny access
        if ($hasAdminAccess) {
            // Update last login timestamp
            $user->updateLastLogin();
            return $next($request);
        }
        
        // Access denied
        Log::warning('Admin access denied - insufficient permissions', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'route' => $request->route()?->getName(),
            'user_roles' => $user->getRoles()->pluck('name')->toArray(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Access denied. Super Admin or Admin role required.',
                'error' => 'Insufficient privileges'
            ], 403);
        }

        return redirect()->route('dashboard')
            ->with('error', 'Access denied. You need Super Admin or Admin privileges to access this area.');
    }
}