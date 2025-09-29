<?php
// filepath: app/Http/Middleware/ChurchContextMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ChurchContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Add church context to request (simplified for single admin system)
        $request->merge([
            'user_church' => $user->local_church ?? 'Sacred Heart Kandara',
            'user_permissions' => $user->is_admin ? ['*'] : [], // Admin has all permissions
            'user_roles' => $user->is_admin ? ['admin'] : []
        ]);

        return $next($request);
    }
}