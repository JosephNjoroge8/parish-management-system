<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticateStrict extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // Check authentication first
        $this->authenticate($request, $guards);
        
        // If authenticated, perform additional security checks
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if account is active
            if (!$user->is_active) {
                Log::warning('Inactive user attempted access', [
                    'user_id' => $user->id,
                    'email' => $user->email,
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
                    ->with('error', 'Your account has been deactivated. Please contact the administrator.');
            }
            
            // Check if user has any roles (every user must have a role)
            if (!$user->hasAnyRole(['super-admin', 'admin', 'parish-priest', 'secretary', 'member'])) {
                Log::warning('User without role attempted access', [
                    'user_id' => $user->id,
                    'email' => $user->email,
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
            
            // Update last login timestamp
            $user->updateLastLogin();
        }
        
        return $next($request);
    }
    
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}