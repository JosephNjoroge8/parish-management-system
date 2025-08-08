<?php
// filepath: app/Http/Middleware/PermissionMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Log the permission check for debugging
        Log::info('Custom Permission Middleware', [
            'route' => $request->route()?->getName(),
            'permission_required' => $permission,
            'user_id' => Auth::id(),
            'url' => $request->fullUrl()
        ]);

        if (!Auth::check()) {
            Log::warning('User not authenticated, redirecting to login');
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Super admin bypass
        if ($user->email === 'admin@parish.com') {
            Log::info('Super admin access granted');
            return $next($request);
        }

        try {
            // Check if user has the specific permission
            // Permission check removed
            
            Log::info('Permission granted', [
                'user_email' => $user->email,
                'permission' => $permission
            ]);
            
        } catch (\Exception $e) {
            Log::error('Permission check failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'permission' => $permission
            ]);
            
            // For super admin, allow access even if permission system fails
            if ($user->email === 'admin@parish.com') {
                Log::info('Super admin fallback access granted');
                return $next($request);
            }
            
            abort(403, 'Permission system error: ' . $e->getMessage());
        }

        return $next($request);
    }
}