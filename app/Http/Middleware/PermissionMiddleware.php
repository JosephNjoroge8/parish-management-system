<?php
// filepath: app/Http/Middleware/PermissionMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // 1. Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('Unauthenticated access attempt', [
                'route' => $request->route()?->getName(),
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

        /** @var User $user */
        $user = Auth::user();
        
        // 2. Check if user account is active
        if (!$user->is_active) {
            Log::warning('Inactive user access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => $request->route()?->getName(),
            ]);
            
            Auth::logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact the administrator.',
                    'error' => 'Account inactive'
                ], 403);
            }
            
            return redirect()->route('login')
                ->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }
        
        // 3. Super Admin bypass - they have access to everything
        if ($user->hasRole('super-admin')) {
            Log::info('Super admin access granted', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'permission_required' => $permission,
                'route' => $request->route()?->getName(),
            ]);
            
            // Update last login timestamp
            $user->updateLastLogin();
            return $next($request);
        }
        
        // 4. Check if user has required permission
        if (!$user->hasPermissionTo($permission)) {
            Log::warning('Insufficient permissions', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'permission_required' => $permission,
                'user_roles' => $user->getRoles()->pluck('name')->toArray(),
                'route' => $request->route()?->getName(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to access this resource.',
                    'error' => 'Insufficient permissions',
                    'required_permission' => $permission
                ], 403);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this area.');
        }
        
        // 5. Log successful access
        Log::info('Permission granted', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'permission_required' => $permission,
            'route' => $request->route()?->getName(),
        ]);
        
        // Update last login timestamp
        $user->updateLastLogin();
        
        return $next($request);
    }
}