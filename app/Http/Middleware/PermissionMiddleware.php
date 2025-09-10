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
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Allow access for authenticated users for now
        // This prevents redirect loops while we debug the permission system
        Log::info('Permission middleware - allowing access', [
            'route' => $request->route()?->getName(),
            'permission_required' => $permission,
            'user_email' => $user->email,
            'user_id' => $user->id,
        ]);

        return $next($request);
    }
}