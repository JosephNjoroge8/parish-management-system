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
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        /** @var User $user */
        $user = Auth::user();

        try {
            // Check if user has admin role or super-admin role using safer method
            if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
                return $next($request);
            }
        } catch (\Exception $e) {
            Log::info('Spatie role check failed, using fallback', ['error' => $e->getMessage()]);
            
            // Fallback: Check by email for super admin or direct role check
            if ($user->email === 'admin@parish.com') {
                return $next($request);
            }
            
            // Additional fallback: Check roles collection directly
            try {
                $userRoles = $user->roles->pluck('name')->toArray();
                if (in_array('super-admin', $userRoles) || in_array('admin', $userRoles)) {
                    return $next($request);
                }
            } catch (\Exception $e2) {
                Log::error('Complete role check failure in AdminMiddleware', [
                    'user_id' => $user->id,
                    'error' => $e2->getMessage()
                ]);
            }
        }

        // If not admin, redirect appropriately
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You do not have permission to access this area.'
            ], 403);
        }

        return redirect()->route('dashboard')
            ->with('error', 'You do not have permission to access this area.');
    }
}