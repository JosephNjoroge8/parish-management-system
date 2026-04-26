<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if user is authenticated
        if (! Auth::check()) {
            Log::warning('Unauthenticated admin access attempt', [
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Authentication required for admin access',
                    'error' => 'Unauthenticated',
                ], 401);
            }

            return redirect()->route('login')
                ->with('message', 'Please log in to access the admin area.');
        }

        /** @var User $user */
        $user = Auth::user();

        // 2. Check if user account is active
        if (! $user->is_active) {
            Log::warning('Inactive user attempted admin access', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => $request->route()?->getName(),
            ]);

            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been deactivated.',
                    'error' => 'Account inactive',
                ], 403);
            }

            return redirect()->route('login')
                ->with('error', 'Your account has been deactivated.');
        }

        // 3. Simplified admin check - only users with is_admin flag allowed
        if (! $user->is_admin) {
            Log::warning('Admin access denied - not an admin user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => $request->route()?->getName(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Only system administrators can access this area.',
                    'error' => 'Insufficient privileges',
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

        // Update last login timestamp (with safety check for production)
        try {
            $user->updateLastLogin();
        } catch (\Exception $e) {
            // Log the error but don't break the login process
            Log::warning('Failed to update last login timestamp', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
