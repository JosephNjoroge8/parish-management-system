<?php
// filepath: app/Http/Middleware/RoleMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Super admin bypass
        if ($user->email === 'admin@parish.com') {
            return $next($request);
        }

        // Check if user has the specific role
        if ($user->role !== $role) {
            abort(403, 'You do not have the required role to access this resource.');
        }

        return $next($request);
    }
}